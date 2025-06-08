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
    
    $envio_id = (int)($input['envio_id'] ?? 0);
    $novo_status = $input['status'] ?? '';
    $valor_cotacao = floatval($input['valor_cotacao'] ?? 0);
    $observacoes = trim($input['observacoes'] ?? '');

    if (!$envio_id) {
        throw new Exception('ID do envio é obrigatório');
    }

    $status_validos = ['enviado', 'visualizado', 'respondido', 'cotacao_recebida', 'sem_resposta'];
    if (!in_array($novo_status, $status_validos)) {
        throw new Exception('Status inválido. Use: ' . implode(', ', $status_validos));
    }

    // Verificar se o envio existe
    $sql_envio = "SELECT e.*, f.nome as fornecedor_nome, l.nome as lista_nome
                  FROM envios_lista_fornecedores e
                  JOIN fornecedores f ON e.fornecedor_id = f.id
                  JOIN listas_compras l ON e.lista_id = l.id
                  WHERE e.id = ?";
    $stmt_envio = $pdo->prepare($sql_envio);
    $stmt_envio->execute([$envio_id]);
    $envio = $stmt_envio->fetch();

    if (!$envio) {
        throw new Exception('Envio não encontrado');
    }

    $status_anterior = $envio['status_resposta'];

    // Preparar dados para atualização
    $dados_update = [
        'status_resposta' => $novo_status,
        'data_resposta' => ($novo_status != 'enviado') ? date('Y-m-d H:i:s') : null
    ];

    // Se recebeu cotação, incluir valor
    if ($novo_status == 'cotacao_recebida' && $valor_cotacao > 0) {
        $dados_update['valor_cotacao'] = $valor_cotacao;
    }

    // Se tem observações, incluir
    if ($observacoes) {
        $dados_update['observacoes_fornecedor'] = $observacoes;
    }

    // Construir SQL dinamicamente
    $campos = [];
    $valores = [];
    foreach ($dados_update as $campo => $valor) {
        if ($valor !== null) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
    }
    
    $valores[] = $envio_id; // Para o WHERE

    $sql_update = "UPDATE envios_lista_fornecedores SET " . implode(', ', $campos) . " WHERE id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute($valores);

    // Registrar no histórico
    $descricao_historico = "Status do envio para '{$envio['fornecedor_nome']}' alterado de '{$status_anterior}' para '{$novo_status}'";
    if ($valor_cotacao > 0) {
        $descricao_historico .= " - Valor cotado: R$ " . number_format($valor_cotacao, 2, ',', '.');
    }

    $sql_historico = "INSERT INTO historico_listas_compras (lista_id, acao, descricao, usuario_id) 
                      VALUES (?, 'envio_atualizado', ?, ?)";
    $stmt_historico = $pdo->prepare($sql_historico);
    $stmt_historico->execute([
        $envio['lista_id'],
        $descricao_historico,
        $_SESSION['usuario_id']
    ]);

    // Se status mudou para "em_cotacao", atualizar status da lista se necessário
    if ($novo_status == 'cotacao_recebida') {
        $sql_lista_status = "SELECT status FROM listas_compras WHERE id = ?";
        $stmt_lista_status = $pdo->prepare($sql_lista_status);
        $stmt_lista_status->execute([$envio['lista_id']]);
        $lista_status = $stmt_lista_status->fetchColumn();

        if ($lista_status == 'enviada') {
            $sql_update_lista = "UPDATE listas_compras SET status = 'em_cotacao' WHERE id = ?";
            $stmt_update_lista = $pdo->prepare($sql_update_lista);
            $stmt_update_lista->execute([$envio['lista_id']]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Status do envio atualizado com sucesso!",
        'status_anterior' => $status_anterior,
        'status_novo' => $novo_status,
        'fornecedor_nome' => $envio['fornecedor_nome'],
        'valor_cotacao' => $valor_cotacao
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>