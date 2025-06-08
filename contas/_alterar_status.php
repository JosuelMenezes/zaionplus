<?php
// contas/_alterar_status.php
// API para alterar status de contas

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = "Método não permitido.";
    $_SESSION['msg_type'] = "error";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

try {
    $conta_id = (int)$_POST['conta_id'];
    $novo_status = $_POST['novo_status'];
    
    // Validar status
    $status_permitidos = ['pendente', 'pago_parcial', 'pago', 'vencido', 'cancelado'];
    if (!in_array($novo_status, $status_permitidos)) {
        throw new Exception('Status inválido');
    }
    
    // Buscar conta
    $sql = "SELECT * FROM contas WHERE id = :id";
    $stmt = $pdo_connection->prepare($sql);
    $stmt->execute([':id' => $conta_id]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conta) {
        throw new Exception('Conta não encontrada');
    }
    
    // Atualizar status
    $sql_update = "UPDATE contas SET status = :status WHERE id = :id";
    $stmt_update = $pdo_connection->prepare($sql_update);
    $stmt_update->execute([
        ':status' => $novo_status,
        ':id' => $conta_id
    ]);
    
    $_SESSION['msg'] = "Status alterado com sucesso!";
    $_SESSION['msg_type'] = "success";
    
} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao alterar status: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
?>