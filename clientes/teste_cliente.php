<?php
// clientes/teste_cliente.php
require_once '../config/database.php';

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h1>Erro: ID do cliente não especificado</h1>";
    exit;
}

$cliente_id = intval($_GET['id']);

// Buscar informações do cliente
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = $conn->query($sql_cliente);

if (!$result_cliente || $result_cliente->num_rows == 0) {
    echo "<h1>Erro: Cliente não encontrado</h1>";
    exit;
}

$cliente = $result_cliente->fetch_assoc();

// Exibir informações do cliente
echo "<h1>Teste de Cliente</h1>";
echo "<p>ID do cliente: " . $cliente_id . "</p>";
echo "<p>Nome do cliente: " . htmlspecialchars($cliente['nome']) . "</p>";
echo "<p>Telefone: " . htmlspecialchars($cliente['telefone']) . "</p>";
echo "<p>Empresa: " . htmlspecialchars($cliente['empresa']) . "</p>";

// Exibir vendas do cliente
echo "<h2>Vendas do Cliente</h2>";
$sql_vendas = "SELECT id, data_venda, status FROM vendas WHERE cliente_id = $cliente_id ORDER BY data_venda DESC LIMIT 5";
$result_vendas = $conn->query($sql_vendas);

if ($result_vendas && $result_vendas->num_rows > 0) {
    echo "<ul>";
    while ($venda = $result_vendas->fetch_assoc()) {
        echo "<li>Venda #" . $venda['id'] . " - Data: " . date('d/m/Y', strtotime($venda['data_venda'])) . " - Status: " . $venda['status'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nenhuma venda encontrada para este cliente.</p>";
}

// Exibir pagamentos do cliente
echo "<h2>Pagamentos do Cliente</h2>";
$sql_pagamentos = "SELECT p.id, p.venda_id, p.valor, p.data_pagamento 
                  FROM pagamentos p
                  JOIN vendas v ON p.venda_id = v.id
                  WHERE v.cliente_id = $cliente_id
                  ORDER BY p.data_pagamento DESC LIMIT 5";
$result_pagamentos = $conn->query($sql_pagamentos);

if ($result_pagamentos && $result_pagamentos->num_rows > 0) {
    echo "<ul>";
    while ($pagamento = $result_pagamentos->fetch_assoc()) {
        echo "<li>Pagamento #" . $pagamento['id'] . " - Venda #" . $pagamento['venda_id'] . " - Valor: R$ " . 
             number_format($pagamento['valor'], 2, ',', '.') . " - Data: " . date('d/m/Y', strtotime($pagamento['data_pagamento'])) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nenhum pagamento encontrado para este cliente.</p>";
}

// Exibir formulário de teste
echo "<h2>Formulário de Teste</h2>";
echo "<form method='post' action='teste_cliente.php?id=" . $cliente_id . "'>";
echo "<input type='hidden' name='cliente_id' value='" . $cliente_id . "'>";
echo "<p>Este formulário é apenas para testar se o ID do cliente está sendo mantido corretamente.</p>";
echo "<button type='submit'>Enviar Formulário de Teste</button>";
echo "</form>";

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Resultado do Formulário</h2>";
    echo "<p>ID do cliente na URL: " . $cliente_id . "</p>";
    echo "<p>ID do cliente no formulário: " . (isset($_POST['cliente_id']) ? $_POST['cliente_id'] : 'Não definido') . "</p>";
    
    if (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente_id) {
        echo "<p style='color: green;'>Os IDs correspondem!</p>";
    } else {
        echo "<p style='color: red;'>Os IDs NÃO correspondem!</p>";
    }
}
?>