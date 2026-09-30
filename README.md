# Cozinha Pet Afetiva - Projeto Cliente-Servidor

Projeto desenvolvido para a disciplina de Cliente-Servidor do 4º Período do curso de Análise e Desenvolvimento de Sistemas (ADS) da UNIBRA, sob orientação do Professor Gabriel Cândido da Silva.

## Objetivo
O sistema aplica na prática os conceitos fundamentais de programação em PHP ensinados em sala, estruturando um site funcional com 5 páginas interligadas voltado para a gestão de uma cozinha de alimentação natural para pets.

## Estrutura e Atendimento aos Requisitos

* **`index.php` e `menu.php` (Estrutura Base):** Atende à exigência de uma página principal simples atuando como ponto de entrada. O arquivo `menu.php` demonstra o reaproveitamento de código ao ser incluído em todas as páginas para navegação.
* **`aula01.php` (Variáveis, Arrays e Operadores):** Aplica os conceitos da Aula 01 no planejamento de lotes de produção. Utiliza variáveis para definir metas semanais, operadores matemáticos para calcular a proporção exata de proteína necessária no lote e arrays para listar os insumos frescos disponíveis na cozinha.
* **`aula02.php` (Decisão, Laços de Repetição e Include):** Focado no controle de estoque de insumos, utiliza um laço `foreach` para iterar sobre os ingredientes e uma estrutura condicional `if/else` (Aula 02) para avaliar dinamicamente a quantidade disponível, emitindo um alerta visual caso algum item precise ser reposto. Inclui o arquivo de menu exigido.
* **`aula03.php` (Funções com Parâmetros e Return):** Transforma o sistema de orçamento em uma função encapsulada (Aula 03). Recebe parâmetros obrigatórios (peso do pet, dias do pacote), utiliza um parâmetro com valor padrão (taxa de consulta zootécnica) e aciona o comando `return` para devolver o custo final simulado para exibição na tela.
* **`aula04.php` (Orientação a Objetos):** Implementa as classes `Profissional` e `Pet` (Aula 04). Utiliza o método construtor `__construct()` para inicializar os atributos dos objetos instanciados, simulando a emissão de uma validação técnica de dieta assinada pela zootecnista responsável.