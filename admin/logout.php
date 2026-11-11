
<?php
require_once "proteger.php";

// El cierre de sesión solo se permite mediante POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método no permitido.");
}

$token = $_POST["token_csrf"] ?? "";

if (!hash_equals($_SESSION["token_csrf"], $token)) {
    http_response_code(403);
    exit("Solicitud no válida.");
}

// Vaciar los datos de sesión.
$_SESSION = [];

// Eliminar la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        [
            "expires" => time() - 42000,
            "path" => $parametros["path"],
            "domain" => $parametros["domain"],
            "secure" => $parametros["secure"],
            "httponly" => $parametros["httponly"],
            "samesite" => "Lax"
        ]
    );
}

// Destruir la sesión.
session_destroy();

header("Location: login.php");
exit;
?>
