<?php
require_once 'verifica_master.php';
require_once 'api/config/conexao.php';

if (!isset($_GET['id_receita'])) { header("Location: admin_painel.php"); exit; }
$id_receita = (int)$_GET['id_receita'];

// AÇÕES DO FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // AÇÃO 1: CADASTRAR NOVO INSUMO NO BANCO
    if (isset($_POST['acao']) && $_POST['acao'] === 'novo_insumo') {
        $nome_novo = $_POST['nome_insumo_novo'];
        $categoria_novo = $_POST['categoria_novo'];
        
        $stmt_add = $pdo->prepare("INSERT INTO insumos (nome, categoria) VALUES (?, ?)");
        $stmt_add->execute([$nome_novo, $categoria_novo]);
        $id_inserido = $pdo->lastInsertId();
        
        // Já joga o insumo recém-criado direto na dieta do cão
        $qtd_novo = (float)$_POST['qtd_novo'];
        $stmt_vincula = $pdo->prepare("INSERT INTO receita_composicao (id_receita, id_insumo, quantidade_gramas) VALUES (?, ?, ?)");
        $stmt_vincula->execute([$id_receita, $id_inserido, $qtd_novo]);
    }
    
    // AÇÃO 2: ADICIONAR INSUMO EXISTENTE VIA DROPDOWN
    if (isset($_POST['acao']) && $_POST['acao'] === 'adicionar_existente') {
        $id_insumo = (int)$_POST['id_insumo'];
        $quantidade = (float)$_POST['quantidade_gramas'];
        
        $stmt_add = $pdo->prepare("INSERT INTO receita_composicao (id_receita, id_insumo, quantidade_gramas) VALUES (?, ?, ?)");
        $stmt_add->execute([$id_receita, $id_insumo, $quantidade]);
    }

    // AÇÃO 3: EXCLUIR ITEM DA DIETA
    if (isset($_POST['acao']) && $_POST['acao'] === 'excluir_item') {
        $id_insumo_del = (int)$_POST['id_insumo_del'];
        $stmt_del = $pdo->prepare("DELETE FROM receita_composicao WHERE id_receita = ? AND id_insumo = ?");
        $stmt_del->execute([$id_receita, $id_insumo_del]);
    }
    
    // Redireciona para evitar reenvio de formulário (F5)
    header("Location: admin_montar_dieta.php?id_receita=$id_receita");
    exit;
}

// 1. DADOS DA RECEITA
$stmt_rec = $pdo->prepare("SELECT nome FROM receitas WHERE id = ?");
$stmt_rec->execute([$id_receita]);
$receita = $stmt_rec->fetch(PDO::FETCH_ASSOC);

// 2. INSUMOS CADASTRADOS PARA O DROPDOWN
$insumos_dropdown = $pdo->query("SELECT id, nome, categoria FROM insumos ORDER BY categoria, nome")->fetchAll(PDO::FETCH_ASSOC);

// 3. O QUE JÁ ESTÁ NA DIETA DESTE CÃO
$sql_dieta = "SELECT c.id_insumo, c.quantidade_gramas, i.nome, i.categoria 
              FROM receita_composicao c 
              JOIN insumos i ON c.id_insumo = i.id 
              WHERE c.id_receita = ? ORDER BY i.categoria, i.nome";
$stmt_dieta = $pdo->prepare($sql_dieta);
$stmt_dieta->execute([$id_receita]);
$itens_dieta = $stmt_dieta->fetchAll(PDO::FETCH_ASSOC);

$total_dia_gramas = 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Construtor de Dieta Clínica</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .box-add { background: #f8f9fa; border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-inline { display: flex; gap: 15px; align-items: flex-end; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #444; }
        .form-group input, .form-group select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-add { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .tabela { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 20px; }
        .tabela th, .tabela td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .tabela th { background: #333; color: white; }
    </style>
    <script>
        function toggleNovoInsumo() {
            var formExistente = document.getElementById('form-existente');
            var formNovo = document.getElementById('form-novo');
            if (formNovo.style.display === 'none') {
                formNovo.style.display = 'block';
                formExistente.style.display = 'none';
            } else {
                formNovo.style.display = 'none';
                formExistente.style.display = 'flex';
            }
        }
    </script>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    <section style="padding: 40px 20px; max-width: 900px; margin: 0 auto;">
        
        <h2 style="color: #333; margin-bottom: 5px;">Construção Zootécnica: <span style="color: #e83e8c;"><?= htmlspecialchars($receita['nome']) ?></span></h2>
        <h3 style="color: #666; margin-bottom: 25px;">Defina a quantidade de <strong>consumo DIÁRIO</strong> (g/dia). O ERP dividirá pelas refeições e multiplicará pelo pacote solicitado na hora de gerar o lote.</h3>

        <!-- FORMULÁRIO 1: INSERIR VIA DROPDOWN (PADRÃO) -->
        <div class="box-add" id="form-existente" class="form-inline">
            <form method="POST" style="display: flex; gap: 15px; align-items: flex-end; width: 100%;">
                <input type="hidden" name="acao" value="adicionar_existente">
                <div class="form-group" style="flex: 2;">
                    <label>Buscar Insumo do Estoque</label>
                    <select name="id_insumo" required>
                        <option value="">-- Selecione o Insumo --</option>
                        <?php foreach($insumos_dropdown as $ins): ?>
                            <option value="<?= $ins['id'] ?>"><?= $ins['nome'] ?> (<?= $ins['categoria'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Grama / Dia (g)</label>
                    <input type="number" step="0.1" name="quantidade_gramas" placeholder="Ex: 5.5" required>
                </div>
                <button type="submit" class="btn-add">Adicionar à Dieta</button>
            </form>
            
            <div style="margin-top: 15px;">
                <span style="font-size: 0.9rem; color: #666;">Não encontrou na lista?</span>
                <button type="button" onclick="toggleNovoInsumo()" style="background:none; border:none; color:#007bff; text-decoration:underline; font-weight:bold; cursor:pointer;">+ Cadastrar Novo Insumo</button>
            </div>
        </div>

        <!-- FORMULÁRIO 2: CADASTRAR NOVO INSUMO NA HORA (ESCONDIDO) -->
        <div class="box-add" id="form-novo" style="display: none; border-left: 4px solid #28a745;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h4 style="margin: 0; color: #28a745;">Cadastrar e Inserir Insumo Inédito</h4>
                <button type="button" onclick="toggleNovoInsumo()" style="background:none; border:none; color:#dc3545; font-weight:bold; cursor:pointer;">X Cancelar</button>
            </div>
            
            <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
                <input type="hidden" name="acao" value="novo_insumo">
                <div class="form-group" style="flex: 2;">
                    <label>Nome do Ingrediente Novo</label>
                    <input type="text" name="nome_insumo_novo" placeholder="Ex: Psyllium, Casca de" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Categoria</label>
                    <select name="categoria_novo" required>
                        <option value="Proteína">Proteína</option>
                        <option value="Carboidrato">Carboidrato</option>
                        <option value="Vegetal">Vegetal</option>
                        <option value="Víscera">Víscera / Suplemento</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Grama / Dia (g)</label>
                    <input type="number" step="0.1" name="qtd_novo" placeholder="Ex: 2.0" required>
                </div>
                <button type="submit" class="btn-add" style="background: #28a745;">Salvar e Inserir</button>
            </form>
        </div>

        <!-- RELATÓRIO DA DIETA CLÍNICA -->
        <h3 style="margin-top: 30px; color: #333;">Ficha Técnica (Consumo Diário)</h3>
        <table class="tabela">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Insumo</th>
                    <th>Quantidade Diária</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens_dieta)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #888;">Nenhum insumo inserido nesta dieta ainda.</td></tr>
                <?php else: ?>
                    <?php foreach ($itens_dieta as $item): 
                        $total_dia_gramas += $item['quantidade_gramas'];
                    ?>
                    <tr>
                        <td><span style="background: #e9ecef; padding: 3px 8px; border-radius: 12px; font-size: 0.85rem; font-weight: bold;"><?= $item['categoria'] ?></span></td>
                        <td><strong><?= htmlspecialchars($item['nome']) ?></strong></td>
                        <td style="color: #007bff; font-weight: bold;"><?= number_format($item['quantidade_gramas'], 1, ',', '.') ?> g / dia</td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="excluir_item">
                                <input type="hidden" name="id_insumo_del" value="<?= $item['id_insumo'] ?>">
                                <button type="submit" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Remover</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div style="background: #333; color: white; padding: 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 1.1rem;">Total da Refeição Diária Calculada:</span>
            <span style="font-size: 1.4rem; font-weight: bold; color: #28a745;"><?= number_format($total_dia_gramas, 1, ',', '.') ?> g</span>
        </div>

    </section>
</body>
</html>