<?php

// =====================================================
// CONEXIÓN Y PROTECCIÓN
// =====================================================

require_once "proteger.php";
require_once "../config/conexion.php";

// =====================================================
// VARIABLES
// =====================================================

$mensaje = "";
$tipoMensaje = "";
$modoEdicion = false;

$idCategoria = "";
$nombre = "";
$descripcion = "";

// =====================================================
// FUNCIÓN PARA MOSTRAR TEXTO DE FORMA SEGURA
// =====================================================

function escapar($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// =====================================================
// PROCESAR FORMULARIOS
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validar token CSRF.
    $token = $_POST["token_csrf"] ?? "";

    if (!hash_equals($_SESSION["token_csrf"], $token)) {
        http_response_code(403);
        exit("Solicitud no válida.");
    }

    $accion = $_POST["accion"] ?? "";

    // -------------------------------------------------
    // CREAR CATEGORÍA
    // -------------------------------------------------

    if ($accion === "crear") {

        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");

        if ($nombre === "" || $descripcion === "") {

            $mensaje = "Completa el nombre y la descripción.";
            $tipoMensaje = "error";
        } else {

            $consulta = $conexion->prepare(
                "INSERT INTO categorias (nombre, descripcion, activo)
                 VALUES (?, ?, 1)"
            );

            $consulta->bind_param(
                "ss",
                $nombre,
                $descripcion
            );

            if ($consulta->execute()) {
                header("Location: categorias.php?mensaje=creada");
                exit;
            } else {
                $mensaje = "No se pudo crear la categoría.";
                $tipoMensaje = "error";
            }

            $consulta->close();
        }
    }

    // -------------------------------------------------
    // EDITAR CATEGORÍA
    // -------------------------------------------------

    elseif ($accion === "editar") {

        $idCategoria = filter_input(
            INPUT_POST,
            "id_categoria",
            FILTER_VALIDATE_INT
        );

        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");

        if (
            !$idCategoria ||
            $idCategoria < 1 ||
            $nombre === "" ||
            $descripcion === ""
        ) {

            $mensaje = "Los datos de la categoría no son válidos.";
            $tipoMensaje = "error";
        } else {

            $consulta = $conexion->prepare(
                "UPDATE categorias
                 SET nombre = ?, descripcion = ?
                 WHERE id_categoria = ?"
            );

            $consulta->bind_param(
                "ssi",
                $nombre,
                $descripcion,
                $idCategoria
            );

            if ($consulta->execute()) {
                header("Location: categorias.php?mensaje=editada");
                exit;
            } else {
                $mensaje = "No se pudo actualizar la categoría.";
                $tipoMensaje = "error";
            }

            $consulta->close();
        }
    }

    // -------------------------------------------------
    // CAMBIAR ESTADO DE LA CATEGORÍA
    // -------------------------------------------------

    elseif ($accion === "estado") {

        $idCategoria = filter_input(
            INPUT_POST,
            "id_categoria",
            FILTER_VALIDATE_INT
        );

        $estado = filter_input(
            INPUT_POST,
            "estado",
            FILTER_VALIDATE_INT
        );

        if (
            !$idCategoria ||
            $idCategoria < 1 ||
            !in_array($estado, [0, 1], true)
        ) {

            $mensaje = "Los datos recibidos no son válidos.";
            $tipoMensaje = "error";
        } else {

            $consulta = $conexion->prepare(
                "UPDATE categorias
                 SET activo = ?
                 WHERE id_categoria = ?"
            );

            $consulta->bind_param(
                "ii",
                $estado,
                $idCategoria
            );

            if ($consulta->execute()) {
                header("Location: categorias.php?mensaje=estado");
                exit;
            } else {
                $mensaje = "No se pudo cambiar el estado.";
                $tipoMensaje = "error";
            }

            $consulta->close();
        }
    }
}

// =====================================================
// MENSAJES DESPUÉS DE LAS OPERACIONES
// =====================================================

if (isset($_GET["mensaje"])) {

    switch ($_GET["mensaje"]) {

        case "creada":
            $mensaje = "Categoría creada correctamente.";
            $tipoMensaje = "exito";
            break;

        case "editada":
            $mensaje = "Categoría actualizada correctamente.";
            $tipoMensaje = "exito";
            break;

        case "estado":
            $mensaje = "Estado de la categoría actualizado.";
            $tipoMensaje = "exito";
            break;
    }
}

// =====================================================
// CARGAR CATEGORÍA PARA EDITAR
// =====================================================

if (isset($_GET["editar"])) {

    $idEditar = filter_input(
        INPUT_GET,
        "editar",
        FILTER_VALIDATE_INT
    );

    if ($idEditar && $idEditar > 0) {

        $consulta = $conexion->prepare(
            "SELECT id_categoria, nombre, descripcion
             FROM categorias
             WHERE id_categoria = ?"
        );

        $consulta->bind_param("i", $idEditar);
        $consulta->execute();

        $resultado = $consulta->get_result();
        $categoriaEditar = $resultado->fetch_assoc();

        if ($categoriaEditar) {

            $modoEdicion = true;
            $idCategoria = $categoriaEditar["id_categoria"];
            $nombre = $categoriaEditar["nombre"];
            $descripcion = $categoriaEditar["descripcion"];
        } else {

            $mensaje = "La categoría no existe.";
            $tipoMensaje = "error";
        }

        $consulta->close();
    }
}

// =====================================================
// OBTENER TODAS LAS CATEGORÍAS
// =====================================================

$sqlCategorias = "
    SELECT
        id_categoria,
        nombre,
        descripcion,
        activo,
        fecha_creacion
    FROM categorias
    ORDER BY id_categoria DESC
";

$resultadoCategorias = $conexion->query($sqlCategorias);

if (!$resultadoCategorias) {
    die("No se pudieron cargar las categorías.");
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar categorías | Pizzas Los Navíos</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 16px;
            color: #292929;
            background: #f7f7f7;
        }

        h1,
        h2 {
            color: #333;
        }

        form,
        .contenedor-tabla {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 28px;
        }

        label {
            display: block;
            margin-top: 14px;
            font-weight: bold;
        }

        input,
        textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button {
            margin-top: 18px;
            padding: 12px 18px;
            cursor: pointer;
            background: #a62929;
            color: white;
            border: none;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #eee;
        }

        .mensaje {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .exito {
            background: #d8f3dc;
            color: #1b5e20;
        }

        .error {
            background: #ffe0e0;
            color: #9b1c1c;
        }

        .tabla-desplazable {
            overflow-x: auto;
        }

        .estado-activo {
            color: #1b5e20;
            font-weight: bold;
        }

        .estado-inactivo {
            color: #9b1c1c;
            font-weight: bold;
        }

        .enlace-volver {
            display: inline-block;
            margin-bottom: 20px;
            color: #a62929;
        }

        .formulario-accion {
            display: inline-block;
            padding: 0;
            margin: 0;
            background: transparent;
        }

        .formulario-accion button {
            margin-top: 5px;
        }
    </style>

</head>

<body>

    <h1>Administración de categorías</h1>

    <p>Administra las categorías de la carta de Pizzas Los Navíos.</p>

    <a class="enlace-volver" href="productos.php">
        ← Volver a productos
    </a>

    <?php if ($mensaje !== ""): ?>

        <div class="mensaje <?= escapar($tipoMensaje) ?>">
            <?= escapar($mensaje) ?>
        </div>

    <?php endif; ?>


    <!-- FORMULARIO PARA CREAR O EDITAR -->

    <h2>
        <?= $modoEdicion ? "Editar categoría" : "Agregar categoría" ?>
    </h2>

    <form method="POST" action="categorias.php">

        <input
            type="hidden"
            name="token_csrf"
            value="<?= escapar($_SESSION["token_csrf"]) ?>">

        <input
            type="hidden"
            name="accion"
            value="<?= $modoEdicion ? "editar" : "crear" ?>">

        <?php if ($modoEdicion): ?>

            <input
                type="hidden"
                name="id_categoria"
                value="<?= escapar($idCategoria) ?>">

        <?php endif; ?>

        <label for="nombre">Nombre de la categoría</label>

        <input
            type="text"
            id="nombre"
            name="nombre"
            maxlength="100"
            value="<?= escapar($nombre) ?>"
            required>

        <label for="descripcion">Descripción</label>

        <textarea
            id="descripcion"
            name="descripcion"
            required><?= escapar($descripcion) ?></textarea>

        <button type="submit">
            <?= $modoEdicion ? "Guardar cambios" : "Crear categoría" ?>
        </button>

        <?php if ($modoEdicion): ?>

            <a href="categorias.php">Cancelar edición</a>

        <?php endif; ?>

    </form>


    <!-- LISTADO DE CATEGORÍAS -->

    <h2>Categorías registradas</h2>

    <div class="contenedor-tabla">

        <div class="tabla-desplazable">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Fecha de creación</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($categoria = $resultadoCategorias->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= escapar($categoria["id_categoria"]) ?>
                            </td>

                            <td>
                                <?= escapar($categoria["nombre"]) ?>
                            </td>

                            <td>
                                <?= escapar($categoria["descripcion"]) ?>
                            </td>

                            <td>

                                <?php if ((int) $categoria["activo"] === 1): ?>

                                    <span class="estado-activo">Activa</span>

                                <?php else: ?>

                                    <span class="estado-inactivo">Inactiva</span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= escapar($categoria["fecha_creacion"]) ?>
                            </td>

                            <td>

                                <a href="categorias.php?editar=<?= escapar($categoria["id_categoria"]) ?>">
                                    Editar
                                </a>

                                <?php if ((int) $categoria["activo"] === 1): ?>

                                    <form
                                        class="formulario-accion"
                                        method="POST"
                                        action="categorias.php">

                                        <input
                                            type="hidden"
                                            name="token_csrf"
                                            value="<?= escapar($_SESSION["token_csrf"]) ?>">

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="estado">

                                        <input
                                            type="hidden"
                                            name="id_categoria"
                                            value="<?= escapar($categoria["id_categoria"]) ?>">

                                        <input
                                            type="hidden"
                                            name="estado"
                                            value="0">

                                        <button
                                            type="submit"
                                            onclick="return confirm('¿Desactivar esta categoría?');">

                                            Desactivar

                                        </button>

                                    </form>

                                <?php else: ?>

                                    <form
                                        class="formulario-accion"
                                        method="POST"
                                        action="categorias.php">

                                        <input
                                            type="hidden"
                                            name="token_csrf"
                                            value="<?= escapar($_SESSION["token_csrf"]) ?>">

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="estado">

                                        <input
                                            type="hidden"
                                            name="id_categoria"
                                            value="<?= escapar($categoria["id_categoria"]) ?>">

                                        <input
                                            type="hidden"
                                            name="estado"
                                            value="1">

                                        <button
                                            type="submit"
                                            onclick="return confirm('¿Activar esta categoría?');">

                                            Activar

                                        </button>

                                    </form>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

    <form method="POST" action="logout.php">

        <input
            type="hidden"
            name="token_csrf"
            value="<?= escapar($_SESSION["token_csrf"]) ?>">

        <button type="submit">Cerrar sesión</button>

    </form>

</body>

</html>