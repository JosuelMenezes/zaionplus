<?php
// Desativar o buffer de saída para ver os resultados imediatamente
ob_implicit_flush(true);
ob_end_flush();

echo "<h1>Teste de Listagem de Clientes</h1>";

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<p>Sessão iniciada</p>";

// Incluir arquivo de conexão com o banco de dados
require_once '../config/database.php';

echo "<p>Arquivo de conexão incluído</p>";

// Verificar a variável de conexão
if (isset($conn)) {
    echo "<p>Variável de conexão \$conn encontrada</p>";
} else {
    echo "<p>Variável de conexão \$conn NÃO encontrada</p>";
    exit;
}

// Consulta simples para obter todos os clientes
$sql = "SELECT * FROM clientes ORDER BY nome ASC LIMIT 10";
echo "<p>SQL: $sql</p>";

$result = $conn->query($sql);

if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
    exit;
}

echo "<p>Consulta executada com sucesso</p>";
echo "<p>Total de registros: " . $result->num_rows . "</p>";

// Exibir os clientes em uma tabela simples
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nome</th><th>Telefone</th><th>Empresa</th></tr>";

while ($cliente = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $cliente['id'] . "</td>";
    echo "<td>" . htmlspecialchars($cliente['nome']) . "</td>";
    echo "<td>" . htmlspecialchars($cliente['telefone']) . "</td>";
    echo "<td>" . htmlspecialchars($cliente['empresa']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Verificar o conteúdo do arquivo header.php
echo "<h2>Verificando o arquivo header.php</h2>";
$header_file = '../includes/header.php';

if (file_exists($header_file)) {
    echo "<p>Arquivo header.php encontrado</p>";
    
    // Exibir as primeiras 20 linhas do arquivo
    $header_content = file_get_contents($header_file);
    $header_lines = explode("\n", $header_content);
    $header_preview = array_slice($header_lines, 0, 20);
    
    echo "<pre>";
    echo htmlspecialchars(implode("\n", $header_preview)) . "\n...";
    echo "</pre>";
} else {
    echo "<p>Arquivo header.php NÃO encontrado</p>";
}

// Verificar o conteúdo do arquivo footer.php
echo "<h2>Verificando o arquivo footer.php</h2>";
$footer_file = '../includes/footer.php';

if (file_exists($footer_file)) {
    echo "<p>Arquivo footer.php encontrado</p>";
    
    // Exibir as primeiras 10 linhas do arquivo
    $footer_content = file_get_contents($footer_file);
    $footer_lines = explode("\n", $footer_content);
    $footer_preview = array_slice($footer_lines, 0, 10);
    
    echo "<pre>";
    echo htmlspecialchars(implode("\n", $footer_preview)) . "\n...";
    echo "</pre>";
} else {
    echo "<p>Arquivo footer.php NÃO encontrado</p>";
}

// Verificar o conteúdo do arquivo listar.php atual
echo "<h2>Verificando o arquivo listar.php atual</h2>";
$listar_file = 'listar.php';

if (file_exists($listar_file)) {
    echo "<p>Arquivo listar.php encontrado</p>";
    
    // Exibir as primeiras 30 linhas do arquivo
    $listar_content = file_get_contents($listar_file);
    $listar_lines = explode("\n", $listar_content);
    $listar_preview = array_slice($listar_lines, 0, 30);
    
    echo "<pre>";
    echo htmlspecialchars(implode("\n", $listar_preview)) . "\n...";
    echo "</pre>";
} else {
    echo "<p>Arquivo listar.php NÃO encontrado</p>";
}

echo "<h2>Teste concluído</h2>";
?>