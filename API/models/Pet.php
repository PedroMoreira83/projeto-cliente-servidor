<?php
class Pet {
    private $conn;
    private $table_name = "pets";

    // Propriedades correspondentes às colunas do banco de dados
    public $id;
    public $cpf_cliente;
    public $nome;
    public $idade;
    public $peso_kg;
    public $raca;
    public $qtd_porcoes;
    public $peso_porcao_gramas;
    public $proteinas_preferidas;

    // O construtor recebe a instância da conexão PDO criada no database.php
    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para inserir um novo pet no banco de dados
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET cpf_cliente=:cpf_cliente, nome=:nome, idade=:idade, peso_kg=:peso_kg, 
                      raca=:raca, qtd_porcoes=:qtd_porcoes, peso_porcao_gramas=:peso_porcao_gramas, 
                      proteinas_preferidas=:proteinas_preferidas";

        $stmt = $this->conn->prepare($query);

        // Higienização básica para evitar execução de scripts maliciosos (XSS)
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->raca = htmlspecialchars(strip_tags($this->raca));
        $this->proteinas_preferidas = htmlspecialchars(strip_tags($this->proteinas_preferidas));

        // Bind (vinculação) dos valores às chaves da query
        $stmt->bindParam(":cpf_cliente", $this->cpf_cliente);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":idade", $this->idade);
        $stmt->bindParam(":peso_kg", $this->peso_kg);
        $stmt->bindParam(":raca", $this->raca);
        $stmt->bindParam(":qtd_porcoes", $this->qtd_porcoes);
        $stmt->bindParam(":peso_porcao_gramas", $this->peso_porcao_gramas);
        $stmt->bindParam(":proteinas_preferidas", $this->proteinas_preferidas);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Método para buscar todos os pets pertencentes a um CPF específico
    public function listarPorCliente($cpf) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE cpf_cliente = :cpf_cliente";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cpf_cliente", $cpf);
        $stmt->execute();
        
        return $stmt;
    }
}
?>