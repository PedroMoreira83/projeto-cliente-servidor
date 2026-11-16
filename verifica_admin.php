<?php
// Arquivo: verifica_admin.php
session_start();

// Se a variável de sessão não existir ou não for true, redireciona para o login
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: admin_login.php");
    exit;
}
?>