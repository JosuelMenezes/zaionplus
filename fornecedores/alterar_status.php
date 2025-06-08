<?php
// fornecedores/alterar_status.php
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados JSON da requisição
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$pedido_id = isset($input['pedido_id']) ? intval($input['pedido_id']) : 0;
$novo_status = isset($input['status']) ? trim($input['status']) : '';
$data_entrega = isset($input['data_entrega']) ? trim($input['data_entrega']) : null;
$observacoes = isset($input['observacoes']) ? trim($input['observacoes']) : '';

// Validações
if ($pedido_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do pedido inválido']);
    exit;
}

$status_validos = ['pendente', 'confirmado', 'em_transito', 'entregue', 'cancelado'];
if (!in_array($novo_status, $status_validos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit;
}

// Verificar se o pedido existe
$sql_check = "SELECT id, status FROM pedidos_fornecedores WHERE id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $pedido_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
    exit;
}

$pedido_atual = $result_check->fetch_assoc();

try {
    // Iniciar transação
    $conn->begin_transaction();
    
    // Preparar SQL baseado no status
    if ($novo_status === 'entregue' && !empty($data_entrega)) {
        // Se está marcando como entregue e tem data de entrega
        $sql_update = "UPDATE pedidos_fornecedores 
                       SET status = ?, data_entrega_realizada = ?, data_atualizacao = NOW() 
                       WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $novo_status, $data_entrega, $pedido_id);
    } else {
        // Para outros status, apenas atualizar o status
        $sql_update = "UPDATE pedidos_fornecedores 
                       SET status = ?, data_atualizacao = NOW() 
                       WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $novo_status, $pedido_id);
    }
    
    if (!$stmt_update->execute()) {
        throw new Exception("Erro ao atualizar status: " . $stmt_update->error);
    }
    
    // Se há observações, salvar no histórico (opcional - você pode criar uma tabela de histórico)
    if (!empty($observacoes)) {
        // Por enquanto, vamos apenas adicionar às observações do pedido
        $sql_obs = "UPDATE pedidos_fornecedores 
                    SET observacoes = CONCAT(IFNULL(observacoes, ''), '\n\n[', NOW(), '] Status alterado para ', ?, ': ', ?) 
                    WHERE id = ?";
        $stmt_obs = $conn->prepare($sql_obs);
        $stmt_obs->bind_param("ssi", $novo_status, $observacoes, $pedido_id);
        $stmt_obs->execute();
    }
    
    // Confirmar transação
    $conn->commit();
    
    // Buscar dados atualizados do pedido
    $sql_pedido = "SELECT pf.*, f.nome as fornecedor_nome 
                   FROM pedidos_fornecedores pf 
                   LEFT JOIN fornecedores f ON pf.fornecedor_id = f.id 
                   WHERE pf.id = ?";
    $stmt_pedido = $conn->prepare($sql_pedido);
    $stmt_pedido->bind_param("i", $pedido_id);
    $stmt_pedido->execute();
    $result_pedido = $stmt_pedido->get_result();
    $pedido_atualizado = $result_pedido->fetch_assoc();
    
    // Preparar resposta de sucesso
    $response = [
        'success' => true,
        'message' => 'Status alterado com sucesso!',
        'data' => [
            'pedido_id' => $pedido_id,
            'status_anterior' => $pedido_atual['status'],
            'status_novo' => $novo_status,
            'data_entrega' => $data_entrega,
            'pedido' => $pedido_atualizado
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Reverter transação
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>