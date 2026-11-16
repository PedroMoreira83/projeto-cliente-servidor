<?php
// Primeiro garante que o usuário está logado
require_once 'verifica_admin.php';

// Depois verifica se ele é o Administrador Master (is_admin = 1 no banco)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("
    <div style='padding: 40px; font-family: Arial, sans-serif; text-align: center; background: #f8d7da; color: #721c24; height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center;'>
        <h2 style='font-size: 2rem; margin-bottom: 10px;'>Acesso Restrito ⛔</h2>
        <p style='font-size: 1.2rem; margin-bottom: 20px;'>Apenas o administrador do sistema pode adicionar, editar ou remover cadastros, clientes e receitas.</p>
        <a href='lote_semanal.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>← Voltar ao Lote Semanal</a>
    </div>
    ");
}
?>