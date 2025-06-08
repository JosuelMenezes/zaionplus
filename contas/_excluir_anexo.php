<?php
// contas/_excluir_anexo.php
// API para excluir anexos

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = "Método não permitido.";
    $_SESSION['msg_type'] = "error";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

try {
    $anexo_id = (int)$_POST['anexo_id'];
    
    // Buscar anexo
    $sql = "SELECT * FROM anexos_contas WHERE id = :id";
    $stmt = $pdo_connection->prepare($sql);
    $stmt->execute([':id' => $anexo_id]);
    $anexo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anexo) {
        throw new Exception('Anexo não encontrado');
    }
    
    // Excluir arquivo físico
    if (file_exists($anexo['caminho'])) {
        unlink($anexo['caminho']);
    }
    
    // Excluir do banco
    $sql_delete = "DELETE FROM anexos_contas WHERE id = :id";
    $stmt_delete = $pdo_connection->prepare($sql_delete);
    $stmt_delete->execute([':id' => $anexo_id]);
    
    $_SESSION['msg'] = "Anexo excluído com sucesso!";
    $_SESSION['msg_type'] = "success";
    
} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao excluir anexo: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
?>