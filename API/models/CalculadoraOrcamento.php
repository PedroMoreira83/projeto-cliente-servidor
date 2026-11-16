<?php
class CalculadoraOrcamento {
    
    // RN01: Calcula o volume total da dieta em gramas
    public function calcularVolumeDieta(float $peso_kg, int $dias): float {
        $consumo_diario_gramas = ($peso_kg * 1000) * 0.04;
        return $consumo_diario_gramas * $dias;
    }

    // RN02: Determina o preço por grama com base no peso e dias
    public function calcularMatrizAtacado(float $peso_kg, int $dias): float {
        if ($peso_kg < 5) {
            return $dias === 30 ? 0.060 : ($dias === 15 ? 0.065 : 0.070);
        } elseif ($peso_kg < 10) {
            return $dias === 30 ? 0.055 : ($dias === 15 ? 0.060 : 0.065);
        } elseif ($peso_kg < 15) {
            return $dias === 30 ? 0.050 : ($dias === 15 ? 0.055 : 0.060);
        } else {
            return $dias === 30 ? 0.045 : ($dias === 15 ? 0.050 : 0.055);
        }
    }

    // RN03: Calcula a taxa de consulta da zootecnista
    public function calcularTaxaConsulta(int $idade, bool $tem_condicoes, bool $aceitou_consulta): float {
        if ($idade < 1 || $idade > 10) {
            return 60.00; // Automática
        }
        if ($tem_condicoes && $aceitou_consulta) {
            return 60.00; // Condicional Opt-in
        }
        return 0.00;
    }

    // RN04: Calcula a taxa de fracionamento das embalagens
    public function calcularFracionamento(int $refeicoes_dia, int $dias): float {
        if ($refeicoes_dia > 1) {
            return ($refeicoes_dia - 1) * 0.20 * $dias;
        }
        return 0.00;
    }

    // RN05: Calcula o valor do frete por distância
    public function calcularFrete(float $distancia_km): ?float {
        if ($distancia_km <= 5) return 10.00;
        if ($distancia_km <= 10) return 15.00;
        if ($distancia_km <= 15) return 20.00;
        if ($distancia_km <= 20) return 25.00;
        return null; // Representa "Sob consulta"
    }

    // RN06 e Orquestração: Gera o orçamento completo
    public function gerarOrcamento(object $dados): array {
        $volume_total = $this->calcularVolumeDieta($dados->peso_kg, $dados->dias_plano);
        $preco_grama = $this->calcularMatrizAtacado($dados->peso_kg, $dados->dias_plano);
        
        $custo_alimento = $volume_total * $preco_grama;
        $taxa_consulta = $this->calcularTaxaConsulta($dados->idade, $dados->tem_condicoes, $dados->aceitou_consulta);
        $taxa_fracionamento = $this->calcularFracionamento($dados->refeicoes_dia, $dados->dias_plano);
        $frete = $this->calcularFrete($dados->distancia_km);

        // Se o frete for null (sob consulta), o valor base ignora o frete na soma automática
        $valor_frete = $frete !== null ? $frete : 0.00;
        
        $valor_base = $custo_alimento + $taxa_consulta + $taxa_fracionamento + $valor_frete;

        // O valor base já incorpora o desconto real de 5% da empresa. 
        // O valor do cartão recebe o acréscimo matemático (markup) para absorver a taxa da operadora.
        $valor_cartao = $valor_base / 0.95;

        return [
            "resumo_dieta" => [
                "volume_total_gramas" => $volume_total,
                "preco_aplicado_por_grama" => $preco_grama,
                "custo_alimento" => round($custo_alimento, 2)
            ],
            "taxas" => [
                "consulta" => $taxa_consulta,
                "fracionamento" => $taxa_fracionamento,
                "frete" => $frete !== null ? $frete : "Sob consulta"
            ],
            "totais" => [
                "valor_pix" => round($valor_base, 2),
                "valor_cartao" => round($valor_cartao, 2)
            ]
        ];
    }
}
?>