<?php
// clientes/registrar_pagamento_simples.php
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

// Calcular saldo devedor
$sql_saldo = "SELECT 
              COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as total_compras,
              COALESCE((SELECT SUM(p.valor) FROM pagamentos p 
                        JOIN vendas v2 ON p.venda_id = v2.id 
                        WHERE v2.cliente_id = $cliente_id), 0) as total_pago
              FROM vendas v
              LEFT JOIN itens_venda iv ON v.id = iv.venda_id
              WHERE v.cliente_id = $cliente_id";
$result_saldo = $conn->query($sql_saldo);
$saldo_info = $result_saldo->fetch_assoc();
$saldo_devedor = $saldo_info['total_compras'] - $saldo_info['total_pago'];

// Processar o formulário de pagamento
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valor'])) {
    $valor = isset($_POST['valor']) ? str_replace(',', '.', $_POST['valor']) : 0;
    $valor = floatval($valor);
    $observacao = isset($_POST['observacao']) ? $conn->real_escape_string($_POST['observacao']) : '';
    $data_pagamento = isset($_POST['data_pagamento']) ? $conn->real_escape_string($_POST['data_pagamento']) : date('Y-m-d');
    
    // Verificar se o cliente_id do formulário corresponde ao cliente_id da URL
    $form_cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
    
    if ($form_cliente_id != $cliente_id) {
        $msg = "Erro: ID do cliente não corresponde. Form ID: $form_cliente_id, URL ID: $cliente_id";
        $msg_type = "danger";
    } elseif ($valor <= 0) {
        $msg = "O valor do pagamento deve ser maior que zero.";
        $msg_type = "danger";
    } else {
        // Buscar vendas em aberto do cliente
        $sql_vendas = "SELECT v.id, v.data_venda, 
                       COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as valor_total,
                       COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.venda_id = v.id), 0) as valor_pago
                       FROM vendas v
                       LEFT JOIN itens_venda iv ON v.id = iv.venda_id
                       WHERE v.cliente_id = $cliente_id AND v.status = 'aberto'
                       GROUP BY v.id
                       ORDER BY v.data_venda ASC";
        $result_vendas = $conn->query($sql_vendas);
        
        if ($result_vendas && $result_vendas->num_rows > 0) {
            $conn->begin_transaction();
            
            try {
                $valor_restante = $valor;
                $vendas_atualizadas = [];
                
                while ($venda = $result_vendas->fetch_assoc()) {
                    if ($valor_restante <= 0) break;
                    
                    $saldo_venda = $venda['valor_total'] - $venda['valor_pago'];
                    
                    if ($saldo_venda > 0) {
                        $valor_aplicado = min($valor_restante, $saldo_venda);
                        
                        // Inserir o pagamento
                        $sql_insert = "INSERT INTO pagamentos (venda_id, valor, data_pagamento, observacao) 
                                      VALUES ({$venda['id']}, $valor_aplicado, '$data_pagamento', '$observacao')";
                        
                        if ($conn->query($sql_insert)) {
                            $valor_restante -= $valor_aplicado;
                            $vendas_atualizadas[] = $venda['id'];
                            
                            // Verificar se a venda foi totalmente paga
                            if ($valor_aplicado >= $saldo_venda) {
                                $conn->query("UPDATE vendas SET status = 'pago' WHERE id = {$venda['id']}");
                            }
                        }
                    }
                }
                
                if (count($vendas_atualizadas) > 0) {
                    $conn->commit();
                    $msg = "Pagamento de R$ " . number_format($valor, 2, ',', '.') . " registrado com sucesso!";
                    $msg_type = "success";
                } else {
                    throw new Exception("Nenhuma venda foi atualizada.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "Erro ao registrar pagamento: " . $e->getMessage();
                $msg_type = "danger";
            }
        } else {
            $msg = "Não há vendas em aberto para este cliente.";
            $msg_type = "warning";
        }
    }
}

// HTML básico
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pagamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Registrar Pagamento</h1>
            <a href="detalhes.php?id=<?php echo $cliente_id; ?>" class="btn btn-secondary">Voltar</a>
        </div>
        
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informações do Cliente #<?php echo $cliente_id; ?></h5>
            </div>
            <div class="card-body">
                <h4><?php echo htmlspecialchars($cliente['nome']); ?></h4>
                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                <p><strong>Empresa:</strong> <?php echo htmlspecialchars($cliente['empresa']); ?></p>
                <p><strong>Saldo Devedor:</strong> R$ <?php echo number_format($saldo_devedor, 2, ',', '.'); ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Registrar Novo Pagamento</h5>
            </div>
            <div class="card-body">
                <form method="post" action="registrar_pagamento_simples.php?id=<?php echo $cliente_id; ?>">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                    
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor do Pagamento (R$)</label>
                        <input type="text" class="form-control" id="valor" name="valor" required 
                               placeholder="0,00">
                    </div>
                    
                    <div class="mb-3">
                        <label for="data_pagamento" class="form-label">Data do Pagamento</label>
                        <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacao" class="form-label">Observação</label>
                        <textarea class="form-control" id="observacao" name="observacao" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Confirmar Pagamento</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>