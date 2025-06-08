<?php
// fornecedores/get_categoria.php
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Verificar se o ID da categoria foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da categoria não especificado']);
    exit;
}

$categoria_id = intval($_GET['id']);

// Buscar dados da categoria
$sql = "SELECT id, nome, descricao, cor, icone, ativo FROM categorias_fornecedores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $categoria_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Categoria não encontrada']);
    exit;
}

$categoria = $result->fetch_assoc();

// Retornar dados em JSON
header('Content-Type: application/json');
echo json_encode($categoria);
?>