<?php
// fornecedores/excluir.php
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Verificar se o ID do fornecedor foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Fornecedor não especificado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor_id = intval($_GET['id']);

// Buscar informações do fornecedor antes de excluir
$sql_fornecedor = "SELECT nome, empresa FROM fornecedores WHERE id = $fornecedor_id";
$result_fornecedor = $conn->query($sql_fornecedor);

if (!$result_fornecedor || $result_fornecedor->num_rows == 0) {
    $_SESSION['msg'] = "Fornecedor não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor = $result_fornecedor->fetch_assoc();

// Verificar quantos pedidos serão afetados
$sql_count = "SELECT 
              COUNT(DISTINCT pf.id) as total_pedidos,
              COUNT(DISTINCT cf.id) as total_comunicacoes,
              COUNT(DISTINCT cont.id) as total_contatos
              FROM fornecedores f
              LEFT JOIN pedidos_fornecedores pf ON f.id = pf.fornecedor_id
              LEFT JOIN comunicacoes_fornecedores cf ON f.id = cf.fornecedor_id
              LEFT JOIN contatos_fornecedores cont ON f.id = cont.fornecedor_id
              WHERE f.id = $fornecedor_id";
$result_count = $conn->query($sql_count);
$counts = $result_count->fetch_assoc();

try {
    $conn->begin_transaction();
    
    // 1. Excluir itens dos pedidos
    $conn->query("DELETE ipf FROM itens_pedido_fornecedor ipf 
                  JOIN pedidos_fornecedores pf ON ipf.pedido_id = pf.id 
                  WHERE pf.fornecedor_id = $fornecedor_id");
    
    // 2. Excluir pedidos
    $conn->query("DELETE FROM pedidos_fornecedores WHERE fornecedor_id = $fornecedor_id");
    
    // 3. Excluir comunicações
    $conn->query("DELETE FROM comunicacoes_fornecedores WHERE fornecedor_id = $fornecedor_id");
    
    // 4. Excluir contatos
    $conn->query("DELETE FROM contatos_fornecedores WHERE fornecedor_id = $fornecedor_id");
    
    // 5. Excluir associações de categorias
    $conn->query("DELETE FROM fornecedor_categorias WHERE fornecedor_id = $fornecedor_id");
    
    // 6. Finalmente, excluir o fornecedor
    $sql_delete = "DELETE FROM fornecedores WHERE id = $fornecedor_id";
    $result_delete = $conn->query($sql_delete);
    
    if ($result_delete && $conn->affected_rows > 0) {
        $conn->commit();
        
        // Preparar mensagem de sucesso detalhada
        $msg_detalhes = "Fornecedor '{$fornecedor['nome']}' excluído com sucesso!";
        if ($counts['total_pedidos'] > 0 || $counts['total_comunicacoes'] > 0 || $counts['total_contatos'] > 0) {
            $msg_detalhes .= " Também foram excluídos:";
            $itens_excluidos = [];
            
            if ($counts['total_pedidos'] > 0) {
                $itens_excluidos[] = "{$counts['total_pedidos']} pedido(s)";
            }
            if ($counts['total_comunicacoes'] > 0) {
                $itens_excluidos[] = "{$counts['total_comunicacoes']} comunicação(ões)";
            }
            if ($counts['total_contatos'] > 0) {
                $itens_excluidos[] = "{$counts['total_contatos']} contato(s)";
            }
            
            $msg_detalhes .= " " . implode(", ", $itens_excluidos) . ".";
        }
        
        $_SESSION['msg'] = $msg_detalhes;
        $_SESSION['msg_type'] = "success";
    } else {
        throw new Exception("Nenhum registro foi excluído. O fornecedor pode já ter sido removido.");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = "Erro ao excluir fornecedor: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Redirecionar para a lista
header("Location: listar.php");
exit;
?>