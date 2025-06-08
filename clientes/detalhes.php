<?php
// clientes/detalhes.php - Versão Melhorada
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

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Cliente não especificado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$cliente_id = intval($_GET['id']);

// Buscar informações do cliente
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = $conn->query($sql_cliente);

if (!$result_cliente || $result_cliente->num_rows == 0) {
    $_SESSION['msg'] = "Cliente não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$cliente = $result_cliente->fetch_assoc();

// Calcular estatísticas do cliente
$sql_stats = "SELECT 
              COUNT(DISTINCT v.id) as total_vendas,
              COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as total_compras,
              COALESCE((SELECT SUM(p.valor) FROM pagamentos p 
                        JOIN vendas v2 ON p.venda_id = v2.id 
                        WHERE v2.cliente_id = $cliente_id), 0) as total_pago,
              MAX(v.data_venda) as ultima_compra,
              MIN(v.data_venda) as primeira_compra,
              COUNT(DISTINCT CASE WHEN v.status = 'aberto' THEN v.id END) as vendas_abertas,
              COUNT(DISTINCT CASE WHEN v.status = 'pago' THEN v.id END) as vendas_pagas,
              AVG(CASE WHEN iv.quantidade > 0 THEN iv.quantidade * iv.valor_unitario END) as ticket_medio
              FROM vendas v
              LEFT JOIN itens_venda iv ON v.id = iv.venda_id
              WHERE v.cliente_id = $cliente_id";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

$total_vendas = $stats['total_vendas'];
$total_compras = $stats['total_compras'];
$total_pago = $stats['total_pago'];
$saldo_devedor = $total_compras - $total_pago;
$ticket_medio = $stats['ticket_medio'] ?: 0;

// Buscar vendas do cliente (com mais detalhes)
$sql_vendas = "SELECT v.id, v.data_venda, v.status,
               COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as valor_total,
               COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.venda_id = v.id), 0) as valor_pago,
               COUNT(iv.id) as total_itens
               FROM vendas v
               LEFT JOIN itens_venda iv ON v.id = iv.venda_id
               WHERE v.cliente_id = $cliente_id
               GROUP BY v.id
               ORDER BY v.data_venda DESC
               LIMIT 10";
$result_vendas = $conn->query($sql_vendas);

// Buscar histórico de pagamentos do cliente
$sql_pagamentos = "SELECT p.id, p.venda_id, p.valor, p.data_pagamento, p.observacao,
                   COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as valor_venda_total
                  FROM pagamentos p
                  JOIN vendas v ON p.venda_id = v.id
                  LEFT JOIN itens_venda iv ON v.id = iv.venda_id
                  WHERE v.cliente_id = $cliente_id
                  GROUP BY p.id
                  ORDER BY p.data_pagamento DESC, p.id DESC
                  LIMIT 10";
$result_pagamentos = $conn->query($sql_pagamentos);

// Buscar produtos mais comprados
$sql_produtos = "SELECT p.nome, SUM(iv.quantidade) as total_quantidade,
                 SUM(iv.quantidade * iv.valor_unitario) as total_valor,
                 COUNT(DISTINCT v.id) as vezes_comprado
                 FROM itens_venda iv
                 JOIN produtos p ON iv.produto_id = p.id
                 JOIN vendas v ON iv.venda_id = v.id
                 WHERE v.cliente_id = $cliente_id
                 GROUP BY p.id, p.nome
                 ORDER BY total_quantidade DESC
                 LIMIT 5";
$result_produtos = $conn->query($sql_produtos);

// Verificar se há mensagens na sessão
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';
$msg_type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : '';
unset($_SESSION['msg']);
unset($_SESSION['msg_type']);

// Preparar mensagem para WhatsApp
$saldo_formatado = number_format($saldo_devedor, 2, ',', '.');
$mensagem_whatsapp = "Olá " . $cliente['nome'] . "! ";

if ($saldo_devedor <= 0) {
    $mensagem_whatsapp .= "Informamos que você está em dia com seus pagamentos. Obrigado pela preferência!";
} else {
    $mensagem_whatsapp .= "Gostaríamos de informar que seu saldo devedor é de R$ " . $saldo_formatado . ". Qualquer dúvida estamos à disposição.";
}

// Codificar a mensagem para URL
$mensagem_whatsapp_codificada = urlencode($mensagem_whatsapp);

include '../includes/header.php';
?>

<style>
:root {
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-danger: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
}

.profile-header {
    background: var(--gradient-primary);
    color: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
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

.client-avatar-xl {
    width: 120px;
    height: 120px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 1rem;
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255,255,255,0.3);
}

.stats-card {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.stats-card .card-header {
    border: none;
    padding: 1.5rem;
    background: var(--gradient-info);
    color: white;
    position: relative;
}

.stats-card.danger .card-header {
    background: var(--gradient-danger);
}

.stats-card.success .card-header {
    background: var(--gradient-success);
}

.stats-card.warning .card-header {
    background: var(--gradient-warning);
}

.metric-card {
    background: white;
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.metric-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.metric-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.metric-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge.excellent {
    background: linear-gradient(135deg, #11998e, #38ef7d);
    color: white;
}

.status-badge.warning {
    background: linear-gradient(135deg, #f093fb, #f5576c);
    color: white;
}

.status-badge.danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
}

.action-btn {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.action-btn:hover::before {
    left: 100%;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.action-btn.primary {
    background: var(--gradient-primary);
    color: white;
}

.action-btn.success {
    background: var(--gradient-success);
    color: white;
}

.action-btn.warning {
    background: var(--gradient-warning);
    color: white;
}

.action-btn.whatsapp {
    background: linear-gradient(135deg, #25d366, #128c7e);
    color: white;
}

.data-table {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-soft);
}

.data-table .table {
    margin: 0;
}

.data-table .table thead {
    background: var(--gradient-info);
    color: white;
}

.data-table .table tbody tr {
    transition: all 0.3s ease;
}

.data-table .table tbody tr:hover {
    background: rgba(79, 172, 254, 0.1);
    transform: scale(1.01);
}

.progress-ring {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(from 0deg, #11998e 0deg, #38ef7d 180deg, #e9ecef 180deg);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.progress-ring::before {
    content: '';
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: white;
    position: absolute;
}

.progress-text {
    font-weight: bold;
    color: #11998e;
    z-index: 1;
    font-size: 0.9rem;
}

.timeline-item {
    border-left: 3px solid #e9ecef;
    padding-left: 1rem;
    margin-bottom: 1rem;
    position: relative;
}

.timeline-item::before {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #4facfe;
    position: absolute;
    left: -6.5px;
    top: 0.5rem;
}

.timeline-item.payment::before {
    background: #11998e;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.client-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-item {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: var(--shadow-soft);
    border-left: 4px solid #4facfe;
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

.slide-in {
    animation: slideInUp 0.6s ease-out forwards;
}
</style>

<!-- Header do Cliente -->
<div class="profile-header slide-in">
    <div class="row align-items-center">
        <div class="col-auto">
            <div class="client-avatar-xl" style="background: linear-gradient(135deg, <?php echo stringToColor($cliente['nome']); ?>, <?php echo stringToColor($cliente['nome'] . 'x'); ?>);">
                <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
            </div>
        </div>
        <div class="col">
            <h1 class="mb-2"><?php echo htmlspecialchars($cliente['nome']); ?></h1>
            <h5 class="mb-3 opacity-75"><?php echo htmlspecialchars($cliente['empresa']); ?></h5>
            
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if ($saldo_devedor <= 0): ?>
                    <span class="status-badge excellent">
                        <i class="fas fa-check-circle"></i> Em Dia
                    </span>
                <?php elseif ($saldo_devedor > $cliente['limite_compra']): ?>
                    <span class="status-badge danger">
                        <i class="fas fa-exclamation-triangle"></i> Limite Excedido
                    </span>
                <?php else: ?>
                    <span class="status-badge warning">
                        <i class="fas fa-clock"></i> Débitos Pendentes
                    </span>
                <?php endif; ?>
                
                <?php if ($stats['ultima_compra']): ?>
                    <?php
                    $dias_ultima_compra = floor((time() - strtotime($stats['ultima_compra'])) / (60 * 60 * 24));
                    if ($dias_ultima_compra <= 7):
                    ?>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-fire"></i> Cliente Ativo
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-auto">
            <div class="d-flex flex-column gap-2">
                <a href="listar.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i> Voltar
                </a>
                <a href="editar.php?id=<?php echo $cliente_id; ?>" class="btn btn-light">
                    <i class="fas fa-edit me-2"></i> Editar
                </a>
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

<!-- Métricas Principais -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.1s">
            <div class="metric-value text-success">R$ <?php echo number_format($total_compras, 0, ',', '.'); ?></div>
            <div class="metric-label">Total de Compras</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.2s">
            <div class="metric-value text-info"><?php echo $total_vendas; ?></div>
            <div class="metric-label">Total de Pedidos</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.3s">
            <div class="metric-value text-primary">R$ <?php echo number_format($ticket_medio, 0, ',', '.'); ?></div>
            <div class="metric-label">Ticket Médio</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.4s">
            <div class="metric-value <?php echo $saldo_devedor > 0 ? 'text-danger' : 'text-success'; ?>">
                R$ <?php echo number_format($saldo_devedor, 0, ',', '.'); ?>
            </div>
            <div class="metric-label">Saldo Devedor</div>
        </div>
    </div>
</div>

<!-- Informações Detalhadas -->
<div class="client-info-grid slide-in" style="animation-delay: 0.5s">
    <div class="info-item">
        <div class="info-label">Telefone</div>
        <div class="info-value">
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>" 
               class="text-success text-decoration-none" target="_blank">
                <i class="fab fa-whatsapp me-1"></i>
                <?php echo htmlspecialchars($cliente['telefone']); ?>
            </a>
        </div>
    </div>
    
    <div class="info-item">
        <div class="info-label">Limite de Compra</div>
        <div class="info-value">R$ <?php echo number_format($cliente['limite_compra'], 2, ',', '.'); ?></div>
    </div>
    
    <div class="info-item">
        <div class="info-label">Cliente Desde</div>
        <div class="info-value">
            <?php 
            if ($stats['primeira_compra']) {
                echo date('d/m/Y', strtotime($stats['primeira_compra']));
                $dias_cliente = floor((time() - strtotime($stats['primeira_compra'])) / (60 * 60 * 24));
                echo " <small class='text-muted'>($dias_cliente dias)</small>";
            } else {
                echo date('d/m/Y', strtotime($cliente['data_cadastro']));
            }
            ?>
        </div>
    </div>
    
    <div class="info-item">
        <div class="info-label">Última Compra</div>
        <div class="info-value">
            <?php 
            if ($stats['ultima_compra']) {
                echo date('d/m/Y', strtotime($stats['ultima_compra']));
                $dias_ultima = floor((time() - strtotime($stats['ultima_compra'])) / (60 * 60 * 24));
                echo " <small class='text-muted'>($dias_ultima dias atrás)</small>";
            } else {
                echo '<span class="text-muted">Nenhuma compra</span>';
            }
            ?>
        </div>
    </div>
    
    <div class="info-item">
        <div class="info-label">Total Pago</div>
        <div class="info-value text-success">R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></div>
    </div>
    
    <div class="info-item">
        <div class="info-label">Utilização do Limite</div>
        <div class="info-value">
            <?php 
            $utilizacao = $cliente['limite_compra'] > 0 ? ($saldo_devedor / $cliente['limite_compra']) * 100 : 0;
            $utilizacao = max(0, $utilizacao);
            ?>
            <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height: 8px;">
                    <div class="progress-bar <?php echo $utilizacao > 100 ? 'bg-danger' : ($utilizacao > 75 ? 'bg-warning' : 'bg-success'); ?>" 
                         style="width: <?php echo min(100, $utilizacao); ?>%"></div>
                </div>
                <span class="small <?php echo $utilizacao > 100 ? 'text-danger' : 'text-muted'; ?>">
                    <?php echo number_format($utilizacao, 1); ?>%
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card slide-in" style="animation-delay: 0.6s;">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="fas fa-bolt text-warning me-2"></i>
                    Ações Rápidas
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="../vendas/nova_venda.php?cliente_id=<?php echo $cliente_id; ?>" 
                       class="action-btn success">
                        <i class="fas fa-shopping-cart me-2"></i> Nova Venda
                    </a>
                    
                    <a href="https://api.whatsapp.com/send?phone=<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>&text=<?php echo $mensagem_whatsapp_codificada; ?>" 
                       class="action-btn whatsapp" target="_blank">
                        <i class="fab fa-whatsapp me-2"></i> Enviar Saldo WhatsApp
                    </a>
                    
                    <?php if ($saldo_devedor > 0): ?>
                        <a href="registrar_pagamento.php?id=<?php echo $cliente_id; ?>" 
                           class="action-btn warning">
                            <i class="fas fa-money-bill-wave me-2"></i> Registrar Pagamento
                        </a>
                    <?php endif; ?>
                    
                    <a href="../vendas/listar.php?cliente_id=<?php echo $cliente_id; ?>" 
                       class="action-btn primary">
                        <i class="fas fa-history me-2"></i> Ver Todas as Vendas
                    </a>
                    
                    <button onclick="gerarRelatorio()" class="action-btn primary">
                        <i class="fas fa-file-pdf me-2"></i> Gerar Relatório
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Últimas Vendas -->
    <div class="col-lg-6 mb-4">
        <div class="card stats-card slide-in" style="animation-delay: 0.7s;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Histórico de Pagamentos
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if ($result_pagamentos && $result_pagamentos->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: var(--gradient-success); color: white;">
                                <tr>
                                    <th>Data</th>
                                    <th>Venda</th>
                                    <th>Valor</th>
                                    <th>Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pagamento = $result_pagamentos->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($pagamento['data_pagamento'])); ?></small>
                                        </td>
                                        <td>
                                            <a href="../vendas/detalhes.php?id=<?php echo $pagamento['venda_id']; ?>" 
                                               class="text-decoration-none">
                                                <strong>#<?php echo $pagamento['venda_id']; ?></strong>
                                            </a>
                                            <br><small class="text-muted">Total: R$ <?php echo number_format($pagamento['valor_venda_total'], 2, ',', '.'); ?></small>
                                        </td>
                                        <td>
                                            <strong class="text-success">R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></strong>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($pagamento['observacao'] ?: 'Sem observações'); ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($saldo_devedor > 0): ?>
                        <div class="card-footer text-center bg-transparent">
                            <a href="registrar_pagamento.php?id=<?php echo $cliente_id; ?>" 
                               class="btn btn-warning">
                                <i class="fas fa-plus me-2"></i> Novo Pagamento
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-money-bill-wave"></i>
                        <h6>Nenhum pagamento registrado</h6>
                        <p class="mb-3">Este cliente ainda não fez nenhum pagamento.</p>
                        <?php if ($saldo_devedor > 0): ?>
                            <a href="registrar_pagamento.php?id=<?php echo $cliente_id; ?>" 
                               class="btn btn-warning">
                                <i class="fas fa-plus me-2"></i> Primeiro Pagamento
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Produtos Mais Comprados -->
<?php if ($result_produtos && $result_produtos->num_rows > 0): ?>
<div class="row">
    <div class="col-12">
        <div class="card stats-card slide-in" style="animation-delay: 0.9s;">
            <div class="card-header warning">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i>
                    Produtos Favoritos
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while ($produto = $result_produtos->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border-0" style="background: linear-gradient(135deg, rgba(240, 147, 251, 0.1), rgba(245, 87, 108, 0.1));">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($produto['nome']); ?></h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="fw-bold text-primary"><?php echo $produto['total_quantidade']; ?></div>
                                            <small class="text-muted">Unidades</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold text-success">R$ <?php echo number_format($produto['total_valor'], 0, ',', '.'); ?></div>
                                            <small class="text-muted">Total</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold text-info"><?php echo $produto['vezes_comprado']; ?>x</div>
                                            <small class="text-muted">Pedidos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Resumo Financeiro -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card stats-card slide-in" style="animation-delay: 1s;">
            <div class="card-header <?php echo $saldo_devedor > 0 ? 'danger' : 'success'; ?>">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Resumo Financeiro
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Situação Atual</h6>
                                <div class="mb-3">
                                    <?php if ($saldo_devedor <= 0): ?>
                                        <div class="alert alert-success mb-2">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Cliente em dia!</strong><br>
                                            Todos os pagamentos estão em ordem.
                                        </div>
                                    <?php elseif ($saldo_devedor > $cliente['limite_compra']): ?>
                                        <div class="alert alert-danger mb-2">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Limite excedido!</strong><br>
                                            Saldo devedor acima do limite permitido.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-2">
                                            <i class="fas fa-clock me-2"></i>
                                            <strong>Débitos pendentes</strong><br>
                                            Cliente possui valores em aberto.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted">Estatísticas</h6>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Vendas Pagas:</span>
                                        <span class="fw-bold text-success"><?php echo $stats['vendas_pagas']; ?></span>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Vendas Abertas:</span>
                                        <span class="fw-bold text-warning"><?php echo $stats['vendas_abertas']; ?></span>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Taxa de Pagamento:</span>
                                        <span class="fw-bold text-info">
                                            <?php 
                                            $taxa = $total_compras > 0 ? ($total_pago / $total_compras) * 100 : 0;
                                            echo number_format($taxa, 1); 
                                            ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-center">
                        <div class="progress-ring mx-auto mb-3">
                            <div class="progress-text">
                                <?php echo number_format($taxa, 0); ?>%
                            </div>
                        </div>
                        <h6 class="text-muted">Taxa de Pagamento</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Função para gerar cor baseada em string
function stringToColor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = hash % 360;
    return `hsl(${hue}, 70%, 50%)`;
}

// Função para gerar relatório
function gerarRelatorio() {
    Swal.fire({
        title: 'Gerar Relatório',
        html: `
            <div class="text-start">
                <p>Selecione o tipo de relatório que deseja gerar para <strong><?php echo addslashes($cliente['nome']); ?></strong>:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="tipoRelatorio" id="relatorio1" value="completo" checked>
                    <label class="form-check-label" for="relatorio1">
                        <strong>Relatório Completo</strong><br>
                        <small class="text-muted">Informações, vendas e pagamentos</small>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="tipoRelatorio" id="relatorio2" value="financeiro">
                    <label class="form-check-label" for="relatorio2">
                        <strong>Relatório Financeiro</strong><br>
                        <small class="text-muted">Apenas dados financeiros</small>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tipoRelatorio" id="relatorio3" value="vendas">
                    <label class="form-check-label" for="relatorio3">
                        <strong>Histórico de Vendas</strong><br>
                        <small class="text-muted">Lista detalhada de vendas</small>
                    </label>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-file-pdf me-2"></i>Gerar PDF',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        preConfirm: () => {
            const tipo = document.querySelector('input[name="tipoRelatorio"]:checked').value;
            return tipo;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Gerando Relatório...',
                text: 'Aguarde enquanto o relatório é gerado.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Simular geração de relatório
            setTimeout(() => {
                window.open(`relatorio.php?cliente_id=<?php echo $cliente_id; ?>&tipo=${result.value}`, '_blank');
                Swal.close();
            }, 2000);
        }
    });
}

// Sistema de notificações
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

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N para nova venda
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        window.location.href = '../vendas/nova_venda.php?cliente_id=<?php echo $cliente_id; ?>';
    }
    
    // Ctrl/Cmd + E para editar cliente
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        window.location.href = 'editar.php?id=<?php echo $cliente_id; ?>';
    }
    
    // Ctrl/Cmd + P para pagamento (se há débitos)
    <?php if ($saldo_devedor > 0): ?>
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.location.href = 'registrar_pagamento.php?id=<?php echo $cliente_id; ?>';
    }
    <?php endif; ?>
    
    // Ctrl/Cmd + W para WhatsApp
    if ((e.ctrlKey || e.metaKey) && e.key === 'w') {
        e.preventDefault();
        window.open('https://api.whatsapp.com/send?phone=<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>&text=<?php echo $mensagem_whatsapp_codificada; ?>', '_blank');
    }
});

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Animações de entrada escalonadas
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
    
    // Tooltips para ações rápidas
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mostrar dicas de atalhos se necessário
    setTimeout(() => {
        <?php if ($saldo_devedor > 0): ?>
            showNotification('💡 Dica: Use Ctrl+P para registrar pagamento rapidamente!', 'info');
        <?php else: ?>
            showNotification('💡 Dica: Use Ctrl+N para criar uma nova venda rapidamente!', 'info');
        <?php endif; ?>
    }, 3000);
});

// Adicionar SweetAlert2 se não estiver incluído
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}

// Atualizar progress ring baseado na taxa de pagamento
document.addEventListener('DOMContentLoaded', function() {
    const progressRing = document.querySelector('.progress-ring');
    if (progressRing) {
        const taxa = <?php echo $taxa; ?>;
        const color1 = taxa >= 80 ? '#11998e' : taxa >= 60 ? '#f093fb' : '#ff6b6b';
        const color2 = taxa >= 80 ? '#38ef7d' : taxa >= 60 ? '#f5576c' : '#ee5a52';
        
        progressRing.style.background = `conic-gradient(from 0deg, ${color1} 0deg, ${color2} ${taxa * 3.6}deg, #e9ecef ${taxa * 3.6}deg)`;
    }
});
</script>

<!-- SweetAlert2 para confirmações -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Função para gerar cor baseada em string (versão PHP)
function stringToColor($str) {
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ord($str[$i]) + (($hash << 5) - $hash);
    }
    $color = '#';
    for ($i = 0; $i < 3; $i++) {
        $value = ($hash >> ($i * 8)) & 0xFF;
        $color .= str_pad(dechex($value), 2, '0', STR_PAD_LEFT);
    }
    return $color;
}

include '../includes/footer.php';
?>
                    <i class="fas fa-shopping-cart me-2"></i>
                    Últimas Vendas
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if ($result_vendas && $result_vendas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: var(--gradient-info); color: white;">
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($venda = $result_vendas->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $venda['id']; ?></strong></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($venda['data_venda'])); ?>
                                            <br><small class="text-muted"><?php echo $venda['total_itens']; ?> itens</small>
                                        </td>
                                        <td>
                                            <strong class="text-success">R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></strong>
                                            <?php if ($venda['valor_pago'] > 0): ?>
                                                <br><small class="text-info">Pago: R$ <?php echo number_format($venda['valor_pago'], 2, ',', '.'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($venda['status'] === 'aberto'): ?>
                                                <span class="badge bg-warning text-dark">Aberto</span>
                                            <?php elseif ($venda['status'] === 'pago'): ?>
                                                <span class="badge bg-success">Pago</span>
                                            <?php elseif ($venda['status'] === 'cancelado'): ?>
                                                <span class="badge bg-danger">Cancelado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../vendas/detalhes.php?id=<?php echo $venda['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card-footer text-center bg-transparent">
                        <a href="../vendas/listar.php?cliente_id=<?php echo $cliente_id; ?>" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i> Ver Todas as Vendas
                        </a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h6>Nenhuma venda registrada</h6>
                        <p class="mb-3">Este cliente ainda não fez nenhuma compra.</p>
                        <a href="../vendas/nova_venda.php?cliente_id=<?php echo $cliente_id; ?>" 
                           class="btn btn-success">
                            <i class="fas fa-plus me-2"></i> Primeira Venda
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Histórico de Pagamentos -->
    <div class="col-lg-6 mb-4">
        <div class="card stats-card slide-in" style="animation-delay: 0.8s;">
            <div class="card-header">
                <h5 class="mb-0">