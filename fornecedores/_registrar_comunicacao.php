<?php
// fornecedores/_registrar_comunicacao.php
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
$usuario_id = intval($_SESSION['usuario_id']);
$data_comunicacao = isset($_POST['data_comunicacao']) ? $_POST['data_comunicacao'] : '';
$tipo = isset($_POST['tipo_comunicacao']) ? trim($_POST['tipo_comunicacao']) : '';
$resumo = isset($_POST['resumo']) ? trim($_POST['resumo']) : '';

// Validações
$erros = [];

if ($fornecedor_id <= 0) {
    $erros[] = "Fornecedor inválido";
}

if (empty($data_comunicacao)) {
    $erros[] = "Data da comunicação é obrigatória";
}

if (empty($tipo)) {
    $erros[] = "Tipo de comunicação é obrigatório";
}

if (empty($resumo)) {
    $erros[] = "Resumo da comunicação é obrigatório";
}

// Verificar se o fornecedor existe
if ($fornecedor_id > 0) {
    $sql_check = "SELECT id FROM fornecedores WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $fornecedor_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $erros[] = "Fornecedor não encontrado";
    }
}

if (!empty($erros)) {
    $_SESSION['msg'] = "Erro ao registrar comunicação: " . implode(", ", $erros);
    $_SESSION['msg_type'] = "danger";
    header("Location: detalhes.php?id=" . $fornecedor_id);
    exit;
}

try {
    // Inserir a comunicação
    $sql_insert = "INSERT INTO comunicacoes_fornecedores (
                    fornecedor_id, tipo, assunto, mensagem, data_comunicacao, usuario_id
                   ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);
    $assunto = "Comunicação via " . ucfirst($tipo);
    
    $stmt->bind_param("issssi", 
        $fornecedor_id, $tipo, $assunto, $resumo, $data_comunicacao, $usuario_id
    );
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Comunicação registrada com sucesso!";
        $_SESSION['msg_type'] = "success";
    } else {
        throw new Exception("Erro ao inserir no banco: " . $stmt->error);
    }
    
} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao registrar comunicação: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Redirecionar de volta para a página de detalhes
header("Location: detalhes.php?id=" . $fornecedor_id);
exit;
?>