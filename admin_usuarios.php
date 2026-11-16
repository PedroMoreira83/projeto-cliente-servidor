<?php
require_once 'verifica_master.php'; // Apenas o Master entra aqui
require_once 'api/config/conexao.php';

$erro = ""; $sucesso = "";

// AÇÃO: REVOGAR ACESSO (O usuário volta a ser apenas um "cliente", sem acesso ao ERP)
if (isset($_GET['revogar'])) {
    $cpf_alvo = $_GET['revogar'];
    
    // Trava de segurança: O admin não pode revogar o próprio acesso
    if ($cpf_alvo === $_SESSION['admin_cpf']) {
        $erro = "Você não pode revogar o seu próprio acesso!";
    } else {
        $stmt_rev = $pdo->prepare("UPDATE clientes SET is_admin = 0, senha = NULL WHERE cpf = ?");
        $stmt_rev->execute([$cpf_alvo]);
        $sucesso = "Acesso do sistema revogado com sucesso!";
    }
}

// AÇÃO: CADASTRAR / PROMOVER NOVO USUÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $nome = $_POST['nome'];
    $senha_pura = $_POST['senha'];
    $nivel_acesso = (int)$_POST['nivel_acesso']; // 1 = Admin, 2 = Funcionário

    if (strlen($cpf) !== 11) {
        $erro = "CPF inválido. Digite apenas os 11 números.";
    } else {
        $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

        // Verifica se o CPF já existe no banco (pode ser um tutor que virou funcionário)
        $stmt_check = $pdo->prepare("SELECT cpf FROM clientes WHERE cpf = ?");
        $stmt_check->execute([$cpf]);
        
        if ($stmt_check->rowCount() > 0) {
            // Atualiza o cadastro existente para dar o acesso
            $stmt_up = $pdo->prepare("UPDATE clientes SET nome = ?, is_admin = ?, senha = ? WHERE cpf = ?");
            $stmt_up->execute([$nome, $nivel_acesso, $senha_hash, $cpf]);
            $sucesso = "Acesso concedido a um cadastro existente!";
        } else {
            // Cria um cadastro totalmente novo
            $stmt_in = $pdo->prepare("INSERT INTO clientes (cpf, nome, is_admin, senha) VALUES (?, ?, ?, ?)");
            $stmt_in->execute([$cpf, $nome, $nivel_acesso, $senha_hash]);
            $sucesso = "Novo usuário cadastrado com sucesso!";
        }
    }
}

// BUSCA LISTA DE USUÁRIOS COM ACESSO AO ERP (is_admin > 0)
$usuarios = $pdo->query("SELECT cpf, nome, is_admin FROM clientes WHERE is_admin IN (1, 2) ORDER BY is_admin, nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Equipe e Acessos</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .card-form { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #444; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-salvar { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 24px; }
        .tabela { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 40px; }
        .tabela th, .tabela td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .tabela th { background: #2c3e50; color: #fff; }
        .badge-master { background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .badge-func { background: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .btn-revogar { color: white; background: #dc3545; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.85rem; }
    </style>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
        <h2 style="color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px;">Gestão de <span style="color: #007bff;">Acessos da Equipe</span></h2>
        
        <?php if ($sucesso) echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;'>$sucesso</div>"; ?>
        <?php if ($erro) echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>$erro</div>"; ?>

        <!-- FORMULÁRIO DE NOVO ACESSO -->
        <div class="card-form">
            <h3 style="margin-top: 0; color: #444;">Conceder Novo Acesso</h3>
            <form method="POST" action="admin_usuarios.php">
                <div class="grid-3">
                    <div class="form-group"><label>Nome do Funcionário</label><input type="text" name="nome" required></div>
                    <div class="form-group"><label>CPF (Login)</label><input type="text" name="cpf" maxlength="11" required></div>
                    <div class="form-group"><label>Senha Temporária</label><input type="text" name="senha" required></div>
                </div>
                <div class="grid-3" style="margin-top: 15px;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Nível de Permissão no ERP</label>
                        <select name="nivel_acesso" required>
                            <option value="2">Funcionário Operacional (Apenas gera lote e separa insumos)</option>
                            <option value="1">Administrador Master (Acesso total aos clientes e receitas)</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-salvar">+ Adicionar / Promover Usuário</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- LISTA DE USUÁRIOS ATIVOS -->
        <h3 style="color: #333;">Usuários com Acesso Ativo</h3>
        <table class="tabela">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF (Login)</th>
                    <th>Nível de Acesso</th>
                    <th style="text-align: right;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usr): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($usr['nome']) ?></strong></td>
                        <td><?= htmlspecialchars($usr['cpf']) ?></td>
                        <td>
                            <?php if ($usr['is_admin'] == 1): ?>
                                <span class="badge-master">Admin Master</span>
                            <?php else: ?>
                                <span class="badge-func">Operacional</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <!-- O botão editar aparece para todos os usuários -->
                            <a href="admin_editar_usuario.php?cpf=<?= $usr['cpf'] ?>" style="color: #333; background: #ffc107; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.85rem; margin-right: 5px;">Editar</a>
                            
                            <!-- O botão revogar aparece apenas para os OUTROS usuários (você não revoga você mesmo) -->
                            <?php if ($usr['cpf'] !== $_SESSION['admin_cpf']): ?>
                                <a href="admin_usuarios.php?revogar=<?= $usr['cpf'] ?>" class="btn-revogar" onclick="return confirm('Tem certeza que deseja cortar o acesso de <?= htmlspecialchars($usr['nome']) ?>?');">Revogar</a>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: bold; font-size: 0.85rem; margin-left: 5px;">(Você)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>