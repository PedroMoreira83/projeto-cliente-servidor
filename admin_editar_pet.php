<?php
require_once 'verifica_master.php';
require_once 'api/config/conexao.php';

$erro = ""; $sucesso = "";

if (!isset($_GET['id'])) { header("Location: admin_painel.php"); exit; }
$id_pet = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $peso_kg = (float)$_POST['peso_kg'];
    $peso_porcao = (float)$_POST['peso_porcao_gramas'];
    $restricoes = trim($_POST['proteinas_preferidas']);
    
    // Logística Flexível
    $dias_pacote = (int)$_POST['dias_pacote'];
    $refeicoes_dia = (int)$_POST['refeicoes_dia'];
    
    $padrao_boi = (int)$_POST['padrao_boi']; $padrao_frango = (int)$_POST['padrao_frango'];
    $padrao_porco = (int)$_POST['padrao_porco']; $padrao_peixe = (int)$_POST['padrao_peixe']; $padrao_cordeiro = (int)$_POST['padrao_cordeiro'];
    
    $tipo_dieta_selecionada = $_POST['tipo_dieta']; 

    try {
        $pdo->beginTransaction();

        $id_receita_final = null;
        if ($tipo_dieta_selecionada === 'padrao') {
            $id_receita_final = 1; 
        } else {
            $stmt_check = $pdo->prepare("SELECT r.id FROM receitas r JOIN pets p ON p.id_receita = r.id WHERE p.id = ? AND r.tipo = 'especifica'");
            $stmt_check->execute([$id_pet]);
            $receita_existente = $stmt_check->fetchColumn();

            if ($receita_existente) {
                $id_receita_final = $receita_existente;
            } else {
                $nome_pet = $_POST['nome_pet_oculto'];
                $nome_dieta = "Prescrição Clínica - " . $nome_pet . " (ID: $id_pet)";
                $stmt_nova = $pdo->prepare("INSERT INTO receitas (nome, tipo) VALUES (?, 'especifica')");
                $stmt_nova->execute([$nome_dieta]);
                $id_receita_final = $pdo->lastInsertId();
            }
        }

        $sql_update = "UPDATE pets SET 
                       peso_kg = ?, peso_porcao_gramas = ?, proteinas_preferidas = ?,
                       dias_pacote = ?, refeicoes_dia = ?,
                       padrao_boi = ?, padrao_frango = ?, padrao_porco = ?, padrao_peixe = ?, padrao_cordeiro = ?,
                       id_receita = ? WHERE id = ?";
        $stmt_up = $pdo->prepare($sql_update);
        $stmt_up->execute([
            $peso_kg, $peso_porcao, $restricoes, $dias_pacote, $refeicoes_dia,
            $padrao_boi, $padrao_frango, $padrao_porco, $padrao_peixe, $padrao_cordeiro,
            $id_receita_final, $id_pet
        ]);

        $pdo->commit();
        $sucesso = "Perfil e Logística do pet atualizados!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao atualizar dados: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT p.*, c.nome AS tutor_nome, r.tipo AS tipo_receita FROM pets p JOIN clientes c ON p.cpf_cliente = c.cpf LEFT JOIN receitas r ON p.id_receita = r.id WHERE p.id = ?");
$stmt->execute([$id_pet]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pet) die("Pet não encontrado.");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Perfil do Pet - <?= htmlspecialchars($pet['nome']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .box-perfil { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-5 { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 10px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #444; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .section-title { border-bottom: 2px solid #007bff; color: #333; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; font-size: 1.2rem; }
        .btn-salvar { background: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1rem; margin-top: 20px; width: 100%; }
        .btn-montar-dieta { display: inline-block; background: #e83e8c; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 10px; }
        .resumo-porcoes { background: #e2eef9; color: #0056b3; padding: 15px; border-radius: 4px; text-align: center; font-weight: bold; font-size: 1.1rem; margin-top: 15px; border: 1px solid #b8daff; }
    </style>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    <section style="padding: 40px 20px; max-width: 900px; margin: 0 auto;">
        <h2 style="color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px;">Ficha Clínica: <span style="color: #007bff;"><?= htmlspecialchars($pet['nome']) ?></span></h2>
        
        <?php if ($sucesso) echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>$sucesso</div>"; ?>
        <?php if ($erro) echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>$erro</div>"; ?>
        
        <form method="POST" action="admin_editar_pet.php?id=<?= $id_pet ?>" class="box-perfil">
            <input type="hidden" name="nome_pet_oculto" value="<?= htmlspecialchars($pet['nome']) ?>">

            <div class="grid-2">
                <div class="form-group"><label>Tutor Responsável</label><input type="text" value="<?= htmlspecialchars($pet['tutor_nome']) ?>" disabled style="background: #e9ecef;"></div>
                <div class="form-group"><label>Raça / Porte</label><input type="text" value="<?= htmlspecialchars($pet['raca'] ?? '') ?>" disabled style="background: #e9ecef;"></div>
            </div>

            <div class="section-title">Logística do Pacote</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Dias Fechados no Pacote (Ciclo)</label>
                    <input type="number" id="dias_pacote" name="dias_pacote" value="<?= htmlspecialchars($pet['dias_pacote'] ?? 7) ?>" min="1" required placeholder="Ex: 7, 14, 15, 28, 30">
                </div>
                <div class="form-group">
                    <label>Refeições por Dia</label>
                    <input type="number" id="refeicoes_dia" name="refeicoes_dia" value="<?= htmlspecialchars($pet['refeicoes_dia'] ?? 1) ?>" min="1" required>
                </div>
            </div>
            
            <div class="resumo-porcoes" id="display_total_porcoes">
                Total do Pacote: <span>0</span> marmitas (porções)
            </div>

            <div class="section-title">Parâmetros de Produção</div>
            <div class="grid-2">
                <div class="form-group"><label>Peso do Cão (kg)</label><input type="number" step="0.1" name="peso_kg" value="<?= htmlspecialchars($pet['peso_kg']) ?>" required></div>
                <div class="form-group"><label>Peso Alvo por Marmita (g)</label><input type="number" name="peso_porcao_gramas" value="<?= htmlspecialchars($pet['peso_porcao_gramas']) ?>" required></div>
            </div>
            <div class="form-group" style="margin-top: 15px;"><label>Restrições / Alergias (Aparece na Cozinha)</label><input type="text" name="proteinas_preferidas" value="<?= htmlspecialchars($pet['proteinas_preferidas']) ?>"></div>

            <div class="section-title" style="border-color: #28a745;">Matriz de Separação de Proteínas</div>
            <div class="grid-5">
                <div class="form-group"><label>Boi</label><input type="number" id="pb" name="padrao_boi" value="<?= $pet['padrao_boi'] ?>" min="0"></div>
                <div class="form-group"><label>Frango</label><input type="number" id="pf" name="padrao_frango" value="<?= $pet['padrao_frango'] ?>" min="0"></div>
                <div class="form-group"><label>Porco</label><input type="number" id="pp" name="padrao_porco" value="<?= $pet['padrao_porco'] ?>" min="0"></div>
                <div class="form-group"><label>Peixe</label><input type="number" id="px" name="padrao_peixe" value="<?= $pet['padrao_peixe'] ?>" min="0"></div>
                <div class="form-group"><label>Cordeiro</label><input type="number" id="pc" name="padrao_cordeiro" value="<?= $pet['padrao_cordeiro'] ?>" min="0"></div>
            </div>
            <div id="alerta_matriz" style="color: #dc3545; font-size: 0.9rem; font-weight: bold; text-align: center; margin-top: 10px; display: none;">
                Atenção: A soma das proteínas da Matriz está diferente do Total de Marmitas do pacote.
            </div>

            <div class="section-title" style="border-color: #e83e8c;">Prescrição Nutricional</div>
            <div style="background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="tipo_dieta" style="font-weight: bold;">
                        <option value="padrao" <?= ($pet['tipo_receita'] !== 'especifica') ? 'selected' : '' ?>>Manutenção (Receita Padrão Cozinha Pet)</option>
                        <option value="especifica" <?= ($pet['tipo_receita'] === 'especifica') ? 'selected' : '' ?>>Dieta Clínica Específica (Individualizada)</option>
                    </select>
                </div>
                
                <?php if ($pet['tipo_receita'] === 'especifica'): ?>
                    <div style="margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 15px;">
                        <a href="admin_montar_dieta.php?id_receita=<?= $pet['id_receita'] ?>" class="btn-montar-dieta">?? Configurar Insumos e Gramas Diárias</a>
                    </div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-salvar">Salvar Ficha do Pet</button>
        </form>
    </section>

    <script>
        // Script simples para calcular o total de porções em tempo real
        function calcularMarmitas() {
            let dias = parseInt(document.getElementById('dias_pacote').value) || 0;
            let ref = parseInt(document.getElementById('refeicoes_dia').value) || 0;
            let total = dias * ref;
            document.querySelector('#display_total_porcoes span').innerText = total;

            // Valida se a matriz de proteínas bate com o pacote
            let b = parseInt(document.getElementById('pb').value) || 0;
            let f = parseInt(document.getElementById('pf').value) || 0;
            let p = parseInt(document.getElementById('pp').value) || 0;
            let px = parseInt(document.getElementById('px').value) || 0;
            let c = parseInt(document.getElementById('pc').value) || 0;
            let somaMatriz = b + f + p + px + c;

            let alerta = document.getElementById('alerta_matriz');
            if(somaMatriz > 0 && somaMatriz !== total) {
                alerta.style.display = 'block';
            } else {
                alerta.style.display = 'none';
            }
        }

        document.getElementById('dias_pacote').addEventListener('input', calcularMarmitas);
        document.getElementById('refeicoes_dia').addEventListener('input', calcularMarmitas);
        
        ['pb','pf','pp','px','pc'].forEach(id => {
            document.getElementById(id).addEventListener('input', calcularMarmitas);
        });

        // Executa ao carregar a página
        calcularMarmitas();
    </script>
</body>
</html>