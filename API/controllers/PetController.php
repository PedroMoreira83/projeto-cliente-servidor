<?php
// Cabeçalhos HTTP para permitir comunicação via API (CORS e tipo de conteúdo JSON)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); // Este endpoint só aceita envios (POST)
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir os arquivos de conexão e o Model
include_once '../config/database.php';
include_once '../models/Pet.php';

// Iniciar o banco de dados e obter a conexão PDO
$database = new Database();
$db = $database->getConnection();

// Instanciar o objeto Pet passando a conexão
$pet = new Pet($db);

// Receber a string JSON enviada pelo JavaScript e convertê-la para um objeto PHP
$dados = json_decode(file_get_contents("php://input"));

// Validar se os dados essenciais não estão vazios
if(
    !empty($dados->cpf_cliente) &&
    !empty($dados->nome) &&
    !empty($dados->idade) &&
    !empty($dados->peso_kg) &&
    !empty($dados->raca) &&
    !empty($dados->qtd_porcoes) &&
    !empty($dados->peso_porcao_gramas)
) {
    // Mapear os dados do JSON para as propriedades da classe Pet
    $pet->cpf_cliente = $dados->cpf_cliente;
    $pet->nome = $dados->nome;
    $pet->idade = $dados->idade;
    $pet->peso_kg = $dados->peso_kg;
    $pet->raca = $dados->raca;
    $pet->qtd_porcoes = $dados->qtd_porcoes;
    $pet->peso_porcao_gramas = $dados->peso_porcao_gramas;
    // Campo opcional: se não vier, salva como string vazia
    $pet->proteinas_preferidas = isset($dados->proteinas_preferidas) ? $dados->proteinas_preferidas : "";

    // Tentar salvar no banco de dados usando o método criar() do Model
    if($pet->criar()) {
        // Código HTTP 201 - Criado com sucesso
        http_response_code(201);
        echo json_encode(array("mensagem" => "Pet cadastrado com sucesso. Ele já entrará no cálculo do próximo lote semanal."));
    } else {
        // Código HTTP 503 - Erro interno/Serviço indisponível
        http_response_code(503);
        echo json_encode(array("mensagem" => "Falha no sistema. Não foi possível salvar o pet."));
    }
} else {
    // Código HTTP 400 - Requisição ruim (Faltaram dados no JSON)
    http_response_code(400);
    echo json_encode(array("mensagem" => "Dados incompletos. Preencha todos os campos do formulário."));
}
?>