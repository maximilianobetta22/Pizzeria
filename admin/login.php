<?php
require_once "../config/conexion.php";
require_once "sesion.php";

// Si ya inició sesión, ir directamente al panel.
if (isset($_SESSION["administrador_id"])) {
    header("Location: productos.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token_csrf"] ?? "";
    $usuario = trim($_POST["usuario"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if (!hash_equals($_SESSION["token_csrf"], $token)) {
        $error = "La solicitud no es válida.";
    } else {
        // Buscar la cuenta por su nombre de usuario.
        $consulta = $conexion->prepare(
            "SELECT id_administrador, usuario, contrasena
             FROM administradores
             WHERE usuario = ?
             LIMIT 1"
        );

        $consulta->bind_param("s", $usuario);
        $consulta->execute();

        $resultado = $consulta->get_result();
        $administrador = $resultado->fetch_assoc();

        $consulta->close();

        // Comprobar la contraseña.
        if (
            $administrador &&
            password_verify($contrasena, $administrador["contrasena"])
        ) {
            // Renovar el identificador de sesión al iniciar sesión.
            session_regenerate_id(true);

            $_SESSION["administrador_id"] =
                $administrador["id_administrador"];

            $_SESSION["administrador_usuario"] =
                $administrador["usuario"];

            // Renovar el token CSRF.
            $_SESSION["token_csrf"] = bin2hex(random_bytes(32));

            header("Location: productos.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso administrador | Pizzas Los Navíos</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 15px;
            box-sizing: border-box;
        }

        .contenedor-login {
            width: 100%;
            max-width: 380px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 18px #00000015;
            box-sizing: border-box;
        }

        h1 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        input {
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 22px;
            background: #a51d24;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .error {
            background: #ffe5e5;
            color: #9b111e;
            padding: 12px;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <main class="contenedor-login">
        <h1>Panel de administración</h1>
        <p>Ingresa tus credenciales para continuar.</p>

        <?php if ($error !== ""): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <input
                type="hidden"
                name="token_csrf"
                value="<?= htmlspecialchars($_SESSION["token_csrf"], ENT_QUOTES, "UTF-8") ?>">

            <label for="usuario">Usuario</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                autocomplete="username"
                required>

            <label for="contrasena">Contraseña</label>
            <input
                type="password"
                id="contrasena"
                name="contrasena"
                autocomplete="current-password"
                required>

            <button type="submit">Iniciar sesión</button>
        </form>
    </main>
</body>

</html>