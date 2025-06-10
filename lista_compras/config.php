<?php
// Utilizar a configuração principal de banco de dados
require_once __DIR__ . '/../config/database.php';

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