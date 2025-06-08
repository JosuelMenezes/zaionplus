<?php
// produtos/excluir.php
require_once '../config/database.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: listar.php?msg=ID do produto não fornecido&type=danger");
    exit;
}

$id = intval($_GET['id']);

// Verificar se o produto está em alguma venda
$sql_check = "SELECT COUNT(*) as total FROM itens_venda WHERE produto_id = $id";
$result_check = $conn->query($sql_check);
$has_vendas = $result_check->fetch_assoc()['total'] > 0;

if ($has_vendas) {
    header("Location: listar.php?msg=Não é possível excluir o produto pois ele está associado a vendas&type=danger");
    exit;
}

// Excluir o produto
$sql = "DELETE FROM produtos WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    header("Location: listar.php?msg=Produto excluído com sucesso!&type=success");
    exit;
} else {
    header("Location: listar.php?msg=Erro ao excluir produto: " . $conn->error . "&type=danger");
    exit;
}
?>