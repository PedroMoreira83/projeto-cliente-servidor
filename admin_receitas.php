<?php
require_once 'verifica_master.php';
require_once 'api/config/conexao.php';

$erro = ""; $sucesso = "";

// LÓGICA DE UPLOAD DE ARQUIVOS (PRESCRIÇÃO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'upload_prescricao') {
    $id_receita = (int)$_POST['id_receita'];
    
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (in_array($extensao, $permitidos)) {
            $diretorio = 'uploads/prescricoes/';
            // Cria a pasta automaticamente se ela não existir
            if (!is_dir($diretorio)) {
                mkdir($diretorio, 0777, true);
            }
            
            // Renomeia o arquivo para evitar conflitos (ex: receita_5_167890.pdf)
            $nome_arquivo = 'receita_' . $id_receita . '_' . time() . '.' . $extensao;
            $caminho_final = $diretorio . $nome_arquivo;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho_final)) {
                $stmt = $pdo->prepare("UPDATE receitas SET arquivo_prescricao = ? WHERE id = ?");
                $stmt->execute([$caminho_final, $id_receita]);
                $sucesso = "Laudo anexado com sucesso!";
            } else {
                $erro = "Falha ao mover o arquivo para o servidor.";
            }
        } else {
            $erro = "Formato de arquivo não permitido. Use PDF, JPG ou PNG.";
        }
    } else {
        $erro = "Nenhum arquivo enviado ou ocorreu um erro no upload.";
    }
}

// Busca as Receitas Padrão
$receitas_padrao = $pdo->query("SELECT * FROM receitas WHERE tipo = 'padrao' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Busca as Prescrições Clínicas com o anexo
$sql_clinicas = "SELECT r.id AS id_receita, r.nome AS nome_receita, r.arquivo_prescricao, 
                        p.id AS id_pet, p.nome AS nome_pet, c.nome AS nome_tutor 
                 FROM receitas r 
                 JOIN pets p ON p.id_receita = r.id 
                 JOIN clientes c ON p.cpf_cliente = c.cpf
                 WHERE r.tipo = 'especifica' 
                 ORDER BY c.nome, p.nome";
$receitas_clinicas = $pdo->query($sql_clinicas)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Central de Dietas - ERP</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tabela { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tabela th, .tabela td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .tabela th { background: #2c3e50; color: #fff; user-select: none; }
        .th-sortable { cursor: pointer; transition: background 0.2s; }
        .th-sortable:hover { background: #34495e; }
        .badge-padrao { background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .badge-esp { background: #e83e8c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .btn-editar { background: #007bff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold; margin-right: 5px; }
        .btn-pet { background: #17a2b8; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .btn-upload { background: #ffc107; color: #333; padding: 6px 12px; cursor: pointer; border-radius: 4px; font-size: 0.85rem; font-weight: bold; margin-right: 5px; display: inline-block; }
        .btn-anexo { background: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold; margin-right: 5px; }
    </style>
    
    <!-- SCRIPT DE ORDENAÇÃO DE TABELAS -->
    <script>
        function sortTable(n, tableId) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById(tableId);
            switching = true;
            dir = "asc"; 
            while (switching) {
                switching = false;
                rows = table.rows;
                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];
                    if (dir == "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount ++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
    </script>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
        <h2 style="color: #333; margin-bottom: 5px;">Central de <span style="color: #007bff;">Dietas e Prescrições</span></h2>
        <p style="color: #666; margin-bottom: 30px;">Visão geral de todas as matrizes nutricionais aplicadas aos cães da Cozinha Pet Afetiva.</p>

        <?php if ($sucesso) echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;'>$sucesso</div>"; ?>
        <?php if ($erro) echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>$erro</div>"; ?>

        <!-- RECEITAS PADRÃO -->
        <h3 style="color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 5px;">Matrizes de Manutenção (Uso Geral)</h3>
        <table class="tabela">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Nome da Matriz</th>
                    <th>Tipo</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receitas_padrao as $rec): ?>
                    <tr>
                        <td>#<?= $rec['id'] ?></td>
                        <td><strong><?= htmlspecialchars($rec['nome']) ?></strong></td>
                        <td><span class="badge-padrao">Padrão / Flexível</span></td>
                        <td><span style="color: #888; font-size: 0.85rem;">Gerenciada via Lote Semanal</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- PRESCRIÇÕES CLÍNICAS -->
        <h3 style="color: #e83e8c; border-bottom: 2px solid #e83e8c; padding-bottom: 5px; margin-top: 50px;">Prescrições Clínicas Ativas (Individuais)</h3>
        <table class="tabela" id="tabelaClinicas">
            <thead>
                <tr>
                    <!-- Colunas clicáveis para ordenação -->
                    <th class="th-sortable" onclick="sortTable(0, 'tabelaClinicas')" title="Clique para ordenar por Pet">Pet ⇕</th>
                    <th class="th-sortable" onclick="sortTable(1, 'tabelaClinicas')" title="Clique para ordenar por Responsável">Responsável ⇕</th>
                    <th class="th-sortable" onclick="sortTable(2, 'tabelaClinicas')" title="Clique para ordenar por Prescrição">Prescrição ⇕</th>
                    <th style="text-align: right;">Auditoria e Zootecnia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receitas_clinicas)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px; color: #666;">Nenhuma prescrição clínica cadastrada no momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($receitas_clinicas as $clinica): ?>
                        <tr>
                            <td><strong style="color: #e83e8c; font-size: 1.1rem;"><?= htmlspecialchars($clinica['nome_pet']) ?></strong></td>
                            <td><?= htmlspecialchars($clinica['nome_tutor']) ?></td>
                            <td>
                                <span class="badge-esp" style="margin-right: 10px;">Exclusiva</span> 
                                <span style="color: #555; font-size: 0.9rem;"><?= htmlspecialchars($clinica['nome_receita']) ?></span>
                            </td>
                            <td style="text-align: right; display: flex; justify-content: flex-end; gap: 5px; align-items: center;">
                                
                                <!-- EXIBE OU ANEXA O LAUDO -->
                                <?php if (!empty($clinica['arquivo_prescricao'])): ?>
                                    <a href="<?= htmlspecialchars($clinica['arquivo_prescricao']) ?>" target="_blank" class="btn-anexo">📄 Ver Laudo</a>
                                <?php else: ?>
                                    <form method="POST" enctype="multipart/form-data" style="margin: 0;">
                                        <input type="hidden" name="acao" value="upload_prescricao">
                                        <input type="hidden" name="id_receita" value="<?= $clinica['id_receita'] ?>">
                                        <input type="file" name="arquivo" accept=".pdf, .jpg, .jpeg, .png" id="file_<?= $clinica['id_receita'] ?>" style="display: none;" onchange="this.form.submit()">
                                        <label for="file_<?= $clinica['id_receita'] ?>" class="btn-upload" title="Upload do PDF ou Foto da receita">⬆️ Anexar Laudo</label>
                                    </form>
                                <?php endif; ?>

                                <!-- ATALHOS -->
                                <a href="admin_montar_dieta.php?id_receita=<?= $clinica['id_receita'] ?>" class="btn-editar">⚙️ Editar Insumos</a>
                                <a href="admin_editar_pet.php?id=<?= $clinica['id_pet'] ?>" class="btn-pet">Ficha Clínica</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
    </section>
</body>
</html>