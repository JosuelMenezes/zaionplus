<?php
// funcionarios/extras_conceder.php - Processar concessão de extras
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir que é uma resposta JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas administradores podem conceder extras.'
    ]);
    exit;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ]);
    exit;
}

// Incluir arquivo de conexão
require_once '../config/database.php';

try {
    // Obter dados do formulário
    $funcionario_id = (int)($_POST['funcionario_id'] ?? 0);
    $extra_tipo_id = (int)($_POST['extra_tipo_id'] ?? 0);
    $valor = (float)($_POST['valor'] ?? 0);
    $mes_referencia = $_POST['mes_referencia'] ?? '';
    $observacao = trim($_POST['observacao'] ?? '');
    $senha_admin = $_POST['senha_admin'] ?? '';
    
    // Validações básicas
    if (empty($funcionario_id) || empty($extra_tipo_id) || $valor <= 0 || empty($mes_referencia) || empty($senha_admin)) {
        throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
    }
    
    if ($valor > 9999.99) {
        throw new Exception('O valor do extra não pode ser superior a R$ 9.999,99.');
    }
    
    // Verificar senha do administrador
    $sql_user = "SELECT senha FROM usuarios WHERE id = ? AND nivel_acesso = 'admin'";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $_SESSION['usuario_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($result_user->num_rows === 0) {
        throw new Exception('Usuário administrador não encontrado.');
    }
    
    $usuario = $result_user->fetch_assoc();
    $stmt_user->close();
    
    // Verificar senha (assumindo que está hashada com password_hash)
    if (!password_verify($senha_admin, $usuario['senha'])) {
        throw new Exception('Senha de administrador incorreta.');
    }
    
    // Verificar se o funcionário existe e está ativo
    $sql_func = "SELECT nome, status FROM funcionarios WHERE id = ?";
    $stmt_func = $conn->prepare($sql_func);
    $stmt_func->bind_param("i", $funcionario_id);
    $stmt_func->execute();
    $result_func = $stmt_func->get_result();
    
    if ($result_func->num_rows === 0) {
        throw new Exception('Funcionário não encontrado.');
    }
    
    $funcionario = $result_func->fetch_assoc();
    $stmt_func->close();
    
    if ($funcionario['status'] !== 'ativo') {
        throw new Exception('Não é possível conceder extras para funcionários inativos.');
    }
    
    // Verificar se o tipo de extra existe e está ativo
    $sql_tipo = "SELECT nome, ativo FROM extras_tipos WHERE id = ?";
    $stmt_tipo = $conn->prepare($sql_tipo);
    $stmt_tipo->bind_param("i", $extra_tipo_id);
    $stmt_tipo->execute();
    $result_tipo = $stmt_tipo->get_result();
    
    if ($result_tipo->num_rows === 0) {
        throw new Exception('Tipo de extra não encontrado.');
    }
    
    $tipo_extra = $result_tipo->fetch_assoc();
    $stmt_tipo->close();
    
    if (!$tipo_extra['ativo']) {
        throw new Exception('Este tipo de extra está inativo e não pode ser concedido.');
    }
    
    // Formatar mês de referência
    $mes_referencia_formatado = $mes_referencia . '-01';
    
    // Verificar se já existe este extra para o funcionário no mês
    $sql_check = "SELECT id FROM funcionarios_extras WHERE funcionario_id = ? AND extra_tipo_id = ? AND mes_referencia = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("iis", $funcionario_id, $extra_tipo_id, $mes_referencia_formatado);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        throw new Exception('Este funcionário já possui este tipo de extra para o mês selecionado.');
    }
    $stmt_check->close();
    
    // Inserir o extra
    $sql_insert = "INSERT INTO funcionarios_extras (
        funcionario_id, extra_tipo_id, valor, mes_referencia, 
        observacao, concedido_por, senha_verificada
    ) VALUES (?, ?, ?, ?, ?, ?, TRUE)";
    
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iidssi", 
        $funcionario_id, $extra_tipo_id, $valor, $mes_referencia_formatado, 
        $observacao, $_SESSION['usuario_id']
    );
    
    if ($stmt_insert->execute()) {
        $extra_id = $conn->insert_id;
        $stmt_insert->close();
        
        // Log da ação (opcional - pode criar uma tabela de logs)
        error_log("EXTRA CONCEDIDO: Admin ID " . $_SESSION['usuario_id'] . 
                 " concedeu R$ " . number_format($valor, 2, ',', '.') . 
                 " para funcionário ID " . $funcionario_id . 
                 " (Tipo: " . $tipo_extra['nome'] . ", Mês: " . $mes_referencia . ")");
        
        echo json_encode([
            'success' => true,
            'message' => "Extra de R$ " . number_format($valor, 2, ',', '.') . 
                        " concedido com sucesso para " . $funcionario['nome'] . "!",
            'extra_id' => $extra_id
        ]);
        
    } else {
        throw new Exception('Erro ao inserir extra no banco de dados: ' . $stmt_insert->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>