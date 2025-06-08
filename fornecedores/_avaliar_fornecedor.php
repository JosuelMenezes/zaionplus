<?php
// fornecedores/_avaliar_fornecedor.php
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['msg'] = "Usuário não logado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = "Método não permitido";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor_id = isset($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : 0;
$avaliacao = isset($_POST['avaliacao']) ? floatval($_POST['avaliacao']) : 0;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
$usuario_id = intval($_SESSION['usuario_id']);

// Validações
$erros = [];

if ($fornecedor_id <= 0) {
    $erros[] = "Fornecedor inválido";
}

if ($avaliacao < 1 || $avaliacao > 5) {
    $erros[] = "Avaliação deve estar entre 1 e 5";
}

// Verificar se o fornecedor existe
if ($fornecedor_id > 0) {
    $sql_check = "SELECT id, avaliacao FROM fornecedores WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $fornecedor_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $erros[] = "Fornecedor não encontrado";
    }
}

if (!empty($erros)) {
    $_SESSION['msg'] = "Erro ao avaliar fornecedor: " . implode(", ", $erros);
    $_SESSION['msg_type'] = "danger";
    header("Location: detalhes.php?id=" . $fornecedor_id);
    exit;
}

try {
    // Iniciar transação
    $conn->begin_transaction();
    
    // Atualizar a avaliação do fornecedor
    $sql_update = "UPDATE fornecedores SET avaliacao = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("di", $avaliacao, $fornecedor_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Erro ao atualizar avaliação: " . $stmt_update->error);
    }
    
    // Se há comentário, registrar como comunicação
    if (!empty($comentario)) {
        $sql_comentario = "INSERT INTO comunicacoes_fornecedores (
                           fornecedor_id, tipo, assunto, mensagem, data_comunicacao, usuario_id
                          ) VALUES (?, 'outro', 'Avaliação do fornecedor', ?, NOW(), ?)";
        
        $stmt_comentario = $conn->prepare($sql_comentario);
        $mensagem_comentario = "Avaliação: " . $avaliacao . " estrelas\nComentário: " . $comentario;
        
        $stmt_comentario->bind_param("isi", 
            $fornecedor_id, $mensagem_comentario, $usuario_id
        );
        
        if (!$stmt_comentario->execute()) {
            throw new Exception("Erro ao registrar comentário: " . $stmt_comentario->error);
        }
    }
    
    // Confirmar transação
    $conn->commit();
    
    $_SESSION['msg'] = "Avaliação de " . $avaliacao . " estrelas registrada com sucesso!";
    $_SESSION['msg_type'] = "success";
    
} catch (Exception $e) {
    // Reverter transação
    $conn->rollback();
    $_SESSION['msg'] = "Erro ao avaliar fornecedor: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Redirecionar de volta para a página de detalhes
header("Location: detalhes.php?id=" . $fornecedor_id);
exit;
?>