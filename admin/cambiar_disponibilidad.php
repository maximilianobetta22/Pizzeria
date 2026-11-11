
<?php
require_once "proteger.php";
require_once "../config/conexion.php";

// Solo aceptar solicitudes POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método no permitido.");
}

// Comprobar el token de seguridad.
$token = $_POST["token_csrf"] ?? "";

if (!hash_equals($_SESSION["token_csrf"], $token)) {
    http_response_code(403);
    exit("Solicitud no válida.");
}

// Validar el producto y el estado.
$id_producto = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$disponible = filter_input(INPUT_POST, "estado", FILTER_VALIDATE_INT);

if (
    !$id_producto ||
    $id_producto < 1 ||
    !in_array($disponible, [0, 1], true)
) {
    exit("Los datos recibidos no son válidos.");
}

// Actualizar la disponibilidad.
$consulta = $conexion->prepare(
    "UPDATE productos SET disponible = ? WHERE id_producto = ?"
);

$consulta->bind_param("ii", $disponible, $id_producto);
$consulta->execute();
$consulta->close();

// Regresar al listado.
header("Location: productos.php");
exit;
?>
