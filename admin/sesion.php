
<?php
// Configurar las cookies de sesión antes de iniciar la sesión.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "httponly" => true,
        "secure" => false, // En localhost usamos HTTP.
        "samesite" => "Lax"
    ]);

    session_start();
}

// Crear un token CSRF si todavía no existe.
if (empty($_SESSION["token_csrf"])) {
    $_SESSION["token_csrf"] = bin2hex(random_bytes(32));
}
?>
