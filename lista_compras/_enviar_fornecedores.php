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
    $lista_id = (int)($_POST['lista_id'] ?? 0);
    $fornecedores = $_POST['fornecedores'] ?? [];
    $meio_envio = $_POST['meio_envio'] ?? 'whatsapp';
    $prazo_resposta = $_POST['prazo_resposta'] ?? null;
    $observacoes = trim($_POST['observacoes_envio'] ?? '');

    if (!$lista_id) {
        throw new Exception('ID da lista é obrigatório');
    }

    if (empty($fornecedores)) {
        throw new Exception('Selecione pelo menos um fornecedor');
    }

    // Verificar se a lista existe
    $sql_lista = "SELECT id, nome, status FROM listas_compras WHERE id = ?";
    $stmt_lista = $pdo->prepare($sql_lista);
    $stmt_lista->execute([$lista_id]);
    $lista = $stmt_lista->fetch();

    if (!$lista) {
        throw new Exception('Lista de compras não encontrada');
    }

    $pdo->beginTransaction();

    $envios_realizados = 0;
    $sql_inserir = "INSERT INTO envios_lista_fornecedores 
                    (lista_id, fornecedor_id, meio_envio, prazo_resposta, observacoes_fornecedor, usuario_envio) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    data_envio = CURRENT_TIMESTAMP,
                    meio_envio = VALUES(meio_envio),
                    prazo_resposta = VALUES(prazo_resposta),
                    observacoes_fornecedor = VALUES(observacoes_fornecedor),
                    status_resposta = 'enviado'";
    
    $stmt_inserir = $pdo->prepare($sql_inserir);

    foreach ($fornecedores as $fornecedor_id) {
        $fornecedor_id = (int)$fornecedor_id;
        
        // Verificar se o fornecedor existe
        $sql_fornecedor = "SELECT id, nome FROM fornecedores WHERE id = ? AND status = 'ativo'";
        $stmt_fornecedor = $pdo->prepare($sql_fornecedor);
        $stmt_fornecedor->execute([$fornecedor_id]);
        
        if ($stmt_fornecedor->fetch()) {
            $stmt_inserir->execute([
                $lista_id,
                $fornecedor_id,
                $meio_envio,
                $prazo_resposta ?: null,
                $observacoes,
                $_SESSION['usuario_id']
            ]);
            $envios_realizados++;
        }
    }

    // Atualizar status da lista se necessário
    if ($lista['status'] == 'rascunho' && $envios_realizados > 0) {
        $sql_atualizar = "UPDATE listas_compras SET status = 'enviada' WHERE id = ?";
        $stmt_atualizar = $pdo->prepare($sql_atualizar);
        $stmt_atualizar->execute([$lista_id]);
    }

    // Registrar no histórico
    $sql_historico = "INSERT INTO historico_listas_compras (lista_id, acao, descricao, usuario_id) 
                      VALUES (?, 'envio_fornecedores', ?, ?)";
    $stmt_historico = $pdo->prepare($sql_historico);
    $stmt_historico->execute([
        $lista_id,
        "Lista enviada para {$envios_realizados} fornecedor(es) via {$meio_envio}",
        $_SESSION['usuario_id']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Lista enviada para {$envios_realizados} fornecedor(es) com sucesso!",
        'envios_realizados' => $envios_realizados
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>