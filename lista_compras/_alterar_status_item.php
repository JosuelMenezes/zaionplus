<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $item_id = (int)($input['item_id'] ?? 0);
    $novo_status = $input['status'] ?? '';

    if (!$item_id) {
        throw new Exception('ID do item é obrigatório');
    }

    $status_validos = ['pendente', 'cotado', 'aprovado', 'comprado'];
    if (!in_array($novo_status, $status_validos)) {
        throw new Exception('Status inválido. Use: ' . implode(', ', $status_validos));
    }

    // Verificar se o item existe e buscar dados atuais
    $sql_item = "SELECT i.*, l.nome as lista_nome 
                 FROM itens_lista_compras i
                 JOIN listas_compras l ON i.lista_id = l.id
                 WHERE i.id = ?";
    $stmt_item = $pdo->prepare($sql_item);
    $stmt_item->execute([$item_id]);
    $item = $stmt_item->fetch();

    if (!$item) {
        throw new Exception('Item não encontrado');
    }

    $status_anterior = $item['status_item'];

    // Atualizar status do item
    $sql_update = "UPDATE itens_lista_compras SET status_item = ? WHERE id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$novo_status, $item_id]);

    // Registrar no histórico
    $sql_historico = "INSERT INTO historico_listas_compras (lista_id, acao, descricao, usuario_id) 
                      VALUES (?, 'status_item_alterado', ?, ?)";
    $stmt_historico = $pdo->prepare($sql_historico);
    $stmt_historico->execute([
        $item['lista_id'],
        "Status do item '{$item['produto_descricao']}' alterado de '{$status_anterior}' para '{$novo_status}'",
        $_SESSION['usuario_id']
    ]);

    // Verificar se todos os itens foram comprados para atualizar status da lista
    $sql_verificar = "SELECT COUNT(*) as total, 
                             COUNT(CASE WHEN status_item = 'comprado' THEN 1 END) as comprados
                      FROM itens_lista_compras 
                      WHERE lista_id = ?";
    $stmt_verificar = $pdo->prepare($sql_verificar);
    $stmt_verificar->execute([$item['lista_id']]);
    $contagem = $stmt_verificar->fetch();

    // Se todos os itens foram comprados, atualizar status da lista
    if ($contagem['total'] > 0 && $contagem['total'] == $contagem['comprados']) {
        $sql_lista = "UPDATE listas_compras SET status = 'finalizada' WHERE id = ?";
        $stmt_lista = $pdo->prepare($sql_lista);
        $stmt_lista->execute([$item['lista_id']]);

        // Registrar finalização no histórico
        $sql_historico_lista = "INSERT INTO historico_listas_compras (lista_id, acao, descricao, usuario_id) 
                                VALUES (?, 'lista_finalizada', 'Lista finalizada automaticamente - todos os itens comprados', ?)";
        $stmt_historico_lista = $pdo->prepare($sql_historico_lista);
        $stmt_historico_lista->execute([$item['lista_id'], $_SESSION['usuario_id']]);
    }

    echo json_encode([
        'success' => true,
        'message' => "Status do item alterado com sucesso!",
        'status_anterior' => $status_anterior,
        'status_novo' => $novo_status,
        'item_nome' => $item['produto_descricao']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>