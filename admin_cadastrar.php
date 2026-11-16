<?php
require_once 'verifica_master.php';
require_once 'api/config/conexao.php';

$erro = "";

// Busca as receitas do banco para o campo de seleção
$stmt_receitas = $pdo->query("SELECT id, nome, tipo FROM receitas ORDER BY id");
$receitas_db = $stmt_receitas->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do Tutor
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']); // Limpa pontuações
    $nome_tutor = $_POST['nome_tutor'];
    $telefone = $_POST['telefone'] ?? null;
    $cidade = $_POST['cidade'] ?? null;
    
    // Dados do Pet e Dieta
    $nome_pet = $_POST['nome_pet'];
    $raca = $_POST['raca'];
    $idade = (int)$_POST['idade'];
    $peso_kg = (float)$_POST['peso_kg'];
    $qtd_porcoes = (int)$_POST['qtd_porcoes'];
    $peso_porcao_gramas = (int)$_POST['peso_porcao_gramas'];
    $proteinas_preferidas = $_POST['proteinas_preferidas'];
    $id_receita = $_POST['id_receita'];

    try {
        // Inicia a transação: ou salva os dois, ou cancela tudo
        $pdo->beginTransaction();

        // 1. Grava o Tutor
        $sql_cli = "INSERT INTO clientes (cpf, nome, telefone, cidade) VALUES (:cpf, :nome, :telefone, :cidade)
                    ON DUPLICATE KEY UPDATE nome = :nome, telefone = :telefone, cidade = :cidade";
        $stmt_cli = $pdo->prepare($sql_cli);
        $stmt_cli->execute([
            ':cpf' => $cpf, ':nome' => $nome_tutor, ':telefone' => $telefone, ':cidade' => $cidade
        ]);

        // 2. Grava o Pet associado ao Tutor
        $sql_pet = "INSERT INTO pets (cpf_cliente, nome, raca, idade, peso_kg, qtd_porcoes, peso_porcao_gramas, proteinas_preferidas, id_receita)
                    VALUES (:cpf, :nome_pet, :raca, :idade, :peso_kg, :qtd_porcoes, :peso_porcao_gramas, :pref, :id_receita)";
        $stmt_pet = $pdo->prepare($sql_pet);
        $stmt_pet->execute([
            ':cpf' => $cpf,
            ':nome_pet' => $nome_pet,
            ':raca' => $raca,
            ':idade' => $idade,
            ':peso_kg' => $peso_kg,
            ':qtd_porcoes' => $qtd_porcoes,
            ':peso_porcao_gramas' => $peso_porcao_gramas,
            ':pref' => $proteinas_preferidas,
            ':id_receita' => $id_receita
        ]);

        // Confirma no banco
        $pdo->commit();
        header("Location: admin_painel.php?msg=" . urlencode("Tutor e Pet cadastrados com sucesso!"));
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = "Erro ao salvar no banco de dados: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo Cadastro - ADMIN</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-admin { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; color: #444; }
        .form-group input, .form-group select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        .section-title { border-bottom: 2px solid #007bff; color: #333; padding-bottom: 5px; margin-top: 25px; margin-bottom: 15px; font-size: 1.2rem; }
        .btn-salvar { background: #28a745; color: white; padding: 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1.1rem; width: 100%; margin-top: 15px; }
        .btn-salvar:hover { background: #218838; }
        .alerta-erro { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; border: 1px solid #f5c6cb; }
    </style>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 800px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #333; margin: 0;">Cadastrar <span style="color: #007bff;">Novo Pacote</span></h2>
                <a href="admin_painel.php" style="color: #666; text-decoration: none; font-weight: bold;">? Voltar ao Painel</a>
            </div>
            
            <?php if ($erro): ?>
                <div class="alerta-erro"><?= $erro ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_cadastrar.php" class="form-admin">
                
                <div class="section-title">1. Dados do Tutor</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>CPF (Apenas números) *</label>
                        <input type="text" name="cpf" maxlength="11" required>
                    </div>
                    <div class="form-group">
                        <label>Nome Completo *</label>
                        <input type="text" name="nome_tutor" required>
                    </div>
                    <div class="form-group">
                        <label>WhatsApp / Telefone</label>
                        <input type="text" name="telefone">
                    </div>
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade">
                    </div>
                </div>

                <div class="section-title">2. Perfil do Pet</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nome do Pet *</label>
                        <input type="text" name="nome_pet" required>
                    </div>
                    <div class="form-group">
                        <label>Raça *</label>
                        <input type="text" name="raca" required>
                    </div>
                    <div class="form-group">
                        <label>Idade (anos) *</label>
                        <input type="number" name="idade" required>
                    </div>
                    <div class="form-group">
                        <label>Peso Corporal (kg) *</label>
                        <input type="number" name="peso_kg" step="0.1" required>
                    </div>
                </div>

                <div class="section-title" style="border-color: #28a745;">3. Configuração da Dieta (Produção)</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Receita Zootécnica Aplicada *</label>
                        <select name="id_receita" required>
                            <option value="">-- Selecione a Receita --</option>
                            <?php foreach ($receitas_db as $rec): ?>
                                <option value="<?= $rec['id'] ?>">
                                    <?= htmlspecialchars($rec['nome']) ?> (<?= strtoupper($rec['tipo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Restrição/Preferência de Proteína</label>
                        <input type="text" name="proteinas_preferidas" placeholder="Ex: Apenas Frango (ou deixe em branco)">
                    </div>
                    <div class="form-group">
                        <label>Qtd. de Marmitas no Pacote *</label>
                        <input type="number" name="qtd_porcoes" required placeholder="Ex: 30">
                    </div>
                    <div class="form-group">
                        <label>Gramas por Marmita *</label>
                        <input type="number" name="peso_porcao_gramas" required placeholder="Ex: 150">
                    </div>
                </div>

                <button type="submit" class="btn-salvar">Finalizar e Salvar no Banco</button>
            </form>
        </div>
    </section>
</body>
</html>