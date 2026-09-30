<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aula 03 - Funções</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <section>
        <h2>Simulação de Orçamento</h2>
        
        <?php
            function calcularOrcamento($peso, $dias, $taxaConsulta = 60) {
                $custoDiario = $peso * 1.5; 
                $custoTotalComida = $custoDiario * $dias;
                
                $valorFinal = $custoTotalComida + $taxaConsulta;
                
                return $valorFinal;
            }

            $pesoDoPet = 6.0; // kg
            $diasDoPacote = 30; // pacote de 30 dias
            
            $resultado = calcularOrcamento($pesoDoPet, $diasDoPacote);
        ?>

        <div class="passo-card" style="max-width: 500px; margin: 0 auto; text-align: center;">
            <h3 style="color: var(--primary-color);">Orçamento Gerado!</h3>
            <p><strong>Peso do Pet:</strong> <?php echo $pesoDoPet; ?> kg</p>
            <p><strong>Pacote:</strong> <?php echo $diasDoPacote; ?> dias</p>
            <br>
            <h2 style="font-size: 2rem; color: #333;">R$ <?php echo number_format($resultado, 2, ',', '.'); ?></h2>
            <p style="color: var(--secondary-color); font-size: 0.95rem;">
                Inclui R$ 60,00 referente à Consulta Especializada.
            </p>
        </div>
    </section>
</body>
</html>