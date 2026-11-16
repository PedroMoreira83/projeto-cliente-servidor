<?php
require_once 'verifica_master.php';
require_once 'api/config/conexao.php';

if (isset($_GET['tipo']) && isset($_GET['id'])) {
    $tipo = $_GET['tipo'];
    $id = $_GET['id'];

    try {
        if ($tipo === 'cliente') {
            // A exclusÃ£o do cliente apaga os pets automaticamente graÃ§as ao ON DELETE CASCADE
            $sql = "DELETE FROM clientes WHERE cpf = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            header("Location: admin_painel.php?msg=" . urlencode("Cliente e seus pets excluÃ­dos com sucesso."));
            exit;
        } 
        elseif ($tipo === 'pet') {
            // 1. Descobre de quem Ã© este pet
            $sql_busca = "SELECT cpf_cliente FROM pets WHERE id = :id";
            $stmt_busca = $pdo->prepare($sql_busca);
            $stmt_busca->execute([':id' => $id]);
            $cpf_dono = $stmt_busca->fetchColumn();

            // Inicia a transaÃ§Ã£o
            $pdo->beginTransaction();
            
            // 2. Apaga o pet
            $sql_del = "DELETE FROM pets WHERE id = :id";
            $stmt_del = $pdo->prepare($sql_del);
            $stmt_del->execute([':id' => $id]);
            
            // 3. Verifica quantos pets restaram e se o tutor Ã© ADMIN
            $sql_check = "SELECT 
                            (SELECT COUNT(*) FROM pets WHERE cpf_cliente = :cpf) as pets_restantes,
                            is_admin 
                          FROM clientes WHERE cpf = :cpf";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([':cpf' => $cpf_dono]);
            $dados_tutor = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            // 4. EXCEÃÃO DA REGRA: SÃ³ limpa o Ã³rfÃ£o se os pets chegarem a zero E nÃ£o for admin
            if ($dados_tutor && $dados_tutor['pets_restantes'] == 0 && $dados_tutor['is_admin'] == 0) {
                $sql_del_tutor = "DELETE FROM clientes WHERE cpf = :cpf";
                $stmt_del_tutor = $pdo->prepare($sql_del_tutor);
                $stmt_del_tutor->execute([':cpf' => $cpf_dono]);
                $mensagem = "Pet excluÃ­do. O tutor foi removido pois nÃ£o possui mais pets.";
            } else {
                $mensagem = "Pet excluÃ­do com sucesso. O cadastro do usuÃ¡rio foi mantido.";
            }
            
            // Confirma tudo no banco
            $pdo->commit();
            
            header("Location: admin_painel.php?msg=" . urlencode($mensagem));
            exit;
        }
    } catch (PDOException $e) {
        // Se der erro no meio da transaÃ§Ã£o do pet, cancela a operaÃ§Ã£o
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Erro ao excluir: " . $e->getMessage());
    }
} else {
    header("Location: admin_painel.php");
    exit;
}
?>