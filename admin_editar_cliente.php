<?php
require_once 'verifica_master.php';
require_once 'api/config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = $_POST['cpf'];
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'] ?? null;
    $cidade = $_POST['cidade'] ?? null;
    
    // Captura o checkbox: se foi marcado retorna 1, senÃ£o 0
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Captura a senha apenas se o campo foi preenchido
    $nova_senha = trim($_POST['nova_senha']);

    try {
        if (!empty($nova_senha)) {
            // Se digitou senha, criptografa e atualiza tudo
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql = "UPDATE clientes SET nome = :nome, telefone = :telefone, cidade = :cidade, is_admin = :is_admin, senha = :senha WHERE cpf = :cpf";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome, ':telefone' => $telefone, ':cidade' => $cidade, 
                ':is_admin' => $is_admin, ':senha' => $senha_hash, ':cpf' => $cpf
            ]);
        } else {
            // Se a senha ficou em branco, atualiza os dados mas mantÃ©m a senha antiga intacta
            $sql = "UPDATE clientes SET nome = :nome, telefone = :telefone, cidade = :cidade, is_admin = :is_admin WHERE cpf = :cpf";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome, ':telefone' => $telefone, ':cidade' => $cidade, 
                ':is_admin' => $is_admin, ':cpf' => $cpf
            ]);
        }

        header("Location: admin_painel.php?msg=" . urlencode("Dados do usuÃ¡rio atualizados com sucesso!"));
        exit;
    } catch (PDOException $e) {
        die("Erro ao atualizar: " . $e->getMessage());
    }
}

if (!isset($_GET['cpf'])) {
    header("Location: admin_painel.php");
    exit;
}

$cpf_atual = $_GET['cpf'];

try {
    $sql_select = "SELECT * FROM clientes WHERE cpf = :cpf";
    $stmt = $pdo->prepare($sql_select);
    $stmt->execute([':cpf' => $cpf_atual]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) die("Cliente nÃ£o encontrado.");
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar UsuÃ¡rio - ADMIN</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-admin { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; color: #444; }
        .form-group input[type="text"], .form-group input[type="password"] { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        .input-readonly { background: #e9ecef; color: #666; cursor: not-allowed; }
        .btn-salvar { background: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        .btn-cancelar { color: #dc3545; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .box-permissoes { background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 600px;">
            <h2 style="color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px;">Editar <span style="color: #007bff;">UsuÃ¡rio</span></h2>
            
            <form method="POST" action="admin_editar_cliente.php" class="form-admin">
                
                <div class="form-group">
                    <label>CPF (Identificador)</label>
                    <input type="text" name="cpf" value="<?= htmlspecialchars($cliente['cpf']) ?>" readonly class="input-readonly">
                </div>
                
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>WhatsApp / Telefone</label>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Cidade</label>
                    <input type="text" name="cidade" value="<?= htmlspecialchars($cliente['cidade'] ?? '') ?>">
                </div>

                <!-- CONTROLE DE ACESSO E SENHA -->
                <div class="box-permissoes">
                    <label style="font-weight: bold; color: #dc3545; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_admin" value="1" <?= $cliente['is_admin'] == 1 ? 'checked' : '' ?> style="transform: scale(1.2);">
                        Conceder privilÃ©gios de Administrador
                    </label>
                    
                    <div class="form-group" style="margin-top: 15px; margin-bottom: 0;">
                        <label>Redefinir Senha de Acesso</label>
                        <input type="password" name="nova_senha" placeholder="Deixe em branco para manter a atual">
                        <small style="color: #666; margin-top: 5px;">A senha serÃ¡ automaticamente criptografada antes de ir ao banco.</small>
                    </div>
                </div>
                
                <div style="margin-top: 25px; display: flex; align-items: center;">
                    <button type="submit" class="btn-salvar">Salvar AlteraÃ§Ãµes</button>
                    <a href="admin_painel.php" class="btn-cancelar">Cancelar</a>
                </div>
            </form>
        </div>
    </section>
</body>
</html>