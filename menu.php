<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$is_logado = isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true;
$is_master_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

if (!$is_logado) return; 
?>
<meta charset="UTF-8"> <!-- Força a acentuação correta se o servidor falhar -->
<style>
    header { 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        background-color: #2c3e50; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
        z-index: 1000; 
    }
    
    header nav { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 10px 20px; /* Reduzi o padding lateral para dar mais espaço central */
        max-width: 1400px; 
        margin: 0 auto; 
    }
    
    .logo { 
        color: #fff; 
        font-size: 1.5rem; 
        font-weight: bold; 
        white-space: nowrap; /* Impede a logo de quebrar */
    }
    
    header nav ul { 
        display: flex; 
        list-style: none; 
        margin: 0; 
        padding: 0; 
        gap: 15px; /* Reduzi o espaço entre os botões para caberem todos */
        align-items: center; 
    }
    
    header nav ul li a { 
        text-decoration: none; 
        color: #ecf0f1; 
        font-weight: 600; 
        font-size: 0.9rem; /* Diminuí levemente a fonte */
        padding: 8px 12px; 
        border-radius: 4px; 
        transition: 0.2s; 
        white-space: nowrap; /* IMPEDE A QUEBRA DE LINHA DOS BOTÕES */
    }
    
    header nav ul li a:hover { background-color: #34495e; color: #3498db; }
    .btn-lote { background-color: #27ae60; color: white !important; }
    .btn-lote:hover { background-color: #2ecc71 !important; }
    .btn-sair { border: 1px solid #e74c3c; color: #e74c3c !important; }
    .btn-sair:hover { background-color: #e74c3c !important; color: white !important; }
    
    .user-tag { 
        color: #bdc3c7; 
        font-size: 0.85rem; 
        margin-right: 15px; 
        white-space: nowrap; 
    }
</style>

<header>
    <nav>
        <div class="logo">Cozinha Pet <span style="font-weight: 300; color: #3498db;">_ERP</span></div>
        <ul>
            <!-- MÓDULO OPERACIONAL -->
            <?php if ($is_logado): ?>
                <li><a href="lote_semanal.php" class="btn-lote">Lote Semanal</a></li>
            <?php endif; ?>

            <!-- MÓDULO DE GESTÃO E ADMINISTRAÇÃO -->
            <?php if ($is_master_admin): ?>
                <li><a href="admin_painel.php">Clientes e Pets</a></li>
                <li><a href="admin_receitas.php">Receitas</a></li>
                <li><a href="admin_usuarios.php" style="color: #f1c40f;">Equipe & Acessos</a></li>
                <li><a href="estoque.php">Estoque</a></li>
            <?php endif; ?>

            <!-- GESTÃO DE SESSÃO / TROCA DE USUÁRIO -->
            <li style="display: flex; align-items: center;">
                <span class="user-tag">Olá, <?= htmlspecialchars($_SESSION['admin_nome'] ?? 'Equipe', ENT_QUOTES, 'UTF-8') ?></span>
                <a href="logout.php" class="btn-sair">Trocar Usuário</a>
            </li>
        </ul>
    </nav>
</header>
<div style="margin-top: 80px;"></div>