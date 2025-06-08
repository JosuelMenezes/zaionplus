<?php
// clientes/listar.php - Versão Corrigida
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../config/database.php';

// Definir variáveis de paginação e filtros
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 20;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Obter parâmetros de pesquisa e ordenação
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'nome_asc';
$filtro_empresa = isset($_GET['empresa']) ? $_GET['empresa'] : '';
$filtro_devedor = isset($_GET['devedor']) ? $_GET['devedor'] : '';
$visualizacao = isset($_GET['visualizacao']) ? $_GET['visualizacao'] : 'tabela';

// Construir a cláusula WHERE para pesquisa
$where = "1=1";
if (!empty($pesquisa)) {
    $termo_pesquisa = $conn->real_escape_string("%{$pesquisa}%");
    $where .= " AND (c.nome LIKE '{$termo_pesquisa}' OR c.empresa LIKE '{$termo_pesquisa}' OR c.telefone LIKE '{$termo_pesquisa}')";
}

if (!empty($filtro_empresa)) {
    $empresa_filtro = $conn->real_escape_string($filtro_empresa);
    $where .= " AND c.empresa = '{$empresa_filtro}'";
}

// Construir a cláusula ORDER BY para ordenação
switch ($ordenacao) {
    case 'nome_desc':
        $order_by = "c.nome DESC";
        break;
    case 'empresa_asc':
        $order_by = "c.empresa ASC, c.nome ASC";
        break;
    case 'empresa_desc':
        $order_by = "c.empresa DESC, c.nome ASC";
        break;
    case 'data_desc':
        $order_by = "c.data_cadastro DESC";
        break;
    case 'maior_comprador':
        $order_by = "total_compras DESC";
        break;
    case 'maior_devedor':
        $order_by = "saldo_devedor DESC";
        break;
    case 'nome_asc':
    default:
        $order_by = "c.nome ASC";
        break;
}

// 🔧 CONSULTA CORRIGIDA para estatísticas gerais
$sql_stats = "SELECT 
    COUNT(*) as total_clientes,
    COUNT(CASE WHEN saldo.saldo_devedor > 0 THEN 1 END) as clientes_devedores,
    COALESCE(SUM(vendas.total_compras), 0) as total_vendas_geral,
    COALESCE(SUM(saldo.saldo_devedor), 0) as total_devedor_geral
    FROM clientes c
    LEFT JOIN (
        SELECT v.cliente_id, SUM(iv.quantidade * iv.valor_unitario) as total_compras
        FROM vendas v
        JOIN itens_venda iv ON v.id = iv.venda_id
        GROUP BY v.cliente_id
    ) vendas ON c.id = vendas.cliente_id
    LEFT JOIN (
        SELECT cliente_id,
            COALESCE(total_vendas, 0) - COALESCE(total_pagamentos, 0) as saldo_devedor
        FROM (
            SELECT v.cliente_id,
                SUM(iv.quantidade * iv.valor_unitario) as total_vendas,
                (SELECT COALESCE(SUM(p.valor), 0) 
                 FROM pagamentos p 
                 JOIN vendas v2 ON p.venda_id = v2.id 
                 WHERE v2.cliente_id = v.cliente_id) as total_pagamentos
            FROM vendas v
            JOIN itens_venda iv ON v.id = iv.venda_id
            GROUP BY v.cliente_id
        ) calc
    ) saldo ON c.id = saldo.cliente_id";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// Consulta para contar o total de registros (para paginação)
$sql_count = "SELECT COUNT(*) as total FROM clientes c WHERE {$where}";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_registros = $row_count['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// 🔧 CONSULTA PRINCIPAL CORRIGIDA para obter os clientes
$sql_clientes = "
    SELECT c.*,
        COALESCE(vendas.total_compras, 0) as total_compras,
        COALESCE(vendas.total_vendas, 0) as total_vendas,
        COALESCE(vendas.ultima_compra, NULL) as ultima_compra,
        COALESCE(saldo.saldo_devedor, 0) as saldo_devedor
    FROM clientes c
    LEFT JOIN (
        SELECT v.cliente_id, 
               SUM(iv.quantidade * iv.valor_unitario) as total_compras,
               COUNT(DISTINCT v.id) as total_vendas,
               MAX(v.data_venda) as ultima_compra
        FROM vendas v
        JOIN itens_venda iv ON v.id = iv.venda_id
        GROUP BY v.cliente_id
    ) vendas ON c.id = vendas.cliente_id
    LEFT JOIN (
        SELECT cliente_id,
            COALESCE(total_vendas, 0) - COALESCE(total_pagamentos, 0) as saldo_devedor
        FROM (
            SELECT v.cliente_id,
                SUM(iv.quantidade * iv.valor_unitario) as total_vendas,
                (SELECT COALESCE(SUM(p.valor), 0) 
                 FROM pagamentos p 
                 JOIN vendas v2 ON p.venda_id = v2.id 
                 WHERE v2.cliente_id = v.cliente_id) as total_pagamentos
            FROM vendas v
            JOIN itens_venda iv ON v.id = iv.venda_id
            GROUP BY v.cliente_id
        ) calc
    ) saldo ON c.id = saldo.cliente_id
    WHERE {$where}";

// Aplicar filtro de devedores se selecionado
if ($filtro_devedor === 'sim') {
    $sql_clientes .= " HAVING saldo_devedor > 0";
} elseif ($filtro_devedor === 'nao') {
    $sql_clientes .= " HAVING saldo_devedor <= 0 OR saldo_devedor IS NULL";
}

$sql_clientes .= " ORDER BY {$order_by} LIMIT {$offset}, {$itens_por_pagina}";

$result_clientes = $conn->query($sql_clientes);

// Buscar empresas distintas para filtro
$sql_empresas = "SELECT DISTINCT empresa FROM clientes WHERE empresa IS NOT NULL AND empresa != '' ORDER BY empresa";
$result_empresas = $conn->query($sql_empresas);
$empresas = [];
while ($row = $result_empresas->fetch_assoc()) {
    $empresas[] = $row['empresa'];
}

// Verificar se houve erro na consulta
if (!$result_clientes) {
    $_SESSION['msg'] = "Erro ao consultar clientes: " . $conn->error;
    $_SESSION['msg_type'] = "danger";
}

// Incluir o cabeçalho
include_once '../includes/header.php';
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

.filter-card {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
}

.client-card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.client-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.client-avatar {
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
}

.client-avatar::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.client-card:hover .client-avatar::before {
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
    background: rgba(102, 126, 234, 0.1);
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
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.badge-empresa {
    background: var(--gradient-info);
    color: white;
    border-radius: 20px;
    padding: 0.3rem 0.8rem;
    font-weight: 500;
}

.badge-devedor {
    background: var(--gradient-danger);
    color: white;
    border-radius: 15px;
    padding: 0.4rem 0.8rem;
    font-weight: 600;
}

.badge-sem-debito {
    background: var(--gradient-success);
    color: white;
    border-radius: 15px;
    padding: 0.4rem 0.8rem;
    font-weight: 600;
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
    background: var(--gradient-primary);
    color: white;
}

.pagination .page-link {
    border-radius: 10px;
    margin: 0 2px;
    border: none;
    color: #667eea;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.pagination .page-item.active .page-link {
    background: var(--gradient-primary);
    border: none;
}

.client-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.client-status.active {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.client-status.inactive {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.client-status.new {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.quick-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
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
            <i class="fas fa-users text-primary me-2"></i>
            Clientes
        </h1>
        <p class="text-muted mb-0">Gerencie seus clientes e relacionamentos</p>
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
                    <i class="fas fa-file-import me-2"></i> Importar Clientes
                </a></li>
            </ul>
        </div>
        <a href="cadastrar.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i> Novo Cliente
        </a>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['total_clientes']); ?></h3>
                <small>Total de Clientes</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-danger);">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['clientes_devedores']); ?></h3>
                <small>Com Débitos</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-success);">
            <div class="card-body text-center">
                <i class="fas fa-dollar-sign fa-2x mb-3"></i>
                <h3 class="mb-1">R$ <?php echo number_format($stats['total_vendas_geral'], 0, ',', '.'); ?></h3>
                <small>Total Faturado</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-warning);">
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-2x mb-3"></i>
                <h3 class="mb-1">R$ <?php echo number_format($stats['total_devedor_geral'], 0, ',', '.'); ?></h3>
                <small>Total a Receber</small>
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
                           placeholder="Nome, empresa ou telefone..." value="<?php echo htmlspecialchars($pesquisa); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Empresa</label>
                <select class="form-select" name="empresa">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?php echo htmlspecialchars($empresa); ?>" 
                                <?php echo $filtro_empresa === $empresa ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($empresa); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Situação</label>
                <select class="form-select" name="devedor">
                    <option value="">Todos</option>
                    <option value="sim" <?php echo $filtro_devedor === 'sim' ? 'selected' : ''; ?>>Com Débitos</option>
                    <option value="nao" <?php echo $filtro_devedor === 'nao' ? 'selected' : ''; ?>>Sem Débitos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ordenar por</label>
                <select class="form-select" name="ordenacao" onchange="this.form.submit()">
                    <option value="nome_asc" <?php echo $ordenacao == 'nome_asc' ? 'selected' : ''; ?>>Nome ↑</option>
                    <option value="nome_desc" <?php echo $ordenacao == 'nome_desc' ? 'selected' : ''; ?>>Nome ↓</option>
                    <option value="empresa_asc" <?php echo $ordenacao == 'empresa_asc' ? 'selected' : ''; ?>>Empresa ↑</option>
                    <option value="data_desc" <?php echo $ordenacao == 'data_desc' ? 'selected' : ''; ?>>Mais Recentes</option>
                    <option value="maior_comprador" <?php echo $ordenacao == 'maior_comprador' ? 'selected' : ''; ?>>Maior Comprador</option>
                    <option value="maior_devedor" <?php echo $ordenacao == 'maior_devedor' ? 'selected' : ''; ?>>Maior Devedor</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <?php if (!empty($pesquisa) || !empty($filtro_empresa) || !empty($filtro_devedor)): ?>
                            <a href="listar.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
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
<?php if ($result_clientes && $result_clientes->num_rows > 0): ?>
    <?php if ($visualizacao == 'cards'): ?>
        <!-- Visualização em Cards -->
        <div class="row">
            <?php while ($cliente = $result_clientes->fetch_assoc()): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card client-card slide-in">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="client-avatar" style="background: linear-gradient(135deg, <?php echo stringToColor($cliente['nome']); ?>, <?php echo stringToColor($cliente['nome'] . 'x'); ?>);">
                                    <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($cliente['nome']); ?></h5>
                                    
                                    <?php if (!empty($cliente['empresa'])): ?>
                                        <span class="badge-empresa mb-2"><?php echo htmlspecialchars($cliente['empresa']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($cliente['telefone'])): ?>
                                        <div class="mb-2">
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>" 
                                               target="_blank" class="whatsapp-link">
                                                <i class="fab fa-whatsapp me-1"></i>
                                                <?php echo htmlspecialchars($cliente['telefone']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Status do Cliente -->
                                    <?php
                                    $dias_desde_cadastro = floor((time() - strtotime($cliente['data_cadastro'])) / (60 * 60 * 24));
                                    if ($dias_desde_cadastro <= 7):
                                    ?>
                                        <div class="client-status new mb-2">
                                            <i class="fas fa-star"></i> NOVO CLIENTE
                                        </div>
                                    <?php elseif ($cliente['total_vendas'] > 0): ?>
                                        <div class="client-status active mb-2">
                                            <i class="fas fa-check-circle"></i> ATIVO
                                        </div>
                                    <?php else: ?>
                                        <div class="client-status inactive mb-2">
                                            <i class="fas fa-clock"></i> SEM COMPRAS
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Informações Financeiras -->
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h6 class="text-success mb-0">R$ <?php echo number_format($cliente['total_compras'], 0, ',', '.'); ?></h6>
                                        <small class="text-muted">Total Compras</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h6 class="text-info mb-0"><?php echo $cliente['total_vendas'] ?: '0'; ?></h6>
                                        <small class="text-muted">Pedidos</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h6 class="<?php echo $cliente['saldo_devedor'] > 0 ? 'text-danger' : 'text-success'; ?> mb-0">
                                        R$ <?php echo number_format($cliente['saldo_devedor'], 0, ',', '.'); ?>
                                    </h6>
                                    <small class="text-muted">Saldo</small>
                                </div>
                            </div>
                            
                            <!-- Ações Rápidas -->
                            <div class="quick-actions">
                                <a href="editar.php?id=<?php echo $cliente['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary btn-action" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="detalhes.php?id=<?php echo $cliente['id']; ?>" 
                                   class="btn btn-sm btn-outline-info btn-action" title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="../vendas/nova_venda.php?cliente_id=<?php echo $cliente['id']; ?>" 
                                   class="btn btn-sm btn-outline-success btn-action" title="Nova Venda">
                                    <i class="fas fa-cart-plus"></i>
                                </a>
                                <?php if ($cliente['saldo_devedor'] > 0): ?>
                                    <a href="registrar_pagamento.php?id=<?php echo $cliente['id']; ?>" 
                                       class="btn btn-sm btn-outline-warning btn-action" title="Registrar Pagamento">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="../vendas/listar.php?cliente_id=<?php echo $cliente['id']; ?>" 
                                   class="btn btn-sm btn-outline-secondary btn-action" title="Ver Vendas">
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
            <div class="card-header" style="background: var(--gradient-primary); color: white;">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?php if (!empty($pesquisa)): ?>
                        Resultados para "<?php echo htmlspecialchars($pesquisa); ?>" 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php else: ?>
                        Lista de Clientes 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gradient-primary); color: white;">
                        <tr>
                            <th>Cliente</th>
                            <th style="font-size: 0.9rem;">Contato</th>
                            <th style="font-size: 0.9rem;">Empresa</th>
                            <th class="text-center" style="font-size: 0.9rem;">Compras</th>
                            <th class="text-center" style="font-size: 0.9rem;">Saldo</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset result para tabela
                        $result_clientes = $conn->query($sql_clientes);
                        while ($cliente = $result_clientes->fetch_assoc()): 
                        ?>
                            <tr class="slide-in">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle" style="background: linear-gradient(135deg, <?php echo stringToColor($cliente['nome']); ?>, <?php echo stringToColor($cliente['nome'] . 'x'); ?>);">
                                            <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                                            <small class="text-muted">
                                                <?php if ($cliente['total_vendas'] > 0): ?>
                                                    <?php echo $cliente['total_vendas']; ?> pedido(s)
                                                    <?php if ($cliente['ultima_compra']): ?>
                                                        • Último: <?php echo date('d/m/Y', strtotime($cliente['ultima_compra'])); ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    Sem compras
                                                <?php endif; ?>
                                            </small>
                                            
                                            <!-- Status movido para baixo do nome (mais discreto) -->
                                            <?php
                                            $dias_desde_cadastro = floor((time() - strtotime($cliente['data_cadastro'])) / (60 * 60 * 24));
                                            if ($dias_desde_cadastro <= 7):
                                            ?>
                                                <div class="mt-1">
                                                    <span class="client-status new" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                                        <i class="fas fa-star"></i> NOVO
                                                    </span>
                                                </div>
                                            <?php elseif ($cliente['total_vendas'] == 0): ?>
                                                <div class="mt-1">
                                                    <span class="client-status inactive" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                                        <i class="fas fa-clock"></i> SEM COMPRAS
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size: 0.9rem;">
                                    <?php if (!empty($cliente['telefone'])): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>" 
                                           target="_blank" class="whatsapp-link">
                                            <i class="fab fa-whatsapp me-1"></i>
                                            <?php echo htmlspecialchars($cliente['telefone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.9rem;">
                                    <?php if (!empty($cliente['empresa'])): ?>
                                        <span class="badge-empresa" style="font-size: 0.8rem;"><?php echo htmlspecialchars($cliente['empresa']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" style="font-size: 0.9rem;">
                                    <div class="fw-bold text-success">R$ <?php echo number_format($cliente['total_compras'], 2, ',', '.'); ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?php echo $cliente['total_vendas']; ?> pedido(s)</small>
                                </td>
                                <td class="text-center" style="font-size: 0.9rem;">
                                    <?php if ($cliente['saldo_devedor'] > 0): ?>
                                        <span class="badge-devedor" style="font-size: 0.8rem;">R$ <?php echo number_format($cliente['saldo_devedor'], 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="badge-sem-debito" style="font-size: 0.8rem;">Em dia</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="quick-actions">
                                        <a href="editar.php?id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary btn-action" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="detalhes.php?id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-sm btn-outline-info btn-action" title="Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../vendas/nova_venda.php?cliente_id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-sm btn-outline-success btn-action" title="Nova Venda">
                                            <i class="fas fa-cart-plus"></i>
                                        </a>
                                        <?php if ($cliente['saldo_devedor'] > 0): ?>
                                            <a href="registrar_pagamento.php?id=<?php echo $cliente['id']; ?>" 
                                               class="btn btn-sm btn-outline-warning btn-action" title="Registrar Pagamento">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="../vendas/listar.php?cliente_id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary btn-action" title="Ver Vendas">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                        <button onclick="confirmarExclusao(<?php echo $cliente['id']; ?>, '<?php echo addslashes($cliente['nome']); ?>', <?php echo $cliente['total_vendas']; ?>)" 
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
    
    <!-- Paginação Melhorada -->
    <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegação de clientes" class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    Mostrando <?php echo (($pagina_atual - 1) * $itens_por_pagina) + 1; ?> até 
                    <?php echo min($pagina_atual * $itens_por_pagina, $total_registros); ?> de 
                    <?php echo $total_registros; ?> clientes
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
        <i class="fas fa-users"></i>
        <h4>
            <?php if (!empty($pesquisa) || !empty($filtro_empresa) || !empty($filtro_devedor)): ?>
                Nenhum cliente encontrado
            <?php else: ?>
                Nenhum cliente cadastrado
            <?php endif; ?>
        </h4>
        <p class="mb-4">
            <?php if (!empty($pesquisa) || !empty($filtro_empresa) || !empty($filtro_devedor)): ?>
                Tente ajustar os filtros ou fazer uma nova pesquisa.
            <?php else: ?>
                Comece cadastrando seus primeiros clientes.
            <?php endif; ?>
        </p>
        <div class="d-flex gap-2 justify-content-center">
            <?php if (!empty($pesquisa) || !empty($filtro_empresa) || !empty($filtro_devedor)): ?>
                <a href="listar.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i> Limpar Filtros
                </a>
            <?php endif; ?>
            <a href="cadastrar.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Cadastrar Primeiro Cliente
            </a>
        </div>
    </div>
<?php endif; ?>

<script>
// Função para gerar uma cor baseada no nome do cliente
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
    url.searchParams.set('pagina', '1'); // Resetar para primeira página
    window.location.href = url.toString();
}

function confirmarExclusao(id, nome, totalVendas) {
    const mensagemExtra = totalVendas > 0 ? 
        `<br><br><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Este cliente possui ${totalVendas} venda(s) registrada(s). Todas as vendas e pagamentos associados serão excluídos também.</span>` : '';
    
    Swal.fire({
        title: 'Confirmar Exclusão',
        html: `Tem certeza que deseja excluir o cliente <strong>"${nome}"</strong>?${mensagemExtra}<br><br><span class="text-danger">Esta ação não pode ser desfeita!</span>`,
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
            // Mostrar loading
            Swal.fire({
                title: 'Excluindo...',
                text: 'Aguarde enquanto o cliente é excluído.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirecionar para exclusão
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
    
    // Ctrl/Cmd + N para novo cliente
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        window.location.href = 'cadastrar.php';
    }
    
    // Ctrl/Cmd + I para importar
    if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
        e.preventDefault();
        window.location.href = 'importar.php';
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
    
    // Auto-submit do formulário de filtros com delay (REMOVIDO CACHE)
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
        if (<?php echo $stats['total_clientes']; ?> === 0) {
            showNotification('👋 Bem-vindo! Comece cadastrando seus primeiros clientes para gerenciar melhor seu negócio.', 'info');
        }
    }, 1000);
});

// Adicionar SweetAlert2 se não estiver incluído
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}
</script>

<!-- SweetAlert2 para confirmações -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Função para gerar uma cor baseada no nome do cliente (versão PHP)
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

// Incluir o rodapé
include_once '../includes/footer.php';
?>