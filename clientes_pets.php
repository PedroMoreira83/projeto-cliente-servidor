<?php
// Inclui o arquivo de conexÃ£o PDO criado anteriormente
require_once 'api/config/conexao.php';

// Consulta SQL utilizando LEFT JOIN para trazer os clientes e, se houver, seus pets vinculados
$sql = "SELECT 
            c.cpf, 
            c.nome AS tutor, 
            c.telefone, 
            p.nome AS pet, 
            p.raca, 
            p.peso_kg, 
            p.qtd_porcoes, 
            p.peso_porcao_gramas 
        FROM clientes c
        LEFT JOIN pets p ON c.cpf = p.cpf_cliente
        ORDER BY c.nome, p.nome";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar registros: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cozinha Pet Afetiva - Clientes e Pets</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tabela-cadastros {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .tabela-cadastros th, .tabela-cadastros td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .tabela-cadastros th {
            background-color: #4CAF50;
            color: white;
        }
        .tabela-cadastros tr:hover {
            background-color: #f1f1f1;
        }
        .badge-sem-pet {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>

    <?php include 'menu.php'; ?>
    
    <section class="hero" style="padding-top: 20px; min-height: 80vh; align-items: flex-start;">
        <div class="hero-content" style="width: 100%; max-width: 1000px; margin: 0 auto; text-align: left;">
            <h2>Lista de <span class="highlight">Tutores e Pets</span></h2>
            <p>VisÃ£o geral dos clientes cadastrados e a demanda base para o lote semanal.</p>
            
            <?php if (count($cadastros) > 0): ?>
                <table class="tabela-cadastros">
                    <thead>
                        <tr>
                            <th>Tutor</th>
                            <th>CPF</th>
                            <th>Pet</th>
                            <th>RaÃ§a</th>
                            <th>Peso (kg)</th>
                            <th>Demanda Semanal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cadastros as $linha): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($linha['tutor']) ?></strong></td>
                                <td><?= htmlspecialchars($linha['cpf']) ?></td>
                                
                                <?php if (!empty($linha['pet'])): ?>
                                    <td><?= htmlspecialchars($linha['pet']) ?></td>
                                    <td><?= htmlspecialchars($linha['raca']) ?></td>
                                    <td><?= htmlspecialchars($linha['peso_kg']) ?> kg</td>
                                    <td><?= $linha['qtd_porcoes'] ?> marmitas de <?= $linha['peso_porcao_gramas'] ?>g</td>
                                <?php else: ?>
                                    <td colspan="4" class="badge-sem-pet">Nenhum pet cadastrado para este tutor ainda.</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    Nenhum cliente ou pet encontrado no banco de dados.
                </div>
            <?php endif; ?>
            
        </div>
    </section>

</body>
</html>