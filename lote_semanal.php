<?php
require_once 'verifica_admin.php';
require_once 'api/config/conexao.php';

$is_master_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$relatorio_gerado = false;

$tipos_proteina = ['Boi', 'Frango', 'Porco', 'Peixe', 'Cordeiro'];

// BUSCA OS VEGETAIS DISPONÍVEIS NO ESTOQUE (DO MAIOR PARA O MENOR)
$stmt_veg = $pdo->query("SELECT id, nome, estoque_gramas FROM insumos WHERE categoria = 'Vegetal' AND estoque_gramas > 0 ORDER BY estoque_gramas DESC");
$vegetais_db = $stmt_veg->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// PROCESSAMENTO DO LOTE E RELATÓRIO
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pets'])) {
    
    $semana_crua = $_POST['semana_producao']; 
    $partes_semana = explode('-W', $semana_crua);
    $semana_formatada = "Semana " . $partes_semana[1] . " de " . $partes_semana[0];

    // LÓGICA INTELIGENTE DE PROPORÇÃO DE VEGETAIS PELO ESTOQUE
    $vegetais_marcados_ids = $_POST['vegetais_selecionados'] ?? [];
    $veg_selecionados = [];
    $estoque_total_selecionados = 0;

    foreach($vegetais_db as $v) {
        if(in_array($v['id'], $vegetais_marcados_ids)) {
            $veg_selecionados[] = $v;
            $estoque_total_selecionados += $v['estoque_gramas'];
        }
    }

    $resumo_macros = [];
    foreach ($tipos_proteina as $tp) {
        $resumo_macros[$tp] = [
            'porcoes' => 0, 'peso_bruto_prot' => 0, 'carbo' => 0, 'vegetais' => 0, 'visceras' => 0, 'suplemento' => 0, 'total_geral' => 0
        ];
    }

    $pets_enviados = $_POST['pets'];
    $relatorio_pets = []; 

    try {
        $pdo->beginTransaction();

        foreach ($pets_enviados as $id_pet => $dados) {
            
            // Se o funcionário não "ticou" o cachorro para a semana, pula!
            if (!isset($dados['participa'])) continue; 
            
            $tem_porcao = false;
            $peso_porcao = (float)$dados['peso_porcao'];
            
            $linha_pet = [
                'tutor' => $dados['tutor'],
                'pet' => $dados['nome_pet'],
                'peso_porcao' => $peso_porcao,
                'porcoes' => [],
                'restricoes' => $dados['restricoes'],
                'tipo_receita' => $dados['tipo_receita']
            ];

            foreach ($tipos_proteina as $tp) {
                $qtd_porcoes = (int)($dados['porcoes'][$tp] ?? 0);
                $linha_pet['porcoes'][$tp] = $qtd_porcoes;

                if ($qtd_porcoes > 0) {
                    $tem_porcao = true;
                    $peso_total_gramas = $qtd_porcoes * $peso_porcao;
                    
                    $resumo_macros[$tp]['porcoes'] += $qtd_porcoes;
                    $resumo_macros[$tp]['total_geral'] += $peso_total_gramas;
                    
                    // SEPARAÇÃO ZOOTÉCNICA: Somar nos macros do tacho apenas quem é Receita Padrão!
                    // (As dietas clínicas são preparadas à parte seguindo as gramas exatas de sua própria ficha).
                    if ($linha_pet['tipo_receita'] !== 'especifica') {
                        $resumo_macros[$tp]['peso_bruto_prot'] += ($peso_total_gramas * 0.35); 
                        $resumo_macros[$tp]['carbo']           += ($peso_total_gramas * 0.30); 
                        $resumo_macros[$tp]['vegetais']        += ($peso_total_gramas * 0.25); 
                        $resumo_macros[$tp]['visceras']        += ($peso_total_gramas * 0.10); 
                        $resumo_macros[$tp]['suplemento']      += ($qtd_porcoes * 1.5); 
                    }
                }
            }

            if ($tem_porcao) {
                $relatorio_pets[] = $linha_pet;
                
                // GERA A FOTOGRAFIA HISTÓRICA DO CÃO
                $str_vegetais_historico = "";
                if ($linha_pet['tipo_receita'] !== 'especifica') {
                    if ($estoque_total_selecionados > 0) {
                        $partes = [];
                        foreach ($veg_selecionados as $v) {
                            $perc = ($v['estoque_gramas'] / $estoque_total_selecionados) * 100;
                            $partes[] = $v['nome'] . " (" . round($perc, 1) . "%)";
                        }
                        $str_vegetais_historico = implode(" + ", $partes);
                    } else {
                        $str_vegetais_historico = "Nenhum vegetal";
                    }
                } else {
                    $str_vegetais_historico = "Dieta Clínica Exclusiva";
                }

                $sql_hist = "INSERT INTO historico_lotes (semana, id_pet, peso_porcao_gramas, qtd_boi, qtd_frango, qtd_porco, qtd_peixe, qtd_cordeiro, vegetais_utilizados) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_hist = $pdo->prepare($sql_hist);
                $stmt_hist->execute([
                    $semana_crua, $id_pet, $peso_porcao, 
                    $linha_pet['porcoes']['Boi'], $linha_pet['porcoes']['Frango'], 
                    $linha_pet['porcoes']['Porco'], $linha_pet['porcoes']['Peixe'], 
                    $linha_pet['porcoes']['Cordeiro'], $str_vegetais_historico
                ]);
            }
        }
        $pdo->commit();
        $relatorio_gerado = true;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao salvar o lote: " . $e->getMessage());
    }
}

// ==========================================
// BUSCA DA LISTA INICIAL DE CÃES
// ==========================================
$sql_todos = "SELECT p.id, p.nome AS pet, p.peso_porcao_gramas, p.proteinas_preferidas, 
                     p.padrao_boi, p.padrao_frango, p.padrao_porco, p.padrao_peixe, p.padrao_cordeiro, 
                     c.nome AS tutor, c.nome_secundario, r.tipo AS tipo_receita 
              FROM pets p 
              JOIN clientes c ON p.cpf_cliente = c.cpf 
              JOIN receitas r ON p.id_receita = r.id
              ORDER BY c.nome, p.nome";
$stmt_todos = $pdo->query($sql_todos);
$lista_geral = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lote Semanal - Operacional</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tabela-checklist { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tabela-checklist th, .tabela-checklist td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: middle; }
        .tabela-checklist th { background: #2c3e50; color: #fff; position: sticky; top: 70px; }
        .tabela-checklist tbody tr:hover { background: #f8f9fa; }
        
        .box-config { background: #e9f7ef; padding: 20px; border-left: 5px solid #28a745; margin-bottom: 20px; display: flex; gap: 20px; align-items: stretch; border-radius: 4px;}
        .btn-gerar { background: #007bff; color: white; padding: 12px 25px; border: none; font-size: 1.1rem; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; height: 100%; }
        
        .tabela-resumo { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; }
        .tabela-resumo th, .tabela-resumo td { padding: 8px 12px; border: 1px solid #000; text-align: right; }
        .tabela-resumo th { background: #fff; border-bottom: 2px solid #000; text-align: center; }
        .tabela-resumo .col-prot { text-align: left; font-weight: bold; background: #f4f4f4; }
        .tabela-resumo .linha-total { font-weight: bold; background: #f4f4f4; }
        
        .check-lote { width: 22px; height: 22px; cursor: pointer; accent-color: #28a745; }
        
        @media print {
            header, .btn-gerar, .box-config, .nao-imprimir { display: none !important; }
            body { background: #fff; margin: 0; padding: 0; }
        }
    </style>
    <script>
        function toggleTodos(source) {
            checkboxes = document.querySelectorAll('.check-lote');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; max-width: 1400px; margin: 0 auto;">
        
        <?php if (!$relatorio_gerado): ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
                <div>
                    <h2 style="color: #333; margin: 0;">Planejamento de <span style="color: #007bff;">Lote Semanal</span></h2>
                    <p style="color: #666; margin-top: 5px;">Selecione quem fará parte da produção desta semana.</p>
                </div>
                <?php if (!$is_master_admin): ?>
                    <div style="background: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 4px; border: 1px solid #ffeeba; font-size: 0.9rem; font-weight: bold;">
                        ⚠️ Operador: Caso a quantidade de um cão esteja diferente, solicite a alteração ao Administrador.
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="lote_semanal.php">
                <div class="box-config">
                    <div style="flex: 1;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Semana (Lote):</label>
                        <input type="week" name="semana_producao" required style="padding: 10px; width: 100%; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div style="flex: 3;">
                        <label style="font-weight: bold; color: #155724; display: block; margin-bottom: 5px;">🥬 Mix de Vegetais (Baseado no Estoque Atual):</label>
                        <?php if(empty($vegetais_db)): ?>
                            <div style="color: #dc3545; font-weight: bold; padding: 10px; background: #f8d7da; border-radius: 4px;">
                                Estoque zerado! Vá em "Estoque" para dar entrada.
                            </div>
                        <?php else: ?>
                            <div style="background: #fff; padding: 12px; border-radius: 4px; border: 1px solid #28a745;">
                                <p style="margin-top: 0; font-size: 0.85rem; color: #666; margin-bottom: 10px;">
                                    O sistema misturará estes itens proporcionalmente. Desmarque se não for utilizar.
                                </p>
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    <?php 
                                    $count = 0;
                                    foreach($vegetais_db as $v): 
                                        // Marca automaticamente os 3 insumos de maior volume no estoque
                                        $checked = ($count < 3) ? 'checked' : '';
                                    ?>
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 0.95rem; font-weight: bold; background: #f4f4f4; padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd;">
                                            <input type="checkbox" name="vegetais_selecionados[]" value="<?= $v['id'] ?>" <?= $checked ?> style="width: 18px; height: 18px; accent-color: #28a745;">
                                            <?= htmlspecialchars($v['nome']) ?> <span style="color: #888; font-size: 0.8rem; font-weight: normal;">(<?= number_format($v['estoque_gramas']/1000, 2, ',', '.') ?> kg)</span>
                                        </label>
                                    <?php 
                                    $count++;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="flex: 1;">
                        <button type="submit" class="btn-gerar" <?= empty($vegetais_db) ? 'disabled style="background:#ccc; cursor:not-allowed;"' : '' ?>>🖨️ Processar Lote</button>
                    </div>
                </div>

                <table class="tabela-checklist">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;" title="Marcar/Desmarcar Todos">
                                <input type="checkbox" onClick="toggleTodos(this)" checked style="width: 18px; height: 18px; cursor: pointer;">
                            </th>
                            <th>Responsável</th>
                            <th>Pet</th>
                            <th>Dieta</th>
                            <th>Resumo do Pacote (Matriz)</th>
                            <th>Restrições e Alergias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_geral as $pet): 
                            $resumo_pacote = [];
                            if ($pet['padrao_boi'] > 0) $resumo_pacote[] = "<b>{$pet['padrao_boi']}</b> Boi";
                            if ($pet['padrao_frango'] > 0) $resumo_pacote[] = "<b>{$pet['padrao_frango']}</b> Fra";
                            if ($pet['padrao_porco'] > 0) $resumo_pacote[] = "<b>{$pet['padrao_porco']}</b> Por";
                            if ($pet['padrao_peixe'] > 0) $resumo_pacote[] = "<b>{$pet['padrao_peixe']}</b> Pei";
                            if ($pet['padrao_cordeiro'] > 0) $resumo_pacote[] = "<b>{$pet['padrao_cordeiro']}</b> Cor";
                            
                            $texto_resumo = empty($resumo_pacote) ? "<span style='color:#dc3545;'>Sem porções configuradas</span>" : implode(" | ", $resumo_pacote);
                        ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="check-lote" name="pets[<?= $pet['id'] ?>][participa]" value="1" checked>
                                    
                                    <!-- DADOS INVISÍVEIS PARA PROCESSAMENTO (Impede o funcionário de quebrar a tabela) -->
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][tutor]" value="<?= htmlspecialchars($pet['tutor']) ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][nome_pet]" value="<?= htmlspecialchars($pet['pet']) ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][peso_porcao]" value="<?= $pet['peso_porcao_gramas'] ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][porcoes][Boi]" value="<?= $pet['padrao_boi'] ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][porcoes][Frango]" value="<?= $pet['padrao_frango'] ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][porcoes][Porco]" value="<?= $pet['padrao_porco'] ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][porcoes][Peixe]" value="<?= $pet['padrao_peixe'] ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][porcoes][Cordeiro]" value="<?= $pet['padrao_cordeiro'] ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][restricoes]" value="<?= htmlspecialchars($pet['proteinas_preferidas'] ?? '') ?>">
                                    <input type="hidden" name="pets[<?= $pet['id'] ?>][tipo_receita]" value="<?= $pet['tipo_receita'] ?>">
                                </td>
                                
                                <td>
                                    <?= htmlspecialchars($pet['tutor']) ?>
                                    <?php if (!empty($pet['nome_secundario'])) echo "<br><small style='color:#888;'>" . htmlspecialchars($pet['nome_secundario']) . "</small>"; ?>
                                </td>
                                
                                <td><strong style="color: #007bff; font-size: 1.1rem;"><?= htmlspecialchars($pet['pet']) ?></strong> <br><small>(<?= $pet['peso_porcao_gramas'] ?>g)</small></td>
                                
                                <td>
                                    <?php if ($pet['tipo_receita'] === 'especifica'): ?>
                                        <span style="background: #e83e8c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Clínica</span>
                                    <?php else: ?>
                                        <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">Padrão</span>
                                    <?php endif; ?>
                                </td>

                                <td style="color: #444;"><?= $texto_resumo ?></td>
                                
                                <td style="<?= empty($pet['proteinas_preferidas']) ? 'color: #aaa;' : 'color: #dc3545; font-weight: bold;' ?>">
                                    <?= empty($pet['proteinas_preferidas']) ? 'Nenhuma' : htmlspecialchars($pet['proteinas_preferidas']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

        <?php else: ?>
            <!-- ===================================== -->
            <!-- RELATÓRIO PÓS PROCESSAMENTO -->
            <!-- ===================================== -->
            <div class="nao-imprimir" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-bottom: 20px;">
                <h2 style="color: #333; margin: 0;">Produção da <span style="color: #28a745;"><?= htmlspecialchars($semana_formatada) ?></span></h2>
                <div>
                    <button onclick="window.print()" class="btn-gerar" style="background: #6c757d; margin-right: 10px; width: auto;">🖨️ Imprimir</button>
                    <a href="lote_semanal.php" class="btn-gerar" style="text-decoration: none; width: auto;">← Voltar ao Lote</a>
                </div>
            </div>

            <!-- EXIBIÇÃO DA PROPORÇÃO DA COZINHA (APENAS PADRÃO) -->
            <div style="background: #e9f7ef; padding: 20px; border: 1px solid #28a745; border-radius: 4px; margin-bottom: 20px;">
                <h3 style="margin-top: 0; color: #155724; margin-bottom: 10px;">🥬 Preparo do Mix de Vegetais (Tacho da Receita Padrão)</h3>
                
                <?php 
                $total_veg_necessario = 0;
                foreach ($tipos_proteina as $tp) { $total_veg_necessario += $resumo_macros[$tp]['vegetais']; }
                ?>
                
                <?php if ($total_veg_necessario > 0 && !empty($veg_selecionados)): ?>
                    <p style="font-size: 1rem; margin-bottom: 10px;">Peso <strong>Total</strong> de Vegetais Necessário: <strong style="font-size: 1.2rem;"><?= number_format($total_veg_necessario, 0, '', '') ?> g</strong>. <br>Para bater essa meta, descasque e prepare:</p>
                    
                    <ul style="margin: 0; padding-left: 20px; font-size: 1.1rem; line-height: 1.6;">
                        <?php foreach ($veg_selecionados as $v): 
                            $perc = $v['estoque_gramas'] / $estoque_total_selecionados;
                            $gramas_usar = $total_veg_necessario * $perc;
                        ?>
                            <li>
                                <strong style="color: #007bff;"><?= htmlspecialchars($v['nome']) ?>:</strong> 
                                <strong><?= number_format($gramas_usar, 0, '', '.') ?> g</strong> 
                                <span style="color: #666; font-size: 0.9rem;">(<?= round($perc * 100, 1) ?>% da mistura)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="margin: 0; color: #dc3545; font-weight: bold;">Nenhum vegetal selecionado ou não houve necessidade de processamento para a dieta padrão.</p>
                <?php endif; ?>
            </div>
            
            <h3 style="color: #333;">Totalização de Base Padrão (Sem Dieta Clínica)</h3>
            <table class="tabela-resumo">
                <thead>
                    <tr>
                        <th>Proteína (Base)</th>
                        <th>Porções Padrão + Clínica</th>
                        <th>Kgs de Proteína (Padrão)</th>
                        <th>Kgs de Carbo (Padrão)</th>
                        <th>Total Geral das Marmitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totais = ['porcoes'=>0, 'prot'=>0, 'carbo'=>0, 'geral'=>0];
                    foreach ($tipos_proteina as $tp): 
                        $d = $resumo_macros[$tp];
                        if ($d['porcoes'] == 0) continue;
                        $totais['porcoes'] += $d['porcoes'];
                        $totais['prot']    += $d['peso_bruto_prot'];
                        $totais['carbo']   += $d['carbo'];
                        $totais['geral']   += $d['total_geral'];
                    ?>
                        <tr>
                            <td class="col-prot"><?= $tp ?></td>
                            <td><?= $d['porcoes'] ?> porções</td>
                            <td><?= number_format($d['peso_bruto_prot']/1000, 2, ',', '.') ?> kg</td>
                            <td><?= number_format($d['carbo']/1000, 2, ',', '.') ?> kg</td>
                            <td style="font-weight: bold;"><?= number_format($d['total_geral']/1000, 2, ',', '.') ?> kg</td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <tr class="linha-total">
                        <td class="col-prot">TOTAIS (GERAL)</td>
                        <td><?= $totais['porcoes'] ?></td>
                        <td><?= number_format($totais['prot']/1000, 2, ',', '.') ?> kg</td>
                        <td><?= number_format($totais['carbo']/1000, 2, ',', '.') ?> kg</td>
                        <td style="font-size: 1.1rem;"><?= number_format($totais['geral']/1000, 2, ',', '.') ?> kg</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="color: #333; margin-top: 40px; border-top: 2px solid #ccc; padding-top: 20px;">Linha de Montagem da Semana</h3>
            <table class="tabela-checklist" style="box-shadow: none; border: 1px solid #ddd;">
                <thead>
                    <tr>
                        <th>Tutor</th>
                        <th>Nome do cão</th>
                        <th>Porção (Tamanho)</th>
                        <th>Boi</th>
                        <th>Frango</th>
                        <th>Porco</th>
                        <th>Peixe</th>
                        <th>Cordeiro</th>
                        <th>Restrições Clínicas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relatorio_pets as $pet): ?>
                        <tr>
                            <td><?= htmlspecialchars($pet['tutor']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($pet['pet']) ?></strong> 
                                <?= $pet['tipo_receita'] === 'especifica' ? '<span style="color:#e83e8c; font-size:0.7rem; font-weight:bold;"><br>DIETA CLÍNICA</span>' : '' ?>
                            </td>
                            <td><strong><?= $pet['peso_porcao'] ?> g</strong></td>
                            <td><?= $pet['porcoes']['Boi'] ?: '' ?></td>
                            <td><?= $pet['porcoes']['Frango'] ?: '' ?></td>
                            <td><?= $pet['porcoes']['Porco'] ?: '' ?></td>
                            <td><?= $pet['porcoes']['Peixe'] ?: '' ?></td>
                            <td><?= $pet['porcoes']['Cordeiro'] ?: '' ?></td>
                            <td style="<?= empty($pet['restricoes']) ? '' : 'background: #f8d7da; font-weight: bold; color: #721c24;' ?>">
                                <?= htmlspecialchars($pet['restricoes']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</body>
</html>