<?php
// fornecedores/novo_pedido.php
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

$fornecedor_id = isset($_GET['fornecedor_id']) ? intval($_GET['fornecedor_id']) : 0;

// Se não especificou fornecedor, buscar todos
if ($fornecedor_id == 0) {
    $sql_fornecedores = "SELECT id, nome, empresa FROM fornecedores WHERE status = 'ativo' ORDER BY nome";
    $result_fornecedores = $conn->query($sql_fornecedores);
} else {
    // Verificar se o fornecedor existe
    $sql_fornecedor = "SELECT * FROM fornecedores WHERE id = $fornecedor_id AND status = 'ativo'";
    $result_fornecedor = $conn->query($sql_fornecedor);
    
    if (!$result_fornecedor || $result_fornecedor->num_rows == 0) {
        $_SESSION['msg'] = "Fornecedor não encontrado ou inativo";
        $_SESSION['msg_type'] = "danger";
        header("Location: listar.php");
        exit;
    }
    
    $fornecedor = $result_fornecedor->fetch_assoc();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fornecedor_id = intval($_POST['fornecedor_id']);
    $numero_pedido = trim($_POST['numero_pedido']);
    $data_pedido = $_POST['data_pedido'];
    $data_entrega_prevista = $_POST['data_entrega_prevista'];
    $observacoes = trim($_POST['observacoes']);
    $forma_pagamento = trim($_POST['forma_pagamento']);
    $status = 'pendente';
    $usuario_id = $_SESSION['usuario_id'];
    
    // Itens do pedido
    $itens = $_POST['itens'];
    
    // Validações
    $erros = [];
    
    if (empty($fornecedor_id)) {
        $erros[] = "Fornecedor é obrigatório";
    }
    
    if (empty($data_pedido)) {
        $erros[] = "Data do pedido é obrigatória";
    }
    
    if (empty($itens) || count($itens) == 0) {
        $erros[] = "É necessário adicionar pelo menos um item";
    } else {
        foreach ($itens as $index => $item) {
            if (empty($item['descricao'])) {
                $erros[] = "Descrição do item " . ($index + 1) . " é obrigatória";
            }
            if (empty($item['quantidade']) || $item['quantidade'] <= 0) {
                $erros[] = "Quantidade do item " . ($index + 1) . " deve ser maior que zero";
            }
            if (empty($item['valor_unitario']) || $item['valor_unitario'] <= 0) {
                $erros[] = "Valor unitário do item " . ($index + 1) . " deve ser maior que zero";
            }
        }
    }
    
    if (empty($erros)) {
        // Calcular valor total
        $valor_total = 0;
        foreach ($itens as $item) {
            $quantidade = floatval($item['quantidade']);
            $valor_unitario = floatval(str_replace(',', '.', str_replace('.', '', $item['valor_unitario'])));
            $valor_total += $quantidade * $valor_unitario;
        }
        
        // Iniciar transação
        $conn->begin_transaction();
        
        try {
            // Inserir pedido usando os nomes corretos das colunas
            $sql_pedido = "INSERT INTO pedidos_fornecedores (
                            fornecedor_id, numero_pedido, data_pedido, data_entrega_prevista,
                            valor_total, status, observacoes, forma_pagamento, criado_por
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql_pedido);
            $stmt->bind_param("isssdsssi", 
                $fornecedor_id, $numero_pedido, $data_pedido, $data_entrega_prevista,
                $valor_total, $status, $observacoes, $forma_pagamento, $usuario_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir pedido: " . $stmt->error);
            }
            
            $pedido_id = $conn->insert_id;
            
            // Inserir itens do pedido
            $sql_item = "INSERT INTO itens_pedido_fornecedor (
                          pedido_id, descricao_item, quantidade, valor_unitario, 
                          unidade, observacoes
                         ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt_item = $conn->prepare($sql_item);
            
            foreach ($itens as $item) {
                $quantidade = floatval($item['quantidade']);
                $valor_unitario = floatval(str_replace(',', '.', str_replace('.', '', $item['valor_unitario'])));
                $unidade = trim($item['unidade']);
                $observacoes_item = trim($item['observacoes']);
                
                $stmt_item->bind_param("isddss",
                    $pedido_id, $item['descricao'], $quantidade, $valor_unitario,
                    $unidade, $observacoes_item
                );
                
                if (!$stmt_item->execute()) {
                    throw new Exception("Erro ao inserir item: " . $stmt_item->error);
                }
            }
            
            // Confirmar transação
            $conn->commit();
            
            $_SESSION['msg'] = "Pedido criado com sucesso!";
            $_SESSION['msg_type'] = "success";
            
            // Redirecionar para detalhes do pedido ou lista
            header("Location: detalhes.php?id=" . $fornecedor_id);
            exit;
            
        } catch (Exception $e) {
            // Reverter transação
            $conn->rollback();
            $erros[] = "Erro ao salvar pedido: " . $e->getMessage();
        }
    }
}

// Verificar se há mensagens na sessão
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';
$msg_type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : '';
unset($_SESSION['msg']);
unset($_SESSION['msg_type']);

include '../includes/header.php';
?>

<style>
:root {
    --gradient-fornecedores: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-danger: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
}

.page-header {
    background: var(--gradient-fornecedores);
    color: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.1; }
    50% { transform: scale(1.1); opacity: 0.2; }
}

.form-card {
    background: white;
    border: none;
    border-radius: 20px;
    box-shadow: var(--shadow-soft);
    overflow: hidden;
    margin-bottom: 2rem;
}

.form-card .card-header {
    background: var(--gradient-fornecedores);
    color: white;
    border: none;
    padding: 1.5rem;
}

.form-floating > label {
    color: #6c757d;
}

.form-control:focus {
    border-color: #fd7e14;
    box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
}

.btn-fornecedores {
    background: var(--gradient-fornecedores);
    border: none;
    color: white;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-fornecedores:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.item-row {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 2px dashed #dee2e6;
    transition: all 0.3s ease;
}

.item-row:hover {
    border-color: #fd7e14;
    box-shadow: 0 5px 15px rgba(253, 126, 20, 0.1);
}

.btn-remove-item {
    background: var(--gradient-danger);
    border: none;
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-remove-item:hover {
    color: white;
    transform: scale(1.1);
    box-shadow: var(--shadow-hover);
}

.btn-add-item {
    background: var(--gradient-success);
    border: none;
    color: white;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-add-item:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.total-display {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 1rem 0;
}

.slide-in {
    animation: slideInUp 0.6s ease-out forwards;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.required {
    color: #dc3545;
}

.invalid-feedback {
    display: block;
}

.fornecedor-info {
    background: rgba(253, 126, 20, 0.1);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid #fd7e14;
}
</style>

<div class="container-fluid">
    <!-- Header da Página -->
    <div class="page-header slide-in">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="mb-2">
                    <i class="fas fa-cart-plus me-3"></i>
                    Novo Pedido para Fornecedor
                </h1>
                <p class="mb-0 opacity-75">
                    Crie um novo pedido de compra com itens detalhados
                </p>
            </div>
            <div class="col-auto">
                <a href="<?php echo $fornecedor_id ? 'detalhes.php?id=' . $fornecedor_id : 'listar.php'; ?>" 
                   class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show slide-in" role="alert">
            <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger slide-in">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>Corrija os seguintes erros:</h6>
            <ul class="mb-0">
                <?php foreach ($erros as $erro): ?>
                    <li><?php echo $erro; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="formPedido" method="POST" class="needs-validation" novalidate>
        <!-- Informações do Pedido -->
        <div class="form-card slide-in" style="animation-delay: 0.1s;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações do Pedido
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Seleção do Fornecedor -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <?php if ($fornecedor_id == 0): ?>
                                <select class="form-select" id="fornecedor_id" name="fornecedor_id" required>
                                    <option value="">Selecione um fornecedor...</option>
                                    <?php while ($forn = $result_fornecedores->fetch_assoc()): ?>
                                        <option value="<?php echo $forn['id']; ?>">
                                            <?php echo htmlspecialchars($forn['nome']); ?>
                                            <?php if (!empty($forn['empresa'])): ?>
                                                - <?php echo htmlspecialchars($forn['empresa']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <label for="fornecedor_id">Fornecedor <span class="required">*</span></label>
                                <div class="invalid-feedback">
                                    Por favor, selecione um fornecedor.
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="fornecedor_id" value="<?php echo $fornecedor_id; ?>">
                                <div class="fornecedor-info">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($fornecedor['nome']); ?></h6>
                                    <?php if (!empty($fornecedor['empresa'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($fornecedor['empresa']); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($fornecedor['telefone'])): ?>
                                        <br><small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($fornecedor['telefone']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Número do Pedido -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="numero_pedido" name="numero_pedido" 
                                   placeholder="Ex: PED-2025-001">
                            <label for="numero_pedido">Número do Pedido</label>
                            <div class="form-text">Deixe em branco para gerar automaticamente</div>
                        </div>
                    </div>
                    
                    <!-- Data do Pedido -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="data_pedido" name="data_pedido" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                            <label for="data_pedido">Data do Pedido <span class="required">*</span></label>
                            <div class="invalid-feedback">
                                Data do pedido é obrigatória.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data de Entrega Prevista -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="data_entrega_prevista" name="data_entrega_prevista">
                            <label for="data_entrega_prevista">Data de Entrega Prevista</label>
                        </div>
                    </div>
                    
                    <!-- Forma de Pagamento -->
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="forma_pagamento" name="forma_pagamento">
                                <option value="">Selecione...</option>
                                <option value="a_vista">À Vista</option>
                                <option value="boleto_15">Boleto 15 dias</option>
                                <option value="boleto_30">Boleto 30 dias</option>
                                <option value="boleto_45">Boleto 45 dias</option>
                                <option value="pix">PIX</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="transferencia">Transferência</option>
                                <option value="outros">Outros</option>
                            </select>
                            <label for="forma_pagamento">Forma de Pagamento</label>
                        </div>
                    </div>
                    
                    <!-- Observações -->
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="observacoes" name="observacoes" 
                                      style="height: 100px" placeholder="Observações gerais do pedido..."></textarea>
                            <label for="observacoes">Observações</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Itens do Pedido -->
        <div class="form-card slide-in" style="animation-delay: 0.2s;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Itens do Pedido
                </h5>
                <button type="button" class="btn btn-add-item btn-sm" onclick="adicionarItem()">
                    <i class="fas fa-plus me-2"></i> Adicionar Item
                </button>
            </div>
            <div class="card-body">
                <div id="itens-container">
                    <!-- Os itens serão adicionados aqui via JavaScript -->
                </div>
                
                <div class="total-display" id="total-display">
                    Total do Pedido: R$ 0,00
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?php echo $fornecedor_id ? 'detalhes.php?id=' . $fornecedor_id : 'listar.php'; ?>" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                    <button type="button" class="btn btn-outline-primary" onclick="salvarRascunho()">
                        <i class="fas fa-save me-2"></i> Salvar Rascunho
                    </button>
                    <button type="submit" class="btn btn-fornecedores">
                        <i class="fas fa-paper-plane me-2"></i> Criar Pedido
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let itemCount = 0;

// Adicionar primeiro item automaticamente
document.addEventListener('DOMContentLoaded', function() {
    adicionarItem();
    
    // Auto-complete para data de entrega
    const dataEntrega = document.getElementById('data_entrega_prevista');
    const dataPedido = document.getElementById('data_pedido');
    
    if (dataPedido.value) {
        const prazoEntrega = <?php echo isset($fornecedor['prazo_entrega_padrao']) ? $fornecedor['prazo_entrega_padrao'] : 7; ?>;
        const dataEntregaPrevista = new Date(dataPedido.value);
        dataEntregaPrevista.setDate(dataEntregaPrevista.getDate() + prazoEntrega);
        dataEntrega.value = dataEntregaPrevista.toISOString().split('T')[0];
    }
    
    dataPedido.addEventListener('change', function() {
        if (this.value) {
            const prazoEntrega = <?php echo isset($fornecedor['prazo_entrega_padrao']) ? $fornecedor['prazo_entrega_padrao'] : 7; ?>;
            const dataEntregaPrevista = new Date(this.value);
            dataEntregaPrevista.setDate(dataEntregaPrevista.getDate() + prazoEntrega);
            dataEntrega.value = dataEntregaPrevista.toISOString().split('T')[0];
        }
    });
});

function adicionarItem() {
    itemCount++;
    const container = document.getElementById('itens-container');
    
    const itemHtml = `
        <div class="item-row" id="item-${itemCount}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0">Item ${itemCount}</h6>
                <button type="button" class="btn btn-remove-item" onclick="removerItem(${itemCount})" 
                        ${itemCount === 1 ? 'style="display: none;"' : ''}>
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="itens[${itemCount}][descricao]" 
                               id="descricao_${itemCount}" required>
                        <label for="descricao_${itemCount}">Descrição do Item <span class="required">*</span></label>
                        <div class="invalid-feedback">
                            Descrição é obrigatória.
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" name="itens[${itemCount}][quantidade]" 
                               id="quantidade_${itemCount}" min="0.01" required onchange="calcularTotal()">
                        <label for="quantidade_${itemCount}">Qtd <span class="required">*</span></label>
                        <div class="invalid-feedback">
                            Quantidade é obrigatória.
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-floating">
                        <select class="form-select" name="itens[${itemCount}][unidade]" id="unidade_${itemCount}">
                            <option value="UN">Unidade</option>
                            <option value="KG">Quilograma</option>
                            <option value="G">Grama</option>
                            <option value="L">Litro</option>
                            <option value="ML">Mililitro</option>
                            <option value="M">Metro</option>
                            <option value="CM">Centímetro</option>
                            <option value="M2">Metro²</option>
                            <option value="M3">Metro³</option>
                            <option value="CX">Caixa</option>
                            <option value="PC">Peça</option>
                            <option value="PCT">Pacote</option>
                        </select>
                        <label for="unidade_${itemCount}">Unidade</label>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-floating">
                        <input type="text" class="form-control money" name="itens[${itemCount}][valor_unitario]" 
                               id="valor_unitario_${itemCount}" required onchange="calcularTotal()">
                        <label for="valor_unitario_${itemCount}">Valor Unit. <span class="required">*</span></label>
                        <div class="invalid-feedback">
                            Valor unitário é obrigatório.
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="valor_total_${itemCount}" readonly>
                        <label for="valor_total_${itemCount}">Total Item</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="itens[${itemCount}][observacoes]" 
                               id="obs_item_${itemCount}">
                        <label for="obs_item_${itemCount}">Observações do Item</label>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemHtml);
    
    // Aplicar máscara de dinheiro
    const valorInput = document.getElementById(`valor_unitario_${itemCount}`);
    aplicarMascaraDinheiro(valorInput);
    
    // Mostrar botão de remover no primeiro item se houver mais de um
    if (itemCount > 1) {
        const firstRemoveBtn = document.querySelector('#item-1 .btn-remove-item');
        if (firstRemoveBtn) {
            firstRemoveBtn.style.display = 'flex';
        }
    }
}

function removerItem(id) {
    const item = document.getElementById(`item-${id}`);
    if (item) {
        item.remove();
        calcularTotal();
        
        // Se só sobrou um item, esconder o botão de remover
        const remainingItems = document.querySelectorAll('.item-row');
        if (remainingItems.length === 1) {
            const removeBtn = remainingItems[0].querySelector('.btn-remove-item');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }
    }
}

function calcularTotal() {
    let total = 0;
    const items = document.querySelectorAll('.item-row');
    
    items.forEach((item, index) => {
        const quantidadeInput = item.querySelector('[name*="[quantidade]"]');
        const valorInput = item.querySelector('[name*="[valor_unitario]"]');
        const totalItemInput = item.querySelector('[id*="valor_total_"]');
        
        if (quantidadeInput && valorInput && totalItemInput) {
            const quantidade = parseFloat(quantidadeInput.value) || 0;
            const valor = parseFloat(valorInput.value.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
            const totalItem = quantidade * valor;
            
            totalItemInput.value = formatarMoeda(totalItem);
            total += totalItem;
        }
    });
    
    document.getElementById('total-display').textContent = `Total do Pedido: R$ ${formatarMoeda(total)}`;
}

function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function aplicarMascaraDinheiro(input) {
    input.addEventListener('input', function(e) {
        let value = e.target.value;
        
        // Remove tudo que não é dígito
        value = value.replace(/\D/g, '');
        
        // Adiciona os pontos e vírgula
        if (value.length > 2) {
            value = value.slice(0, -2) + ',' + value.slice(-2);
        }
        if (value.length > 6) {
            value = value.slice(0, -6) + '.' + value.slice(-6);
        }
        if (value.length > 10) {
            value = value.slice(0, -10) + '.' + value.slice(-10);
        }
        
        e.target.value = value;
        calcularTotal();
    });
}

function salvarRascunho() {
    const formData = new FormData(document.getElementById('formPedido'));
    
    // Aqui você pode implementar o salvamento em localStorage ou envio para o servidor
    showNotification('Rascunho salvo com sucesso!', 'info');
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Validação do formulário
(function() {
    'use strict';
    window.addEventListener('load', function() {
        const forms = document.getElementsByClassName('needs-validation');
        const validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Validar se há pelo menos um item
                const items = document.querySelectorAll('.item-row');
                if (items.length === 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    showNotification('É necessário adicionar pelo menos um item ao pedido.', 'danger');
                    return false;
                }
                
                // Validar itens
                let itemsValidos = true;
                items.forEach((item, index) => {
                    const descricao = item.querySelector('[name*="[descricao]"]');
                    const quantidade = item.querySelector('[name*="[quantidade]"]');
                    const valor = item.querySelector('[name*="[valor_unitario]"]');
                    
                    if (!descricao.value.trim()) {
                        descricao.classList.add('is-invalid');
                        itemsValidos = false;
                    } else {
                        descricao.classList.remove('is-invalid');
                    }
                    
                    if (!quantidade.value || parseFloat(quantidade.value) <= 0) {
                        quantidade.classList.add('is-invalid');
                        itemsValidos = false;
                    } else {
                        quantidade.classList.remove('is-invalid');
                    }
                    
                    if (!valor.value.trim()) {
                        valor.classList.add('is-invalid');
                        itemsValidos = false;
                    } else {
                        valor.classList.remove('is-invalid');
                    }
                });
                
                if (!itemsValidos) {
                    event.preventDefault();
                    event.stopPropagation();
                    showNotification('Por favor, preencha todos os campos obrigatórios dos itens.', 'danger');
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl + Enter para submeter o formulário
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('formPedido').submit();
    }
    
    // Ctrl + I para adicionar item
    if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
        e.preventDefault();
        adicionarItem();
    }
    
    // Ctrl + S para salvar rascunho
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        salvarRascunho();
    }
});
</script>

<?php include '../includes/footer.php'; ?>