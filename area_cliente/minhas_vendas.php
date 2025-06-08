<?php
// area_cliente/minhas_vendas.php
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

$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$data_inicio = isset($_GET['data_inicio']) ? $conn->real_escape_string($_GET['data_inicio']) : '';
$data_fim = isset($_GET['data_fim']) ? $conn->real_escape_string($_GET['data_fim']) : '';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'data_desc';

// Construir a cláusula WHERE para filtros
$where = "v.cliente_id = $cliente_id";
if (!empty($status)) {
    $where .= " AND v.status = '$status'";
}
if (!empty($data_inicio)) {
    $where .= " AND DATE(v.data_venda) >= '$data_inicio'";
}
if (!empty($data_fim)) {
    $where .= " AND DATE(v.data_venda) <= '$data_fim'";
}

// Construir a cláusula ORDER BY para ordenação
switch ($ordenacao) {
    case 'data_asc':
        $order_by = "v.data_venda ASC";
        break;
    case 'valor_asc':
        $order_by = "total_venda ASC";
        break;
    case 'valor_desc':
        $order_by = "total_venda DESC";
        break;
    case 'data_desc':
    default:
        $order_by = "v.data_venda DESC";
        break;
}

// Consulta para contar o total de registros (para paginação)
$sql_count = "SELECT COUNT(*) as total FROM vendas v WHERE $where";
$result_count = $conn->query($sql_count);
$total_registros = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Consulta principal com paginação
$sql = "SELECT v.*, 
        (SELECT SUM(iv.quantidade * iv.valor_unitario) FROM itens_venda iv WHERE iv.venda_id = v.id) as total_venda,
        COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.venda_id = v.id), 0) as total_pago
        FROM vendas v
        WHERE $where
        ORDER BY $order_by
        LIMIT $offset, $por_pagina";
$result = $conn->query($sql);

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Minhas Compras</h1>
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
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="aberto" <?php echo $status == 'aberto' ? 'selected' : ''; ?>>Em Aberto</option>
                    <option value="pago" <?php echo $status == 'pago' ? 'selected' : ''; ?>>Pago</option>
                    <option value="cancelado" <?php echo $status == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="data_inicio" class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="data_fim" class="form-label">Data Final</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="minhas_vendas.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpar Filtros
                </a>
                <div class="float-end">
                    <select class="form-select" name="ordenacao" onchange="this.form.submit()">
                        <option value="data_desc" <?php echo $ordenacao == 'data_desc' ? 'selected' : ''; ?>>Data (mais recente)</option>
                        <option value="data_asc" <?php echo $ordenacao == 'data_asc' ? 'selected' : ''; ?>>Data (mais antiga)</option>
                        <option value="valor_desc" <?php echo $ordenacao == 'valor_desc' ? 'selected' : ''; ?>>Valor (maior)</option>
                        <option value="valor_asc" <?php echo $ordenacao == 'valor_asc' ? 'selected' : ''; ?>>Valor (menor)</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            Lista de Compras
            <span class="badge bg-light text-dark"><?php echo $total_registros; ?> compra(s)</span>
        </h5>
        
        <?php if (!empty($status) || !empty($data_inicio) || !empty($data_fim)): ?>
            <span class="badge bg-warning text-dark">
                Filtros aplicados
                <?php if (!empty($status)): ?>
                    | Status: <?php echo ucfirst($status); ?>
                <?php endif; ?>
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
                            <th>ID</th>
                            <th>Data</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Pago</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($venda = $result->fetch_assoc()): 
                            $saldo = $venda['total_venda'] - $venda['total_pago'];
                        ?>
                            <tr>
                                <td><?php echo $venda['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                <td class="text-end">R$ <?php echo number_format($venda['total_venda'], 2, ',', '.'); ?></td>
                                <td class="text-end">R$ <?php echo number_format($venda['total_pago'], 2, ',', '.'); ?></td>
                                <td class="text-end">
                                    <span class="text-<?php echo $saldo > 0 ? 'danger' : 'success'; ?>">
                                        R$ <?php echo number_format($saldo, 2, ',', '.'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($venda['status'] == 'pago'): ?>
                                        <span class="badge bg-success">Pago</span>
                                    <?php elseif ($venda['status'] == 'aberto'): ?>
                                        <span class="badge bg-warning text-dark">Em Aberto</span>
                                    <?php elseif ($venda['status'] == 'cancelado'): ?>
                                        <span class="badge bg-danger">Cancelado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($venda['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="venda_detalhes.php?id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../vendas/imprimir_debito.php?id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-secondary" target="_blank" data-bs-toggle="tooltip" title="Imprimir">
                                        <i class="fas fa-print"></i>
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
                                    <a class="page-link" href="?pagina=1<?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Primeira">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Anterior">
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
                                echo '<li class="page-item ' . $active . '"><a class="page-link" href="?pagina=' . $i . (!empty($status) ? '&status=' . urlencode($status) : '') . (!empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : '') . (!empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : '') . (!empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : '') . '">' . $i . '</a></li>';
                            }
                            
                            if ($fim < $total_paginas) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Próxima">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($data_inicio) ? '&data_inicio=' . urlencode($data_inicio) : ''; ?><?php echo !empty($data_fim) ? '&data_fim=' . urlencode($data_fim) : ''; ?><?php echo !empty($ordenacao) ? '&ordenacao=' . urlencode($ordenacao) : ''; ?>" aria-label="Última">
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
                           <?php
// area_cliente/venda_detalhes.php
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
$venda_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($venda_id <= 0) {
    $_SESSION['msg'] = "Venda não especificada";
    $_SESSION['msg_type'] = "danger";
    header("Location: minhas_vendas.php");
    exit;
}

// Verificar se a venda pertence ao cliente logado
$sql_check = "SELECT * FROM vendas WHERE id = $venda_id AND cliente_id = $cliente_id";
$result_check = $conn->query($sql_check);

if (!$result_check || $result_check->num_rows == 0) {
    $_SESSION['msg'] = "Venda não encontrada ou você não tem permissão para visualizá-la";
    $_SESSION['msg_type'] = "danger";
    header("Location: minhas_vendas.php");
    exit;
}

$venda = $result_check->fetch_assoc();

// Buscar itens da venda
$sql_itens = "SELECT iv.*, p.nome as produto_nome, p.descricao as produto_descricao 
             FROM itens_venda iv 
             JOIN produtos p ON iv.produto_id = p.id 
             WHERE iv.venda_id = $venda_id";
$result_itens = $conn->query($sql_itens);

// Buscar pagamentos da venda
$sql_pagamentos = "SELECT * FROM pagamentos WHERE venda_id = $venda_id ORDER BY data_pagamento DESC";
$result_pagamentos = $conn->query($sql_pagamentos);

// Calcular totais
$total_venda = 0;
$total_pago = 0;

if ($result_itens && $result_itens->num_rows > 0) {
    $itens = [];
    while ($item = $result_itens->fetch_assoc()) {
        $itens[] = $item;
        $total_venda += $item['quantidade'] * $item['valor_unitario'];
    }
}

if ($result_pagamentos && $result_pagamentos->num_rows > 0) {
    $pagamentos = [];
    while ($pagamento = $result_pagamentos->fetch_assoc()) {
        $pagamentos[] = $pagamento;
        $total_pago += $pagamento['valor'];
    }
}

$saldo_devedor = $total_venda - $total_pago;

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Detalhes da Compra #<?php echo $venda_id; ?></h1>
    <a href="minhas_vendas.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<?php if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])): ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Informações da Compra</h5>
                <div>
                    <span class="badge bg-<?php 
                        if ($venda['status'] == 'pago') echo 'success';
                        elseif ($venda['status'] == 'aberto') echo 'warning text-dark';
                        elseif ($venda['status'] == 'cancelado') echo 'danger';
                        else echo 'secondary';
                    ?>">
                        <?php 
                            if ($venda['status'] == 'aberto') echo 'Em Aberto';
                            else echo ucfirst($venda['status']); 
                        ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Data da Compra:</strong> <?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong>Código:</strong> #<?php echo $venda_id; ?></p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Quantidade</th>
                                <th class="text-end">Valor Unitário</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($itens) && !empty($itens)): ?>
                                <?php foreach ($itens as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($item['produto_nome']); ?></div>
                                            <?php if (!empty($item['produto_descricao'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['produto_descricao']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?php echo $item['quantidade']; ?></td>
                                        <td class="text-end">R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></td>
                                        <td class="text-end">R$ <?php echo number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum item encontrado para esta venda.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="text-end">R$ <?php echo number_format($total_venda, 2, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Resumo Financeiro</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span>Total da Compra:</span>
                    <span class="fw-bold">R$ <?php echo number_format($total_venda, 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Total Pago:</span>
                    <span class="fw-bold">R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Saldo Devedor:</span>
                    <span class="fw-bold text-<?php echo $saldo_devedor > 0 ? 'danger' : 'success'; ?>">
                        R$ <?php echo number_format($saldo_devedor, 2, ',', '.'); ?>
                    </span>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-grid gap-2">
                    <a href="../vendas/imprimir_debito.php?id=<?php echo $venda_id; ?>" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-print"></i> Imprimir Comprovante
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Histórico de Pagamentos</h5>
            </div>
            <div class="card-body p-0">
                <?php if (isset($pagamentos) && !empty($pagamentos)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagamentos as $pagamento): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?>
                                            <?php if (!empty($pagamento['observacao'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($pagamento['observacao']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info m-3 mb-0">
                        <i class="fas fa-info-circle"></i> Nenhum pagamento registrado para esta compra.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>