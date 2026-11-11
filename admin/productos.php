<?php

// =====================================================
// CONEXIÓN A LA BASE DE DATOS
// =====================================================

require_once "proteger.php";
require_once "../config/conexion.php";


// =====================================================
// VARIABLES PARA MENSAJES Y FORMULARIO
// =====================================================

$mensaje = "";
$tipoMensaje = "";

$nombre = "";
$descripcion = "";
$precio = "";
$idCategoria = "";
$destacado = false;


// =====================================================
// OBTENER CATEGORÍAS ACTIVAS
// =====================================================

$sqlCategorias = "
    SELECT id_categoria, nombre
    FROM categorias
    WHERE activo = 1
    ORDER BY nombre ASC
";

$resultadoCategorias = $conexion->query($sqlCategorias);

if (!$resultadoCategorias) {
    die("No se pudieron cargar las categorías.");
}

$categorias = $resultadoCategorias->fetch_all(MYSQLI_ASSOC);


// =====================================================
// PROCESAR EL FORMULARIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validar el token CSRF.
    $token = $_POST["token_csrf"] ?? "";

    if (!hash_equals($_SESSION["token_csrf"], $token)) {
        http_response_code(403);
        exit("Solicitud no válida.");
    }

    // Recoger los datos enviados por el formulario.
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $precio = trim($_POST["precio"] ?? "");
    $idCategoria = $_POST["id_categoria"] ?? "";
    $destacado = isset($_POST["destacado"]);

    // Validar los datos recibidos.
    if (
        $nombre === "" ||
        $descripcion === "" ||
        !ctype_digit($precio) ||
        (int) $precio <= 0 ||
        !ctype_digit((string) $idCategoria)
    ) {
        $mensaje = "Completa los campos correctamente.";
        $tipoMensaje = "error";
    } else {

        // Comprobar que la categoría exista y esté activa.
        $sqlValidarCategoria = "
            SELECT id_categoria
            FROM categorias
            WHERE id_categoria = ?
              AND activo = 1
        ";

        $consultaCategoria = $conexion->prepare($sqlValidarCategoria);
        $consultaCategoria->bind_param("i", $idCategoria);
        $consultaCategoria->execute();

        $resultadoCategoria = $consultaCategoria->get_result();

        if ($resultadoCategoria->num_rows === 0) {

            $mensaje = "Selecciona una categoría válida.";
            $tipoMensaje = "error";
        } else {

            // Insertar el producto mediante una consulta preparada.
            $sqlInsertar = "
                INSERT INTO productos
                    (id_categoria, nombre, descripcion, precio,
                     imagen, disponible, destacado)
                VALUES (?, ?, ?, ?, NULL, 1, ?)
            ";

            $consultaInsertar = $conexion->prepare($sqlInsertar);

            $precioNumero = (int) $precio;
            $valorDestacado = $destacado ? 1 : 0;

            $consultaInsertar->bind_param(
                "issii",
                $idCategoria,
                $nombre,
                $descripcion,
                $precioNumero,
                $valorDestacado
            );

            if ($consultaInsertar->execute()) {

                // Limpiar los campos después de guardar.
                $mensaje = "Producto agregado correctamente.";
                $tipoMensaje = "exito";

                $nombre = "";
                $descripcion = "";
                $precio = "";
                $idCategoria = "";
                $destacado = false;
            } else {

                $mensaje = "No se pudo guardar el producto.";
                $tipoMensaje = "error";
            }

            $consultaInsertar->close();
        }

        $consultaCategoria->close();
    }
}


// =====================================================
// OBTENER PRODUCTOS PARA EL LISTADO
// =====================================================

$sqlProductos = "
    SELECT
        p.id_producto,
        p.nombre,
        p.precio,
        p.disponible,
        p.destacado,
        c.nombre AS categoria
    FROM productos p
    INNER JOIN categorias c
        ON p.id_categoria = c.id_categoria
    ORDER BY p.id_producto DESC
";

$resultadoProductos = $conexion->query($sqlProductos);

if (!$resultadoProductos) {
    die("No se pudieron cargar los productos.");
}


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

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar productos | Pizzas Los Navíos</title>

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
        textarea,
        select {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            margin-top: 6px;
        }

        input[type="checkbox"] {
            width: auto;
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
    </style>
</head>

<body>

    <h1>Administración de productos</h1>

    <p>Agrega productos a la carta de Pizzas Los Navíos.</p>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje <?= escapar($tipoMensaje) ?>">
            <?= escapar($mensaje) ?>
        </div>
    <?php endif; ?>


    <!-- FORMULARIO PARA AGREGAR PRODUCTOS xd -->

    <h2>Agregar producto</h2>

    <form method="POST" action="productos.php">

        <input
            type="hidden"
            name="token_csrf"
            value="<?= escapar($_SESSION["token_csrf"]) ?>">

        <label for="nombre">Nombre del producto</label>
        <input
            type="text"
            id="nombre"
            name="nombre"
            maxlength="150"
            value="<?= escapar($nombre) ?>"
            required>

        <label for="descripcion">Descripción</label>
        <textarea
            id="descripcion"
            name="descripcion"
            rows="3"
            required><?= escapar($descripcion) ?></textarea>

        <label for="precio">Precio en pesos chilenos</label>
        <input
            type="number"
            id="precio"
            name="precio"
            min="1"
            step="1"
            value="<?= escapar($precio) ?>"
            required>

        <label for="id_categoria">Categoría</label>
        <select
            id="id_categoria"
            name="id_categoria"
            required>
            <option value="">Selecciona una categoría</option>

            <?php foreach ($categorias as $categoria): ?>
                <option
                    value="<?= escapar($categoria["id_categoria"]) ?>"
                    <?= (string) $idCategoria === (string) $categoria["id_categoria"] ? "selected" : "" ?>>
                    <?= escapar($categoria["nombre"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>
            <input
                type="checkbox"
                name="destacado"
                value="1"
                <?= $destacado ? "checked" : "" ?>>
            Mostrar como producto destacado
        </label>

        <button type="submit">Guardar producto</button>

    </form>


    <!-- LISTADO DE PRODUCTOS -->

    <h2>Productos registrados</h2>

    <div class="contenedor-tabla">
        <div class="tabla-desplazable">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Disponible</th>
                        <th>Destacado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($producto = $resultadoProductos->fetch_assoc()): ?>
                        <tr>
                            <td><?= escapar($producto["id_producto"]) ?></td>
                            <td><?= escapar($producto["nombre"]) ?></td>
                            <td><?= escapar($producto["categoria"]) ?></td>
                            <td>
                                $<?= number_format(
                                        (float) $producto["precio"],
                                        0,
                                        ",",
                                        "."
                                    ) ?>
                            </td>
                            <td>
                                <?= $producto["disponible"] ? "Sí" : "No" ?>
                            </td>
                            <td>
                                <?= $producto["destacado"] ? "Sí" : "No" ?>
                            </td>
                            <td>
                                <a href="editar_producto.php?id=<?= escapar($producto["id_producto"]) ?>">
                                    Editar
                                </a>

                                <?php if ((int) $producto["disponible"] === 1): ?>
                                    <form method="POST" action="cambiar_disponibilidad.php">
                                        <input type="hidden" name="token_csrf"
                                            value="<?= escapar($_SESSION["token_csrf"]) ?>">
                                        <input type="hidden" name="id"
                                            value="<?= escapar($producto["id_producto"]) ?>">
                                        <input type="hidden" name="estado" value="0">

                                        <button type="submit"
                                            onclick="return confirm('¿Desactivar este producto?');">
                                            Desactivar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="cambiar_disponibilidad.php">
                                        <input type="hidden" name="token_csrf"
                                            value="<?= escapar($_SESSION["token_csrf"]) ?>">
                                        <input type="hidden" name="id"
                                            value="<?= escapar($producto["id_producto"]) ?>">
                                        <input type="hidden" name="estado" value="1">

                                        <button type="submit"
                                            onclick="return confirm('¿Activar este producto?');">
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