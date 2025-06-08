<?php
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivo de conexão com o banco de dados
require_once 'config/database.php';

// Função para exibir resultados de consulta em formato de tabela
function exibir_resultados($result, $titulo) {
    echo "<h3>$titulo</h3>";
    
    if (!$result) {
        echo "<div class='alert alert-danger'>Erro na consulta: " . $conn->error . "</div>";
        return;
    }
    
    if ($result->num_rows == 0) {
        echo "<div class='alert alert-warning'>Nenhum resultado encontrado</div>";
        return;
    }
    
    echo "<div class='table-responsive'><table class='table table-striped table-bordered'>";
    
    // Cabeçalho da tabela
    $first_row = $result->fetch_assoc();
    $result->data_seek(0);
    
    echo "<thead><tr>";
    foreach (array_keys($first_row) as $column) {
        echo "<th>$column</th>";
    }
    echo "</tr></thead><tbody>";
    
    // Dados
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</tbody></table></div>";
}

// Função para exibir estrutura de tabela
function exibir_estrutura_tabela($conn, $tabela) {
    $result = $conn->query("DESCRIBE $tabela");
    exibir_resultados($result, "Estrutura da tabela '$tabela'");
}

// Função para calcular saldo devedor de um cliente específico
function calcular_saldo_devedor($conn, $cliente_id) {
    // Total de vendas pendentes
    $sql_vendas = "
        SELECT v.id, v.data_venda, v.status,
               SUM(iv.quantidade * iv.valor_unitario) as valor_total
        FROM vendas v
        JOIN itens_venda iv ON v.id = iv.venda_id
        WHERE v.cliente_id = $cliente_id AND v.status = 'pendente'
        GROUP BY v.id
    ";
    $result_vendas = $conn->query($sql_vendas);
    
    $saldo_total = 0;
    $detalhes_vendas = [];
    
    if ($result_vendas && $result_vendas->num_rows > 0) {
        while ($venda = $result_vendas->fetch_assoc()) {
            $venda_id = $venda['id'];
            $valor_venda = $venda['valor_total'];
            
            // Total de pagamentos para esta venda
            $sql_pagamentos = "
                SELECT COALESCE(SUM(valor), 0) as total_pago
                FROM pagamentos
                WHERE venda_id = $venda_id
            ";
            $result_pagamentos = $conn->query($sql_pagamentos);
            $pagamento = $result_pagamentos->fetch_assoc();
            $total_pago = $pagamento['total_pago'];
            
            $saldo_venda = $valor_venda - $total_pago;
            $saldo_total += $saldo_venda;
            
            $detalhes_vendas[] = [
                'venda_id' => $venda_id,
                'data' => $venda['data_venda'],
                'valor_total' => $valor_venda,
                'total_pago' => $total_pago,
                'saldo' => $saldo_venda
            ];
        }
    }
    
    return [
        'saldo_total' => $saldo_total,
        'detalhes' => $detalhes_vendas
    ];
}

// Obter cliente para diagnóstico
$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : null;

// Se não foi especificado um cliente, pegar o primeiro com vendas pendentes
if (!$cliente_id) {
    $sql = "
        SELECT DISTINCT c.id, c.nome
        FROM clientes c
        JOIN vendas v ON c.id = v.cliente_id
        WHERE v.status = 'pendente'
        LIMIT 1
    ";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
        $cliente_id = $cliente['id'];
    } else {
        // Se não encontrar nenhum, pegar o primeiro cliente
        $sql = "SELECT id, nome FROM clientes LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $cliente = $result->fetch_assoc();
            $cliente_id = $cliente['id'];
        }
    }
}

// Obter informações do cliente
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = $conn->query($sql_cliente);
$cliente = $result_cliente->fetch_assoc();

// Obter todas as vendas do cliente
$sql_vendas = "
    SELECT v.*, 
           (SELECT SUM(iv.quantidade * iv.valor_unitario) FROM itens_venda iv WHERE iv.venda_id = v.id) as valor_total,
           (SELECT SUM(p.valor) FROM pagamentos p WHERE p.venda_id = v.id) as total_pago
    FROM vendas v
    WHERE v.cliente_id = $cliente_id
    ORDER BY v.data_venda DESC
";
$result_vendas = $conn->query($sql_vendas);

// Obter itens de vendas pendentes
$sql_itens = "
    SELECT iv.*, v.data_venda, p.nome as produto_nome
    FROM itens_venda iv
    JOIN vendas v ON iv.venda_id = v.id
    JOIN produtos p ON iv.produto_id = p.id
    WHERE v.cliente_id = $cliente_id AND v.status = 'pendente'
    ORDER BY v.data_venda DESC
";
$result_itens = $conn->query($sql_itens);

// Obter pagamentos
$sql_pagamentos = "
    SELECT p.*, v.data_venda
    FROM pagamentos p
    JOIN vendas v ON p.venda_id = v.id
    WHERE v.cliente_id = $cliente_id
    ORDER BY p.data_pagamento DESC
";
$result_pagamentos = $conn->query($sql_pagamentos);

// Calcular saldo devedor usando a função
$saldo_info = calcular_saldo_devedor($conn, $cliente_id);

// Calcular saldo devedor usando a consulta da listagem
$sql_saldo = "
    SELECT 
        COALESCE(
            (
                SELECT SUM(iv.quantidade * iv.valor_unitario) 
                FROM vendas v 
                JOIN itens_venda iv ON v.id = iv.venda_id 
                WHERE v.cliente_id = $cliente_id AND v.status = 'pendente'
            ) - 
            COALESCE(
                (
                    SELECT SUM(p.valor) 
                    FROM pagamentos p 
                    JOIN vendas v ON p.venda_id = v.id 
                    WHERE v.cliente_id = $cliente_id AND v.status = 'pendente'
                ), 
                0
            ), 
            0
        ) as saldo_devedor
";
$result_saldo = $conn->query($sql_saldo);
$saldo_consulta = $result_saldo->fetch_assoc();

// Verificar a estrutura das tabelas
$tabelas = ['vendas', 'itens_venda', 'pagamentos'];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Saldo Devedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .section { margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Diagnóstico de Saldo Devedor</h1>
        
        <div class="alert alert-info">
            <h4>Cliente: <?= htmlspecialchars($cliente['nome']) ?> (ID: <?= $cliente_id ?>)</h4>
            <p>
                <strong>Saldo devedor calculado pela função:</strong> R$ <?= number_format($saldo_info['saldo_total'], 2, ',', '.') ?><br>
                <strong>Saldo devedor calculado pela consulta SQL:</strong> R$ <?= number_format($saldo_consulta['saldo_devedor'], 2, ',', '.') ?>
            </p>
        </div>
        
        <div class="section">
            <h2>Detalhes do Cálculo do Saldo</h2>
            <?php if (empty($saldo_info['detalhes'])): ?>
                <div class="alert alert-warning">Nenhuma venda pendente encontrada para este cliente.</div>
            <?php else: ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Venda ID</th>
                            <th>Data</th>
                            <th>Valor Total</th>
                            <th>Total Pago</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saldo_info['detalhes'] as $detalhe): ?>
                            <tr>
                                <td><?= $detalhe['venda_id'] ?></td>
                                <td><?= date('d/m/Y', strtotime($detalhe['data'])) ?></td>
                                <td>R$ <?= number_format($detalhe['valor_total'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($detalhe['total_pago'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($detalhe['saldo'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Saldo Total:</th>
                            <th>R$ <?= number_format($saldo_info['saldo_total'], 2, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Informações do Cliente</h2>
            <?php if ($result_cliente): ?>
                <table class="table table-striped table-bordered">
                    <tbody>
                        <?php foreach ($cliente as $key => $value): ?>
                            <tr>
                                <th width="200"><?= htmlspecialchars($key) ?></th>
                                <td><?= htmlspecialchars($value ?? 'NULL') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Vendas do Cliente</h2>
            <?php exibir_resultados($result_vendas, "Todas as vendas"); ?>
        </div>
        
        <div class="section">
            <h2>Itens de Vendas Pendentes</h2>
            <?php exibir_resultados($result_itens, "Itens de vendas pendentes"); ?>
        </div>
        
        <div class="section">
            <h2>Pagamentos</h2>
            <?php exibir_resultados($result_pagamentos, "Todos os pagamentos"); ?>
        </div>
        
        <div class="section">
            <h2>Estrutura das Tabelas</h2>
            <?php foreach ($tabelas as $tabela): ?>
                <?php exibir_estrutura_tabela($conn, $tabela); ?>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h2>Consultas SQL Utilizadas</h2>
            <div class="card">
                <div class="card-header">Consulta para calcular saldo devedor</div>
                <div class="card-body">
                    <pre><?= htmlspecialchars($sql_saldo) ?></pre>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>