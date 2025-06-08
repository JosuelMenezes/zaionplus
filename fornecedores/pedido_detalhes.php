<?php
// fornecedores/pedido_detalhes.php
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

// Verificar se o ID do pedido foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Pedido não especificado";
    $_SESSION['msg_type'] = "danger";
    header("Location: pedidos.php");
    exit;
}

$pedido_id = intval($_GET['id']);

// Buscar informações do pedido
$sql_pedido = "SELECT pf.*, f.nome as fornecedor_nome, f.empresa as fornecedor_empresa,
               f.telefone as fornecedor_telefone, f.whatsapp as fornecedor_whatsapp,
               f.email as fornecedor_email, f.endereco as fornecedor_endereco,
               u.nome as usuario_nome
               FROM pedidos_fornecedores pf
               LEFT JOIN fornecedores f ON pf.fornecedor_id = f.id
               LEFT JOIN usuarios u ON pf.criado_por = u.id
               WHERE pf.id = ?";

$stmt = $conn->prepare($sql_pedido);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result_pedido = $stmt->get_result();

if (!$result_pedido || $result_pedido->num_rows == 0) {
    $_SESSION['msg'] = "Pedido não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: pedidos.php");
    exit;
}

$pedido = $result_pedido->fetch_assoc();

// Buscar itens do pedido
$sql_itens = "SELECT * FROM itens_pedido_fornecedor WHERE pedido_id = ? ORDER BY id";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("i", $pedido_id);
$stmt_itens->execute();
$result_itens = $stmt_itens->get_result();

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
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

.info-card {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.info-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.info-card .card-header {
    background: var(--gradient-fornecedores);
    color: white;
    border: none;
    border-radius: 15px 15px 0 0;
    padding: 1.5rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pendente {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-confirmado {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.status-em_transito {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-entregue {
    background: #d1f2eb;
    color: #0c5460;
    border: 1px solid #7bdcb5;
}

.status-cancelado {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
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

.data-table {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-soft);
    background: white;
}

.data-table .table {
    margin: 0;
}

.data-table .table thead {
    background: var(--gradient-fornecedores);
    color: white;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-item {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: var(--shadow-soft);
    border-left: 4px solid #fd7e14;
}

.info-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
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

.timeline-item {
    padding: 1rem;
    border-left: 3px solid #e9ecef;
    margin-bottom: 1rem;
    position: relative;
}

.timeline-item::before {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #fd7e14;
    position: absolute;
    left: -6.5px;
    top: 1.5rem;
}

.valor-destaque {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
}

.print-button {
    background: var(--gradient-info);
    border: none;
    color: white;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.print-button:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}
</style>

<div class="container-fluid">
    <!-- Header do Pedido -->
    <div class="page-header slide-in">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="mb-2">
                    <i class="fas fa-file-invoice me-3"></i>
                    Pedido #<?php echo $pedido['numero_pedido'] ?: 'PED-' . str_pad($pedido['id'], 4, '0', STR_PAD_LEFT); ?>
                </h1>
                <p class="mb-0 opacity-75">
                    Fornecedor: <?php echo htmlspecialchars($pedido['fornecedor_nome']); ?>
                    <?php if (!empty($pedido['fornecedor_empresa'])): ?>
                        - <?php echo htmlspecialchars($pedido['fornecedor_empresa']); ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <a href="pedidos.php<?php echo $pedido['fornecedor_id'] ? '?fornecedor_id=' . $pedido['fornecedor_id'] : ''; ?>" 
                       class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i> Voltar
                    </a>
                    <button onclick="imprimirPedido()" class="btn print-button">
                        <i class="fas fa-print me-2"></i> Imprimir
                    </button>
                    <button onclick="editarStatus()" class="btn btn-light">
                        <i class="fas fa-edit me-2"></i> Alterar Status
                    </button>
                </div>
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

    <!-- Informações Básicas do Pedido -->
    <div class="info-grid slide-in" style="animation-delay: 0.1s">
        <div class="info-item">
            <div class="info-label">Data do Pedido</div>
            <div class="info-value">
                <i class="fas fa-calendar me-2"></i>
                <?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value">
                <span class="status-badge status-<?php echo $pedido['status']; ?>">
                    <?php 
                    $status_labels = [
                        'pendente' => 'Pendente',
                        'confirmado' => 'Confirmado',
                        'em_transito' => 'Em Trânsito',
                        'entregue' => 'Entregue',
                        'cancelado' => 'Cancelado'
                    ];
                    echo $status_labels[$pedido['status']] ?? ucfirst($pedido['status']);
                    ?>
                </span>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Valor Total</div>
            <div class="info-value valor-destaque">
                R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
            </div>
        </div>
        
        <?php if ($pedido['data_entrega_prevista']): ?>
        <div class="info-item">
            <div class="info-label">Entrega Prevista</div>
            <div class="info-value">
                <i class="fas fa-truck me-2"></i>
                <?php echo date('d/m/Y', strtotime($pedido['data_entrega_prevista'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($pedido['data_entrega_realizada']): ?>
        <div class="info-item">
            <div class="info-label">Entrega Realizada</div>
            <div class="info-value text-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo date('d/m/Y', strtotime($pedido['data_entrega_realizada'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($pedido['forma_pagamento']): ?>
        <div class="info-item">
            <div class="info-label">Forma de Pagamento</div>
            <div class="info-value">
                <i class="fas fa-credit-card me-2"></i>
                <?php echo htmlspecialchars($pedido['forma_pagamento']); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="info-item">
            <div class="info-label">Criado por</div>
            <div class="info-value">
                <i class="fas fa-user me-2"></i>
                <?php echo htmlspecialchars($pedido['usuario_nome'] ?: 'Sistema'); ?>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-label">Data de Criação</div>
            <div class="info-value">
                <i class="fas fa-clock me-2"></i>
                <?php echo date('d/m/Y H:i', strtotime($pedido['data_criacao'])); ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Itens do Pedido -->
        <div class="col-lg-8">
            <div class="info-card slide-in" style="animation-delay: 0.2s">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Itens do Pedido
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($result_itens && $result_itens->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="background: var(--gradient-fornecedores); color: white;">
                                    <tr>
                                        <th>Item</th>
                                        <th>Qtd</th>
                                        <th>Unidade</th>
                                        <th>Valor Unit.</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_geral = 0;
                                    while ($item = $result_itens->fetch_assoc()): 
                                        $total_item = $item['quantidade'] * $item['valor_unitario'];
                                        $total_geral += $total_item;
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['descricao_item']); ?></strong>
                                                <?php if (!empty($item['observacoes'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['observacoes']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($item['quantidade'], 2, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($item['unidade']); ?></td>
                                            <td>R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></td>
                                            <td><strong class="text-success">R$ <?php echo number_format($total_item, 2, ',', '.'); ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot style="background: #f8f9fa;">
                                    <tr>
                                        <th colspan="4" class="text-end">Total Geral:</th>
                                        <th class="text-success">R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h5>Nenhum item encontrado</h5>
                            <p class="text-muted">Este pedido não possui itens cadastrados.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informações do Fornecedor -->
        <div class="col-lg-4">
            <div class="info-card slide-in" style="animation-delay: 0.3s">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Dados do Fornecedor
                    </h5>
                </div>
                <div class="card-body">
                    <h6><?php echo htmlspecialchars($pedido['fornecedor_nome']); ?></h6>
                    <?php if (!empty($pedido['fornecedor_empresa'])): ?>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($pedido['fornecedor_empresa']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($pedido['fornecedor_telefone'])): ?>
                        <p class="mb-1">
                            <i class="fas fa-phone me-2"></i>
                            <a href="tel:<?php echo $pedido['fornecedor_telefone']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($pedido['fornecedor_telefone']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($pedido['fornecedor_whatsapp'])): ?>
                        <p class="mb-1">
                            <i class="fab fa-whatsapp me-2 text-success"></i>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $pedido['fornecedor_whatsapp']); ?>" 
                               target="_blank" class="text-decoration-none text-success">
                                <?php echo htmlspecialchars($pedido['fornecedor_whatsapp']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($pedido['fornecedor_email'])): ?>
                        <p class="mb-1">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:<?php echo $pedido['fornecedor_email']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($pedido['fornecedor_email']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($pedido['fornecedor_endereco'])): ?>
                        <p class="mb-0 mt-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($pedido['fornecedor_endereco']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="detalhes.php?id=<?php echo $pedido['fornecedor_id']; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-2"></i> Ver Fornecedor
                        </a>
                    </div>
                </div>
            </div>

            <!-- Observações -->
            <?php if (!empty($pedido['observacoes'])): ?>
            <div class="info-card slide-in" style="animation-delay: 0.4s">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note me-2"></i>
                        Observações
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($pedido['observacoes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function editarStatus() {
    const statusAtual = <?php echo json_encode($pedido['status']); ?>;
    const pedidoId = <?php echo $pedido['id']; ?>;
    
    Swal.fire({
        title: 'Alterar Status do Pedido',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Novo Status</label>
                    <select class="form-select" id="novo_status">
                        <option value="pendente" ${statusAtual === 'pendente' ? 'selected' : ''}>Pendente</option>
                        <option value="confirmado" ${statusAtual === 'confirmado' ? 'selected' : ''}>Confirmado</option>
                        <option value="em_transito" ${statusAtual === 'em_transito' ? 'selected' : ''}>Em Trânsito</option>
                        <option value="entregue" ${statusAtual === 'entregue' ? 'selected' : ''}>Entregue</option>
                        <option value="cancelado" ${statusAtual === 'cancelado' ? 'selected' : ''}>Cancelado</option>
                    </select>
                </div>
                <div class="mb-3" id="data_entrega_div" style="display: none;">
                    <label class="form-label">Data de Entrega</label>
                    <input type="date" class="form-control" id="data_entrega" value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes_status" rows="3" placeholder="Observações sobre a alteração..."></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#fd7e14',
        didOpen: () => {
            const statusSelect = document.getElementById('novo_status');
            const dataEntregaDiv = document.getElementById('data_entrega_div');
            
            function toggleDataEntrega() {
                if (statusSelect.value === 'entregue') {
                    dataEntregaDiv.style.display = 'block';
                } else {
                    dataEntregaDiv.style.display = 'none';
                }
            }
            
            toggleDataEntrega();
            statusSelect.addEventListener('change', toggleDataEntrega);
        },
        preConfirm: () => {
            const status = document.getElementById('novo_status').value;
            const dataEntrega = document.getElementById('data_entrega').value;
            const observacoes = document.getElementById('observacoes_status').value;
            
            return { status, dataEntrega, observacoes };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Preparar dados para envio
            const dadosEnvio = {
                pedido_id: pedidoId,
                status: result.value.status,
                observacoes: result.value.observacoes
            };
            
            // Adicionar data de entrega se status for "entregue"
            if (result.value.status === 'entregue' && result.value.dataEntrega) {
                dadosEnvio.data_entrega = result.value.dataEntrega;
            }
            
            // Mostrar loading
            Swal.fire({
                title: 'Salvando...',
                text: 'Aguarde enquanto o status é atualizado.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Fazer requisição AJAX
            fetch('alterar_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dadosEnvio)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#fd7e14'
                    }).then(() => {
                        // Recarregar a página para mostrar as alterações
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao alterar status: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#fd7e14'
                });
            });
        }
    });
}

function imprimirPedido() {
    window.print();
}

// Animações de entrada
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.slide-in');
    elements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl + P para imprimir
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        imprimirPedido();
    }
    
    // Ctrl + E para editar status
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        editarStatus();
    }
});
</script>

<?php include '../includes/footer.php'; ?>