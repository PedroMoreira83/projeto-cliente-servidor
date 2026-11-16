<?php
require_once 'verifica_master.php';
require_once 'api/config/conexao.php';

if (!isset($_GET['cpf'])) {
    header("Location: admin_usuarios.php");
    exit;
}

$cpf_alvo = preg_replace('/[^0-9]/', '', $_GET['cpf']);
$erro = ""; $sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $nivel_acesso = (int)$_POST['nivel_acesso'];
    $nova_senha = $_POST['senha'];

    // Trava de segurança: O Admin não pode rebaixar a si mesmo para não perder o sistema!
    if ($cpf_alvo === $_SESSION['admin_cpf'] && $nivel_acesso !== 1) {
        $erro = "Trava de Segurança: Você não pode rebaixar o seu próprio nível de acesso.";
        $nivel_acesso = 1; // Força a manter como Master
    }

    if (empty($erro)) {
        try {
            if (!empty($nova_senha)) {
                // Atualiza TUDO, incluindo a senha nova
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, is_admin = ?, senha = ? WHERE cpf = ?");
                $stmt->execute([$nome, $nivel_acesso, $senha_hash, $cpf_alvo]);
            } else {
                // Atualiza apenas os dados, mantendo a senha intacta
                $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, is_admin = ? WHERE cpf = ?");
                $stmt->execute([$nome, $nivel_acesso, $cpf_alvo]);
            }

            // Se você estiver editando o seu próprio usuário, atualiza a sua tag no menu em tempo real
            if ($cpf_alvo === $_SESSION['admin_cpf']) {
                $_SESSION['admin_nome'] = $nome;
            }

            $sucesso = "Usuário atualizado com sucesso!";
        } catch (Exception $e) {
            $erro = "Erro ao atualizar usuário: " . $e->getMessage();
        }
    }
}

// Busca os dados do usuário para preencher o formulário
$stmt = $pdo->prepare("SELECT cpf, nome, is_admin FROM clientes WHERE cpf = ?");
$stmt->execute([$cpf_alvo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) die("Usuário não encontrado.");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .box-edit { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #444; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-salvar { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; }
        .btn-salvar:hover { background: #0056b3; }
    </style>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; max-width: 700px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="color: #333; margin: 0;">Editar <span style="color: #007bff;">Usuário</span></h2>
            <a href="admin_usuarios.php" style="color: #666; text-decoration: none; font-weight: bold;">← Voltar para Equipe</a>
        </div>

        <?php if ($sucesso) echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;'>$sucesso</div>"; ?>
        <?php if ($erro) echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>$erro</div>"; ?>

        <form method="POST" action="admin_editar_usuario.php?cpf=<?= htmlspecialchars($cpf_alvo) ?>" class="box-edit">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label>CPF (Login)</label>
                <input type="text" value="<?= htmlspecialchars($usuario['cpf']) ?>" disabled style="background: #e9ecef; color: #666;">
                <small style="color: #888;">O CPF é o identificador único e não pode ser alterado.</small>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Nome do Funcionário</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Nível de Acesso</label>
                    <select name="nivel_acesso" required>
                        <option value="2" <?= ($usuario['is_admin'] == 2) ? 'selected' : '' ?>>Operacional (Apenas Lote)</option>
                        <option value="1" <?= ($usuario['is_admin'] == 1) ? 'selected' : '' ?>>Administrador Master</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nova Senha</label>
                    <input type="text" name="senha" placeholder="Deixe em branco para manter a mesma">
                </div>
            </div>

            <button type="submit" class="btn-salvar">Salvar Alterações</button>
        </form>
    </section>
</body>
</html>