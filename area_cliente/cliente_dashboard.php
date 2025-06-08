<?php
// area_cliente/dashboard.php
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

// Buscar informações do cliente
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = $conn->query($sql_cliente);

if (!$result_cliente || $result_cliente->num_rows == 0) {
    $_SESSION['msg'] = "Cliente não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../index.php");
    exit;
}

$cliente = $result_cliente->fetch_assoc();

// Calcular saldo devedor
$sql_saldo = "SELECT 
    SUM(iv.quantidade * iv.valor_unitario) as total_compras,
    COALESCE((SELECT SUM(p.valor) FROM pagamentos p 
              JOIN vendas v ON p.venda_id = v.id 
              WHERE v.cliente_id = $cliente_id), 0) as total_pago
    FROM vendas v
    JOIN itens_venda iv ON v.id = iv.venda_id
    WHERE v.cliente_id = $cliente_id AND v.status = 'aberto'";

$result_saldo = $conn->query($sql_saldo);
$saldo = $result_saldo->fetch_assoc();

$total_compras = $saldo['total_compras'] ?? 0;
$total_pago = $saldo['total_pago'] ?? 0;
$saldo_devedor = $total_compras - $total_pago;

// Buscar últimas vendas
$sql_vendas = "SELECT v.*, 
              (SELECT SUM(iv.quantidade * iv.valor_unitario) FROM itens_venda iv WHERE iv.venda_id = v.id) as total_venda,
              COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.venda_id = v.id), 0) as total_pago
              FROM vendas v
              WHERE v.cliente_id = $cliente_id
              ORDER BY v.data_venda DESC
              LIMIT 5";
$result_vendas = $conn->query($sql_vendas);

// Buscar últimos pagamentos
$sql_pagamentos = "SELECT p.*, v.data_venda, v.id as venda_id
                  FROM pagamentos p
                  JOIN vendas v ON p.venda_id = v.id
                  WHERE v.cliente_id = $cliente_id
                  ORDER BY p.data_pagamento DESC
                  LIMIT 5";
$result_pagamentos = $conn->query($sql_pagamentos);

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Minha Conta</h1>
</div>

<?php if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])): ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Informações Pessoais</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="client-avatar me-3" style="background-color: <?php echo sprintf('#%06X', crc32($cliente['nome'])); ?>">
                        <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
                    </div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($cliente['nome']); ?></h4>
                </div>
                
                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                <?php if (!empty($cliente['empresa'])): ?>
                    <p><strong>Empresa:</strong> <?php echo htmlspecialchars($cliente['empresa']); ?></p>
                <?php endif; ?>
                <p><strong>Limite de Compra:</strong> R$ <?php echo number_format($cliente['limite_compra'], 2, ',', '.'); ?></p>
                
                <div class="mt-3">
                    <a href="alterar_senha.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-key"></i> Alterar Senha
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Resumo Financeiro</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center mb-3">
                            <h6 class="text-muted">Total em Compras</h6>
                            <h3>R$ <?php echo number_format($total_compras, 2, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center mb-3">
                            <h6 class="text-muted">Total Pago</h6>
                            <h3>R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="text-center p-3 bg-light rounded">
                    <h6 class="text-muted">Saldo Devedor</h6>
                    <h2 class="text-<?php echo $saldo_devedor > 0 ? 'danger' : 'success'; ?>">
                        R$ <?php echo number_format($saldo_devedor, 2, ',', '.'); ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Minhas Compras Recentes</h5>
                <a href="minhas_vendas.php" class="btn btn-sm btn-light">Ver Todas</a>
            </div>
            <div class="card-body p-0">
                <?php if ($result_vendas && $result_vendas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Pago</th>
                                    <th class="text-end">Saldo</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($venda = $result_vendas->fetch_assoc()): 
                                    $saldo = $venda['total_venda'] - $venda['total_pago'];
                                ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($venda['data_venda'])); ?></td>
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
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info m-3">
                        <i class="fas fa-info-circle"></i> Você ainda não possui compras registradas.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Meus Pagamentos Recentes</h5>
                <a href="meus_pagamentos.php" class="btn btn-sm btn-light">Ver Todos</a>
            </div>
            <div class="card-body p-0">
                <?php if ($result_pagamentos && $result_pagamentos->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Venda</th>
                                    <th class="text-end">Valor</th>
                                    <th>Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pagamento = $result_pagamentos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td>
                                        <td>
                                            <a href="venda_detalhes.php?id=<?php echo $pagamento['venda_id']; ?>">
                                                Venda #<?php echo $pagamento['venda_id']; ?> (<?php echo date('d/m/Y', strtotime($pagamento['data_venda'])); ?>)
                                            </a>
                                        </td>
                                        <td class="text-end">R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
                                        <td><?php echo !empty($pagamento['observacao']) ? htmlspecialchars($pagamento['observacao']) : '<span class="text-muted">Nenhuma observação</span>'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info m-3">
                        <i class="fas fa-info-circle"></i> Você ainda não possui pagamentos registrados.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.client-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 24px;
}
</style>

<?php include '../includes/footer.php'; ?>