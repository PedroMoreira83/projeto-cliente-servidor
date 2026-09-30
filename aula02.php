<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aula 02 - Controle de Estoque</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'menu.php'; ?> 
    
    <section id="como-funciona">
        <h2>Verificação de Estoque para Produção</h2>
        <div class="passos-grid">
            
            <?php
                $estoqueInsumos = [
                    "Filé Mignon Suíno" => 20,
                    "Batata Doce" => 50,
                    "Cenoura" => 0, 
                    "Chuchu" => 70
                ];

                foreach ($estoqueInsumos as $insumo => $quantidadeKg) {
                    echo "<div class='passo-card'>";
                    echo "<h3>$insumo</h3>";
                    
                    if ($quantidadeKg > 0) {
                        echo "<p style='color: green;'>Em estoque: {$quantidadeKg}kg</p>";
                    } else {
                        echo "<p style='color: red; font-weight: bold;'>Falta no estoque! Necessário comprar.</p>";
                    }
                    
                    echo "</div>";
                }
            ?>

        </div>
    </section>
</body>
</html>