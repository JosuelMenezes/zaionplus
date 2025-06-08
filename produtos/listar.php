<?php
// produtos/listar.php - Versão Melhorada
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

// Parâmetros de paginação e filtros
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$por_pagina = isset($_GET['por_pagina']) ? intval($_GET['por_pagina']) : 20;
$offset = ($pagina - 1) * $por_pagina;

$pesquisa = isset($_GET['pesquisa']) ? $conn->real_escape_string($_GET['pesquisa']) : '';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'nome_asc';
$valor_min = isset($_GET['valor_min']) ? floatval($_GET['valor_min']) : 0;
$valor_max = isset($_GET['valor_max']) ? floatval($_GET['valor_max']) : 0;
$visualizacao = isset($_GET['visualizacao']) ? $_GET['visualizacao'] : 'tabela';

// Construir a cláusula WHERE para pesquisa
$where = "1=1";
if (!empty($pesquisa)) {
    $where .= " AND (p.nome LIKE '%$pesquisa%' OR p.descricao LIKE '%$pesquisa%')";
}

if ($valor_min > 0) {
    $where .= " AND p.valor_venda >= $valor_min";
}

if ($valor_max > 0) {
    $where .= " AND p.valor_venda <= $valor_max";
}

// Construir a cláusula ORDER BY para ordenação
switch ($ordenacao) {
    case 'nome_desc':
        $order_by = "p.nome DESC";
        break;
    case 'valor_asc':
        $order_by = "p.valor_venda ASC";
        break;
    case 'valor_desc':
        $order_by = "p.valor_venda DESC";
        break;
    case 'mais_vendidos':
        $order_by = "vendidos DESC, p.nome ASC";
        break;
    case 'menos_vendidos':
        $order_by = "vendidos ASC, p.nome ASC";
        break;
    case 'data_desc':
        $order_by = "p.data_cadastro DESC";
        break;
    case 'nome_asc':
    default:
        $order_by = "p.nome ASC";
        break;
}

// Consulta para estatísticas
$sql_stats = "SELECT 
    COUNT(*) as total_produtos,
    AVG(valor_venda) as preco_medio,
    MAX(valor_venda) as preco_maximo,
    MIN(valor_venda) as preco_minimo,
    SUM(COALESCE((SELECT SUM(iv.quantidade) FROM itens_venda iv WHERE iv.produto_id = p.id), 0)) as total_vendido
    FROM produtos p";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// Consulta para contar o total de registros (para paginação)
$sql_count = "SELECT COUNT(*) as total FROM produtos p WHERE $where";
$result_count = $conn->query($sql_count);
$total_registros = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Consulta principal com paginação
$sql = "SELECT p.*, 
        COALESCE((SELECT SUM(iv.quantidade) FROM itens_venda iv WHERE iv.produto_id = p.id), 0) as vendidos,
        DATE(p.data_cadastro) as data_cadastro_formatada
        FROM produtos p
        WHERE $where
        ORDER BY $order_by
        LIMIT $offset, $por_pagina";
$result = $conn->query($sql);

// Verificar se há mensagem na sessão
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
    --shadow-soft: 0 2px 10px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 20px rgba(0,0,0,0.15);
}

.product-avatar {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 20px;
    background: var(--gradient-primary);
    box-shadow: var(--shadow-soft);
    transition: transform 0.3s ease;
}

.product-avatar:hover {
    transform: scale(1.1);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 15px;
    color: white;
    box-shadow: var(--shadow-soft);
    transition: transform 0.3s ease;
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

.product-card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.product-card-header {
    background: var(--gradient-info);
    padding: 1.5rem;
    border-radius: 15px 15px 0 0;
}

.badge-vendido {
    background: var(--gradient-success);
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

.badge-novo {
    background: var(--gradient-warning);
    border: none;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
}

.btn-gradient {
    background: var(--gradient-primary);
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1.5rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    color: white;
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
    transition: background-color 0.3s ease;
}

.table-modern tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.1);
}

.price-tag {
    background: var(--gradient-success);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-weight: bold;
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

.filter-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
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

.pagination .page-link {
    border-radius: 10px;
    margin: 0 2px;
    border: none;
    color: #667eea;
    padding: 0.75rem 1rem;
}

.pagination .page-item.active .page-link {
    background: var(--gradient-primary);
    border: none;
}
</style>

<!-- Cabeçalho com ações -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">
            <i class="fas fa-box text-primary me-2"></i>
            Produtos
        </h1>
        <p class="text-muted mb-0">Gerencie seu catálogo de produtos</p>
    </div>
    <div class="d-flex gap-2">
        <a href="cadastrar.php" class="btn btn-gradient">
            <i class="fas fa-plus-circle me-2"></i> Novo Produto
        </a>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-file-import me-2"></i> Importar
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="modelo_importacao.php">
                    <i class="fas fa-download me-2"></i> Baixar Modelo
                </a></li>
                <li><a class="dropdown-item" href="importar.php">
                    <i class="fas fa-file-import me-2"></i> Importar Produtos
                </a></li>
            </ul>
        </div>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-box fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['total_produtos']); ?></h3>
                <small>Total de Produtos</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-success);">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['total_vendido']); ?></h3>
                <small>Unidades Vendidas</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-info);">
            <div class="card-body text-center">
                <i class="fas fa-tags fa-2x mb-3"></i>
                <h3 class="mb-1">R$ <?php echo number_format($stats['preco_medio'], 2, ',', '.'); ?></h3>
                <small>Preço Médio</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-warning);">
            <div class="card-body text-center">
                <i class="fas fa-crown fa-2x mb-3"></i>
                <h3 class="mb-1">R$ <?php echo number_format($stats['preco_maximo'], 2, ',', '.'); ?></h3>
                <small>Maior Preço</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtros Avançados -->
<div class="filter-section">
    <form method="GET" action="">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Pesquisar</label>
                <div class="input-group">
                    <input type="text" class="form-control search-input" name="pesquisa" 
                           placeholder="Nome ou descrição..." value="<?php echo htmlspecialchars($pesquisa); ?>">
                    <button class="btn btn-gradient" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Valor Mínimo</label>
                <input type="number" class="form-control" name="valor_min" 
                       placeholder="0,00" step="0.01" value="<?php echo $valor_min > 0 ? $valor_min : ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Valor Máximo</label>
                <input type="number" class="form-control" name="valor_max" 
                       placeholder="0,00" step="0.01" value="<?php echo $valor_max > 0 ? $valor_max : ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ordenar por</label>
                <select class="form-select" name="ordenacao" onchange="this.form.submit()">
                    <option value="nome_asc" <?php echo $ordenacao == 'nome_asc' ? 'selected' : ''; ?>>Nome (A-Z)</option>
                    <option value="nome_desc" <?php echo $ordenacao == 'nome_desc' ? 'selected' : ''; ?>>Nome (Z-A)</option>
                    <option value="valor_asc" <?php echo $ordenacao == 'valor_asc' ? 'selected' : ''; ?>>Preço ↑</option>
                    <option value="valor_desc" <?php echo $ordenacao == 'valor_desc' ? 'selected' : ''; ?>>Preço ↓</option>
                    <option value="mais_vendidos" <?php echo $ordenacao == 'mais_vendidos' ? 'selected' : ''; ?>>Mais Vendidos</option>
                    <option value="data_desc" <?php echo $ordenacao == 'data_desc' ? 'selected' : ''; ?>>Mais Recentes</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <?php if (!empty($pesquisa) || $valor_min > 0 || $valor_max > 0): ?>
                        <a href="listar.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    <?php endif; ?>
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
<?php if ($result && $result->num_rows > 0): ?>
    <?php if ($visualizacao == 'cards'): ?>
        <!-- Visualização em Cards -->
        <div class="row">
            <?php while ($produto = $result->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="product-avatar me-3" style="background: linear-gradient(135deg, <?php echo sprintf('#%06X', crc32($produto['nome'])); ?>, <?php echo sprintf('#%06X', crc32($produto['nome']) + 100000); ?>);">
                                    <?php echo strtoupper(substr($produto['nome'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($produto['nome']); ?></h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($produto['descricao'] ?? ''); ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="price-tag">R$ <?php echo number_format($produto['valor_venda'], 2, ',', '.'); ?></span>
                                        <?php if ($produto['vendidos'] > 0): ?>
                                            <span class="badge badge-vendido"><?php echo $produto['vendidos']; ?> vendidos</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sem vendas</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php
                                    $data_cadastro = strtotime($produto['data_cadastro']);
                                    $dias_desde_cadastro = (time() - $data_cadastro) / (60 * 60 * 24);
                                    if ($dias_desde_cadastro <= 7):
                                    ?>
                                        <span class="badge badge-novo mb-2">NOVO</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="editar.php?id=<?php echo $produto['id']; ?>" class="btn btn-gradient flex-fill">
                                    <i class="fas fa-edit me-1"></i> Editar
                                </a>
                                <?php if ($produto['vendidos'] == 0): ?>
                                    <button onclick="confirmarExclusao(<?php echo $produto['id']; ?>, '<?php echo addslashes($produto['nome']); ?>')" 
                                            class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- Visualização em Tabela -->
        <div class="card table-modern">
            <div class="product-card-header">
                <h5 class="mb-0 text-white">
                    <i class="fas fa-list me-2"></i>
                    <?php if (!empty($pesquisa)): ?>
                        Resultados para "<?php echo htmlspecialchars($pesquisa); ?>" 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php else: ?>
                        Lista de Produtos 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Descrição</th>
                            <th class="text-center">Preço</th>
                            <th class="text-center">Vendidos</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset result para tabela
                        $result = $conn->query($sql);
                        while ($produto = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="product-avatar me-3" style="background: linear-gradient(135deg, <?php echo sprintf('#%06X', crc32($produto['nome'])); ?>, <?php echo sprintf('#%06X', crc32($produto['nome']) + 100000); ?>);">
                                            <?php echo strtoupper(substr($produto['nome'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($produto['nome']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo htmlspecialchars($produto['descricao'] ?? ''); ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="price-tag">R$ <?php echo number_format($produto['valor_venda'], 2, ',', '.'); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($produto['vendidos'] > 0): ?>
                                        <span class="badge badge-vendido"><?php echo $produto['vendidos']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $data_cadastro = strtotime($produto['data_cadastro']);
                                    $dias_desde_cadastro = (time() - $data_cadastro) / (60 * 60 * 24);
                                    if ($dias_desde_cadastro <= 7):
                                    ?>
                                        <span class="badge badge-novo">NOVO</span>
                                    <?php elseif ($produto['vendidos'] > 0): ?>
                                        <span class="badge bg-success">ATIVO</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">SEM VENDAS</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="editar.php?id=<?php echo $produto['id']; ?>" 
                                           class="btn btn-sm btn-gradient" data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($produto['vendidos'] == 0): ?>
                                            <button onclick="confirmarExclusao(<?php echo $produto['id']; ?>, '<?php echo addslashes($produto['nome']); ?>')" 
                                                    class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-secondary" disabled 
                                                    data-bs-toggle="tooltip" title="Produto com vendas não pode ser excluído">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
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
        <nav aria-label="Navegação de produtos" class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    Mostrando <?php echo (($pagina - 1) * $por_pagina) + 1; ?> até 
                    <?php echo min($pagina * $por_pagina, $total_registros); ?> de 
                    <?php echo $total_registros; ?> produtos
                </small>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 me-2">Itens por página:</label>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="alterarPorPagina(this.value)">
                        <option value="20" <?php echo $por_pagina == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
            </div>
            
            <ul class="pagination justify-content-center">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                $inicio = max(1, $pagina - 2);
                $fim = min($total_paginas, $pagina + 2);
                
                for ($i = $inicio; $i <= $fim; $i++):
                ?>
                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
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
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="fas fa-box-open fa-4x text-muted"></i>
        </div>
        <h4 class="text-muted">
            <?php if (!empty($pesquisa)): ?>
                Nenhum produto encontrado
            <?php else: ?>
                Nenhum produto cadastrado
            <?php endif; ?>
        </h4>
        <p class="text-muted mb-4">
            <?php if (!empty($pesquisa)): ?>
                Tente ajustar os filtros ou fazer uma nova pesquisa.
            <?php else: ?>
                Comece cadastrando seus primeiros produtos.
            <?php endif; ?>
        </p>
        <a href="cadastrar.php" class="btn btn-gradient">
            <i class="fas fa-plus-circle me-2"></i> Cadastrar Primeiro Produto
        </a>
    </div>
<?php endif; ?>

<script>
function confirmarExclusao(id, nome) {
    Swal.fire({
        title: 'Confirmar Exclusão',
        html: `Tem certeza que deseja excluir o produto <strong>"${nome}"</strong>?`,
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
                text: 'Aguarde enquanto o produto é excluído.',
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

function alterarPorPagina(valor) {
    const url = new URL(window.location);
    url.searchParams.set('por_pagina', valor);
    url.searchParams.set('pagina', '1'); // Resetar para primeira página
    window.location.href = url.toString();
}

// Inicializar tooltips e outras funcionalidades
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Animações de entrada
    const cards = document.querySelectorAll('.product-card, .stats-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
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
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K para focar na pesquisa
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput?.focus();
        }
        
        // Ctrl/Cmd + N para novo produto
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'cadastrar.php';
        }
    });
});

// Função para alternar visualização
function toggleView(view) {
    const url = new URL(window.location);
    url.searchParams.set('visualizacao', view);
    window.location.href = url.toString();
}

// Adicionar SweetAlert2 se não estiver incluído
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}
</script>

<!-- Adicionar SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<?php include '../includes/footer.php'; ?>