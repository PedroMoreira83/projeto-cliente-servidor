<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aula 04 - Orientação a Objetos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <section>
        <h2>Cadastro e Validação (Orientação a Objetos)</h2>
        
        <?php
            class Profissional {
                public $nome;
                public $especialidade;

                public function __construct($nome, $especialidade) {
                    $this->nome = $nome;
                    $this->especialidade = $especialidade;
                }

                public function aprovarDieta($nomePet) {
                    return "A dieta do pet <strong>$nomePet</strong> foi validada nutricionalmente por <strong>{$this->nome}</strong> ({$this->especialidade}).";
                }
            }

            class Pet {
                public $nome;
                public $raca;
                public $condicaoSaude;

                public function __construct($nome, $raca, $condicaoSaude) {
                    $this->nome = $nome;
                    $this->raca = $raca;
                    $this->condicaoSaude = $condicaoSaude;
                }
            }

            $especialista = new Profissional("Mariana", "Zootecnista");
            $clientePet = new Pet("Maylon", "Yorkshire", "Controle de Peso");
        ?>

        <div class="passos-grid">
            <div class="passo-card">
                <h3>Dados do Pet</h3>
                <p><strong>Nome:</strong> <?php echo $clientePet->nome; ?></p>
                <p><strong>Raça:</strong> <?php echo $clientePet->raca; ?></p>
                <p><strong>Saúde:</strong> <?php echo $clientePet->condicaoSaude; ?></p>
            </div>
            
            <div class="passo-card" style="border: 2px solid var(--primary-color);">
                <h3>Status da Dieta</h3>
                <p><?php echo $especialista->aprovarDieta($clientePet->nome); ?></p>
            </div>
        </div>
    </section>
</body>
</html>