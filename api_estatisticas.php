<?php
// api_estatisticas.php - Para atualizar dados em tempo real via AJAX

session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    $stats = [];
    $data_hoje = date('Y-m-d');
    $mes_atual = date('m');
    $ano_atual = date('Y');

    // Vendas de hoje
    $sql = "SELECT COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as total
            FROM vendas v 
            JOIN itens_venda iv ON v.id = iv.venda_id 
            WHERE DATE(v.data_venda) = '$data_hoje'";
    $result = $conn->query($sql);
    $stats['vendas_hoje'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Funcionários presentes
    $sql = "SELECT COUNT(DISTINCT funcionario_id) as total
            FROM ponto_registros 
            WHERE data_registro = '$data_hoje' 
            AND entrada_manha IS NOT NULL";
    $result = $conn->query($sql);
    $stats['funcionarios_presente'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Vendas do mês
    $sql = "SELECT COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as total
            FROM vendas v 
            JOIN itens_venda iv ON v.id = iv.venda_id 
            WHERE MONTH(v.data_venda) = $mes_atual 
            AND YEAR(v.data_venda) = $ano_atual";
    $result = $conn->query($sql);
    $stats['vendas_mes'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Total de clientes
    $sql = "SELECT COUNT(*) as total FROM clientes";
    $result = $conn->query($sql);
    $stats['clientes_total'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Adicionar informações extras
    $stats['ultima_atualizacao'] = date('Y-m-d H:i:s');
    $stats['success'] = true;

    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'success' => false
    ]);
}
?>