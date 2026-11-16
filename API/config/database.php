<?php

class Database {
    // Credenciais padrão do XAMPP no Windows 10
    private $host = "localhost";
    private $db_name = "cozinhapet"; // Altere para o nome exato do seu banco no phpMyAdmin
    private $username = "root";
    private $password = ""; 
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // DSN (Data Source Name) especificando o driver mysql e charset utf8
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Configura o PDO para lançar exceções em caso de erros no banco
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Força o retorno dos dados do banco como arrays associativos
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            // Impede vazamento do stack trace na tela, retornando um JSON amigável para o frontend
            http_response_code(500);
            echo json_encode(["erro" => "Falha na conexão com o banco de dados: " . $exception->getMessage()]);
            exit;
        }

        return $this->conn;
    }
}
?>