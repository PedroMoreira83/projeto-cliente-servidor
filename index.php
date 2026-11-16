<?php
session_start();
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    header("Location: admin_painel.php");
} else {
    header("Location: admin_login.php");
}
exit;
?>