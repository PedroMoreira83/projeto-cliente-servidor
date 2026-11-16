<?php
require_once 'api/config/conexao.php';

// Escolha o seu CPF de acesso e a sua senha
$cpf = '04293829440'; 
$senha = '@112233*';
$nome = 'Administrador ERP';

$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    // Insere ou atualiza o seu usuário para ter acesso Master
    $sql = "INSERT INTO clientes (cpf, nome, is_admin, senha) 
            VALUES (:cpf, :nome, 1, :senha)
            ON DUPLICATE KEY UPDATE is_admin = 1, senha = :senha, nome = :nome";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cpf' => $cpf, ':nome' => $nome, ':senha' => $hash]);

    echo "<div style='font-family: Arial; padding: 20px; text-align: center; color: #155724; background: #d4edda; border-radius: 8px; max-width: 400px; margin: 50px auto;'>
            <h2>✅ Administrador Configurado!</h2>
            <p><strong>Login (CPF):</strong> $cpf</p>
            <p><strong>Senha:</strong> $senha</p>
            <br>
            <br>
            <a href='admin_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Ir para o Login</a>
          </div>";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>