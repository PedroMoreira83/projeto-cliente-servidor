<?php
// Inicia a sessÃ£o para guardar os dados do orÃ§amento entre as telas
session_start();
require_once 'api/config/conexao.php';

// Permite reiniciar o processo limpando a sessÃ£o
if (isset($_GET['reiniciar'])) {
    session_destroy();
    header("Location: aula03.php");
    exit;
}

// === FUNÃÃES DE PRECIFICAÃÃO (RN01 a RN06) ===
function calcularVolumeDieta($peso_kg, $dias) { return ($peso_kg * 1000) * 0.04 * $dias; }
function calcularMatrizAtacado($peso_kg, $dias) {
    if ($peso_kg < 5) return $dias == 30 ? 0.060 : ($dias == 15 ? 0.065 : 0.070);
    if ($peso_kg < 10) return $dias == 30 ? 0.055 : ($dias == 15 ? 0.060 : 0.065);
    if ($peso_kg < 15) return $dias == 30 ? 0.050 : ($dias == 15 ? 0.055 : 0.060);
    return $dias == 30 ? 0.045 : ($dias == 15 ? 0.050 : 0.055);
}
function calcularTaxaConsulta($idade, $tem_condicoes, $aceitou_consulta) {
    if ($idade < 1 || $idade > 10) return 60.00;
    if ($tem_condicoes && $aceitou_consulta) return 60.00;
    return 0.00;
}
function calcularFracionamento($refeicoes_dia, $dias) {
    return $refeicoes_dia > 1 ? ($refeicoes_dia - 1) * 0.20 * $dias : 0.00;
}
function calcularFrete($distancia_km) {
    if ($distancia_km <= 5) return 10.00;
    if ($distancia_km <= 10) return 15.00;
    if ($distancia_km <= 15) return 20.00;
    if ($distancia_km <= 20) return 25.00;
    return null; 
}

// Controle de Etapas do FormulÃ¡rio
$etapa = 1;
$erro_banco = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    
    // ETAPA 1: Processa o cÃ¡lculo e guarda na memÃ³ria (SessÃ£o)
    if ($_POST['acao'] === 'gerar_orcamento') {
        $peso = (float) $_POST['peso_kg'];
        $idade = (int) $_POST['idade'];
        $dias = (int) $_POST['dias_plano'];
        $refeicoes = (int) $_POST['refeicoes'];
        $distancia = (float) $_POST['distancia'];
        
        $tem_condicoes = isset($_POST['condicoes']) && $_POST['condicoes'] === 'sim';
        $aceitou_consulta = isset($_POST['consulta']) && $_POST['consulta'] === 'sim';

        $volume_total = calcularVolumeDieta($peso, $dias);
        $preco_grama = calcularMatrizAtacado($peso, $dias);
        $custo_alimento = $volume_total * $preco_grama;
        
        $taxa_consulta = calcularTaxaConsulta($idade, $tem_condicoes, $aceitou_consulta);
        $taxa_fracionamento = calcularFracionamento($refeicoes, $dias);
        $frete = calcularFrete($distancia);
        
        $valor_base = $custo_alimento + $taxa_consulta + $taxa_fracionamento + ($frete ?? 0);
        $valor_cartao = $valor_base / 0.95;

        // Salva os dados na sessÃ£o para usar nas prÃ³ximas telas
        $_SESSION['orcamento'] = [
            'nome_pet' => $_POST['nome_pet'],
            'raca' => $_POST['raca'],
            'idade' => $idade,
            'peso_kg' => $peso,
            'volume_total' => $volume_total,
            'custo_alimento' => $custo_alimento,
            'taxa_consulta' => $taxa_consulta,
            'taxa_fracionamento' => $taxa_fracionamento,
            'frete' => $frete,
            'valor_base' => $valor_base,
            'valor_cartao' => $valor_cartao,
            // Dados para o banco
            'qtd_porcoes_banco' => $dias * $refeicoes,
            'peso_porcao_banco' => round($volume_total / ($dias * $refeicoes))
        ];
        
        $etapa = 2; // AvanÃ§a para exibir o resultado
    }

    // ETAPA 2: Verifica se o CPF informado jÃ¡ existe no banco
    elseif ($_POST['acao'] === 'verificar_cpf') {
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf_tutor']); // Limpa formataÃ§Ã£o
        $_SESSION['orcamento']['cpf_tutor'] = $cpf;

        try {
            $stmt = $pdo->prepare("SELECT nome FROM clientes WHERE cpf = :cpf");
            $stmt->execute([':cpf' => $cpf]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cliente) {
                $_SESSION['orcamento']['nome_tutor'] = $cliente['nome'];
                $_SESSION['orcamento']['cliente_novo'] = false;
            } else {
                $_SESSION['orcamento']['cliente_novo'] = true;
            }
            $etapa = 3; // AvanÃ§a para o fechamento
        } catch (PDOException $e) {
            $erro_banco = "Erro ao consultar CPF: " . $e->getMessage();
            $etapa = 2;
        }
    }

    // ETAPA 3: Salva o pedido final no MySQL
    elseif ($_POST['acao'] === 'salvar_pedido') {
        $orc = $_SESSION['orcamento'];
        $cpf = $orc['cpf_tutor'];
        $nome_tutor = $orc['cliente_novo'] ? $_POST['nome_tutor'] : $orc['nome_tutor'];

        try {
            // Inicia a transaÃ§Ã£o: o MySQL sÃ³ vai salvar definitivamente se tudo der certo
            $pdo->beginTransaction();
        
            // 1. Cadastra o Tutor
            $sql_cli = "INSERT INTO clientes (cpf, nome) VALUES (:cpf, :nome) ON DUPLICATE KEY UPDATE nome = :nome";
            $stmt_cli = $pdo->prepare($sql_cli);
            $stmt_cli->execute([':cpf' => $cpf, ':nome' => $nome_tutor]);
        
            // 2. Vincula o Pet ao Tutor
            $sql_pet = "INSERT INTO pets (cpf_cliente, nome, idade, peso_kg, raca, qtd_porcoes, peso_porcao_gramas, proteinas_preferidas) 
                        VALUES (:cpf_cliente, :nome, :idade, :peso_kg, :raca, :qtd_porcoes, :peso_porcao_gramas, :pref)";
            $stmt_pet = $pdo->prepare($sql_pet);
            $stmt_pet->execute([
                ':cpf_cliente' => $cpf,
                ':nome' => $orc['nome_pet'],
                ':idade' => $orc['idade'],
                ':peso_kg' => $orc['peso_kg'],
                ':raca' => $orc['raca'],
                ':qtd_porcoes' => $orc['qtd_porcoes_banco'],
                ':peso_porcao_gramas' => $orc['peso_porcao_banco'],
                ':pref' => "Sem restriÃ§Ã£o"
            ]);
            
            // Confirma as duas operaÃ§Ãµes simultaneamente no banco
            $pdo->commit();
            $etapa = 4; // Sucesso!
            
        } catch (Exception $e) { // Usamos Exception genÃ©rica para capturar qualquer falha
            // Se der erro no pet, o PHP desfaz a inserÃ§Ã£o do Tutor instantaneamente
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro_banco = "Erro ao gravar pedido. OperaÃ§Ã£o cancelada por seguranÃ§a: " . $e->getMessage();
         
            $etapa = 3;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aula 03 - Calculadora UX Otimizada</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .box-conteudo { max-width: 700px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: left; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 15px; }
        .form-group label { font-weight: bold; margin-bottom: 5px; color: #444; }
        .form-group input, .form-group select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { padding: 15px; background: #4CAF50; color: white; border: none; font-size: 1.1rem; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn-submit:hover { background: #45a049; }
        .btn-voltar { color: #888; text-decoration: none; display: block; text-align: center; margin-top: 15px; }
        .section-title { grid-column: span 2; border-bottom: 2px solid #eee; padding-bottom: 5px; margin: top: 15px; margin-bottom: 15px; color: #333; }
        .resumo-item { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 8px 0; }
        .alerta-erro { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .alerta-sucesso { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; text-align: center; font-size: 1.2rem; }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px;">
        <h2 style="text-align: center;">Simulador de <span class="highlight">OrÃ§amento</span></h2>
        
        <div class="box-conteudo">
            <?php if ($erro_banco) echo "<div class='alerta-erro'>$erro_banco</div>"; ?>

            <!-- ETAPA 1: ORÃAMENTO SEM COMPROMISSO -->
            <?php if ($etapa === 1): ?>
                <p style="text-align: center; color: #666; margin-bottom: 20px;">Preencha os dados do pet para ver os valores imediatamente.</p>
                <form method="POST" action="aula03.php" class="form-grid">
                    <input type="hidden" name="acao" value="gerar_orcamento">
                    
                    <div class="section-title">Dados do Pet</div>
                    <div class="form-group"><label>Nome do Pet</label><input type="text" name="nome_pet" required></div>
                    <div class="form-group"><label>RaÃ§a</label><input type="text" name="raca" required></div>
                    <div class="form-group"><label>Idade (anos)</label><input type="number" name="idade" min="0" required></div>
                    <div class="form-group"><label>Peso (kg)</label><input type="number" name="peso_kg" step="0.1" required></div>

                    <div class="section-title">SaÃºde e Dieta</div>
                    <div class="form-group"><label>CondiÃ§Ãµes EspecÃ­ficas / Sobrepeso?</label><select name="condicoes"><option value="nao">NÃ£o, Ã© saudÃ¡vel</option><option value="sim">Sim</option></select></div>
                    <div class="form-group"><label>Incluir AvaliaÃ§Ã£o da Zootecnista?</label><select name="consulta"><option value="nao">NÃ£o</option><option value="sim">Sim, desejo incluir</option></select></div>
                    <div class="form-group"><label>Dias do Plano</label><select name="dias_plano" required><option value="7">7 dias</option><option value="15">15 dias</option><option value="30">30 dias</option></select></div>
                    <div class="form-group"><label>RefeiÃ§Ãµes por dia</label><input type="number" name="refeicoes" min="1" value="1" required></div>

                    <div class="section-title">LogÃ­stica</div>
                    <div class="form-group" style="grid-column: span 2;"><label>DistÃ¢ncia para Entrega (km)</label><input type="number" name="distancia" step="0.1" value="0" required></div>

                    <div style="grid-column: span 2;"><button type="submit" class="btn-submit">Calcular OrÃ§amento GrÃ¡tis</button></div>
                </form>
            <?php endif; ?>

            <!-- ETAPA 2: MOSTRA RESULTADO E PEDE CPF -->
            <?php if ($etapa === 2): $orc = $_SESSION['orcamento']; ?>
                <h3 style="color: #4CAF50; text-align: center; border-bottom: 2px solid #4CAF50; padding-bottom: 10px;">OrÃ§amento de <?= htmlspecialchars($orc['nome_pet']) ?></h3>
                
                <div class="resumo-item"><span>Custo do Alimento Base:</span><strong>R$ <?= number_format($orc['custo_alimento'], 2, ',', '.') ?></strong></div>
                <?php if ($orc['taxa_consulta'] > 0): ?><div class="resumo-item"><span>AvaliaÃ§Ã£o ZootÃ©cnica:</span><strong>R$ <?= number_format($orc['taxa_consulta'], 2, ',', '.') ?></strong></div><?php endif; ?>
                <?php if ($orc['taxa_fracionamento'] > 0): ?><div class="resumo-item"><span>Taxa de Fracionamento:</span><strong>R$ <?= number_format($orc['taxa_fracionamento'], 2, ',', '.') ?></strong></div><?php endif; ?>
                <div class="resumo-item"><span>Frete:</span><strong><?= $orc['frete'] !== null ? 'R$ ' . number_format($orc['frete'], 2, ',', '.') : 'Sob Consulta' ?></strong></div>

                <div style="background: #f8f9fa; padding: 20px; text-align: center; margin: 15px 0; border-radius: 8px;">
                    <p style="margin: 0; color: #555;">Valor no CartÃ£o: <strong>R$ <?= number_format($orc['valor_cartao'], 2, ',', '.') ?></strong></p>
                    <h2 style="color: #28a745; font-size: 2.2rem; margin: 10px 0;">PIX: R$ <?= number_format($orc['valor_base'], 2, ',', '.') ?></h2>
                </div>

                <!-- FormulÃ¡rio Captura de CPF -->
                <div style="background: #e9ecef; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h4 style="margin-top:0; color:#333;">Quer salvar este plano?</h4>
                    <p style="font-size: 0.9rem; color:#555;">Informe seu CPF para verificarmos se vocÃª jÃ¡ Ã© cliente da Cozinha Pet Afetiva:</p>
                    <form method="POST" action="aula03.php" style="display: flex; gap: 10px;">
                        <input type="hidden" name="acao" value="verificar_cpf">
                        <input type="text" name="cpf_tutor" placeholder="Digite seu CPF (apenas nÃºmeros)" maxlength="11" required style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <button type="submit" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Verificar</button>
                    </form>
                </div>
                <a href="aula03.php?reiniciar=1" class="btn-voltar">Refazer CÃ¡lculo</a>
            <?php endif; ?>

            <!-- ETAPA 3: COMPLETA O CADASTRO E CONFIRMA -->
            <?php if ($etapa === 3): $orc = $_SESSION['orcamento']; ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php if ($orc['cliente_novo']): ?>
                        <h3 style="color: #007bff;">Bem-vindo Ã  Cozinha Pet Afetiva!</h3>
                        <p>Falta pouco para garantirmos a dieta de <strong><?= htmlspecialchars($orc['nome_pet']) ?></strong>. Por favor, informe seu nome para completarmos seu cadastro.</p>
                    <?php else: ?>
                        <h3 style="color: #007bff;">OlÃ¡ de volta, <?= htmlspecialchars($orc['nome_tutor']) ?>!</h3>
                        <p>Identificamos seu cadastro. Clique abaixo para amarrar este novo pacote de <strong><?= htmlspecialchars($orc['nome_pet']) ?></strong> ao seu perfil.</p>
                    <?php endif; ?>
                </div>

                <form method="POST" action="aula03.php">
                    <input type="hidden" name="acao" value="salvar_pedido">
                    
                    <?php if ($orc['cliente_novo']): ?>
                        <div class="form-group">
                            <label>Seu Nome Completo:</label>
                            <input type="text" name="nome_tutor" required>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn-submit">Finalizar InclusÃ£o no Sistema</button>
                </form>
                <a href="aula03.php?reiniciar=1" class="btn-voltar">Cancelar</a>
            <?php endif; ?>

            <!-- ETAPA 4: SUCESSO -->
            <?php if ($etapa === 4): ?>
                <div class="alerta-sucesso">
                    <strong>Tudo pronto!</strong><br><br>
                    O orÃ§amento foi aprovado e gravado no banco de dados. O pedido de <?= htmlspecialchars($_SESSION['orcamento']['nome_pet']) ?> jÃ¡ consta na lista de produÃ§Ã£o do prÃ³ximo lote.<br><br>
                    <a href="aula03.php?reiniciar=1" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Novo OrÃ§amento</a>
                </div>
                <?php session_destroy(); // Limpa a memÃ³ria apÃ³s o sucesso ?>
            <?php endif; ?>

        </div>
    </section>
</body>
</html>