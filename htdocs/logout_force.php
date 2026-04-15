<?php
// Limpa completamente qualquer sessão existente
session_start();
$_SESSION = [];
session_destroy();

// Deleta o cookie de sessão do navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redireciona para o login limpo
header("Location: login.php");
exit;
