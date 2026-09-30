<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aula 01 - Receita da Semana</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <section>
        <h2>Receita Padrão da Semana</h2>
        
        <?php
            $semanaAtual = "Semana 42 - Base Suína";
            $pesoTotalLote = 50.0; // Em kg
            $percentualProteina = 0.30; // 30% da receita é proteína
            
            $qtdProteinaNecessaria = $pesoTotalLote * $percentualProteina;
            
            $insumosDaSemana = ["Filé Mignon Suíno", "Batata Doce", "Cenoura", "Chuchu", "Azeite Extra Virgem"];
        ?>

        <div class="passos-grid" style="margin-bottom: 40px;">
            <div class="passo-card">
                <h3>Planejamento do Lote</h3>
                <p>Lote: <strong><?php echo $semanaAtual; ?></strong></p>
                <p>Produção Total: <?php echo $pesoTotalLote; ?> kg</p>
                <p>Proteína Necessária: <strong style="color: var(--primary-color);"><?php echo $qtdProteinaNecessaria; ?> kg</strong></p>
            </div>
        </div>

        <h2>Insumos Selecionados (Lidos do Array)</h2>
        <div class="diferenciais-grid">
            <div class="diferencial-card"><?php echo $insumosDaSemana[0]; ?></div>
            <div class="diferencial-card"><?php echo $insumosDaSemana[1]; ?></div>
            <div class="diferencial-card"><?php echo $insumosDaSemana[2]; ?></div>
            <div class="diferencial-card"><?php echo $insumosDaSemana[3]; ?></div>
        </div>
    </section>
</body>
</html>