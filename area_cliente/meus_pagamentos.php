<?php
// area_cliente/meus_pagamentos.php
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e é um cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] != 'cliente' || !isset($_SESSION['cliente_id'])) {
    $_SESSION['msg'] = "Acesso restrito";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// Parâmetros de paginação e filtros
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$data_inicio = isset($_GET['data_inicio']) ? $conn->real_escape_string($_GET['data_inicio']) : '';
$data_fim = isset($_GET['data_fim']) ? $conn->real_escape_string($_GET['data_fim']) : '';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'data_desc';

// Construir a cláusula WHERE para filtros
$where = "v.cliente_id = $cliente_id";
if (!empty($data_inicio)) {
    $where .= " AND DATE(p.data_pagamento) >= '$data_inicio'";
}
if (!empty($data_fim)) {
    $where .= " AND DATE(p.data_pagamento) <= '$data_fim'";
}

// Construir a cláusula ORDER BY para ordenação
switch ($ordenacao) {
    case 'data_asc':
        $order_by = "p.data_pagamento ASC";
        break;
    case 'valor_asc':
        $order_by = "p.valor ASC";
        break;
    case 'valor_desc':
        $order_by = "p.valor DESC";
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
              WHERE $where";
$result_count = $conn->query($sql_count);
$total_registros = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Consulta principal com paginação
$sql = "SELECT p.*, v.data_venda, v.id as venda_id, v.status as venda_status
        FROM pagamentos p
        JOIN vendas v ON p.venda_id = v.id
        WHERE $where
        ORDER BY $order_by
        LIMIT $offset, $por_pagina";
$result = $conn->query($sql);

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Meus Pagamentos</h1>
    <a href="dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar ao Painel
    </a>
</div>

<?php if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])): ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-5">
                <label for="data_inicio" class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
            </div>
            
            <div class="col-md-5">
                <label for="data_fim" class="form-label">Data Final</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
            </div>
            
            <div class="col-md-2">
                <label for="ordenacao" class="form-label">Ordenar por</label>
                <select class="form-select" id="ordenacao" name="ordenacao">
                    <option value="data_desc" <?php echo $ordenacao == 'data_desc' ? 'selected' : ''; ?>>Data (mais recente)</option>
                    <option value="data_asc" <?php echo $ordenacao == 'data_asc' ? 'selected' : ''; ?>>Data (mais antiga)</option>
                    <option value="valor_desc" <?php echo $ordenacao == 'valor_desc' ? 'selected' : ''; ?>>Valor (maior)</option>
                    <option value="valor_asc" <?php echo $ordenacao == 'valor_asc' ? 'selected' : ''; ?>>Valor (menor)</option>
                </select>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="meus_pagamentos.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Histórico de Pagamentos
            <span class="badge bg-light text-dark"><?php echo $total_registros; ?> pagamento(s)</span>
        </h5>
        
        <?php if (!empty($data_inicio) || !empty($data_fim)): ?>
            <span class="badge bg-warning text-dark">
                Filtros aplicados
                <?php if (!empty($data_inicio)): ?>
                    | De: <?php echo date('d/m/Y', strtotime($data_inicio)); ?>
                <?php endif; ?>
                <?php if (!empty($data_fim)): ?>
                    | Até: <?php echo date('d/m/Y', strtotime($data_fim)); ?>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Venda</th>
                            <th class="text-end">Valor</th>
                            <th>Observação</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pagamento = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td>
                                <td>
                                    <a href="venda_detalhes.php?id=<?php echo $pagamento['venda_id']; ?>">
                                        Venda #<?php echo $pagamento['venda_id']; ?> (<?php echo date('d/m/Y', strtotime($pagamento['data_venda'])); ?>)
                                    </a>
                                    <br>
                                    <span class="badge bg-<?php 
                                        if ($pagamento['venda_status'] == 'pago') echo 'success';
                                        elseif ($pagamento['venda_status'] == 'aberto') echo 'warning text-dark';
                                        elseif ($pagamento['venda_status'] == 'cancelado') echo 'danger';
                                        else echo 'secondary';
                                    ?>">
                                        <?php 
                                            if ($pagamento['venda_status'] == 'aberto') echo 'Em Aberto';
                                            else echo ucfirst($pagamento['venda_status']); 
                                        ?>
                                    </span>
                                </td>
                                <td class="text-end">R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo !empty($pagamento['observacao']) ? htmlspecialchars($pagamento['observacao']) : '<span class="text-muted">Nenhuma observação</span>'; ?></td>
                                <td class="text-center">
                                    <a href="venda_detalhes.php?id=<?php echo $pagamento['venda_id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver Detalhes da Venda">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_paginas > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Navegação de página">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($pagina > 1): ?>
                               <li class="page-item">
                                    <a class="page-link" href="?pagina=1<?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Primeira">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Primeira">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $inicio = max(1, $pagina - 2);
                            $fim = min($total_paginas, $pagina + 2);
                            
                            if ($inicio > 1) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            
                            for ($i = $inicio; $i <= $fim; $i++) {
                                $active = $i == $pagina ? 'active' : '';
                                echo '<li class="page-item ' . $active . '"><a class="page-link" href="?pagina=' . $i . (!empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : '') . (!empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : '') . (!empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : '') . '">' . $i . '</a></li>';
                            }
                            
                            if ($fim < $total_paginas) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Próxima">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Última">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Próxima">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Última">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info m-3">
                <i class="fas fa-info-circle"></i> Nenhum pagamento encontrado com os filtros selecionados.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<?php include '../includes/footer.php'; ?>