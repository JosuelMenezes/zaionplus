<?php

// Detalhes da sua conexão
$host = 'localhost';
$dbname = 'dunkac76_uzumaki';
$user = 'dunkac76_jghoste';
$pass = "382707020@'";
$charset = 'utf8mb4';

// Nomes de coluna que vamos testar
$nomes_possiveis = ['nome', 'nome_fantasia', 'razao_social', 'nome_fornecedor'];

echo "<h1>Teste de Conexão e Coluna 'fornecedores'</h1>";

// Tenta conectar ao banco
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color:green; font-weight:bold;'>✅ Conexão com o banco de dados bem-sucedida!</p>";
} catch (PDOException $e) {
    die("<p style='color:red; font-weight:bold;'>❌ Falha na conexão: " . $e->getMessage() . "</p>");
}

// Testa cada nome de coluna possível
$nome_correto_encontrado = null;
foreach ($nomes_possiveis as $nome_coluna) {
    try {
        // Tenta fazer uma consulta simples com o nome da coluna
        $stmt = $pdo->prepare("SELECT `{$nome_coluna}` FROM fornecedores LIMIT 1");
        $stmt->execute();
        echo "<p style='color:green;'>✅ Teste com a coluna '<b>{$nome_coluna}</b>' funcionou!</p>";
        $nome_correto_encontrado = $nome_coluna;
        break; // Para o loop assim que encontrar o nome certo
    } catch (PDOException $e) {
        // Se der erro, mostra que este nome não é o correto
        echo "<p style='color:red;'>❌ Teste com a coluna '<b>{$nome_coluna}</b>' falhou.</p>";
    }
}

echo "<hr>";

if ($nome_correto_encontrado) {
    echo "<h2>🎉 SUCESSO!</h2>";
    echo "<p>O nome correto da coluna na sua tabela 'fornecedores' é: <b style='font-size: 24px; background-color: #e0ffe0; padding: 5px;'>{$nome_correto_encontrado}</b></p>";
    echo "<p>Agora, use este nome para corrigir os outros arquivos.</p>";
} else {
    echo "<h2>❌ FALHA!</h2>";
    echo "<p>Nenhum dos nomes testados ('nome', 'nome_fantasia', 'razao_social') funcionou.</p>";
    echo "<p><b>Ação necessária:</b> Verifique manualmente na sua tabela 'fornecedores' no phpMyAdmin qual é o nome exato da coluna e use-o para corrigir os arquivos.</p>";
}

?>