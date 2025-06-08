<?php
// clientes/excluir.php
require_once '../config/database.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: listar.php?msg=ID do cliente não fornecido&type=danger");
    exit;
}

$id = intval($_GET['id']);

// Verificar se o cliente tem vendas associadas
$sql_check = "SELECT COUNT(*) as total FROM vendas WHERE cliente_id = $id";
$result_check = $conn->query($sql_check);
$has_vendas = $result_check->fetch_assoc()['total'] > 0;

if ($has_vendas) {
    header("Location: listar.php?msg=Não é possível excluir o cliente pois existem vendas associadas a ele&type=danger");
    exit;
}

// Excluir o cliente
$sql = "DELETE FROM clientes WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    header("Location: listar.php?msg=Cliente excluído com sucesso!&type=success");
    exit;
} else {
    header("Location: listar.php?msg=Erro ao excluir cliente: " . $conn->error . "&type=danger");
    exit;
}
?>