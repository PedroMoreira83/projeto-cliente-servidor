<?php
session_start();
require_once 'api/config/conexao.php';

// Se já estiver logado, pula o login e manda pro painel
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    header("Location: admin_painel.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $senha = $_POST['senha'];

    // Busca apenas usuários que têm permissão de acesso (is_admin > 0)
    $stmt = $pdo->prepare("SELECT cpf, nome, senha, is_admin FROM clientes WHERE cpf = ? AND is_admin > 0");
    $stmt->execute([$cpf]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Sucesso! Cria as sessões exigidas pelo novo ERP
        $_SESSION['admin_logado'] = true;
        $_SESSION['admin_cpf']    = $usuario['cpf'];
        $_SESSION['admin_nome']   = $usuario['nome'];
        $_SESSION['is_admin']     = (int)$usuario['is_admin']; // 1=Master, 2=Funcionário
        
        header("Location: admin_painel.php");
        exit;
    } else {
        $erro = "Acesso negado: CPF ou Senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login Interno - ERP</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background-color: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: Arial, sans-serif; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        .login-box h2 { text-align: center; color: #333; margin-top: 0; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        .btn-login { width: 100%; background: #007bff; color: white; border: none; padding: 15px; border-radius: 4px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-login:hover { background: #0056b3; }
        .alerta { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Cozinha Pet <span style="color: #e83e8c;">_ERP</span></h2>
        
        <?php if ($erro) echo "<div class='alerta'>$erro</div>"; ?>

        <form method="POST" action="admin_login.php">
            <div class="form-group">
                <label>CPF do Usuário</label>
                <input type="text" name="cpf" maxlength="14" placeholder="Apenas números" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required>
            </div>
            <button type="submit" class="btn-login">Entrar no Sistema</button>
        </form>
    </div>
</body>
</html>