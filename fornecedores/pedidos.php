<?php
// fornecedores/pedidos.php
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

// Parâmetros de filtro
$fornecedor_id = isset($_GET['fornecedor_id']) ? intval($_GET['fornecedor_id']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Paginação
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$itens_por_pagina = 15;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Construir query com filtros
$where_conditions = [];
$params = [];
$types = '';

if ($fornecedor_id > 0) {
    $where_conditions[] = "pf.fornecedor_id = ?";
    $params[] = $fornecedor_id;
    $types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "pf.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($data_inicio)) {
    $where_conditions[] = "pf.data_pedido >= ?";
    $params[] = $data_inicio;
    $types .= 's';
}

if (!empty($data_fim)) {
    $where_conditions[] = "pf.data_pedido <= ?";
    $params[] = $data_fim;
    $types .= 's';
}

if (!empty($busca)) {
    $where_conditions[] = "(pf.numero_pedido LIKE ? OR f.nome LIKE ? OR pf.observacoes LIKE ?)";
    $busca_like = "%$busca%";
    $params[] = $busca_like;
    $params[] = $busca_like;
    $params[] = $busca_like;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query principal
$sql = "SELECT pf.*, f.nome as fornecedor_nome, f.empresa as fornecedor_empresa,
        COUNT(ipf.id) as total_itens,
        u.nome as usuario_nome
        FROM pedidos_fornecedores pf
        LEFT JOIN fornecedores f ON pf.fornecedor_id = f.id
        LEFT JOIN itens_pedido_fornecedor ipf ON pf.id = ipf.pedido_id
        LEFT JOIN usuarios u ON pf.criado_por = u.id
        $where_clause
        GROUP BY pf.id
        ORDER BY pf.data_pedido DESC, pf.id DESC
        LIMIT $itens_por_pagina OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Query para contar total de registros
$sql_count = "SELECT COUNT(DISTINCT pf.id) as total
              FROM pedidos_fornecedores pf
              LEFT JOIN fornecedores f ON pf.fornecedor_id = f.id
              $where_clause";

if (!empty($params)) {
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
} else {
    $result_count = $conn->query($sql_count);
}

$total_registros = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Buscar estatísticas gerais
$sql_stats = "SELECT 
              COUNT(*) as total_pedidos,
              SUM(CASE WHEN pf.status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
              SUM(CASE WHEN pf.status = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
              SUM(CASE WHEN pf.status = 'em_transito' THEN 1 ELSE 0 END) as em_transito,
              SUM(CASE WHEN pf.status = 'entregue' THEN 1 ELSE 0 END) as entregues,
              SUM(CASE WHEN pf.status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
              SUM(pf.valor_total) as valor_total,
              SUM(CASE WHEN pf.status IN ('pendente', 'confirmado', 'em_transito') THEN pf.valor_total ELSE 0 END) as valor_aberto
              FROM pedidos_fornecedores pf
              LEFT JOIN fornecedores f ON pf.fornecedor_id = f.id
              $where_clause";

if (!empty($params)) {
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param($types, ...$params);
    $stmt_stats->execute();
    $result_stats = $stmt_stats->get_result();
} else {
    $result_stats = $conn->query($sql_stats);
}

$stats = $result_stats->fetch_assoc();

// Buscar fornecedores para o filtro
$sql_fornecedores = "SELECT id, nome, empresa FROM fornecedores WHERE status = 'ativo' ORDER BY nome";
$result_fornecedores = $conn->query($sql_fornecedores);

// Se há um fornecedor específico, buscar seus dados
$fornecedor_nome = '';
if ($fornecedor_id > 0) {
    $sql_fornecedor = "SELECT nome, empresa FROM fornecedores WHERE id = $fornecedor_id";
    $result_fornecedor = $conn->query($sql_fornecedor);
    if ($result_fornecedor && $result_fornecedor->num_rows > 0) {
        $fornecedor_data = $result_fornecedor->fetch_assoc();
        $fornecedor_nome = $fornecedor_data['nome'];
        if (!empty($fornecedor_data['empresa'])) {
            $fornecedor_nome .= ' - ' . $fornecedor_data['empresa'];
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

.stats-card {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    margin-bottom: 1rem;
}

.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
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

.filter-card {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    margin-bottom: 2rem;
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

.data-table .table tbody tr {
    border: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.data-table .table tbody tr:hover {
    background: rgba(253, 126, 20, 0.1);
    transform: scale(1.01);
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.8rem;
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

.pagination {
    --bs-pagination-active-bg: #fd7e14;
    --bs-pagination-active-border-color: #fd7e14;
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

.pedido-row {
    cursor: pointer;
    transition: all 0.3s ease;
}

.pedido-row:hover {
    background-color: rgba(253, 126, 20, 0.1) !important;
    transform: scale(1.01);
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.3;
}

.quick-actions {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.quick-action-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    box-shadow: var(--shadow-hover);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    transform: scale(1.1);
}

.breadcrumb-item a {
    color: #fd7e14;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #e83e8c;
    text-decoration: underline;
}
</style>

<div class="container-fluid">
    <!-- Header da Página -->
    <div class="page-header slide-in">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="mb-2">
                    <i class="fas fa-shopping-cart me-3"></i>
                    Pedidos para Fornecedores
                    <?php if ($fornecedor_nome): ?>
                        <small class="opacity-75 d-block fs-5 mt-2"><?php echo htmlspecialchars($fornecedor_nome); ?></small>
                    <?php endif; ?>
                </h1>
                <p class="mb-0 opacity-75">
                    Gerencie todos os pedidos de compra realizados
                </p>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <?php if ($fornecedor_id > 0): ?>
                        <a href="detalhes.php?id=<?php echo $fornecedor_id; ?>" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i> Voltar
                        </a>
                    <?php else: ?>
                        <a href="listar.php" class="btn btn-outline-light">
                            <i class="fas fa-users me-2"></i> Fornecedores
                        </a>
                    <?php endif; ?>
                    <a href="novo_pedido.php<?php echo $fornecedor_id ? '?fornecedor_id=' . $fornecedor_id : ''; ?>" 
                       class="btn btn-light">
                        <i class="fas fa-plus me-2"></i> Novo Pedido
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

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="metric-card slide-in" style="animation-delay: 0.1s">
                <div class="metric-value text-primary"><?php echo number_format($stats['total_pedidos']); ?></div>
                <div class="metric-label">Total</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="metric-card slide-in" style="animation-delay: 0.2s">
                <div class="metric-value text-warning"><?php echo number_format($stats['pendentes']); ?></div>
                <div class="metric-label">Pendentes</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="metric-card slide-in" style="animation-delay: 0.3s">
                <div class="metric-value text-info"><?php echo number_format($stats['confirmados']); ?></div>
                <div class="metric-label">Confirmados</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="metric-card slide-in" style="animation-delay: 0.4s">
                <div class="metric-value text-primary"><?php echo number_format($stats['em_transito']); ?></div>
                <div class="metric-label">Em Trânsito</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="metric-card slide-in" style="animation-delay: 0.5s">
                <div class="metric-value text-success"><?php echo number_format($stats['entregues']); ?></div>
                <div class="metric-label">Entregues</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="metric-card slide-in" style="animation-delay: 0.6s">
                <div class="metric-value text-danger"><?php echo number_format($stats['cancelados']); ?></div>
                <div class="metric-label">Cancelados</div>
            </div>
        </div>
    </div>

    <!-- Valores Totais -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="metric-card slide-in" style="animation-delay: 0.7s">
                <div class="metric-value text-success">R$ <?php echo number_format($stats['valor_total'], 2, ',', '.'); ?></div>
                <div class="metric-label">Valor Total Geral</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="metric-card slide-in" style="animation-delay: 0.8s">
                <div class="metric-value text-warning">R$ <?php echo number_format($stats['valor_aberto'], 2, ',', '.'); ?></div>
                <div class="metric-label">Valor em Aberto</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-card slide-in" style="animation-delay: 0.9s">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filtrosForm">
                <?php if ($fornecedor_id > 0): ?>
                    <input type="hidden" name="fornecedor_id" value="<?php echo $fornecedor_id; ?>">
                <?php else: ?>
                    <div class="col-md-3">
                        <label class="form-label">Fornecedor</label>
                        <select name="fornecedor_id" class="form-select">
                            <option value="">Todos os fornecedores</option>
                            <?php while ($forn = $result_fornecedores->fetch_assoc()): ?>
                                <option value="<?php echo $forn['id']; ?>" 
                                        <?php echo $fornecedor_id == $forn['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($forn['nome']); ?>
                                    <?php if (!empty($forn['empresa'])): ?>
                                        - <?php echo htmlspecialchars($forn['empresa']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo $status_filter == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="confirmado" <?php echo $status_filter == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                        <option value="em_transito" <?php echo $status_filter == 'em_transito' ? 'selected' : ''; ?>>Em Trânsito</option>
                        <option value="entregue" <?php echo $status_filter == 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                        <option value="cancelado" <?php echo $status_filter == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($data_inicio); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($data_fim); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="busca" class="form-control" 
                           placeholder="Número, fornecedor..." value="<?php echo htmlspecialchars($busca); ?>">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-fornecedores">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF'] . ($fornecedor_id ? '?fornecedor_id=' . $fornecedor_id : ''); ?>" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Pedidos -->
    <div class="data-table slide-in" style="animation-delay: 1s">
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Fornecedor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Entrega</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pedido = $result->fetch_assoc()): ?>
                            <tr class="pedido-row" onclick="verDetalhes(<?php echo $pedido['id']; ?>)">
                                <td>
                                    <strong>#<?php echo $pedido['numero_pedido'] ?: 'PED-' . str_pad($pedido['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                    <br><small class="text-muted"><?php echo $pedido['total_itens']; ?> item(s)</small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pedido['fornecedor_nome']); ?></strong>
                                    <?php if (!empty($pedido['fornecedor_empresa'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($pedido['fornecedor_empresa']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?>
                                    <br><small class="text-muted">por <?php echo htmlspecialchars($pedido['usuario_nome']); ?></small>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <strong class="text-success">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></strong>
                                </td>
                                <td>
                                    <?php if ($pedido['data_entrega_realizada']): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                            <?php echo date('d/m/Y', strtotime($pedido['data_entrega_realizada'])); ?>
                                        </span>
                                    <?php elseif ($pedido['data_entrega_prevista']): ?>
                                        <?php
                                        $is_atrasado = strtotime($pedido['data_entrega_prevista']) < time() && $pedido['status'] != 'entregue';
                                        ?>
                                        <span class="<?php echo $is_atrasado ? 'text-danger' : 'text-warning'; ?>">
                                            <i class="fas fa-<?php echo $is_atrasado ? 'exclamation-triangle' : 'clock'; ?>"></i>
                                            <?php echo date('d/m/Y', strtotime($pedido['data_entrega_prevista'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Não definida</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                type="button" data-bs-toggle="dropdown" 
                                                onclick="event.stopPropagation();">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="pedido_detalhes.php?id=<?php echo $pedido['id']; ?>" 
                                                   onclick="event.stopPropagation();">
                                                    <i class="fas fa-eye me-2"></i> Ver Detalhes
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)" 
                                                   onclick="event.stopPropagation(); editarStatus(<?php echo $pedido['id']; ?>, '<?php echo $pedido['status']; ?>')">
                                                    <i class="fas fa-edit me-2"></i> Alterar Status
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)" 
                                                   onclick="event.stopPropagation(); imprimirPedido(<?php echo $pedido['id']; ?>)">
                                                    <i class="fas fa-print me-2"></i> Imprimir
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="javascript:void(0)" 
                                                   onclick="event.stopPropagation(); cancelarPedido(<?php echo $pedido['id']; ?>)">
                                                    <i class="fas fa-times me-2"></i> Cancelar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Mostrando <?php echo $offset + 1; ?> a <?php echo min($offset + $itens_por_pagina, $total_registros); ?> 
                                de <?php echo $total_registros; ?> registros
                            </small>
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php if ($pagina_atual > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo '?' . http_build_query(array_merge($_GET, ['pagina' => $pagina_atual - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $inicio = max(1, $pagina_atual - 2);
                                $fim = min($total_paginas, $pagina_atual + 2);
                                
                                for ($i = $inicio; $i <= $fim; $i++):
                                ?>
                                    <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo '?' . http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagina_atual < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo '?' . http_build_query(array_merge($_GET, ['pagina' => $pagina_atual + 1])); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h4>Nenhum pedido encontrado</h4>
                <p class="mb-3">
                    <?php if (!empty($busca) || !empty($status_filter) || !empty($data_inicio) || !empty($data_fim)): ?>
                        Não há pedidos que correspondam aos filtros aplicados.
                    <?php else: ?>
                        Ainda não há pedidos cadastrados para este fornecedor.
                    <?php endif; ?>
                </p>
                <a href="novo_pedido.php<?php echo $fornecedor_id ? '?fornecedor_id=' . $fornecedor_id : ''; ?>" 
                   class="btn btn-fornecedores">
                    <i class="fas fa-plus me-2"></i> Criar Primeiro Pedido
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions">
    <button type="button" class="quick-action-btn btn btn-fornecedores" 
            onclick="window.location.href='novo_pedido.php<?php echo $fornecedor_id ? '?fornecedor_id=' . $fornecedor_id : ''; ?>'"
            title="Novo Pedido">
        <i class="fas fa-plus"></i>
    </button>
</div>

<!-- Modal para Alterar Status -->
<div class="modal fade" id="modalStatus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alterar Status do Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formStatus">
                    <input type="hidden" id="pedido_id" name="pedido_id">
                    <div class="mb-3">
                        <label class="form-label">Novo Status</label>
                        <select class="form-select" id="novo_status" name="novo_status" required>
                            <option value="pendente">Pendente</option>
                            <option value="confirmado">Confirmado</option>
                            <option value="em_transito">Em Trânsito</option>
                            <option value="entregue">Entregue</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3" id="data_entrega_div" style="display: none;">
                        <label class="form-label">Data de Entrega</label>
                        <input type="date" class="form-control" id="data_entrega" name="data_entrega">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes_status" name="observacoes" rows="3" 
                                  placeholder="Observações sobre a alteração..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-fornecedores" onclick="salvarStatus()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function verDetalhes(pedidoId) {
    window.location.href = 'pedido_detalhes.php?id=' + pedidoId;
}

function editarStatus(pedidoId, statusAtual) {
    document.getElementById('pedido_id').value = pedidoId;
    document.getElementById('novo_status').value = statusAtual;
    
    // Mostrar campo de data de entrega se status for "entregue"
    document.getElementById('novo_status').addEventListener('change', function() {
        const dataEntregaDiv = document.getElementById('data_entrega_div');
        if (this.value === 'entregue') {
            dataEntregaDiv.style.display = 'block';
            document.getElementById('data_entrega').value = new Date().toISOString().split('T')[0];
        } else {
            dataEntregaDiv.style.display = 'none';
        }
    });
    
    const modal = new bootstrap.Modal(document.getElementById('modalStatus'));
    modal.show();
}

function salvarStatus() {
    const pedidoId = document.getElementById('pedido_id').value;
    const novoStatus = document.getElementById('novo_status').value;
    const dataEntrega = document.getElementById('data_entrega').value;
    const observacoes = document.getElementById('observacoes_status').value;
    
    // Validação básica
    if (!pedidoId || !novoStatus) {
        Swal.fire({
            title: 'Erro!',
            text: 'Dados obrigatórios não preenchidos.',
            icon: 'error',
            confirmButtonColor: '#fd7e14'
        });
        return;
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
    
    // Preparar dados para envio
    const dadosEnvio = {
        pedido_id: parseInt(pedidoId),
        status: novoStatus,
        observacoes: observacoes
    };
    
    // Adicionar data de entrega se status for "entregue"
    if (novoStatus === 'entregue' && dataEntrega) {
        dadosEnvio.data_entrega = dataEntrega;
    }
    
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
    
    // Fechar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalStatus'));
    if (modal) {
        modal.hide();
    }
}

function imprimirPedido(pedidoId) {
    // Implementar impressão/PDF do pedido
    Swal.fire({
        title: 'Gerando PDF...',
        text: 'Aguarde enquanto o documento é preparado.',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    setTimeout(() => {
        Swal.fire({
            title: 'PDF Gerado!',
            text: 'O documento está sendo baixado.',
            icon: 'success',
            confirmButtonColor: '#fd7e14'
        });
        
        // Simular download
        // window.open('imprimir_pedido.php?id=' + pedidoId, '_blank');
    }, 2000);
}

function cancelarPedido(pedidoId) {
    Swal.fire({
        title: 'Cancelar Pedido?',
        text: 'Esta ação não pode ser desfeita. O pedido será marcado como cancelado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Não, manter'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implementar cancelamento via AJAX
            Swal.fire({
                title: 'Pedido Cancelado!',
                text: 'O pedido foi cancelado com sucesso.',
                icon: 'success',
                confirmButtonColor: '#fd7e14'
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Auto-submit no filtro de status
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.querySelector('select[name="status"]');
    const fornecedorSelect = document.querySelector('select[name="fornecedor_id"]');
    
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            document.getElementById('filtrosForm').submit();
        });
    }
    
    if (fornecedorSelect) {
        fornecedorSelect.addEventListener('change', function() {
            document.getElementById('filtrosForm').submit();
        });
    }
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl + N para novo pedido
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'novo_pedido.php<?php echo $fornecedor_id ? '?fornecedor_id=' . $fornecedor_id : ''; ?>';
        }
        
        // F5 para atualizar
        if (e.key === 'F5') {
            e.preventDefault();
            location.reload();
        }
    });
    
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
});

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

// Tooltip para ações
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

<?php include '../includes/footer.php'; ?>