<?php
// fornecedores/gerar_relatorio_fornecedor.php
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

// Verificar se o ID do fornecedor foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Fornecedor não especificado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor_id = intval($_GET['id']);

// Buscar informações do fornecedor
$sql_fornecedor = "SELECT f.*,
                    GROUP_CONCAT(DISTINCT cat.nome ORDER BY cat.nome ASC SEPARATOR ', ') as categorias
                   FROM fornecedores f
                   LEFT JOIN fornecedor_categorias fc ON f.id = fc.fornecedor_id
                   LEFT JOIN categorias_fornecedores cat ON fc.categoria_id = cat.id
                   WHERE f.id = ?
                   GROUP BY f.id";

$stmt = $conn->prepare($sql_fornecedor);
$stmt->bind_param("i", $fornecedor_id);
$stmt->execute();
$result_fornecedor = $stmt->get_result();

if ($result_fornecedor->num_rows === 0) {
    $_SESSION['msg'] = "Fornecedor não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor = $result_fornecedor->fetch_assoc();

// Buscar estatísticas
$sql_stats = "SELECT
              COUNT(DISTINCT pf.id) as total_pedidos,
              COALESCE(SUM(CASE WHEN pf.status = 'entregue' THEN pf.valor_total END), 0) as valor_total_comprado,
              COALESCE(SUM(CASE WHEN pf.status IN ('pendente', 'confirmado', 'em_transito') THEN pf.valor_total END), 0) as valor_pedidos_abertos,
              MAX(pf.data_pedido) as ultimo_pedido,
              MIN(pf.data_pedido) as primeiro_pedido
              FROM pedidos_fornecedores pf
              WHERE pf.fornecedor_id = ?";

$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $fornecedor_id);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
$stats = $result_stats->fetch_assoc();

// Buscar pedidos
$sql_pedidos = "SELECT pf.*, COUNT(ipf.id) as total_itens
                FROM pedidos_fornecedores pf
                LEFT JOIN itens_pedido_fornecedor ipf ON pf.id = ipf.pedido_id
                WHERE pf.fornecedor_id = ?
                GROUP BY pf.id
                ORDER BY pf.data_pedido DESC";

$stmt_pedidos = $conn->prepare($sql_pedidos);
$stmt_pedidos->bind_param("i", $fornecedor_id);
$stmt_pedidos->execute();
$result_pedidos = $stmt_pedidos->get_result();

// Buscar comunicações
$sql_comunicacoes = "SELECT cf.*, u.nome as usuario_nome
                     FROM comunicacoes_fornecedores cf
                     LEFT JOIN usuarios u ON cf.usuario_id = u.id
                     WHERE cf.fornecedor_id = ?
                     ORDER BY cf.data_comunicacao DESC
                     LIMIT 20";

$stmt_comunicacoes = $conn->prepare($sql_comunicacoes);
$stmt_comunicacoes->bind_param("i", $fornecedor_id);
$stmt_comunicacoes->execute();
$result_comunicacoes = $stmt_comunicacoes->get_result();

// Gerar HTML do relatório
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório - <?php echo htmlspecialchars($fornecedor['nome']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #fd7e14; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #fd7e14; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        .section { margin-bottom: 30px; }
        .section h2 { color: #fd7e14; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .info-item { padding: 10px; border: 1px solid #eee; border-radius: 5px; }
        .info-label { font-weight: bold; color: #666; font-size: 0.9em; margin-bottom: 5px; }
        .info-value { color: #333; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f8f9fa; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f9f9f9; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { text-align: center; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
        .stat-value { font-size: 1.5em; font-weight: bold; color: #fd7e14; }
        .stat-label { font-size: 0.9em; color: #666; margin-top: 5px; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-confirmado { background: #d1ecf1; color: #0c5460; }
        .status-em_transito { background: #d4edda; color: #155724; }
        .status-entregue { background: #d1f2eb; color: #0c5460; }
        .status-cancelado { background: #f8d7da; color: #721c24; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 0.9em; border-top: 1px solid #eee; padding-top: 20px; }
        @media print { body { margin: 0; } .header { break-after: avoid; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório Completo do Fornecedor</h1>
        <p><strong><?php echo htmlspecialchars($fornecedor['nome']); ?></strong></p>
        <?php if (!empty($fornecedor['empresa'])): ?>
            <p><?php echo htmlspecialchars($fornecedor['empresa']); ?></p>
        <?php endif; ?>
        <p>Gerado em: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="section">
        <h2>Informações Básicas</h2>
        <div class="info-grid">
            <?php if (!empty($fornecedor['cnpj'])): ?>
            <div class="info-item">
                <div class="info-label">CNPJ</div>
                <div class="info-value"><?php echo htmlspecialchars($fornecedor['cnpj']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($fornecedor['telefone'])): ?>
            <div class="info-item">
                <div class="info-label">Telefone</div>
                <div class="info-value"><?php echo htmlspecialchars($fornecedor['telefone']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($fornecedor['email'])): ?>
            <div class="info-item">
                <div class="info-label">E-mail</div>
                <div class="info-value"><?php echo htmlspecialchars($fornecedor['email']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value"><?php echo ucfirst($fornecedor['status']); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Tipo</div>
                <div class="info-value"><?php echo ucfirst($fornecedor['tipo_fornecedor']); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Avaliação</div>
                <div class="info-value"><?php echo number_format($fornecedor['avaliacao'], 1); ?> ⭐</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Prazo de Entrega</div>
                <div class="info-value"><?php echo $fornecedor['prazo_entrega_padrao']; ?> dias</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Cadastrado em</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($fornecedor['data_cadastro'])); ?></div>
            </div>
        </div>

        <?php if (!empty($fornecedor['categorias'])): ?>
        <div class="info-item">
            <div class="info-label">Categorias</div>
            <div class="info-value"><?php echo htmlspecialchars($fornecedor['categorias']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($fornecedor['endereco'])): ?>
        <div class="info-item">
            <div class="info-label">Endereço</div>
            <div class="info-value">
                <?php echo htmlspecialchars($fornecedor['endereco']); ?>
                <?php if (!empty($fornecedor['cidade'])): ?>
                    <br><?php echo htmlspecialchars($fornecedor['cidade']); ?>
                    <?php if (!empty($fornecedor['estado'])): ?>
                        - <?php echo htmlspecialchars($fornecedor['estado']); ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($fornecedor['cep'])): ?>
                    <br>CEP: <?php echo htmlspecialchars($fornecedor['cep']); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Estatísticas de Compras</h2>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_pedidos']; ?></div>
                <div class="stat-label">Total de Pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">R$ <?php echo number_format($stats['valor_total_comprado'], 0, ',', '.'); ?></div>
                <div class="stat-label">Total Comprado</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">R$ <?php echo number_format($stats['valor_pedidos_abertos'], 0, ',', '.'); ?></div>
                <div class="stat-label">Pedidos Abertos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php 
                    if ($stats['ultimo_pedido']) {
                        echo date('d/m/Y', strtotime($stats['ultimo_pedido']));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="stat-label">Último Pedido</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Histórico de Pedidos</h2>
        <?php if ($result_pedidos->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Itens</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pedido = $result_pedidos->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($pedido['numero_pedido']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $pedido['status']; ?>">
                                    <?php echo ucfirst($pedido['status']); ?>
                                </span>
                            </td>
                            <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                            <td><?php echo $pedido['total_itens']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum pedido encontrado.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Histórico de Comunicações</h2>
        <?php if ($result_comunicacoes->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Assunto</th>
                        <th>Usuário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($comunicacao = $result_comunicacoes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($comunicacao['data_comunicacao'])); ?></td>
                            <td><?php echo ucfirst($comunicacao['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($comunicacao['assunto']); ?></td>
                            <td><?php echo htmlspecialchars($comunicacao['usuario_nome'] ?: 'Sistema'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma comunicação registrada.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($fornecedor['observacoes'])): ?>
    <div class="section">
        <h2>Observações</h2>
        <div class="info-item">
            <div class="info-value"><?php echo nl2br(htmlspecialchars($fornecedor['observacoes'])); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Relatório gerado automaticamente pelo Sistema de Gerenciamento de Fornecedores</p>
        <p>Data: <?php echo date('d/m/Y H:i'); ?> | Usuário: <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Sistema'); ?></p>
    </div>
</body>
</html>

<script>
// Auto-imprimir quando a página carregar
window.onload = function() {
    window.print();
};
</script>
<?php
$html_content = ob_get_clean();
echo $html_content;
?>