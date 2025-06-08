<?php
// funcionarios/inativar.php - Sistema Premium de Funcionários
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../config/database.php';

// Verificar se foi passado um ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['msg'] = "Funcionário não encontrado.";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$funcionario_id = (int)$_GET['id'];

try {
    // Buscar dados do funcionário
    $sql = "SELECT nome, status FROM funcionarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $funcionario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['msg'] = "Funcionário não encontrado.";
        $_SESSION['msg_type'] = "danger";
        header("Location: listar.php");
        exit;
    }
    
    $funcionario = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar se já está inativo
    if ($funcionario['status'] === 'inativo') {
        $_SESSION['msg'] = "Funcionário '{$funcionario['nome']}' já está inativo.";
        $_SESSION['msg_type'] = "warning";
        header("Location: listar.php");
        exit;
    }
    
    // Inativar o funcionário
    $sql_update = "UPDATE funcionarios SET status = 'inativo' WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $funcionario_id);
    
    if ($stmt_update->execute()) {
        $stmt_update->close();
        
        $_SESSION['msg'] = "Funcionário '{$funcionario['nome']}' foi inativado com sucesso.";
        $_SESSION['msg_type'] = "success";
    } else {
        throw new Exception("Erro ao inativar funcionário: " . $conn->error);
    }
    
} catch (Exception $e) {
    $_SESSION['msg'] = $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Redirecionar de volta para a listagem
header("Location: listar.php");
exit;
?>