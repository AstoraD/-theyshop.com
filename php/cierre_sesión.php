<?php
session_start(); // Inicia la sesión

// Eliminar todas las variables de sesión
$_SESSION = [];

// Si se usan cookies, eliminar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir completamente la sesión
session_destroy();

// Redirigir al inicio y marcar sesión como cerrada
echo '<script>
    sessionStorage.setItem("session_expired", "true");
    window.location.href = "/Proyecto/index.html";
</script>';
exit;
?>