<?php
// funcionarios/listar.php - Sistema Premium de Funcionários
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

// Função para gerar uma cor baseada no nome do funcionário (versão PHP)
function stringToColor($str) {
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ord($str[$i]) + (($hash << 5) - $hash);
    }
    $hue = abs($hash) % 360;
    return "hsl($hue, 70%, 50%)";
}

// Definir variáveis de paginação e filtros
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itens_por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 20;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Obter parâmetros de pesquisa e ordenação
$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'nome_asc';
$filtro_departamento = isset($_GET['departamento']) ? $_GET['departamento'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$visualizacao = isset($_GET['visualizacao']) ? $_GET['visualizacao'] : 'cards';

// Construir a cláusula WHERE para pesquisa
$where = "1=1";
$params = [];

if (!empty($pesquisa)) {
    $where .= " AND (f.nome LIKE ? OR f.codigo LIKE ? OR f.cargo LIKE ? OR f.email LIKE ?)";
    $termo_pesquisa = "%{$pesquisa}%";
    $params = array_merge($params, [$termo_pesquisa, $termo_pesquisa, $termo_pesquisa, $termo_pesquisa]);
}

if (!empty($filtro_departamento)) {
    $where .= " AND f.departamento = ?";
    $params[] = $filtro_departamento;
}

if (!empty($filtro_status)) {
    $where .= " AND f.status = ?";
    $params[] = $filtro_status;
}

// Construir a cláusula ORDER BY para ordenação
switch ($ordenacao) {
    case 'nome_desc':
        $order_by = "f.nome DESC";
        break;
    case 'departamento_asc':
        $order_by = "f.departamento ASC, f.nome ASC";
        break;
    case 'cargo_asc':
        $order_by = "f.cargo ASC, f.nome ASC";
        break;
    case 'data_desc':
        $order_by = "f.data_admissao DESC";
        break;
    case 'nome_asc':
    default:
        $order_by = "f.nome ASC";
        break;
}

// Consulta para estatísticas gerais usando prepared statements
try {
    $sql_stats = "SELECT 
        COUNT(*) as total_funcionarios,
        COUNT(CASE WHEN f.status = 'ativo' THEN 1 END) as funcionarios_ativos,
        COUNT(CASE WHEN f.status = 'inativo' THEN 1 END) as funcionarios_inativos,
        COUNT(CASE WHEN f.status = 'ferias' THEN 1 END) as funcionarios_ferias,
        COUNT(CASE WHEN f.status = 'licenca' THEN 1 END) as funcionarios_licenca
        FROM funcionarios f";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->execute();
    $result_stats = $stmt_stats->get_result();
    $stats = $result_stats->fetch_assoc();
    $stmt_stats->close();
    
    if (!$stats) {
        $stats = [
            'total_funcionarios' => 0,
            'funcionarios_ativos' => 0,
            'funcionarios_inativos' => 0,
            'funcionarios_ferias' => 0,
            'funcionarios_licenca' => 0
        ];
    }

    // Consulta para contar o total de registros (para paginação)
    $sql_count = "SELECT COUNT(*) as total FROM funcionarios f WHERE {$where}";
    $stmt_count = $conn->prepare($sql_count);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt_count->bind_param($types, ...$params);
    }
    
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    $total_registros = $row_count ? $row_count['total'] : 0;
    $total_paginas = ceil($total_registros / $itens_por_pagina);
    $stmt_count->close();

    // Consulta principal para obter os funcionários
    $sql_funcionarios = "
        SELECT f.*
        FROM funcionarios f
        WHERE {$where}
        ORDER BY {$order_by} 
        LIMIT ?, ?";

    $stmt_funcionarios = $conn->prepare($sql_funcionarios);
    
    // Adicionar parâmetros de LIMIT
    $limit_params = array_merge($params, [$offset, $itens_por_pagina]);
    $types = str_repeat('s', count($params)) . 'ii';
    
    if (!empty($limit_params)) {
        $stmt_funcionarios->bind_param($types, ...$limit_params);
    }
    
    $stmt_funcionarios->execute();
    $result_funcionarios = $stmt_funcionarios->get_result();

    // Buscar departamentos distintos para filtro
    $sql_departamentos = "SELECT DISTINCT departamento FROM funcionarios WHERE departamento IS NOT NULL AND departamento != '' ORDER BY departamento";
    $stmt_departamentos = $conn->prepare($sql_departamentos);
    $stmt_departamentos->execute();
    $result_departamentos = $stmt_departamentos->get_result();
    
    $departamentos = [];
    while ($row = $result_departamentos->fetch_assoc()) {
        $departamentos[] = $row['departamento'];
    }
    $stmt_departamentos->close();

} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao consultar funcionários: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
    
    // Valores padrão em caso de erro
    $stats = [
        'total_funcionarios' => 0,
        'funcionarios_ativos' => 0,
        'funcionarios_inativos' => 0,
        'funcionarios_ferias' => 0,
        'funcionarios_licenca' => 0
    ];
    $total_registros = 0;
    $total_paginas = 1;
    $departamentos = [];
    $result_funcionarios = null;
}

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<style>
:root {
    --gradient-funcionarios: linear-gradient(135deg, #7B68EE 0%, #9370DB 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-danger: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
}

.stats-card {
    background: var(--gradient-funcionarios);
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

.funcionario-card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.funcionario-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.funcionario-avatar {
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
    background: var(--gradient-funcionarios);
    color: white;
}

.table-modern tbody tr {
    border: none;
    transition: all 0.3s ease;
}

.table-modern tbody tr:hover {
    background: rgba(123, 104, 238, 0.1);
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
    border-color: #7B68EE;
    box-shadow: 0 0 0 0.2rem rgba(123, 104, 238, 0.25);
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
    color: white;
    text-transform: uppercase;
}

.status-badge.ativo { background: var(--gradient-success); }
.status-badge.inativo { background: var(--gradient-danger); }
.status-badge.ferias { background: var(--gradient-warning); }
.status-badge.licenca { background: var(--gradient-info); }

.department-tag {
    background: var(--gradient-info);
    color: white;
    border-radius: 20px;
    padding: 0.3rem 0.8rem;
    font-weight: 500;
    font-size: 0.8rem;
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
    background: var(--gradient-funcionarios);
    color: white;
}

.pagination .page-link {
    border-radius: 10px;
    margin: 0 2px;
    border: none;
    color: #7B68EE;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.pagination .page-item.active .page-link {
    background: var(--gradient-funcionarios);
    border: none;
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
            <i class="fas fa-id-badge text-primary me-2"></i>
            Funcionários
        </h1>
        <p class="text-muted mb-0">Gerencie sua equipe e recursos humanos</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="exportarFuncionarios()">
            <i class="fas fa-download me-2"></i>Exportar
        </button>
        <button class="btn btn-outline-secondary" onclick="importarFuncionarios()">
            <i class="fas fa-upload me-2"></i>Importar
        </button>
        <a href="cadastrar.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Novo Funcionário
        </a>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['total_funcionarios']); ?></h3>
                <small>Total de Funcionários</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-success);">
            <div class="card-body text-center">
                <i class="fas fa-user-check fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['funcionarios_ativos']); ?></h3>
                <small>Funcionários Ativos</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-warning);">
            <div class="card-body text-center">
                <i class="fas fa-umbrella-beach fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['funcionarios_ferias']); ?></h3>
                <small>Em Férias</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card" style="background: var(--gradient-danger);">
            <div class="card-body text-center">
                <i class="fas fa-user-times fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo number_format($stats['funcionarios_inativos']); ?></h3>
                <small>Inativos</small>
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
                           placeholder="Nome, código, cargo..." value="<?php echo htmlspecialchars($pesquisa); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Departamento</label>
                <select class="form-select" name="departamento">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $departamento): ?>
                        <option value="<?php echo htmlspecialchars($departamento); ?>" 
                                <?php echo $filtro_departamento === $departamento ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($departamento); ?>
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
                    <option value="ferias" <?php echo $filtro_status === 'ferias' ? 'selected' : ''; ?>>Férias</option>
                    <option value="licenca" <?php echo $filtro_status === 'licenca' ? 'selected' : ''; ?>>Licença</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ordenar por</label>
                <select class="form-select" name="ordenacao" onchange="this.form.submit()">
                    <option value="nome_asc" <?php echo $ordenacao == 'nome_asc' ? 'selected' : ''; ?>>Nome ↑</option>
                    <option value="nome_desc" <?php echo $ordenacao == 'nome_desc' ? 'selected' : ''; ?>>Nome ↓</option>
                    <option value="departamento_asc" <?php echo $ordenacao == 'departamento_asc' ? 'selected' : ''; ?>>Departamento ↑</option>
                    <option value="cargo_asc" <?php echo $ordenacao == 'cargo_asc' ? 'selected' : ''; ?>>Cargo ↑</option>
                    <option value="data_desc" <?php echo $ordenacao == 'data_desc' ? 'selected' : ''; ?>>Mais Recentes</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <?php if (!empty($pesquisa) || !empty($filtro_departamento) || !empty($filtro_status)): ?>
                            <a href="listar.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                    
                    <!-- Toggle de Visualização -->
                    <div class="view-toggle btn-group">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['visualizacao' => 'cards'])); ?>" 
                           class="btn <?php echo $visualizacao == 'cards' ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['visualizacao' => 'tabela'])); ?>" 
                           class="btn <?php echo $visualizacao == 'tabela' ? 'active' : ''; ?>">
                            <i class="fas fa-table"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Resultados -->
<?php if ($result_funcionarios && $result_funcionarios->num_rows > 0): ?>
    <?php if ($visualizacao == 'cards'): ?>
        <!-- Visualização em Cards -->
        <div class="row">
            <?php while ($funcionario = $result_funcionarios->fetch_assoc()): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card funcionario-card slide-in">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="funcionario-avatar" style="background: <?php echo stringToColor($funcionario['nome']); ?>;">
                                    <?php echo strtoupper(substr($funcionario['nome'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($funcionario['nome']); ?></h5>
                                    
                                    <?php if (!empty($funcionario['departamento'])): ?>
                                        <span class="department-tag mb-2"><?php echo htmlspecialchars($funcionario['departamento']); ?></span>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted"><?php echo htmlspecialchars($funcionario['cargo'] ?? 'Não informado'); ?></small>
                                    </div>
                                    
                                    <?php if (!empty($funcionario['telefone'])): ?>
                                        <div class="mb-2">
                                            <small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($funcionario['telefone']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <span class="status-badge <?php echo $funcionario['status'] ?? 'ativo'; ?>"><?php echo ucfirst($funcionario['status'] ?? 'Ativo'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informações Profissionais -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h6 class="text-info mb-0"><?php echo htmlspecialchars($funcionario['codigo'] ?? 'N/A'); ?></h6>
                                        <small class="text-muted">Código</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-success mb-0">
                                        <?php 
                                        if (!empty($funcionario['data_admissao'])) {
                                            echo date('d/m/Y', strtotime($funcionario['data_admissao'])); 
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </h6>
                                    <small class="text-muted">Admissão</small>
                                </div>
                            </div>
                            
                            <!-- Ações Rápidas -->
                            <div class="quick-actions">
                                <a href="editar.php?id=<?php echo $funcionario['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary btn-action" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="visualizar.php?id=<?php echo $funcionario['id']; ?>" 
                                   class="btn btn-sm btn-outline-info btn-action" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="../ponto/consultar.php?funcionario_id=<?php echo $funcionario['id']; ?>" 
                                   class="btn btn-sm btn-outline-success btn-action" title="Ver Ponto">
                                    <i class="fas fa-clock"></i>
                                </a>
                                <button onclick="confirmarInativacao(<?php echo $funcionario['id']; ?>, '<?php echo addslashes($funcionario['nome']); ?>')" 
                                        class="btn btn-sm btn-outline-danger btn-action" title="Inativar">
                                    <i class="fas fa-user-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- Visualização em Tabela -->
        <div class="card table-modern">
            <div class="card-header" style="background: var(--gradient-funcionarios); color: white;">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?php if (!empty($pesquisa)): ?>
                        Resultados para "<?php echo htmlspecialchars($pesquisa); ?>" 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php else: ?>
                        Lista de Funcionários 
                        <span class="badge bg-light text-dark"><?php echo $total_registros; ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gradient-funcionarios); color: white;">
                        <tr>
                            <th>Funcionário</th>
                            <th style="font-size: 0.9rem;">Cargo</th>
                            <th style="font-size: 0.9rem;">Departamento</th>
                            <th style="font-size: 0.9rem;">Status</th>
                            <th style="font-size: 0.9rem;">Contato</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Para tabela, precisamos re-executar a query
                        $stmt_funcionarios->execute();
                        $result_funcionarios_table = $stmt_funcionarios->get_result();
                        
                        while ($funcionario = $result_funcionarios_table->fetch_assoc()): 
                        ?>
                            <tr class="slide-in">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle" style="background: <?php echo stringToColor($funcionario['nome']); ?>;">
                                            <?php echo strtoupper(substr($funcionario['nome'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($funcionario['nome']); ?></div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($funcionario['codigo'] ?? 'N/A'); ?> • 
                                                Desde <?php echo !empty($funcionario['data_admissao']) ? date('m/Y', strtotime($funcionario['data_admissao'])) : 'N/A'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size: 0.9rem;"><?php echo htmlspecialchars($funcionario['cargo'] ?? 'Não informado'); ?></td>
                                <td style="font-size: 0.9rem;">
                                    <?php if (!empty($funcionario['departamento'])): ?>
                                        <span class="department-tag"><?php echo htmlspecialchars($funcionario['departamento']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.9rem;">
                                    <span class="status-badge <?php echo $funcionario['status'] ?? 'ativo'; ?>"><?php echo ucfirst($funcionario['status'] ?? 'Ativo'); ?></span>
                                </td>
                                <td style="font-size: 0.9rem;">
                                    <?php if (!empty($funcionario['telefone'])): ?>
                                        <div><small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($funcionario['telefone']); ?></small></div>
                                    <?php endif; ?>
                                    <?php if (!empty($funcionario['email'])): ?>
                                        <div><small><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($funcionario['email']); ?></small></div>
                                    <?php endif; ?>
                                    <?php if (empty($funcionario['telefone']) && empty($funcionario['email'])): ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="quick-actions">
                                        <a href="editar.php?id=<?php echo $funcionario['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary btn-action" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="visualizar.php?id=<?php echo $funcionario['id']; ?>" 
                                           class="btn btn-sm btn-outline-info btn-action" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../ponto/consultar.php?funcionario_id=<?php echo $funcionario['id']; ?>" 
                                           class="btn btn-sm btn-outline-success btn-action" title="Ver Ponto">
                                            <i class="fas fa-clock"></i>
                                        </a>
                                        <button onclick="confirmarInativacao(<?php echo $funcionario['id']; ?>, '<?php echo addslashes($funcionario['nome']); ?>')" 
                                                class="btn btn-sm btn-outline-danger btn-action" title="Inativar">
                                            <i class="fas fa-user-times"></i>
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
        <nav aria-label="Navegação de funcionários" class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    Mostrando <?php echo (($pagina_atual - 1) * $itens_por_pagina) + 1; ?> até 
                    <?php echo min($pagina_atual * $itens_por_pagina, $total_registros); ?> de 
                    <?php echo $total_registros; ?> funcionários
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
            <?php if (!empty($pesquisa) || !empty($filtro_departamento) || !empty($filtro_status)): ?>
                Nenhum funcionário encontrado
            <?php else: ?>
                Nenhum funcionário cadastrado
            <?php endif; ?>
        </h4>
        <p class="mb-4">
            <?php if (!empty($pesquisa) || !empty($filtro_departamento) || !empty($filtro_status)): ?>
                Tente ajustar os filtros ou fazer uma nova pesquisa.
            <?php else: ?>
                Comece cadastrando seus primeiros funcionários.
            <?php endif; ?>
        </p>
        <div class="d-flex gap-2 justify-content-center">
            <?php if (!empty($pesquisa) || !empty($filtro_departamento) || !empty($filtro_status)): ?>
                <a href="listar.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i> Limpar Filtros
                </a>
            <?php endif; ?>
            <a href="cadastrar.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Cadastrar Primeiro Funcionário
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de Loading -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mb-0">Processando...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Incluir SweetAlert2 se não estiver disponível
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}

function alterarPorPagina(valor) {
    const url = new URL(window.location);
    url.searchParams.set('por_pagina', valor);
    url.searchParams.set('pagina', '1'); // Resetar para primeira página
    window.location.href = url.toString();
}

function confirmarInativacao(id, nome) {
    // Verificar se SweetAlert2 está carregado
    if (typeof Swal === 'undefined') {
        if (confirm(`Tem certeza que deseja inativar o funcionário "${nome}"?`)) {
            window.location.href = `inativar.php?id=${id}`;
        }
        return;
    }
    
    Swal.fire({
        title: 'Confirmar Inativação',
        html: `Tem certeza que deseja inativar o funcionário <strong>"${nome}"</strong>?<br><br><span class="text-danger">Esta ação pode ser revertida posteriormente.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-user-times me-2"></i>Sim, inativar',
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
                title: 'Processando...',
                text: 'Aguarde enquanto o funcionário é inativado.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirecionar para inativação
            window.location.href = `inativar.php?id=${id}`;
        }
    });
}

// Funções de exportação/importação
function exportarFuncionarios() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Exportação Iniciada!',
            text: 'O download será iniciado em breve.',
            timer: 2000,
            showConfirmButton: false
        });
    }
    
    // Implementar exportação
    window.location.href = 'exportar.php';
}

function importarFuncionarios() {
    if (typeof Swal === 'undefined') {
        const arquivo = prompt('Funcionalidade de importação será implementada em breve.');
        return;
    }
    
    Swal.fire({
        title: 'Importar Funcionários',
        html: `
            <div class="mb-3">
                <label class="form-label">Selecione o arquivo CSV/Excel:</label>
                <input type="file" class="form-control" accept=".csv,.xlsx,.xls" id="arquivoImport">
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Formatos aceitos: CSV, Excel (.xlsx, .xls)
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Importar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7B68EE',
        preConfirm: () => {
            const arquivo = document.getElementById('arquivoImport').files[0];
            if (!arquivo) {
                Swal.showValidationMessage('Por favor, selecione um arquivo');
                return false;
            }
            return arquivo;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Implementar upload do arquivo
            window.location.href = 'importar.php';
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
        const searchInput = document.querySelector('input[name="pesquisa"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Ctrl/Cmd + N para novo funcionário
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        window.location.href = 'cadastrar.php';
    }
});

// Auto-submit do formulário de filtros com delay
let searchTimeout;
function setupAutoSubmit() {
    const searchInput = document.querySelector('input[name="pesquisa"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 800); // Aumentado para 800ms para melhor UX
        });
    }
}

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
    
    // Setup auto-submit
    setupAutoSubmit();
    
    // Tooltips do Bootstrap
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Mostrar mensagem de boas-vindas se não houver funcionários
    setTimeout(() => {
        const totalFuncionarios = <?php echo $stats['total_funcionarios']; ?>;
        if (totalFuncionarios === 0) {
            showNotification('👋 Bem-vindo! Comece cadastrando seus primeiros funcionários para gerenciar melhor sua equipe.', 'info');
        }
    }, 1000);
    
    // Verificar se há mensagens de sessão para exibir
    <?php if (isset($_SESSION['msg'])): ?>
        showNotification('<?php echo addslashes($_SESSION['msg']); ?>', '<?php echo $_SESSION['msg_type'] ?? 'info'; ?>');
        <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
    <?php endif; ?>
});

// Função para recarregar a página com novos parâmetros
function updateUrl(params) {
    const url = new URL(window.location);
    Object.keys(params).forEach(key => {
        if (params[key]) {
            url.searchParams.set(key, params[key]);
        } else {
            url.searchParams.delete(key);
        }
    });
    window.location.href = url.toString();
}

// Adicionar loading nos formulários
document.addEventListener('submit', function(e) {
    if (e.target.tagName === 'FORM') {
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Carregando...';
        }
    }
});
</script>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Fechar statements se existirem
if (isset($stmt_funcionarios)) {
    $stmt_funcionarios->close();
}
if (isset($stmt_count)) {
    $stmt_count->close();
}

// Incluir o rodapé
include_once '../includes/footer.php';
?>