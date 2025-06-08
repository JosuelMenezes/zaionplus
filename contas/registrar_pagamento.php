<?php
// contas/registrar_pagamento.php
// Formulário para registrar pagamento - Sistema Domaria Café

require_once 'config.php';

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Conta não encontrada.";
    $_SESSION['msg_type'] = "error";
    header("Location: index.php");
    exit;
}

$conta_id = (int)$_GET['id'];

// Buscar dados da conta
$sql = "
    SELECT 
        c.*,
        cat.nome as categoria_nome,
        cat.cor as categoria_cor,
        cat.icone as categoria_icone
    FROM contas c
    LEFT JOIN categorias_contas cat ON c.categoria_id = cat.id
    WHERE c.id = :id AND c.status IN ('pendente', 'pago_parcial')
";

$stmt = $pdo_connection->prepare($sql);
$stmt->execute([':id' => $conta_id]);
$conta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conta) {
    $_SESSION['msg'] = "Conta não encontrada ou não permite novos pagamentos.";
    $_SESSION['msg_type'] = "error";
    header("Location: index.php");
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo_connection->beginTransaction();
        
        // Validar dados obrigatórios
        $required_fields = ['valor', 'data_pagamento', 'forma_pagamento'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Campo obrigatório não preenchido: " . $field);
            }
        }
        
        // Preparar dados
        $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
        $valor = (float)$valor;
        
        if ($valor <= 0) {
            throw new Exception("Valor deve ser maior que zero");
        }
        
        if ($valor > $conta['valor_pendente']) {
            throw new Exception("Valor não pode ser maior que o pendente: " . formatarMoeda($conta['valor_pendente']));
        }
        
        $dados_pagamento = [
            'conta_id' => $conta_id,
            'valor' => $valor,
            'data_pagamento' => $_POST['data_pagamento'],
            'forma_pagamento' => $_POST['forma_pagamento'],
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'banco' => trim($_POST['banco'] ?? ''),
            'agencia' => trim($_POST['agencia'] ?? ''),
            'conta' => trim($_POST['conta_bancaria'] ?? ''),
            'usuario_registro' => $_SESSION['usuario_id']
        ];
        
        // Upload do comprovante se enviado
        $documento_comprovante = null;
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadComprovante($_FILES['comprovante'], $conta_id);
            if ($upload_result['success']) {
                $documento_comprovante = $upload_result['filename'];
            } else {
                throw new Exception($upload_result['error']);
            }
        }
        
        $dados_pagamento['documento_comprovante'] = $documento_comprovante;
        
        // Inserir pagamento
        $sql_pagamento = "
            INSERT INTO pagamentos_contas (
                conta_id, valor, data_pagamento, forma_pagamento, observacoes,
                documento_comprovante, banco, agencia, conta, usuario_registro
            ) VALUES (
                :conta_id, :valor, :data_pagamento, :forma_pagamento, :observacoes,
                :documento_comprovante, :banco, :agencia, :conta, :usuario_registro
            )
        ";
        
        $stmt_pagamento = $pdo_connection->prepare($sql_pagamento);
        $stmt_pagamento->execute($dados_pagamento);
        
        // A conta será atualizada automaticamente pelo trigger
        
        $pdo_connection->commit();
        
        $_SESSION['msg'] = "Pagamento registrado com sucesso!";
        $_SESSION['msg_type'] = "success";
        
        header("Location: detalhes.php?id=" . $conta_id);
        exit;
        
    } catch (Exception $e) {
        $pdo_connection->rollBack();
        $erro = $e->getMessage();
    }
}

/**
 * Função para upload de comprovante
 */
function uploadComprovante($arquivo, $conta_id) {
    if (!isArquivoPermitido($arquivo['name'])) {
        return ['success' => false, 'error' => 'Tipo de arquivo não permitido'];
    }
    
    if ($arquivo['size'] > CONTAS_MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Arquivo muito grande (máx. 5MB)'];
    }
    
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nome_arquivo = 'comprovante_' . $conta_id . '_' . uniqid() . '.' . $extensao;
    $caminho_completo = CONTAS_UPLOAD_PATH . $nome_arquivo;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
        return ['success' => true, 'filename' => $nome_arquivo];
    } else {
        return ['success' => false, 'error' => 'Erro ao salvar arquivo'];
    }
}

$page_title = "Registrar Pagamento";
$page_description = "Registrar pagamento para: " . htmlspecialchars($conta['descricao']);

require_once '../header.php';
?>

<style>
.form-pagamento {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    border-top: 4px solid;
    border-image: var(--gradient-success) 1;
}

.conta-resumo {
    background: var(--gradient-contas);
    color: white;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.valor-calculado {
    background: rgba(17, 153, 142, 0.1);
    border: 1px solid rgba(17, 153, 142, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.forma-pagamento-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.forma-pagamento-option {
    position: relative;
}

.forma-pagamento-option input[type="radio"] {
    display: none;
}

.forma-pagamento-option label {
    display: block;
    padding: 1rem;
    border: 2px solid #e9ecef;
    border-radius: var(--border-radius);
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.forma-pagamento-option label:hover {
    border-color: #11998e;
    transform: translateY(-2px);
    box-shadow: var(--shadow-soft);
}

.forma-pagamento-option input[type="radio"]:checked + label {
    border-color: #11998e;
    background: var(--gradient-success);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.forma-pagamento-option i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: block;
}

.dados-bancarios {
    background: rgba(79, 172, 254, 0.05);
    border: 1px solid rgba(79, 172, 254, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    display: none;
}

.dados-bancarios.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.valor-input {
    position: relative;
}

.valor-input::before {
    content: 'R$';
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-weight: 600;
    z-index: 10;
}

.valor-input input {
    padding-left: 35px;
    font-size: 1.25rem;
    font-weight: 600;
}

.comprovante-preview {
    max-width: 200px;
    border-radius: 8px;
    box-shadow: var(--shadow-soft);
}

.fade-in {
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Header da Página -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <h1 class="h3 mb-1">
            <i class="fas fa-credit-card text-success me-2"></i>
            Registrar Pagamento
        </h1>
        <p class="text-muted mb-0">Registrar pagamento para a conta</p>
    </div>
    <div>
        <a href="detalhes.php?id=<?php echo $conta['id']; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>
</div>

<!-- Resumo da Conta -->
<div class="conta-resumo p-4 fade-in">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo htmlspecialchars($conta['descricao']); ?></h4>
            
            <?php if ($conta['categoria_nome']): ?>
            <span class="badge" style="background-color: <?php echo $conta['categoria_cor']; ?>; color: white;">
                <i class="<?php echo $conta['categoria_icone']; ?> me-1"></i>
                <?php echo htmlspecialchars($conta['categoria_nome']); ?>
            </span>
            <?php endif; ?>
            
            <div class="mt-3">
                <div class="row">
                    <div class="col-sm-4">
                        <small class="opacity-75">Valor Original:</small>
                        <div class="fs-5 fw-bold"><?php echo formatarMoeda($conta['valor_original']); ?></div>
                    </div>
                    <div class="col-sm-4">
                        <small class="opacity-75">Já Pago:</small>
                        <div class="fs-5 fw-bold"><?php echo formatarMoeda($conta['valor_pago']); ?></div>
                    </div>
                    <div class="col-sm-4">
                        <small class="opacity-75">Pendente:</small>
                        <div class="fs-5 fw-bold text-warning"><?php echo formatarMoeda($conta['valor_pendente']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 text-md-end">
            <div class="mb-2">
                <small class="opacity-75">Vencimento:</small>
                <div class="fs-6"><?php echo formatarData($conta['data_vencimento']); ?></div>
            </div>
            
            <?php if ($conta['valor_pago'] > 0): ?>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-light" style="width: <?php echo ($conta['valor_pago'] / $conta['valor_original']) * 100; ?>%"></div>
            </div>
            <small class="opacity-75">
                <?php echo number_format(($conta['valor_pago'] / $conta['valor_original']) * 100, 1); ?>% pago
            </small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Alertas -->
<?php if (isset($erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($erro); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Formulário -->
<form method="POST" enctype="multipart/form-data" class="fade-in" style="animation-delay: 0.2s" id="formPagamento">
    <div class="form-pagamento p-4">
        
        <!-- Valor do Pagamento -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="mb-3">
                    <i class="fas fa-money-bill-wave text-success me-2"></i>
                    Valor do Pagamento
                </h5>
                
                <div class="valor-input mb-3">
                    <input type="text" class="form-control form-control-lg" name="valor" id="valor" 
                           placeholder="0,00" required data-mask="money" 
                           data-max="<?php echo $conta['valor_pendente']; ?>">
                    <small class="text-muted">
                        Valor máximo: <?php echo formatarMoeda($conta['valor_pendente']); ?>
                    </small>
                </div>
                
                <!-- Botões de valor rápido -->
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Valores rápidos:</small>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-success btn-sm" 
                                onclick="definirValor(<?php echo $conta['valor_pendente']; ?>)">
                            Total Pendente
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" 
                                onclick="definirValor(<?php echo $conta['valor_pendente'] / 2; ?>)">
                            50%
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" 
                                onclick="definirValor(<?php echo $conta['valor_pendente'] / 4; ?>)">
                            25%
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h5 class="mb-3">
                    <i class="fas fa-calendar text-primary me-2"></i>
                    Data do Pagamento
                </h5>
                
                <input type="date" class="form-control form-control-lg" name="data_pagamento" id="data_pagamento" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <!-- Forma de Pagamento -->
        <div class="mb-4">
            <h5 class="mb-3">
                <i class="fas fa-credit-card text-info me-2"></i>
                Forma de Pagamento
            </h5>
            
            <div class="forma-pagamento-grid">
                <?php 
                $formas_icones = [
                    'dinheiro' => 'fas fa-money-bill-wave',
                    'pix' => 'fab fa-pix',
                    'transferencia' => 'fas fa-exchange-alt',
                    'cartao_credito' => 'fas fa-credit-card',
                    'cartao_debito' => 'fas fa-credit-card',
                    'boleto' => 'fas fa-barcode',
                    'cheque' => 'fas fa-money-check',
                    'deposito' => 'fas fa-university'
                ];
                
                foreach (CONTAS_FORMAS_PAGAMENTO as $key => $label): 
                    if ($key === 'outros') continue; // Outros será mostrado separadamente
                ?>
                <div class="forma-pagamento-option">
                    <input type="radio" name="forma_pagamento" value="<?php echo $key; ?>" id="forma_<?php echo $key; ?>" required>
                    <label for="forma_<?php echo $key; ?>">
                        <i class="<?php echo $formas_icones[$key] ?? 'fas fa-circle'; ?>"></i>
                        <div><?php echo $label; ?></div>
                    </label>
                </div>
                <?php endforeach; ?>
                
                <div class="forma-pagamento-option">
                    <input type="radio" name="forma_pagamento" value="outros" id="forma_outros" required>
                    <label for="forma_outros">
                        <i class="fas fa-ellipsis-h"></i>
                        <div>Outros</div>
                    </label>
                </div>
            </div>
            
            <!-- Campos bancários (aparecem para certas formas de pagamento) -->
            <div class="dados-bancarios" id="dados-bancarios">
                <h6><i class="fas fa-university me-2"></i>Dados Bancários</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Banco</label>
                        <input type="text" class="form-control" name="banco" placeholder="Nome do banco">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Agência</label>
                        <input type="text" class="form-control" name="agencia" placeholder="0000">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Conta</label>
                        <input type="text" class="form-control" name="conta_bancaria" placeholder="00000-0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Comprovante -->
        <div class="mb-4">
            <h5 class="mb-3">
                <i class="fas fa-file-upload text-warning me-2"></i>
                Comprovante (Opcional)
            </h5>
            
            <div class="row">
                <div class="col-md-8">
                    <input type="file" class="form-control" name="comprovante" id="comprovante"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    <small class="text-muted">
                        Formatos aceitos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (máx. 5MB)
                    </small>
                </div>
                <div class="col-md-4">
                    <div id="preview-comprovante"></div>
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div class="mb-4">
            <h5 class="mb-3">
                <i class="fas fa-comment-alt text-secondary me-2"></i>
                Observações
            </h5>
            
            <textarea class="form-control" name="observacoes" rows="3" 
                      placeholder="Observações sobre este pagamento (opcional)"></textarea>
        </div>

        <!-- Resumo do Pagamento -->
        <div class="valor-calculado" id="resumo-pagamento" style="display: none;">
            <h6><i class="fas fa-calculator me-2"></i>Resumo do Pagamento</h6>
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted">Valor do Pagamento:</small>
                    <div class="fw-bold text-success" id="resumo-valor">R$ 0,00</div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Valor Atual Pago:</small>
                    <div class="fw-bold"><?php echo formatarMoeda($conta['valor_pago']); ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Total Após Pagamento:</small>
                    <div class="fw-bold text-primary" id="resumo-total">R$ 0,00</div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Restante a Pagar:</small>
                    <div class="fw-bold text-warning" id="resumo-restante">R$ 0,00</div>
                </div>
            </div>
            
            <div class="progress mt-3" style="height: 10px;">
                <div class="progress-bar bg-success" id="progress-pagamento" style="width: 0%"></div>
            </div>
            <small class="text-muted mt-1 d-block">
                <span id="percentual-pago">0%</span> do valor total será pago
            </small>
        </div>

        <!-- Botões de Ação -->
        <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
            <div>
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" onclick="salvarRascunho()">
                    <i class="fas fa-save me-2"></i>Salvar Rascunho
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-2"></i>Registrar Pagamento
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const valorInput = document.getElementById('valor');
    const formaPagamentoInputs = document.querySelectorAll('input[name="forma_pagamento"]');
    const dadosBancarios = document.getElementById('dados-bancarios');
    const resumoPagamento = document.getElementById('resumo-pagamento');
    const comprovanteInput = document.getElementById('comprovante');
    
    const valorOriginal = <?php echo $conta['valor_original']; ?>;
    const valorPago = <?php echo $conta['valor_pago']; ?>;
    const valorPendente = <?php echo $conta['valor_pendente']; ?>;
    
    // Máscara de valor monetário
    valorInput.addEventListener('input', function() {
        let valor = this.value.replace(/\D/g, '');
        valor = valor.replace(/(\d)(\d{2})$/, '$1,$2');
        valor = valor.replace(/(?=(\d{3})+(\D))\B/g, '.');
        this.value = valor;
        
        atualizarResumo();
    });
    
    // Configurar formas de pagamento que precisam de dados bancários
    const formasComDadosBancarios = ['transferencia', 'deposito', 'cheque'];
    
    formaPagamentoInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (formasComDadosBancarios.includes(this.value)) {
                dadosBancarios.classList.add('show');
            } else {
                dadosBancarios.classList.remove('show');
            }
        });
    });
    
    // Preview do comprovante
    comprovanteInput.addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('preview-comprovante');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" class="comprovante-preview img-fluid" alt="Preview">
                    `;
                } else {
                    preview.innerHTML = `
                        <div class="text-center p-3 border rounded">
                            <i class="fas fa-file fs-2 text-muted"></i>
                            <div class="mt-2 small text-muted">${file.name}</div>
                        </div>
                    `;
                }
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    });
    
    // Validação do formulário
    document.getElementById('formPagamento').addEventListener('submit', function(e) {
        const valor = parseFloat(valorInput.value.replace(/[^\d,]/g, '').replace(',', '.'));
        
        if (isNaN(valor) || valor <= 0) {
            e.preventDefault();
            alert('Informe um valor válido para o pagamento.');
            valorInput.focus();
            return;
        }
        
        if (valor > valorPendente) {
            e.preventDefault();
            alert(`O valor não pode ser maior que o pendente: ${formatarMoeda(valorPendente)}`);
            valorInput.focus();
            return;
        }
        
        const formaSelecionada = document.querySelector('input[name="forma_pagamento"]:checked');
        if (!formaSelecionada) {
            e.preventDefault();
            alert('Selecione uma forma de pagamento.');
            return;
        }
    });
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            switch(e.key) {
                case 's':
                case 'S':
                    e.preventDefault();
                    document.getElementById('formPagamento').submit();
                    break;
                case 'd':
                case 'D':
                    e.preventDefault();
                    salvarRascunho();
                    break;
            }
        }
    });
});

function definirValor(valor) {
    const valorInput = document.getElementById('valor');
    valorInput.value = formatarValorInput(valor);
    atualizarResumo();
    valorInput.focus();
}

function formatarValorInput(valor) {
    return valor.toFixed(2).replace('.', ',').replace(/(?=(\d{3})+(\D))\B/g, '.');
}

function formatarMoeda(valor) {
    return 'R$ ' + valor.toFixed(2).replace('.', ',').replace(/(?=(\d{3})+(\D))\B/g, '.');
}

function atualizarResumo() {
    const valorInput = document.getElementById('valor');
    const resumoPagamento = document.getElementById('resumo-pagamento');
    
    const valorStr = valorInput.value.replace(/[^\d,]/g, '').replace(',', '.');
    const valorPagamento = parseFloat(valorStr) || 0;
    
    if (valorPagamento > 0) {
        resumoPagamento.style.display = 'block';
        
        const valorOriginal = <?php echo $conta['valor_original']; ?>;
        const valorPago = <?php echo $conta['valor_pago']; ?>;
        
        const totalAposPagamento = valorPago + valorPagamento;
        const restanteAPagar = valorOriginal - totalAposPagamento;
        const percentualPago = (totalAposPagamento / valorOriginal) * 100;
        
        document.getElementById('resumo-valor').textContent = formatarMoeda(valorPagamento);
        document.getElementById('resumo-total').textContent = formatarMoeda(totalAposPagamento);
        document.getElementById('resumo-restante').textContent = formatarMoeda(Math.max(0, restanteAPagar));
        document.getElementById('percentual-pago').textContent = percentualPago.toFixed(1) + '%';
        
        const progressBar = document.getElementById('progress-pagamento');
        progressBar.style.width = Math.min(100, percentualPago) + '%';
        
        // Alterar cor do progress bar baseado no percentual
        progressBar.className = 'progress-bar';
        if (percentualPago >= 100) {
            progressBar.classList.add('bg-success');
        } else if (percentualPago >= 50) {
            progressBar.classList.add('bg-info');
        } else {
            progressBar.classList.add('bg-warning');
        }
        
        // Alterar cor do valor restante
        const restanteElement = document.getElementById('resumo-restante');
        if (restanteAPagar <= 0) {
            restanteElement.className = 'fw-bold text-success';
        } else {
            restanteElement.className = 'fw-bold text-warning';
        }
        
    } else {
        resumoPagamento.style.display = 'none';
    }
}

function salvarRascunho() {
    // Implementar funcionalidade de rascunho
    alert('Funcionalidade de rascunho será implementada em breve.');
}

// Função para calcular troco (para pagamentos em dinheiro)
function calcularTroco() {
    const formaSelecionada = document.querySelector('input[name="forma_pagamento"]:checked');
    
    if (formaSelecionada && formaSelecionada.value === 'dinheiro') {
        const valorRecebido = prompt('Valor recebido em dinheiro:');
        if (valorRecebido) {
            const valor = parseFloat(valorRecebido.replace(',', '.'));
            const valorPagamento = parseFloat(document.getElementById('valor').value.replace(/[^\d,]/g, '').replace(',', '.'));
            
            if (valor >= valorPagamento) {
                const troco = valor - valorPagamento;
                if (troco > 0) {
                    alert(`Troco: ${formatarMoeda(troco)}`);
                }
            } else {
                alert('Valor recebido é menor que o valor do pagamento.');
            }
        }
    }
}

// Adicionar botão de troco para pagamentos em dinheiro
document.addEventListener('DOMContentLoaded', function() {
    const formaDinheiro = document.getElementById('forma_dinheiro');
    if (formaDinheiro) {
        formaDinheiro.addEventListener('change', function() {
            if (this.checked) {
                // Adicionar botão de calcular troco
                const trocoBtn = document.createElement('button');
                trocoBtn.type = 'button';
                trocoBtn.className = 'btn btn-outline-success btn-sm mt-2';
                trocoBtn.innerHTML = '<i class="fas fa-calculator me-1"></i>Calcular Troco';
                trocoBtn.onclick = calcularTroco;
                trocoBtn.id = 'btn-troco';
                
                // Remover botão anterior se existir
                const btnAnterior = document.getElementById('btn-troco');
                if (btnAnterior) {
                    btnAnterior.remove();
                }
                
                formaDinheiro.closest('.forma-pagamento-option').appendChild(trocoBtn);
            }
        });
    }
});

// Auto-complete para bancos comuns
document.addEventListener('DOMContentLoaded', function() {
    const bancoInput = document.querySelector('input[name="banco"]');
    if (bancoInput) {
        const bancosComuns = [
            'Banco do Brasil', 'Bradesco', 'Caixa Econômica Federal', 'Itaú',
            'Santander', 'Nubank', 'Inter', 'BTG Pactual', 'Sicredi', 'Sicoob'
        ];
        
        bancoInput.addEventListener('input', function() {
            const valor = this.value.toLowerCase();
            const sugestoes = bancosComuns.filter(banco => 
                banco.toLowerCase().includes(valor)
            );
            
            // Implementar dropdown de sugestões se necessário
            // Por simplicidade, deixamos apenas o input livre
        });
    }
});

// Validação de data
document.addEventListener('DOMContentLoaded', function() {
    const dataInput = document.getElementById('data_pagamento');
    const hoje = new Date();
    const maxData = new Date();
    maxData.setDate(hoje.getDate() + 30); // Máximo 30 dias no futuro
    
    dataInput.max = maxData.toISOString().split('T')[0];
    
    dataInput.addEventListener('change', function() {
        const dataSelecionada = new Date(this.value);
        
        if (dataSelecionada > maxData) {
            alert('A data do pagamento não pode ser superior a 30 dias da data atual.');
            this.value = hoje.toISOString().split('T')[0];
        }
    });
});

// Confirmação antes de submeter pagamento que quita a conta
document.getElementById('formPagamento').addEventListener('submit', function(e) {
    const valorInput = document.getElementById('valor');
    const valor = parseFloat(valorInput.value.replace(/[^\d,]/g, '').replace(',', '.'));
    const valorPendente = <?php echo $conta['valor_pendente']; ?>;
    
    if (Math.abs(valor - valorPendente) < 0.01) { // Quitação completa
        if (!confirm('Este pagamento quitará completamente a conta. Deseja continuar?')) {
            e.preventDefault();
        }
    }
});

// Função para sugerir parcelamento
function sugerirParcelamento() {
    const valorPendente = <?php echo $conta['valor_pendente']; ?>;
    const parcelas = prompt('Em quantas parcelas deseja dividir o valor pendente?');
    
    if (parcelas && parseInt(parcelas) > 1) {
        const valorParcela = valorPendente / parseInt(parcelas);
        document.getElementById('valor').value = formatarValorInput(valorParcela);
        atualizarResumo();
        
        alert(`Sugestão: ${parcelas}x de ${formatarMoeda(valorParcela)}`);
    }
}

// Adicionar botão de parcelamento
document.addEventListener('DOMContentLoaded', function() {
    const botoesRapidos = document.querySelector('.btn-group');
    if (botoesRapidos) {
        const btnParcelar = document.createElement('button');
        btnParcelar.type = 'button';
        btnParcelar.className = 'btn btn-outline-info btn-sm';
        btnParcelar.innerHTML = 'Parcelar';
        btnParcelar.onclick = sugerirParcelamento;
        botoesRapidos.appendChild(btnParcelar);
    }
});
</script>

<?php require_once '../footer.php'; ?>