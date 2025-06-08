<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] != 'admin') {
    $_SESSION['msg'] = "Acesso não autorizado!";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['msg'] = "ID do pagamento não fornecido!";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$id = (int)$_GET['id'];

// Obter informações do pagamento antes de excluir
$sql_info = "SELECT p.*, v.id as venda_id, v.status as venda_status 
            FROM pagamentos p 
            JOIN vendas v ON p.venda_id = v.id 
            WHERE p.id = $id";
$result_info = $conn->query($sql_info);

if ($result_info->num_rows == 0) {
    $_SESSION['msg'] = "Pagamento não encontrado!";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$pagamento = $result_info->fetch_assoc();
$venda_id = $pagamento['venda_id'];
$valor_pagamento = $pagamento['valor'];

// Iniciar transação
$conn->begin_transaction();

try {
    // Excluir o pagamento
    $sql_delete = "DELETE FROM pagamentos WHERE id = $id";
    if (!$conn->query($sql_delete)) {
        throw new Exception("Erro ao excluir pagamento: " . $conn->error);
    }
    
    // Verificar se precisa atualizar o status da venda
    if ($pagamento['venda_status'] == 'pago') {
        // Verificar se ainda existem pagamentos para esta venda
        $sql_check = "SELECT SUM(valor) as total_pago FROM pagamentos WHERE venda_id = $venda_id";
        $result_check = $conn->query($sql_check);
        $total_pago = $result_check->fetch_assoc()['total_pago'] ?? 0;
        
        // Obter o valor total da venda
        $sql_venda = "SELECT SUM(quantidade * valor_unitario) as total FROM itens_venda WHERE venda_id = $venda_id";
        $result_venda = $conn->query($sql_venda);
        $total_venda = $result_venda->fetch_assoc()['total'] ?? 0;
        
        // Se o total pago for menor que o total da venda, mudar status para 'aberto'
        if ($total_pago < $total_venda) {
            $sql_update = "UPDATE vendas SET status = 'aberto' WHERE id = $venda_id";
            if (!$conn->query($sql_update)) {
                throw new Exception("Erro ao atualizar status da venda: " . $conn->error);
            }
        }
    }
    
    // Commit da transação
    $conn->commit();
    
    $_SESSION['msg'] = "Pagamento excluído com sucesso!";
    $_SESSION['msg_type'] = "success";
    
} catch (Exception $e) {
    // Rollback em caso de erro
    $conn->rollback();
    
    $_SESSION['msg'] = $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Redirecionar de volta para a listagem
header("Location: listar.php");
exit;
?>