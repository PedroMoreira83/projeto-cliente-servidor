<?php
require_once 'verifica_master.php'; // Apenas o Master pode alterar o catálogo
require_once 'api/config/conexao.php';

$erro = ""; $sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    
    // AÇÃO 1: ATUALIZAR CONTAGEM FÍSICA (Conversão Inteligente Kg <-> g)
    if ($_POST['acao'] === 'atualizar_saldos') {
        try {
            $pdo->beginTransaction();
            foreach ($_POST['estoque_qtd'] as $id_insumo => $qtd) {
                // Pega a unidade selecionada (g ou kg)
                $unidade = $_POST['estoque_unid'][$id_insumo] ?? 'g';
                $qtd_num = (float)$qtd;
                
                // Se a pessoa selecionou Kg, o sistema multiplica por 1000 para salvar em gramas
                $gramas = ($unidade === 'kg') ? $qtd_num * 1000 : $qtd_num;
                
                $stmt = $pdo->prepare("UPDATE insumos SET estoque_gramas = ? WHERE id = ?");
                $stmt->execute([$gramas, (int)$id_insumo]);
            }
            $pdo->commit();
            $sucesso = "Pesagem do estoque atualizada com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao atualizar saldos: " . $e->getMessage();
        }
    }

    // AÇÃO 2: CADASTRAR NOVO INSUMO
    if ($_POST['acao'] === 'adicionar_insumo') {
        $nome = trim($_POST['nome_novo']);
        $categoria = trim($_POST['categoria_novo']);
        if (!empty($nome)) {
            $stmt = $pdo->prepare("INSERT INTO insumos (nome, categoria, estoque_gramas) VALUES (?, ?, 0)");
            $stmt->execute([$nome, $categoria]);
            $sucesso = "Novo insumo adicionado ao catálogo!";
        }
    }

    // AÇÃO 3: EDITAR NOME/CATEGORIA (Corrigir erros de digitação)
    if ($_POST['acao'] === 'editar_insumo') {
        $id_insumo = (int)$_POST['id_insumo'];
        $nome = trim($_POST['nome_edit']);
        $categoria = trim($_POST['categoria_edit']);
        $stmt = $pdo->prepare("UPDATE insumos SET nome = ?, categoria = ? WHERE id = ?");
        $stmt->execute([$nome, $categoria, $id_insumo]);
        $sucesso = "Insumo corrigido com sucesso!";
    }

    // AÇÃO 4: EXCLUIR INSUMO
    if ($_POST['acao'] === 'excluir_insumo') {
        $id_insumo = (int)$_POST['id_insumo'];
        try {
            $stmt = $pdo->prepare("DELETE FROM insumos WHERE id = ?");
            $stmt->execute([$id_insumo]);
            $sucesso = "Insumo removido do catálogo!";
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $erro = "Bloqueio de Segurança: Você não pode excluir este insumo porque ele faz parte de uma Dieta Clínica salva! Se não for mais usar, apenas zere o estoque dele.";
            } else {
                $erro = "Erro ao excluir: " . $e->getMessage();
            }
        }
    }
}

// Busca todos os insumos para a exibição
$sql = "SELECT id, nome, categoria, estoque_gramas FROM insumos ORDER BY categoria, nome";
$stmt = $pdo->query($sql);
$insumos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estoque = [];
$lista_plana = [];
foreach ($insumos_db as $item) {
    $estoque[$item['categoria']][] = $item;
    $lista_plana[] = $item; 
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Controle de Estoque e Catálogo</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tabela-estoque { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tabela-estoque th, .tabela-estoque td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: middle; }
        .tabela-estoque th { background: #2c3e50; color: #fff; }
        .cat-header { background: #e9ecef; font-weight: bold; color: #333; text-transform: uppercase; font-size: 0.9rem; }
        
        .input-estoque { padding: 8px; border: 1px solid #ccc; border-radius: 4px 0 0 4px; width: 90px; text-align: right; font-weight: bold; color: #007bff; border-right: none; }
        .select-unidade { padding: 8px; border: 1px solid #ccc; border-radius: 0 4px 4px 0; background: #f8f9fa; font-weight: bold; color: #555; cursor: pointer; outline: none; }
        .input-edit { padding: 6px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
        
        .btn-salvar-lote { background: #28a745; color: white; padding: 12px 25px; border: none; font-size: 1.1rem; border-radius: 4px; cursor: pointer; font-weight: bold; position: sticky; bottom: 20px; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .btn-acao { padding: 6px 12px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.85rem; color: white; }
        .btn-add { background: #007bff; padding: 10px 20px; font-size: 1rem; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-del { background: #dc3545; }
        
        .badge-zero { background: #dc3545; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; }
        .box-add { background: #e9f7ef; padding: 20px; border-left: 5px solid #28a745; border-radius: 4px; margin-bottom: 20px; }
        
        .tab-container { display: flex; border-bottom: 2px solid #ccc; margin-bottom: 20px; }
        .tab-btn { background: none; border: none; padding: 10px 20px; font-size: 1.1rem; font-weight: bold; cursor: pointer; color: #666; transition: 0.3s; }
        .tab-btn.active { color: #007bff; border-bottom: 3px solid #007bff; margin-bottom: -2px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
    <script>
        function openTab(tabName, event) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; max-width: 1000px; margin: 0 auto;">
        <h2 style="color: #333; margin-bottom: 25px;">Gestão de <span style="color: #28a745;">Inventário e Insumos</span></h2>
        
        <?php if ($sucesso) echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;'>$sucesso</div>"; ?>
        <?php if ($erro) echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;'>$erro</div>"; ?>

        <div class="tab-container">
            <button class="tab-btn active" onclick="openTab('aba-pesagem', event)">📦 Lançar Pesagem (Dia a Dia)</button>
            <button class="tab-btn" onclick="openTab('aba-catalogo', event)">⚙️ Catálogo e Correções (CRUD)</button>
        </div>

        <!-- ABA 1: LANÇAMENTO DE ESTOQUE RÁPIDO (COM CONVERSÃO DE KG/G) -->
        <div id="aba-pesagem" class="tab-content active">
            <p style="color: #666; margin-bottom: 20px;">Atualize o estoque físico. Use a caixinha seletora para lançar em quilos (Kg) ou gramas (g).</p>
            
            <form method="POST" action="estoque.php">
                <input type="hidden" name="acao" value="atualizar_saldos">
                <table class="tabela-estoque">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th style="text-align: right;">Volume Físico no Estoque</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($estoque)): ?>
                            <tr><td colspan="2" style="text-align: center;">Nenhum insumo cadastrado. Vá na aba "Catálogo" para criar o primeiro.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach ($estoque as $categoria => $itens): ?>
                            <tr><td colspan="2" class="cat-header">↳ <?= htmlspecialchars($categoria) ?></td></tr>
                            <?php foreach ($itens as $item): 
                                // INTELIGÊNCIA DE EXIBIÇÃO: Se tiver mais de 1000 gramas, mostra em Kg.
                                $gramas = (float)$item['estoque_gramas'];
                                if ($gramas >= 1000) {
                                    $valor_exibido = $gramas / 1000;
                                    $unidade_selecionada = 'kg';
                                } else {
                                    $valor_exibido = $gramas;
                                    $unidade_selecionada = 'g';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['nome']) ?></strong>
                                        <?php if ($item['estoque_gramas'] <= 0): ?> <span class="badge-zero">Zerad</span> <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; justify-content: flex-end; align-items: center;">
                                            <!-- step="0.001" permite lançar valores como 1.5 Kg -->
                                            <input type="number" step="0.001" name="estoque_qtd[<?= $item['id'] ?>]" value="<?= $valor_exibido ?>" class="input-estoque" min="0">
                                            <select name="estoque_unid[<?= $item['id'] ?>]" class="select-unidade">
                                                <option value="g" <?= $unidade_selecionada == 'g' ? 'selected' : '' ?>>g</option>
                                                <option value="kg" <?= $unidade_selecionada == 'kg' ? 'selected' : '' ?>>Kg</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!empty($estoque)): ?>
                    <button type="submit" class="btn-salvar-lote">💾 Gravar Pesagem do Estoque</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- ABA 2: GESTÃO DO CATÁLOGO (CRUD COMPLETO) -->
        <div id="aba-catalogo" class="tab-content">
            <div class="box-add">
                <h3 style="margin-top: 0; color: #155724; margin-bottom: 15px;">+ Cadastrar Novo Insumo Inédito</h3>
                <form method="POST" action="estoque.php" style="display: flex; gap: 15px; align-items: flex-end;">
                    <input type="hidden" name="acao" value="adicionar_insumo">
                    <div style="flex: 2;">
                        <label style="font-weight: bold; font-size: 0.9rem;">Nome correto do ingrediente</label>
                        <input type="text" name="nome_novo" placeholder="Ex: Moela de Frango" class="input-edit" style="padding: 10px;" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-weight: bold; font-size: 0.9rem;">Categoria Zootécnica</label>
                        <select name="categoria_novo" class="input-edit" style="padding: 10px;" required>
                            <option value="Proteína">Proteína</option>
                            <option value="Carboidrato">Carboidrato</option>
                            <option value="Vegetal">Vegetal</option>
                            <option value="Víscera">Víscera</option>
                            <option value="Suplemento">Suplemento</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-acao btn-add">Cadastrar</button>
                    </div>
                </form>
            </div>

            <p style="color: #666; margin-bottom: 10px; font-size: 0.9rem;">Edite os erros de digitação direto na linha da tabela e clique no botão amarelo para salvar.</p>
            
            <table class="tabela-estoque">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Nome do Insumo (Editável)</th>
                        <th>Categoria (Editável)</th>
                        <th style="text-align: right; width: 160px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_plana as $insumo): ?>
                        <tr>
                            <td>#<?= $insumo['id'] ?></td>
                            
                            <form method="POST" action="estoque.php" style="display:inline;">
                                <input type="hidden" name="acao" value="editar_insumo">
                                <input type="hidden" name="id_insumo" value="<?= $insumo['id'] ?>">
                                <td>
                                    <input type="text" name="nome_edit" value="<?= htmlspecialchars($insumo['nome']) ?>" class="input-edit" required>
                                </td>
                                <td>
                                    <select name="categoria_edit" class="input-edit">
                                        <option value="Proteína" <?= $insumo['categoria'] == 'Proteína' ? 'selected' : '' ?>>Proteína</option>
                                        <option value="Carboidrato" <?= $insumo['categoria'] == 'Carboidrato' ? 'selected' : '' ?>>Carboidrato</option>
                                        <option value="Vegetal" <?= $insumo['categoria'] == 'Vegetal' ? 'selected' : '' ?>>Vegetal</option>
                                        <option value="Víscera" <?= $insumo['categoria'] == 'Víscera' ? 'selected' : '' ?>>Víscera</option>
                                        <option value="Suplemento" <?= $insumo['categoria'] == 'Suplemento' ? 'selected' : '' ?>>Suplemento</option>
                                    </select>
                                </td>
                                <td style="text-align: right; display: flex; gap: 5px; justify-content: flex-end;">
                                    <button type="submit" class="btn-acao btn-edit" title="Salvar Correção">Salvar</button>
                            </form>
                            
                            <form method="POST" action="estoque.php" style="display:inline;" onsubmit="return confirm('ATENÇÃO: Excluir definitivamente este insumo do sistema?');">
                                <input type="hidden" name="acao" value="excluir_insumo">
                                <input type="hidden" name="id_insumo" value="<?= $insumo['id'] ?>">
                                <button type="submit" class="btn-acao btn-del" title="Excluir Definitivamente">X</button>
                            </form>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>