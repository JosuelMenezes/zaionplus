<?php
// contas/_upload_anexo.php
// API para upload de anexos

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $conta_id = (int)$_POST['conta_id'];
    
    // Verificar se conta existe
    $sql = "SELECT id FROM contas WHERE id = :id";
    $stmt = $pdo_connection->prepare($sql);
    $stmt->execute([':id' => $conta_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Conta não encontrada');
    }
    
    // Verificar arquivo
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo');
    }
    
    $arquivo = $_FILES['arquivo'];
    
    // Validações
    if (!isArquivoPermitido($arquivo['name'])) {
        throw new Exception('Tipo de arquivo não permitido');
    }
    
    if ($arquivo['size'] > CONTAS_MAX_FILE_SIZE) {
        throw new Exception('Arquivo muito grande (máx. 5MB)');
    }
    
    // Gerar nome único
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nome_arquivo = 'anexo_' . $conta_id . '_' . uniqid() . '.' . $extensao;
    $caminho_completo = CONTAS_UPLOAD_PATH . $nome_arquivo;
    
    // Mover arquivo
    if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
        throw new Exception('Erro ao salvar arquivo');
    }
    
    // Salvar no banco
    $sql_anexo = "
        INSERT INTO anexos_contas (
            conta_id, nome_arquivo, nome_original, tipo_arquivo, 
            tamanho, caminho, usuario_upload
        ) VALUES (
            :conta_id, :nome_arquivo, :nome_original, :tipo_arquivo,
            :tamanho, :caminho, :usuario_upload
        )
    ";
    
    $stmt_anexo = $pdo_connection->prepare($sql_anexo);
    $stmt_anexo->execute([
        ':conta_id' => $conta_id,
        ':nome_arquivo' => $nome_arquivo,
        ':nome_original' => $arquivo['name'],
        ':tipo_arquivo' => $arquivo['type'],
        ':tamanho' => $arquivo['size'],
        ':caminho' => $caminho_completo,
        ':usuario_upload' => $_SESSION['usuario_id']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Anexo enviado com sucesso',
        'arquivo' => $nome_arquivo
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>