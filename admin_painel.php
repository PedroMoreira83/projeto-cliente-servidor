<?php
require_once 'verifica_master.php'; // Apenas Admin Master acessa o painel completo
require_once 'api/config/conexao.php';

$erro = ""; $sucesso = "";

// AÇÃO: EXCLUIR CLIENTE (Apaga o cliente e, em cascata, todos os cães dele)
if (isset($_GET['excluir'])) {
    $cpf_del = preg_replace('/[^0-9]/', '', $_GET['excluir']);
    
    // Trava para não excluir a si mesmo acidentalmente
    if ($cpf_del === $_SESSION['admin_cpf']) {
        $erro = "Você não pode excluir o seu próprio cadastro de administrador!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE cpf = ?");
            $stmt->execute([$cpf_del]);
            $sucesso = "Cliente e seus pets foram removidos do sistema com sucesso!";
        } catch (Exception $e) {
            $erro = "Erro ao excluir o cliente: " . $e->getMessage();
        }
    }
}

// 1. BUSCA TODOS OS CLIENTES (Ordenados alfabeticamente)
$sql_clientes = "SELECT cpf, nome, nome_secundario, telefone, is_admin FROM clientes ORDER BY nome";
$clientes = $pdo->query($sql_clientes)->fetchAll(PDO::FETCH_ASSOC);

// 2. BUSCA TODOS OS PETS E SUAS RECEITAS
$sql_pets = "SELECT p.id, p.cpf_cliente, p.nome AS pet_nome, r.tipo AS tipo_receita 
             FROM pets p 
             LEFT JOIN receitas r ON p.id_receita = r.id 
             ORDER BY p.nome";
$pets_raw = $pdo->query($sql_pets)->fetchAll(PDO::FETCH_ASSOC);

// 3. AGRUPA OS PETS POR CPF DO DONO PARA A EXIBIÇÃO NO HTML
$pets_por_cliente = [];
foreach ($pets_raw as $pet) {
    $pets_por_cliente[$pet['cpf_cliente']][] = $pet;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Clientes e Pets - ERP</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tabela-painel { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tabela-painel th, .tabela-painel td { padding: 15px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: middle; }
        .tabela-painel th { background: #2c3e50; color: #fff; position: sticky; top: 70px; }
        .tabela-painel tbody tr:hover { background: #f8f9fa; }
        
        /* Botões de Ação do Cliente */
        .btn-acao { padding: 8px 12px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; text-decoration: none; color: white; display: inline-block; margin-right: 5px; }
        .btn-edit-cli { background: #ffc107; color: #333; }
        .btn-del-cli { background: #dc3545; }
        .btn-novo-cli { background: #28a745; font-size: 1rem; padding: 10px 20px; }
        
        /* Box / "Pílula" de exibição dos Pets */
        .pet-card { background: #e9ecef; border: 1px solid #ccc; border-radius: 6px; padding: 8px 12px; display: inline-flex; align-items: center; gap: 10px; margin: 4px; }
        .pet-name { font-weight: bold; color: #007bff; font-size: 0.95rem; }
        .pet-badge-padrao { background: #28a745; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
        .pet-badge-clinica { background: #e83e8c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
        .btn-edit-pet { background: #17a2b8; color: white; border-radius: 4px; padding: 4px 8px; text-decoration: none; font-size: 0.75rem; font-weight: bold; }
        .btn-edit-pet:hover { background: #138496; }
    </style>
</head>
<body style="background-color: #f4f4f9;">
    <?php include 'menu.php'; ?>
    
    <section style="padding: 40px 20px; max-width: 1400px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #007bff; padding-bottom: 15px;">
            <div>
                <h2 style="color: #333; margin: 0; font-size: 2rem;">Painel <span style="color: #007bff;">Administrativo</span></h2>
                <p style="color: #666; margin-top: 5px; margin-bottom: 0;">Gestão completa de tutores, co-tutores e cães vinculados.</p>
            </div>
            <!-- Esse botão chamará o seu formulário de criação de cliente no futuro -->
            <a href="admin_editar_cliente.php" class="btn-acao btn-novo-cli">+ Cadastrar Novo Cliente</a>
        </div>

        <?php if ($sucesso) echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;'>$sucesso</div>"; ?>
        <?php if ($erro) echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;'>$erro</div>"; ?>

        <table class="tabela-painel">
            <thead>
                <tr>
                    <th style="width: 20%;">Responsável (Tutor)</th>
                    <th style="width: 15%;">Contato / CPF</th>
                    <th style="width: 50%;">Cães e Dietas (Clique para editar o Pet)</th>
                    <th style="width: 15%; text-align: center;">Gestão do Tutor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cli): ?>
                    <tr>
                        <!-- DADOS DO TUTOR E CO-TUTOR -->
                        <td>
                            <strong style="font-size: 1.1rem; color: #333;"><?= htmlspecialchars($cli['nome']) ?></strong>
                            <?php if (!empty($cli['nome_secundario'])): ?>
                                <br><small style="color: #666; font-weight: bold;">Co-tutor: <span style="font-weight: normal;"><?= htmlspecialchars($cli['nome_secundario']) ?></span></small>
                            <?php endif; ?>
                            
                            <?php if ($cli['is_admin'] > 0): ?>
                                <br><span style="background: #ffc107; color: #333; font-size: 0.7rem; font-weight: bold; padding: 2px 6px; border-radius: 4px;">EQUIPE / FUNCIONÁRIO</span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- CONTATO -->
                        <td style="color: #555; font-size: 0.9rem;">
                            CPF: <?= htmlspecialchars($cli['cpf']) ?><br>
                            Tel: <?= empty($cli['telefone']) ? 'Não cadastrado' : htmlspecialchars($cli['telefone']) ?>
                        </td>
                        
                        <!-- LISTAGEM INTELIGENTE DE CÃES (Gera um card para cada cão do cliente) -->
                        <td>
                            <?php if (isset($pets_por_cliente[$cli['cpf']])): ?>
                                <?php foreach ($pets_por_cliente[$cli['cpf']] as $pet): ?>
                                    <div class="pet-card">
                                        <span class="pet-name"><?= htmlspecialchars($pet['pet_nome']) ?></span>
                                        
                                        <?php if ($pet['tipo_receita'] === 'especifica'): ?>
                                            <span class="pet-badge-clinica">CLÍNICA</span>
                                        <?php else: ?>
                                            <span class="pet-badge-padrao">PADRÃO</span>
                                        <?php endif; ?>
                                        
                                        <!-- O LINK DINÂMICO QUE ESTAVA FALTANDO! -->
                                        <a href="admin_editar_pet.php?id=<?= $pet['id'] ?>" class="btn-edit-pet">⚙️ Ficha Clínica</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #999; font-style: italic; font-size: 0.85rem;">Nenhum cão cadastrado.</span>
                            <?php endif; ?>
                            
                            <!-- Botão para adicionar mais um cão para este mesmo dono -->
                            <a href="#" style="font-size: 0.8rem; color: #28a745; font-weight: bold; text-decoration: none; margin-left: 10px; display: inline-block; margin-top: 5px;">+ Novo Pet</a>
                        </td>
                        
                        <!-- AÇÕES DO CLIENTE -->
                        <td style="text-align: center;">
                            <a href="admin_editar_cliente.php?cpf=<?= $cli['cpf'] ?>" class="btn-acao btn-edit-cli" title="Editar Nome, CPF e Contatos do Tutor">Editar</a>
                            <a href="admin_painel.php?excluir=<?= $cli['cpf'] ?>" class="btn-acao btn-del-cli" onclick="return confirm('ATENÇÃO: Isso apagará o cliente e todos os cães dele do sistema. Tem certeza?');" title="Excluir Tutor">X</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</body>
</html>