
<?php
require_once __DIR__ . "/sesion.php";

// Comprobar si el administrador inició sesión.
if (!isset($_SESSION["administrador_id"])) {
    header("Location: login.php");
    exit;
}
?>
