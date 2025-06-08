<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Data de hoje
$data_hoje = date('Y-m-d');
echo "<h2>Diagnóstico de Vendas do Dia: $data_hoje</h2>";

// 1. Verificar vendas do dia
$sql_vendas_dia = "SELECT v.id, v.data_venda, c.nome as cliente, 
                   SUM(iv.quantidade * iv.valor_unitario) as total
                   FROM vendas v 
                   JOIN itens_venda iv ON v.id = iv.venda_id 
                   JOIN clientes c ON v.cliente_id = c.id
                   WHERE DATE(v.data_venda) = '$data_hoje'
                   GROUP BY v.id";
$result_vendas_dia = $conn->query($sql_vendas_dia);

echo "<h3>1. Vendas registradas hoje:</h3>";
echo "<p>SQL: $sql_vendas_dia</p>";

if ($result_vendas_dia && $result_vendas_dia->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Data/Hora</th><th>Cliente</th><th>Total</th></tr>";
    $total_geral = 0;
    
    while ($row = $result_vendas_dia->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['data_venda'] . "</td>";
        echo "<td>" . htmlspecialchars($row['cliente']) . "</td>";
        echo "<td>R$ " . number_format($row['total'], 2, ',', '.') . "</td>";
        echo "</tr>";
        $total_geral += $row['total'];
    }
    
    echo "<tr><td colspan='3' align='right'><strong>Total Geral:</strong></td>";
    echo "<td><strong>R$ " . number_format($total_geral, 2, ',', '.') . "</strong></td></tr>";
    echo "</table>";
} else {
    echo "<p>Nenhuma venda encontrada para hoje.</p>";
}

// 2. Verificar vendas recentes (últimos 3 dias)
echo "<h3>2. Vendas dos últimos 3 dias:</h3>";

$sql_vendas_recentes = "SELECT DATE(v.data_venda) as data, COUNT(*) as quantidade, 
                        SUM(iv.quantidade * iv.valor_unitario) as total
                        FROM vendas v 
                        JOIN itens_venda iv ON v.id = iv.venda_id 
                        WHERE v.data_venda >= DATE_SUB('$data_hoje', INTERVAL 3 DAY)
                        GROUP BY DATE(v.data_venda)
                        ORDER BY data DESC";
$result_vendas_recentes = $conn->query($sql_vendas_recentes);

if ($result_vendas_recentes && $result_vendas_recentes->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Data</th><th>Quantidade</th><th>Total</th></tr>";
    
    while ($row = $result_vendas_recentes->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y', strtotime($row['data'])) . "</td>";
        echo "<td>" . $row['quantidade'] . "</td>";
        echo "<td>R$ " . number_format($row['total'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Nenhuma venda encontrada nos últimos 3 dias.</p>";
}

// 3. Verificar a última venda registrada
echo "<h3>3. Última venda registrada no sistema:</h3>";

$sql_ultima_venda = "SELECT v.id, v.data_venda, c.nome as cliente, 
                     SUM(iv.quantidade * iv.valor_unitario) as total
                     FROM vendas v 
                     JOIN itens_venda iv ON v.id = iv.venda_id 
                     JOIN clientes c ON v.cliente_id = c.id
                     GROUP BY v.id
                     ORDER BY v.id DESC
                     LIMIT 1";
$result_ultima_venda = $conn->query($sql_ultima_venda);

if ($result_ultima_venda && $result_ultima_venda->num_rows > 0) {
    $ultima_venda = $result_ultima_venda->fetch_assoc();
    echo "<p><strong>ID:</strong> " . $ultima_venda['id'] . "</p>";
    echo "<p><strong>Data/Hora:</strong> " . $ultima_venda['data_venda'] . "</p>";
    echo "<p><strong>Cliente:</strong> " . htmlspecialchars($ultima_venda['cliente']) . "</p>";
    echo "<p><strong>Total:</strong> R$ " . number_format($ultima_venda['total'], 2, ',', '.') . "</p>";
} else {
    echo "<p>Nenhuma venda encontrada no sistema.</p>";
}

// 4. Verificar configuração de data e hora do servidor
echo "<h3>4. Configuração de data e hora do servidor:</h3>";
echo "<p><strong>Data/Hora atual do servidor:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Timezone configurado:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Timestamp atual:</strong> " . time() . "</p>";
?>