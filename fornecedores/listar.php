<?php
// fornecedores/listar.php
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

// Definir variáveis de paginação e filtros
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 20;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Obter parâmetros de pesquisa e ordenação
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'nome_asc';
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$visualizacao = isset($_GET['visualizacao']) ? $_GET['visualizacao'] : 'tabela';

// Construir a cláusula WHERE para pesquisa
$where = "1=1";
if (!empty($pesquisa)) {
    $termo_pesquisa = $conn->real_escape_string("%{$pesquisa}%");
    $where .= " AND (f.nome LIKE '{$termo_pesquisa}' OR f.empresa LIKE '{$termo_pesquisa}' OR f.cnpj LIKE '{$termo_pesquisa}' OR f.telefone LIKE '{$termo_pesquisa}')";
}

if (!empty($filtro_categoria)) {
    $categoria_filtro = $conn->real_escape_string($filtro_categoria);
    $where .= " AND EXISTS (SELECT 1 FROM fornecedor_categorias fc WHERE fc.fornecedor_id = f.id AND fc.categoria_id = '$categoria_filtro')";
}

if (!empty($filtro_status)) {
    $status_filtro = $conn->real_escape_string($filtro_status);
    $where .= " AND f.status = '$status_filtro'";
}

if (!empty($filtro_tipo)) {
    $tipo_filtro = $conn->real_escape_string($filtro_tipo);
    $where .= " AND f.tipo_fornecedor = '$tipo_filtro'";
}

// Construir a cláusula ORDER BY para ordenação
switch ($ordenacao) {
    case 'nome_desc':
        $order_by = "f.nome DESC";
        break;
    case 'empresa_asc':
        $order_by = "f.empresa ASC, f.nome ASC";
        break;
    case 'empresa_desc':
        $order_by = "f.empresa DESC, f.nome ASC";
        break;
    case 'data_desc':
        $order_by = "f.data_cadastro DESC";
        break;
    case 'mais_pedidos':
        $order_by = "total_pedidos DESC";
        break;
    case 'maior_valor':
        $order_by = "valor_total_comprado DESC";
        break;
    case 'melhor_avaliacao':
        $order_by = "f.avaliacao DESC";
        break;
    case 'nome_asc':
    default:
        $order_by = "f.nome ASC";
        break;
}

// Consulta para estatísticas gerais
$sql_stats = "SELECT 
    COUNT(*) as total_fornecedores,
    COUNT(CASE WHEN f.status = 'ativo' THEN 1 END) as fornecedores_ativos,
    COUNT(CASE WHEN f.status = 'inativo' THEN 1 END) as fornecedores_inativos,
    COALESCE(SUM(stats.valor_total_comprado), 0) as total_comprado_geral,
    COALESCE(SUM(stats.valor_pedidos_abertos), 0) as total_pedidos_abertos,
    COUNT(CASE WHEN stats.ultimo_pedido >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as fornecedores_ativos_mes
    FROM fornecedores f
    LEFT JOIN view_estatisticas_fornecedores stats ON f.id = stats.id";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// Consulta para contar o total de registros (para paginação)
$sql_count = "SELECT COUNT(DISTINCT f.id) as total FROM fornecedores f WHERE {$where}";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_registros = $row_count['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta principal para obter os fornecedores com informações agregadas
$sql_fornecedores = "
    SELECT f.*,
        stats.total_pedidos,
        stats.valor_total_comprado,
        stats.valor_pedidos_abertos,
        stats.ultimo_pedido,
        stats.entregas_no_prazo,
        stats.total_entregas,
        GROUP_CONCAT(DISTINCT cat.nome ORDER BY cat.nome ASC SEPARATOR ', ') as categorias,
        GROUP_CONCAT(DISTINCT cat.cor ORDER BY cat.nome ASC SEPARATOR ',') as cores_categorias
    FROM fornecedores f
    LEFT JOIN view_estatisticas_fornecedores stats ON f.id = stats.id
    LEFT JOIN fornecedor_categorias fc ON f.id = fc.fornecedor_id
    LEFT JOIN categorias_fornecedores cat ON fc.categoria_id = cat.id
    WHERE {$where}
    GROUP BY f.id
    ORDER BY {$order_by} 
    LIMIT {$offset}, {$itens_por_pagina}";

$result_fornecedores = $conn->query($sql_fornecedores);

// Buscar categorias para filtro
$sql_categorias = "SELECT id, nome, cor FROM categorias_fornecedores WHERE ativo = 1 ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);
$categorias = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Verificar se houve erro na consulta
if (!$result_fornecedores) {
    $_SESSION['msg'] = "Erro ao consultar fornecedores: " . $conn->error;
    $_SESSION['msg_type'] = "danger";
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
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-danger: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    --gradient-fornecedores: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
}

.stats-card {
    background: var(--gradient-primary);
    border: none;
    border-radius: 15px;
    color: white;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: scale(0);
    transition: transform 0.3s ease;
}

.stats-card:hover::before {
    transform: scale(2);
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.fornecedor-card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    margin-bottom: 1.5rem;
    background: white;
}

.fornecedor-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.fornecedor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.5rem;
    margin-right: 1rem;
    position: relative;
    overflow: hidden;
    background: var(--gradient-fornecedores);
}

.fornecedor-avatar::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.fornecedor-card:hover .fornecedor-avatar::before {
    left: 100%;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    margin-right: 12px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    background: var(--gradient-fornecedores);
}

.table-modern {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-soft);
}

.table-modern thead {
    background: var(--gradient-primary);
    color: white;
}

.table-modern tbody tr {
    border: none;
    transition: all 0.3s ease;
}

.table-modern tbody tr:hover {
    background: rgba(253, 126, 20, 0.1);
    transform: scale(1.01);
}

.btn-action {
    border-radius: 8px;
    margin: 2px;
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.search-input {
    border-radius: 25px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #fd7e14;
    box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
}

.badge-categoria {
    border-radius: 20px;
    padding: 0.3rem 0.8rem;
    font-weight: 500;
    font-size: 0.75rem;
    margin: 2px;
    display: inline-block;
}

.badge-status {
    border-radius: 15px;
    padding: 0.4rem 0.8rem;
    font-weight: 600;
}

.badge-ativo {
    background: var(--gradient-success);
    color: white;
}

.badge-inativo {
    background: var(--gradient-danger);
    color: white;
}

.whatsapp-link {
    color: #25d366;
    text-decoration: none;
    transition: all 0.3s ease;
}

.whatsapp-link:hover {
    color: #128c7e;
    transform: scale(1.05);
}

.view-toggle {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.view-toggle .btn {
    border: none;
    border-radius: 0;
    background: transparent;
    color: #6c757d;
    transition: all 0.3s ease;
}

.view-toggle .btn.active {
    background: var(--gradient-fornecedores);
    color: white;
}

.filter-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.avaliacao-stars {
    color: #ffc107;
    font-size: 0.9rem;
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

.quick-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
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
    animation: slideInUp 0.5s ease-out forwards;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">
            <i class="fas fa-truck text-warning me-2"></i>
            Fornecedores
        </h1>
        <p class="text-muted mb-0">Gerencie fornecedores, pedidos e relacionamentos comerciais</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-file-import me-2"></i> Importar
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="modelo_importacao.php">
                    <i class="fas fa-download me-2"></i> Baixar Modelo
                </a></li>
                <li><a class="dropdown-item" href="importar.php">
                    <i class="fas fa-file-import me-2"></i> Importar Fornecedores
                </a></li>
            </ul>
        </div>
        <a href="categorias.php" class="btn btn-outline-info">
            <i class="fas fa-tags me-2"></i> Categorias
        </a>
        <a href="cadastrar.php" class="btn btn-warning">
            <i class="fas fa-plus-circle me-2"></i> Novo Fornecedor
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show slide-in" role="alert">
        <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-truck fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['total_fornecedores']); ?></h3>
                <small>Total de Fornecedores</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-success);">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['fornecedores_ativos']); ?></h3>
                <small>Fornecedores Ativos</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-warning);">
            <div class="card-body text-center">
                <i class="fas fa-dollar-sign fa-2x mb-3"></i>
                <h3 class="mb-1">R$ <?php echo number_format($stats['total_comprado_geral'], 0, ',', '.'); ?></h3>
                <small>Total Comprado</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-info);">
            <div class="card-body text-center">
                <i class="fas fa-hourglass-half fa-2x mb-3"></i>
                <h3 class="mb-1">R$ <?php echo number_format($stats['total_pedidos_abertos'], 0, ',', '.'); ?></h3>
                <small>Pedidos em Aberto</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtros Avançados -->
<div class="filter-section">
    <form method="GET" action="">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Pesquisar</label>
                <div class="input-group">
                    <input type="text" class="form-control search-input" name="pesquisa" 
                           placeholder="Nome, empresa, CNPJ ou telefone..." value="<?php echo htmlspecialchars($pesquisa); ?>">
                    <button class="btn btn-warning" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Categoria</label>
                <select class="form-select" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" 
                                <?php echo $filtro_categoria == $categoria['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="tipo">
                    <option value="">Todos</option>
                    <option value="produtos" <?php echo $filtro_tipo === 'produtos' ? 'selected' : ''; ?>>Produtos</option>
                    <option value="servicos" <?php echo $filtro_tipo === 'servicos' ? 'selected' : ''; ?>>Serviços</option>
                    <option value="ambos" <?php echo $filtro_tipo === 'ambos' ? 'selected' : ''; ?>>Ambos</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <select class="form-select" name="ordenacao" onchange="this.form.submit()">
                            <option value="nome_asc" <?php echo $ordenacao == 'nome_asc' ? 'selected' : ''; ?>>Nome ↑</option>
                            <option value="nome_desc" <?php echo $ordenacao == 'nome_desc' ? 'selected' : ''; ?>>Nome ↓</option>
                            <option value="empresa_asc" <?php echo $ordenacao == 'empresa_asc' ? 'selected' : ''; ?>>Empresa ↑</option>
                            <option value="data_desc" <?php echo $ordenacao == 'data_desc' ? 'selected' : ''; ?>>Mais Recentes</option>
                            <option value="mais_pedidos" <?php echo $ordenacao == 'mais_pedidos' ? 'selected' : ''; ?>>Mais Pedidos</option>
                            <option value="maior_valor" <?php echo $ordenacao == 'maior_valor' ? 'selected' : ''; ?>>Maior Valor</option>
                            <option value="melhor_avaliacao" <?php echo $ordenacao == 'melhor_avaliacao' ? 'selected' : ''; ?>>Melhor Avaliação</option>
                        </select>
                        
                        <?php if (!empty($pesquisa) || !empty($filtro_categoria) || !empty($filtro_status) || !empty($filtro_tipo)): ?>
                            <a href="listar.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Toggle de Visualização -->
                    <div class="view-toggle btn-group">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['visualizacao' => 'tabela'])); ?>" 
                           class="btn <?php echo $visualizacao == 'tabela' ? 'active' : ''; ?>">
                            <i class="fas fa-table"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['visualizacao' => 'cards'])); ?>" 
                           class="btn <?php echo $visualizacao == 'cards' ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Resultados -->
<?php if ($result_fornecedores && $result_fornecedores->num_rows > 0): ?>
    <?php if ($visualizacao == 'cards'): ?>
        <!-- Visualização em Cards -->
        <div class="row">
            <?php while ($fornecedor = $result_fornecedores->fetch_assoc()): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card fornecedor-card slide-in">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="fornecedor-avatar" style="background: linear-gradient(135deg, <?php echo stringToColor($fornecedor['nome']); ?>, <?php echo stringToColor($fornecedor['nome'] . 'x'); ?>);">
                                    <?php echo strtoupper(substr($fornecedor['nome'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($fornecedor['nome']); ?></h5>
                                    
                                    <?php if (!empty($fornecedor['empresa'])): ?>
                                        <p class="text-muted mb-2"><?php echo htmlspecialchars($fornecedor['empresa']); ?></p>
                                    <?php endif; ?>
                                    
                                    <!-- Status e Tipo -->
                                    <div class="mb-2">
                                        <span class="badge-status <?php echo $fornecedor['status'] == 'ativo' ? 'badge-ativo' : 'badge-inativo'; ?>">
                                            <?php echo ucfirst($fornecedor['status']); ?>
                                        </span>
                                        <small class="text-muted ms-2"><?php echo ucfirst($fornecedor['tipo_fornecedor']); ?></small>
                                    </div>
                                    
                                    <!-- Avaliação -->
                                    <div class="avaliacao-stars mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $fornecedor['avaliacao'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                        <small class="text-muted ms-1">(<?php echo number_format($fornecedor['avaliacao'], 1); ?>)</small>
                                    </div>
                                    
                                    <!-- Contato -->
                                    <?php if (!empty($fornecedor['whatsapp'])): ?>
                                        <div class="mb-2">
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $fornecedor['whatsapp']); ?>" 
                                               target="_blank" class="whatsapp-link">
                                                <i class="fab fa-whatsapp me-1"></i>
                                                <?php echo htmlspecialchars($fornecedor['whatsapp']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Categorias -->
                                    <?php if (!empty($fornecedor['categorias'])): ?>
                                        <div class="mb-2">
                                            <?php 
                                            $cats = explode(', ', $fornecedor['categorias']);
                                            $cores = explode(',', $fornecedor['cores_categorias']);
                                            foreach ($cats as $index => $cat): 
                                                $cor = isset($cores[$index]) ? $cores[$index] : '#6c757d';
                                            ?>
                                                <span class="badge-categoria" style="background: <?php echo $cor; ?>20; color: <?php echo $cor; ?>; border: 1px solid <?php echo $cor; ?>;">
                                                    <?php echo htmlspecialchars($cat); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Métricas -->
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h6 class="text-primary mb-0"><?php echo $fornecedor['total_pedidos'] ?: '0'; ?></h6>
                                        <small class="text-muted">Pedidos</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h6 class="text-success mb-0">R$ <?php echo number_format($fornecedor['valor_total_comprado'], 0, ',', '.'); ?></h6>
                                        <small class="text-muted">Comprado</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h6 class="text-warning mb-0">R$ <?php echo number_format($fornecedor['valor_pedidos_abertos'], 0, ',', '.'); ?></h6>
                                    <small class="text-muted">Em Aberto</small>
                                </div>
                            </div>
                            
                            <!-- Ações Rápidas -->
                            <div class="quick-actions">
                                <a href="editar.php?id=<?php echo $fornecedor['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary btn-action" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="detalhes.php?id=<?php echo $fornecedor['id']; ?>" 
                                   class="btn btn-sm btn-outline-info btn-action" title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="novo_pedido.php?fornecedor_id=<?php echo $fornecedor['id']; ?>" 
                                   class="btn btn-sm btn-outline-success btn-action" title="Novo Pedido">
                                    <i class="fas fa-cart-plus"></i>
                                </a>
                                <?php if (!empty($fornecedor['whatsapp'])): ?>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $fornecedor['whatsapp']); ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-success btn-action" title="WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="pedidos.php?fornecedor_id=<?php echo $fornecedor['id']; ?>" 
                                   class="btn btn-sm btn-outline-secondary btn-action" title="Ver Pedidos">
                                    <i class="fas fa-shopping-cart"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- Visualização em Tabela -->
        <div class="card table-modern">
            <div class="card-header" style="background: var(--gradient-fornecedores); color: white;">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?php if (!empty($pesquisa)): ?>
                        Resultados para "<?php echo htmlspecialchars($pesquisa); ?>" 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php else: ?>
                        Lista de Fornecedores 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gradient-fornecedores); color: white;">
                        <tr>
                            <th>Fornecedor</th>
                            <th>Contato</th>
                            <th>Categorias</th>
                            <th class="text-center">Pedidos</th>
                            <th class="text-center">Valor Total</th>
                            <th class="text-center">Avaliação</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset result para tabela
                        $result_fornecedores = $conn->query($sql_fornecedores);
                        while ($fornecedor = $result_fornecedores->fetch_assoc()): 
                        ?>
                            <tr class="slide-in">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle" style="background: linear-gradient(135deg, <?php echo stringToColor($fornecedor['nome']); ?>, <?php echo stringToColor($fornecedor['nome'] . 'x'); ?>);">
                                            <?php echo strtoupper(substr($fornecedor['nome'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($fornecedor['nome']); ?></div>
                                            <?php if (!empty($fornecedor['empresa'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($fornecedor['empresa']); ?></small>
                                            <?php endif; ?>
                                            <div class="mt-1">
                                                <span class="badge-status <?php echo $fornecedor['status'] == 'ativo' ? 'badge-ativo' : 'badge-inativo'; ?>" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                                    <?php echo ucfirst($fornecedor['status']); ?>
                                                </span>
                                                <small class="text-muted ms-1"><?php echo ucfirst($fornecedor['tipo_fornecedor']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($fornecedor['whatsapp'])): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $fornecedor['whatsapp']); ?>" 
                                           target="_blank" class="whatsapp-link">
                                            <i class="fab fa-whatsapp me-1"></i>
                                            <?php echo htmlspecialchars($fornecedor['whatsapp']); ?>
                                        </a>
                                    <?php elseif (!empty($fornecedor['telefone'])): ?>
                                        <i class="fas fa-phone me-1 text-muted"></i>
                                        <?php echo htmlspecialchars($fornecedor['telefone']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($fornecedor['email'])): ?>
                                        <br><a href="mailto:<?php echo $fornecedor['email']; ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>
                                            <small><?php echo htmlspecialchars($fornecedor['email']); ?></small>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($fornecedor['categorias'])): ?>
                                        <?php 
                                        $cats = explode(', ', $fornecedor['categorias']);
                                        $cores = explode(',', $fornecedor['cores_categorias']);
                                        foreach ($cats as $index => $cat): 
                                            $cor = isset($cores[$index]) ? $cores[$index] : '#6c757d';
                                        ?>
                                            <span class="badge-categoria" style="background: <?php echo $cor; ?>20; color: <?php echo $cor; ?>; border: 1px solid <?php echo $cor; ?>; font-size: 0.7rem;">
                                                <?php echo htmlspecialchars($cat); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-primary"><?php echo $fornecedor['total_pedidos'] ?: '0'; ?></div>
                                    <?php if ($fornecedor['ultimo_pedido']): ?>
                                        <small class="text-muted">Último: <?php echo date('d/m/Y', strtotime($fornecedor['ultimo_pedido'])); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Sem pedidos</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-success">R$ <?php echo number_format($fornecedor['valor_total_comprado'], 2, ',', '.'); ?></div>
                                    <?php if ($fornecedor['valor_pedidos_abertos'] > 0): ?>
                                        <small class="text-warning">Em aberto: R$ <?php echo number_format($fornecedor['valor_pedidos_abertos'], 2, ',', '.'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="avaliacao-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $fornecedor['avaliacao'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($fornecedor['avaliacao'], 1); ?></small>
                                    
                                    <?php if ($fornecedor['total_entregas'] > 0): ?>
                                        <br><small class="text-info">
                                            <?php echo round(($fornecedor['entregas_no_prazo'] / $fornecedor['total_entregas']) * 100); ?>% no prazo
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="quick-actions">
                                        <a href="editar.php?id=<?php echo $fornecedor['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary btn-action" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="detalhes.php?id=<?php echo $fornecedor['id']; ?>" 
                                           class="btn btn-sm btn-outline-info btn-action" title="Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="novo_pedido.php?fornecedor_id=<?php echo $fornecedor['id']; ?>" 
                                           class="btn btn-sm btn-outline-success btn-action" title="Novo Pedido">
                                            <i class="fas fa-cart-plus"></i>
                                        </a>
                                        <?php if (!empty($fornecedor['whatsapp'])): ?>
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $fornecedor['whatsapp']); ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-success btn-action" title="WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="pedidos.php?fornecedor_id=<?php echo $fornecedor['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary btn-action" title="Ver Pedidos">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                        <button onclick="confirmarExclusao(<?php echo $fornecedor['id']; ?>, '<?php echo addslashes($fornecedor['nome']); ?>', <?php echo $fornecedor['total_pedidos'] ?: 0; ?>)" 
                                                class="btn btn-sm btn-outline-danger btn-action" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegação de fornecedores" class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    Mostrando <?php echo (($pagina_atual - 1) * $itens_por_pagina) + 1; ?> até 
                    <?php echo min($pagina_atual * $itens_por_pagina, $total_registros); ?> de 
                    <?php echo $total_registros; ?> fornecedores
                </small>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 me-2">Itens por página:</label>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="alterarPorPagina(this.value)">
                        <option value="20" <?php echo $itens_por_pagina == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $itens_por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $itens_por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
            </div>
            
            <ul class="pagination justify-content-center">
                <?php if ($pagina_atual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_atual - 1])); ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                $inicio = max(1, $pagina_atual - 2);
                $fim = min($total_paginas, $pagina_atual + 2);
                
                for ($i = $inicio; $i <= $fim; $i++):
                ?>
                    <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagina_atual < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_atual + 1])); ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
    
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-truck"></i>
        <h4>
            <?php if (!empty($pesquisa) || !empty($filtro_categoria) || !empty($filtro_status) || !empty($filtro_tipo)): ?>
                Nenhum fornecedor encontrado
            <?php else: ?>
                Nenhum fornecedor cadastrado
            <?php endif; ?>
        </h4>
        <p class="mb-4">
            <?php if (!empty($pesquisa) || !empty($filtro_categoria) || !empty($filtro_status) || !empty($filtro_tipo)): ?>
                Tente ajustar os filtros ou fazer uma nova pesquisa.
            <?php else: ?>
                Comece cadastrando seus primeiros fornecedores.
            <?php endif; ?>
        </p>
        <div class="d-flex gap-2 justify-content-center">
            <?php if (!empty($pesquisa) || !empty($filtro_categoria) || !empty($filtro_status) || !empty($filtro_tipo)): ?>
                <a href="listar.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i> Limpar Filtros
                </a>
            <?php endif; ?>
            <a href="cadastrar.php" class="btn btn-warning">
                <i class="fas fa-plus-circle me-2"></i> Cadastrar Primeiro Fornecedor
            </a>
        </div>
    </div>
<?php endif; ?>

<script>
// Função para gerar uma cor baseada no nome
function stringToColor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = hash % 360;
    return `hsl(${hue}, 70%, 50%)`;
}

function alterarPorPagina(valor) {
    const url = new URL(window.location);
    url.searchParams.set('por_pagina', valor);
    url.searchParams.set('pagina', '1');
    window.location.href = url.toString();
}

function confirmarExclusao(id, nome, totalPedidos) {
    const mensagemExtra = totalPedidos > 0 ? 
        `<br><br><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Este fornecedor possui ${totalPedidos} pedido(s) registrado(s). Todos os pedidos e dados associados serão excluídos também.</span>` : '';
    
    Swal.fire({
        title: 'Confirmar Exclusão',
        html: `Tem certeza que deseja excluir o fornecedor <strong>"${nome}"</strong>?${mensagemExtra}<br><br><span class="text-danger">Esta ação não pode ser desfeita!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt me-2"></i>Sim, excluir',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Excluindo...',
                text: 'Aguarde enquanto o fornecedor é excluído.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            window.location.href = `excluir.php?id=${id}`;
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
    // Ctrl/Cmd + K para focar na pesquisa
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('input[name="pesquisa"]')?.focus();
    }
    
    // Ctrl/Cmd + N para novo fornecedor
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        window.location.href = 'cadastrar.php';
    }
    
    // Ctrl/Cmd + G para categorias
    if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
        e.preventDefault();
        window.location.href = 'categorias.php';
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
        }, index * 50);
    });
    
    // Auto-submit do formulário de filtros com delay
    let searchTimeout;
    const searchInput = document.querySelector('input[name="pesquisa"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }
    
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mostrar dica inicial
    setTimeout(() => {
        if (<?php echo $stats['total_fornecedores']; ?> === 0) {
            showNotification('👋 Bem-vindo ao módulo de Fornecedores! Comece cadastrando seus fornecedores.', 'info');
        } else {
            showNotification('💡 Dica: Use Ctrl+K para pesquisar, Ctrl+N para novo fornecedor!', 'info');
        }
    }, 1500);
});

// Adicionar SweetAlert2 se não estiver incluído
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}

// Função para enviar mensagem WhatsApp personalizada
function enviarWhatsApp(telefone, nome) {
    const mensagem = `Olá ${nome}! Gostaríamos de fazer um novo pedido. Poderia nos ajudar?`;
    const url = `https://api.whatsapp.com/send?phone=${telefone}&text=${encodeURIComponent(mensagem)}`;
    window.open(url, '_blank');
}

// Função para avaliar fornecedor
function avaliarFornecedor(id, nomeAtual) {
    Swal.fire({
        title: `Avaliar ${nomeAtual}`,
        html: `
            <div class="text-start">
                <p>Como você avalia este fornecedor?</p>
                <div class="d-flex justify-content-center mb-3">
                    <div class="rating-stars" data-rating="5">
                        <i class="fas fa-star" data-value="1"></i>
                        <i class="fas fa-star" data-value="2"></i>
                        <i class="fas fa-star" data-value="3"></i>
                        <i class="fas fa-star" data-value="4"></i>
                        <i class="fas fa-star" data-value="5"></i>
                    </div>
                </div>
                <textarea class="form-control" id="obs-avaliacao" placeholder="Observações sobre o fornecedor (opcional)" rows="3"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar Avaliação',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            // Adicionar interatividade às estrelas
            const stars = document.querySelectorAll('.rating-stars i');
            const ratingDiv = document.querySelector('.rating-stars');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    const rating = index + 1;
                    ratingDiv.dataset.rating = rating;
                    
                    stars.forEach((s, i) => {
                        if (i < rating) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                            s.style.color = '#ffc107';
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                            s.style.color = '#dee2e6';
                        }
                    });
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const rating = document.querySelector('.rating-stars').dataset.rating;
            const observacao = document.getElementById('obs-avaliacao').value;
            
            // Aqui você implementaria a chamada AJAX para salvar a avaliação
            showNotification(`Avaliação de ${rating} estrelas salva com sucesso!`, 'success');
        }
    });
}
</script>

<!-- SweetAlert2 para confirmações -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Função para gerar uma cor baseada no nome (versão PHP)
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