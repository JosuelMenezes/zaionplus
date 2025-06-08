<?php
// contas/editar.php
// Página para editar contas - Arquivo corrigido

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
    SELECT c.*
    FROM contas c
    WHERE c.id = :id
";

$stmt = $pdo_connection->prepare($sql);
$stmt->execute([':id' => $conta_id]);
$conta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conta) {
    $_SESSION['msg'] = "Conta não encontrada.";
    $_SESSION['msg_type'] = "error";
    header("Location: index.php");
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo_connection->beginTransaction();
        
        // Validar dados obrigatórios
        $required_fields = ['descricao', 'valor_original', 'data_vencimento', 'data_competencia'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Campo obrigatório não preenchido: " . $field);
            }
        }
        
        // Preparar dados
        $dados = [
            'categoria_id' => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
            'descricao' => trim($_POST['descricao']),
            'valor_original' => str_replace(['.', ','], ['', '.'], $_POST['valor_original']),
            'data_vencimento' => $_POST['data_vencimento'],
            'data_competencia' => $_POST['data_competencia'],
            'prioridade' => $_POST['prioridade'] ?? 'media',
            'cliente_id' => !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null,
            'fornecedor_id' => !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null,
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'documento' => trim($_POST['documento'] ?? ''),
            'forma_pagamento' => $_POST['forma_pagamento'] ?? '',
            'id' => $conta_id
        ];
        
        // Recalcular valor pendente
        $dados['valor_pendente'] = $dados['valor_original'] - $conta['valor_pago'];
        
        // Validações
        if ($dados['valor_original'] <= 0) {
            throw new Exception("Valor deve ser maior que zero");
        }
        
        if ($dados['valor_original'] < $conta['valor_pago']) {
            throw new Exception("Valor original não pode ser menor que o valor já pago");
        }
        
        // Atualizar conta
        $sql = "
            UPDATE contas SET 
                categoria_id = :categoria_id,
                descricao = :descricao,
                valor_original = :valor_original,
                valor_pendente = :valor_pendente,
                data_vencimento = :data_vencimento,
                data_competencia = :data_competencia,
                prioridade = :prioridade,
                cliente_id = :cliente_id,
                fornecedor_id = :fornecedor_id,
                observacoes = :observacoes,
                documento = :documento,
                forma_pagamento = :forma_pagamento
            WHERE id = :id
        ";
        
        $stmt = $pdo_connection->prepare($sql);
        $stmt->execute($dados);
        
        $pdo_connection->commit();
        
        $_SESSION['msg'] = "Conta atualizada com sucesso!";
        $_SESSION['msg_type'] = "success";
        
        header("Location: detalhes.php?id=" . $conta_id);
        exit;
        
    } catch (Exception $e) {
        $pdo_connection->rollBack();
        $erro = $e->getMessage();
    }
}

// Buscar dados complementares
$categorias = $contasManager->buscarCategorias($conta['tipo']);

// Buscar clientes para select
$sql_clientes = "SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome";
$stmt_clientes = $pdo_connection->query($sql_clientes);
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Buscar fornecedores para select
$sql_fornecedores = "SELECT id, nome_fantasia, razao_social FROM fornecedores WHERE ativo = 1 ORDER BY nome_fantasia";
$stmt_fornecedores = $pdo_connection->query($sql_fornecedores);
$fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Editar Conta";
$page_description = "Editar: " . htmlspecialchars($conta['descricao']);

require_once '../header.php';
?>

<style>
.form-editar {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    border-top: 4px solid;
    border-image: var(--gradient-contas) 1;
}

.conta-resumo {
    background: rgba(102, 126, 234, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.1);
    border-radius: 8px;
    margin-bottom: 2rem;
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
            <i class="fas fa-edit text-primary me-2"></i>
            Editar Conta
        </h1>
        <p class="text-muted mb-0">Alterar dados da conta</p>
    </div>
    <div>
        <a href="detalhes.php?id=<?php echo $conta_id; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>
</div>

<!-- Resumo da Conta -->
<div class="conta-resumo p-4 fade-in">
    <div class="row">
        <div class="col-md-8">
            <h5 class="mb-1"><?php echo htmlspecialchars($conta['descricao']); ?></h5>
            <span class="badge bg-<?php echo $conta['tipo'] == 'pagar' ? 'danger' : 'success'; ?>">
                <?php echo $conta['tipo'] == 'pagar' ? 'A Pagar' : 'A Receber'; ?>
            </span>
        </div>
        <div class="col-md-4 text-end">
            <div>
                <strong>Valor Atual: </strong><?php echo formatarMoeda($conta['valor_original']); ?>
            </div>
            <?php if ($conta['valor_pago'] > 0): ?>
            <div class="text-muted">
                <small>Pago: <?php echo formatarMoeda($conta['valor_pago']); ?></small>
            </div>
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
<form method="POST" class="fade-in" style="animation-delay: 0.2s" id="formEditar">
    <div class="form-editar p-4">
        
        <!-- Informações Básicas -->
        <div class="mb-4">
            <h5 class="mb-3">
                <i class="fas fa-info-circle text-primary me-2"></i>
                Informações Básicas
            </h5>
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Descrição *</label>
                    <input type="text" class="form-control" name="descricao" 
                           value="<?php echo htmlspecialchars($conta['descricao']); ?>" 
                           required maxlength="255">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria_id">
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo $conta['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Valor Original *</label>
                    <div class="valor-input">
                        <input type="text" class="form-control" name="valor_original" 
                               value="<?php echo number_format($conta['valor_original'], 2, ',', '.'); ?>" 
                               required data-mask="money">
                    </div>
                    <?php if ($conta['valor_pago'] > 0): ?>
                    <small class="text-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Valor mínimo: <?php echo formatarMoeda($conta['valor_pago']); ?> (já pago)
                    </small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data de Vencimento *</label>
                    <input type="date" class="form-control" name="data_vencimento" 
                           value="<?php echo $conta['data_vencimento']; ?>" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data de Competência *</label>
                    <input type="date" class="form-control" name="data_competencia" 
                           value="<?php echo $conta['data_competencia']; ?>" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Prioridade</label>
                    <select class="form-select" name="prioridade">
                        <?php foreach (CONTAS_PRIORIDADES as $key => $config): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo $conta['prioridade'] == $key ? 'selected' : ''; ?>>
                                <?php echo $config['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Relacionamentos -->
        <div class="mb-4">
            <h5 class="mb-3">
                <i class="fas fa-link text-success me-2"></i>
                Relacionamentos
            </h5>
            
            <div class="row">
                <?php if ($conta['tipo'] == 'receber'): ?>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cliente</label>
                    <select class="form-select" name="cliente_id">
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>" 
                                    <?php echo $conta['cliente_id'] == $cliente['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fornecedor</label>
                    <select class="form-select" name="fornecedor_id">
                        <option value="">Selecione um fornecedor</option>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <option value="<?php echo $fornecedor['id']; ?>" 
                                    <?php echo $conta['fornecedor_id'] == $fornecedor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($fornecedor['nome_fantasia'] ?: $fornecedor['razao_social']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Forma de Pagamento</label>
                    <select class="form-select" name="forma_pagamento">
                        <option value="">Selecione a forma</option>
                        <?php foreach (CONTAS_FORMAS_PAGAMENTO as $key => $label): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo $conta['forma_pagamento'] == $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Documento/Referência</label>
                    <input type="text" class="form-control" name="documento" 
                           value="<?php echo htmlspecialchars($conta['documento']); ?>" 
                           maxlength="100">
                </div>
            </div>
        </div>

        <!-- Observações -->
        <div class="mb-4">
            <h5 class="mb-3">
                <i class="fas fa-comment-alt text-warning me-2"></i>
                Observações
            </h5>
            
            <textarea class="form-control" name="observacoes" rows="3" 
                      placeholder="Observações adicionais"><?php echo htmlspecialchars($conta['observacoes']); ?></textarea>
        </div>

        <?php if ($conta['recorrente']): ?>
        <!-- Informação sobre Recorrência -->
        <div class="mb-4">
            <div class="alert alert-info">
                <i class="fas fa-sync-alt me-2"></i>
                <strong>Conta Recorrente:</strong> Esta conta faz parte de um grupo de contas recorrentes. 
                Alterações não afetarão as outras contas do grupo.
            </div>
        </div>
        <?php endif; ?>

        <!-- Botões de Ação -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                <i class="fas fa-times me-2"></i>Cancelar
            </button>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" onclick="resetarFormulario()">
                    <i class="fas fa-undo me-2"></i>Resetar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara de valor
    const valorInput = document.querySelector('input[name="valor_original"]');
    const valorMinimo = <?php echo $conta['valor_pago']; ?>;
    
    valorInput.addEventListener('input', function() {
        let valor = this.value.replace(/\D/g, '');
        valor = valor.replace(/(\d)(\d{2})$/, '$1,$2');
        valor = valor.replace(/(?=(\d{3})+(\D))\B/g, '.');
        this.value = valor;
    });
    
    // Validação do valor mínimo
    valorInput.addEventListener('blur', function() {
        const valor = parseFloat(this.value.replace(/[^\d,]/g, '').replace(',', '.'));
        if (valor < valorMinimo) {
            alert(`O valor não pode ser menor que ${formatarMoeda(valorMinimo)} (valor já pago).`);
            this.focus();
        }
    });
    
    // Detectar mudanças para aviso
    const valoresOriginais = {};
    const inputs = document.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        valoresOriginais[input.name] = input.value;
    });
    
    let mudancasDetectadas = false;
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value !== valoresOriginais[this.name]) {
                mudancasDetectadas = true;
            }
        });
    });
    
    // Aviso ao sair da página
    window.addEventListener('beforeunload', function(e) {
        if (mudancasDetectadas) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // Não mostrar aviso ao submeter
    document.getElementById('formEditar').addEventListener('submit', function() {
        mudancasDetectadas = false;
    });
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            switch(e.key) {
                case 's':
                case 'S':
                    e.preventDefault();
                    document.getElementById('formEditar').submit();
                    break;
                case 'z':
                case 'Z':
                    e.preventDefault();
                    resetarFormulario();
                    break;
            }
        }
    });
});

function resetarFormulario() {
    if (confirm('Tem certeza que deseja resetar o formulário? Todas as alterações serão perdidas.')) {
        location.reload();
    }
}

function formatarMoeda(valor) {
    return 'R$ ' + valor.toFixed(2).replace('.', ',').replace(/(?=(\d{3})+(\D))\B/g, '.');
}
</script>

<?php require_once '../footer.php'; ?>