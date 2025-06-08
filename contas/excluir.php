<?php
// contas/excluir.php
// Página para excluir contas

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

try {
    $conta_id = (int)$_POST['id'];
    
    // Verificar se conta existe
    $sql = "SELECT * FROM contas WHERE id = :id";
    $stmt = $pdo_connection->prepare($sql);
    $stmt->execute([':id' => $conta_id]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conta) {
        throw new Exception('Conta não encontrada');
    }
    
    $pdo_connection->beginTransaction();
    
    // Excluir anexos físicos
    $sql_anexos = "SELECT caminho FROM anexos_contas WHERE conta_id = :conta_id";
    $stmt_anexos = $pdo_connection->prepare($sql_anexos);
    $stmt_anexos->execute([':conta_id' => $conta_id]);
    $anexos = $stmt_anexos->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($anexos as $anexo) {
        if (file_exists($anexo['caminho'])) {
            unlink($anexo['caminho']);
        }
    }
    
    // Excluir dados do banco (cascata automática)
    $sql_delete = "DELETE FROM contas WHERE id = :id";
    $stmt_delete = $pdo_connection->prepare($sql_delete);
    $stmt_delete->execute([':id' => $conta_id]);
    
    $pdo_connection->commit();
    
    $_SESSION['msg'] = "Conta excluída com sucesso!";
    $_SESSION['msg_type'] = "success";
    
} catch (Exception $e) {
    $pdo_connection->rollBack();
    $_SESSION['msg'] = "Erro ao excluir conta: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
}

header("Location: index.php");
exit;
?>