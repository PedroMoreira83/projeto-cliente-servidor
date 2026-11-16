<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cozinha Pet Afetiva - Planejador de Produção</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <?php include 'menu.php'; ?>
    
    <section class="hero" style="padding-top: 20px; min-height: 80vh; align-items: flex-start;">
        <div class="hero-content" style="width: 100%; max-width: 800px; margin: 0 auto; text-align: left;">
            <h2>Cadastrar Pet no <span class="highlight">Lote Semanal</span></h2>
            
            <form id="formCadastroPet" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 15px;">
                    <label>CPF do Tutor (Apenas números):</label>
                    <input type="text" id="cpf_cliente" value="12345678901" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Nome do Pet:</label>
                    <input type="text" id="nome" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Idade:</label>
                    <input type="number" id="idade" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Peso (kg):</label>
                    <input type="number" step="0.1" id="peso_kg" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Raça:</label>
                    <input type="text" id="raca" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Quantidade de Porções (Marmitas na semana):</label>
                    <input type="number" id="qtd_porcoes" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Peso da Porção (gramas):</label>
                    <input type="number" id="peso_porcao_gramas" required style="width: 100%; padding: 8px;">
                </div>
                
                <button type="submit" style="padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer;">Salvar Pet no Lote</button>
            </form>
            <div id="mensagem" style="margin-top: 15px; font-weight: bold;"></div>

            <hr style="margin: 30px 0;">

            <h3>Simulador de Pagamento (<span class="highlight">PIX - 5% OFF</span>)</h3>
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <label>Valor Total do Pedido (R$):</label>
                <input type="number" id="valor_pedido" step="0.01" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                <button type="button" id="btnCalcularPix" style="padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer;">Calcular Desconto</button>
                <div id="resultadoPix" style="margin-top: 15px;"></div>
            </div>
        </div>
    </section>

    <!-- O JavaScript puro consumindo a nossa API PHP -->
    <script>
        document.getElementById('formCadastroPet').addEventListener('submit', function(event) {
            event.preventDefault();

            const dadosPet = {
                cpf_cliente: document.getElementById('cpf_cliente').value,
                nome: document.getElementById('nome').value,
                idade: parseInt(document.getElementById('idade').value),
                peso_kg: parseFloat(document.getElementById('peso_kg').value),
                raca: document.getElementById('raca').value,
                qtd_porcoes: parseInt(document.getElementById('qtd_porcoes').value),
                peso_porcao_gramas: parseInt(document.getElementById('peso_porcao_gramas').value),
                proteinas_preferidas: "Sem restrição"
            };

            // Aponta para a pasta API que você já criou
            fetch('api/controllers/PetController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dadosPet)
            })
            .then(response => {
                const divMensagem = document.getElementById('mensagem');
                if (response.status === 201) {
                    divMensagem.style.color = "green";
                    divMensagem.innerText = "Sucesso! Pet cadastrado e inserido no cálculo do lote.";
                    document.getElementById('formCadastroPet').reset();
                } else {
                    divMensagem.style.color = "red";
                    divMensagem.innerText = "Erro ao salvar. Verifique o console ou o log do Apache.";
                }
            })
            .catch(error => console.error("Erro na requisição:", error));
        });

        document.getElementById('btnCalcularPix').addEventListener('click', function() {
            const valorTotal = parseFloat(document.getElementById('valor_pedido').value);
            
            if(!isNaN(valorTotal) && valorTotal > 0) {
                const desconto = valorTotal * 0.05;
                const valorFinal = valorTotal - desconto;
                
                document.getElementById('resultadoPix').innerHTML = `
                    <p style="color: #333;"><strong>Valor original:</strong> R$ ${valorTotal.toFixed(2)}</p>
                    <p style="color: red;"><strong>Desconto PIX (5%):</strong> - R$ ${desconto.toFixed(2)}</p>
                    <p style="color: green; font-size: 1.2em;"><strong>Total a pagar:</strong> R$ ${valorFinal.toFixed(2)}</p>
                `;
            } else {
                alert("Por favor, insira um valor válido.");
            }
        });
    </script>
</body>
</html>