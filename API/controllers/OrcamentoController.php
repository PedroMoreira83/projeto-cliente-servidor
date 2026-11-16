<?php
// Cabeçalhos HTTP padrão da sua API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclui a classe com as regras matemáticas de precificação
include_once '../models/CalculadoraOrcamento.php';

// Recebe o payload JSON do frontend (passo 4 do formulário)
$dados = json_decode(file_get_contents("php://input"));

// Validação básica garantindo que os nós principais do JSON e os dados matemáticos essenciais existam
if (
    isset($dados->pet) && 
    isset($dados->orcamento) && 
    !empty($dados->pet->peso_kg) && 
    !empty($dados->orcamento->dias_plano)
) {
    // Instancia o serviço de cálculo
    $calculadora = new CalculadoraOrcamento();

    // Mapeia o JSON aninhado (estruturado em passos no frontend) para um objeto plano que a classe espera
    $dados_calculo = new stdClass();
    $dados_calculo->peso_kg = (float) $dados->pet->peso_kg;
    $dados_calculo->idade = isset($dados->pet->idade) ? (int) $dados->pet->idade : 0;
    $dados_calculo->dias_plano = (int) $dados->orcamento->dias_plano;
    $dados_calculo->tem_condicoes = isset($dados->orcamento->tem_condicoes) ? (bool) $dados->orcamento->tem_condicoes : false;
    $dados_calculo->aceitou_consulta = isset($dados->orcamento->aceitou_consulta) ? (bool) $dados->orcamento->aceitou_consulta : false;
    $dados_calculo->refeicoes_dia = isset($dados->orcamento->refeicoes_dia) ? (int) $dados->orcamento->refeicoes_dia : 1;
    $dados_calculo->distancia_km = isset($dados->orcamento->distancia_km) ? (float) $dados->orcamento->distancia_km : 0;

    // Processa a regra de negócio gerando o orçamento
    $resultado = $calculadora->gerarOrcamento($dados_calculo);

    // Retorna HTTP 200 OK com os cálculos detalhados
    http_response_code(200);
    echo json_encode(array(
        "status" => "sucesso",
        "mensagem" => "Orçamento processado com sucesso.",
        "orcamento" => $resultado
    ));

} else {
    // Código HTTP 400 - Faltaram variáveis cruciais para a matemática
    http_response_code(400);
    echo json_encode(array(
        "status" => "erro",
        "mensagem" => "Dados incompletos. O peso do pet e os dias do plano são obrigatórios para gerar o orçamento."
    ));
}
?>