<?php
// Arquivo: api/config/conexao.php

$host = 'localhost';
$dbname = 'cozinhapet'; // Nome do banco que você criou no phpMyAdmin
$user = 'root';         // Usuário padrão do XAMPP
$pass = '';             // Senha padrão do XAMPP é vazia

try {
    // Cria a conexão via PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    
    // Configura para que erros do banco gerem exceções no PHP (ótimo para debugar)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>