<?php
// Configuração do banco de dados para Lista de Compras
// Usando suas credenciais existentes

$host = 'localhost';
$username = 'dunkac76_jghoste';
$password = "382707020@'";
$database = 'dunkac76_uzumaki';

// Conectar usando MySQLi (como seu sistema existente)
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir charset
$conn->set_charset("utf8");

// Criar conexão PDO para compatibilidade com o módulo Lista de Compras
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão PDO: " . $e->getMessage() . "<br><br>
    <strong>Verifique:</strong><br>
    1. Se o MySQL está rodando<br>
    2. Se as credenciais estão corretas<br>
    3. Se as tabelas da Lista de Compras foram criadas");
}

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>