<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Definir a variável base_path para garantir que os links funcionem corretamente
$base_path = '../';

// Definir variáveis de paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20;
$offset = ($pagina_atual - 1) * $por_pagina;

// Obter filtros
$data_inicio = isset($_GET['data_inicio']) ? $conn->real_escape_string($_GET['data_inicio']) : date('Y-m-01'); // Primeiro dia do mês atual
$data_fim = isset($_GET['data_fim']) ? $conn->real_escape_string($_GET['data_fim']) : date('Y-m-t'); // Último dia do mês atual
$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$venda_id = isset($_GET['venda_id']) ? (int)$_GET['venda_id'] : 0;
$ordenacao = isset($_GET['ordenacao']) ? $conn->real_escape_string($_GET['ordenacao']) : 'data_desc';

// Construir a cláusula WHERE para filtros
$where = "1=1";
if (!empty($data_inicio)) {
    $where .= " AND DATE(p.data_pagamento) >= '$data_inicio'";
}
if (!empty($data_fim)) {
    $where .= " AND DATE(p.data_pagamento) <= '$data_fim'";
}
if ($cliente_id > 0) {
    $where .= " AND c.id = $cliente_id";
}
if ($venda_id > 0) {
    $where .= " AND v.id = $venda_id";
}

// Construir a cláusula ORDER BY
$order_by = "";
switch ($ordenacao) {
    case 'data_asc':
        $order_by = "p.data_pagamento ASC";
        break;
    case 'valor_desc':
        $order_by = "p.valor DESC";
        break;
    case 'valor_asc':
        $order_by = "p.valor ASC";
        break;
    case 'cliente_asc':
        $order_by = "c.nome ASC";
        break;
    case 'cliente_desc':
        $order_by = "c.nome DESC";
        break;
    case 'data_desc':
    default:
        $order_by = "p.data_pagamento DESC";
        break;
}

// Consulta para contar o total de registros (para paginação)
$sql_count = "SELECT COUNT(*) as total 
              FROM pagamentos p
              JOIN vendas v ON p.venda_id = v.id
              JOIN clientes c ON v.cliente_id = c.id
              WHERE $where";
$result_count = $conn->query($sql_count);
$total_registros = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Consulta principal com paginação
$sql = "SELECT p.*, v.id as venda_id, v.data_venda, c.id as cliente_id, c.nome as cliente_nome, c.empresa
        FROM pagamentos p
        JOIN vendas v ON p.venda_id = v.id
        JOIN clientes c ON v.cliente_id = c.id
        WHERE $where
        ORDER BY $order_by
        LIMIT $offset, $por_pagina";
$result = $conn->query($sql);

// Buscar todos os clientes para o filtro
$sql_clientes = "SELECT id, nome, empresa FROM clientes ORDER BY nome";
$result_clientes = $conn->query($sql_clientes);
$clientes = [];
while ($row = $result_clientes->fetch_assoc()) {
    $clientes[] = $row;
}

// Calcular o total de pagamentos no período
$sql_total = "SELECT SUM(p.valor) as total
              FROM pagamentos p
              JOIN vendas v ON p.venda_id = v.id
              JOIN clientes c ON v.cliente_id = c.id
              WHERE $where";
$result_total = $conn->query($sql_total);
$total_pagamentos = $result_total->fetch_assoc()['total'] ?? 0;

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Pagamentos</h1>
    <div>
        <a href="../dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
        </a>
    </div>
</div>

<?php if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])): ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="data_inicio" class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
            </div>
            <div class="col-md-3">
                <label for="data_fim" class="form-label">Data Final</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
            </div>
            <div class="col-md-4">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select class="form-select" id="cliente_id" name="cliente_id">
                    <option value="0">Todos os clientes</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo $cliente['id']; ?>" <?php echo ($cliente_id == $cliente['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['nome']); ?> 
                            <?php if (!empty($cliente['empresa'])): ?>
                                (<?php echo htmlspecialchars($cliente['empresa']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="ordenacao" class="form-label">Ordenar por</label>
                <select class="form-select" id="ordenacao" name="ordenacao">
                    <option value="data_desc" <?php echo ($ordenacao == 'data_desc') ? 'selected' : ''; ?>>Data (mais recente)</option>
                    <option value="data_asc" <?php echo ($ordenacao == 'data_asc') ? 'selected' : ''; ?>>Data (mais antiga)</option>
                    <option value="valor_desc" <?php echo ($ordenacao == 'valor_desc') ? 'selected' : ''; ?>>Valor (maior)</option>
                    <option value="valor_asc" <?php echo ($ordenacao == 'valor_asc') ? 'selected' : ''; ?>>Valor (menor)</option>
                    <option value="cliente_asc" <?php echo ($ordenacao == 'cliente_asc') ? 'selected' : ''; ?>>Cliente (A-Z)</option>
                    <option value="cliente_desc" <?php echo ($ordenacao == 'cliente_desc') ? 'selected' : ''; ?>>Cliente (Z-A)</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    <i class="fas fa-eraser"></i> Limpar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Resumo -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">
                    <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?>
                </p>
                <?php if ($cliente_id > 0): ?>
                    <?php 
                    $cliente_nome = "";
                    foreach ($clientes as $cliente) {
                        if ($cliente['id'] == $cliente_id) {
                            $cliente_nome = $cliente['nome'];
                            break;
                        }
                    }
                    ?>
                    <p class="mb-0"><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente_nome); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <h4 class="text-success mb-0">
                    Total Recebido: R$ <?php echo number_format($total_pagamentos, 2, ',', '.'); ?>
                </h4>
                <p class="text-muted mb-0">
                    <?php echo $total_registros; ?> pagamento(s) encontrado(s)
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Listagem de Pagamentos -->
<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="card-title mb-0">Pagamentos Registrados</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Venda</th>
                        <th>Data do Pagamento</th>
                        <th>Valor</th>
                        <th>Observação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows == 0): ?>
                    <tr>
                        <td colspan="7" class="text-center">Nenhum pagamento encontrado</td>
                    </tr>
                    <?php else: ?>
                        <?php while ($pagamento = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $pagamento['id']; ?></td>
                            <td>
                                <a href="../clientes/detalhes.php?id=<?php echo $pagamento['cliente_id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($pagamento['cliente_nome']); ?>
                                </a>
                                <?php if (!empty($pagamento['empresa'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($pagamento['empresa']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../vendas/detalhes.php?id=<?php echo $pagamento['venda_id']; ?>" class="text-decoration-none">
                                    #<?php echo $pagamento['venda_id']; ?>
                                </a>
                                <br>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($pagamento['data_venda'])); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])); ?></td>
                            <td class="text-success fw-bold">R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
                            <td>
                                <?php if (!empty($pagamento['observacao'])): ?>
                                    <?php echo htmlspecialchars($pagamento['observacao']); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../vendas/detalhes.php?id=<?php echo $pagamento['venda_id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver Venda">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
                                <a href="excluir.php?id=<?php echo $pagamento['id']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Excluir Pagamento" onclick="return confirm('Tem certeza que deseja excluir este pagamento?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
    <div class="card-footer">
        <nav aria-label="Navegação de páginas">
            <ul class="pagination justify-content-center mb-0">
                <?php if ($pagina_atual > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=1&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente_id=<?php echo $cliente_id; ?>&ordenacao=<?php echo $ordenacao; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $pagina_atual - 1; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente_id=<?php echo $cliente_id; ?>&ordenacao=<?php echo $ordenacao; ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                $inicio = max(1, $pagina_atual - 2);
                $fim = min($total_paginas, $pagina_atual + 2);
                
                if ($inicio > 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                
                for ($i = $inicio; $i <= $fim; $i++):
                ?>
                <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente_id=<?php echo $cliente_id; ?>&ordenacao=<?php echo $ordenacao; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($fim < $total_paginas): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                
                <?php if ($pagina_atual < $total_paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $pagina_atual + 1; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente_id=<?php echo $cliente_id; ?>&ordenacao=<?php echo $ordenacao; ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>&cliente_id=<?php echo $cliente_id; ?>&ordenacao=<?php echo $ordenacao; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<?php include '../includes/footer.php'; ?>