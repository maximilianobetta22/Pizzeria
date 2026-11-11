<?php
require_once "proteger.php";
require_once "../config/conexion.php";

// Obtener y validar el ID del producto.
$id_producto = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_producto || $id_producto < 1) {
    exit("El ID del producto no es válido.");
}

// Buscar el producto.
$consulta = $conexion->prepare(
    "SELECT * FROM productos WHERE id_producto = ?"
);
$consulta->bind_param("i", $id_producto);
$consulta->execute();
$resultado = $consulta->get_result();
$producto = $resultado->fetch_assoc();
$consulta->close();

if (!$producto) {
    exit("El producto no existe.");
}

// Obtener las categorías activas.
$consulta_categorias = $conexion->prepare(
    "SELECT id_categoria, nombre
     FROM categorias
     WHERE activo = 1
     ORDER BY nombre"
);
$consulta_categorias->execute();
$categorias = $consulta_categorias->get_result();

$error = "";

// Procesar el formulario.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validar el token CSRF.
    $token = $_POST["token_csrf"] ?? "";

    if (!hash_equals($_SESSION["token_csrf"], $token)) {
        http_response_code(403);
        exit("Solicitud no válida.");
    }

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $precio = filter_input(INPUT_POST, "precio", FILTER_VALIDATE_INT);
    $id_categoria_nuevo = filter_input(
        INPUT_POST,
        "id_categoria",
        FILTER_VALIDATE_INT
    );
    $destacado = isset($_POST["destacado"]) ? 1 : 0;

    // Mantener la imagen actual si no se sube otra.
    $imagen = $producto["imagen"];

    if ($nombre === "" || $descripcion === "") {
        $error = "Completa el nombre y la descripción.";
    } elseif ($precio === false || $precio === null || $precio < 1) {
        $error = "El precio debe ser un número entero mayor que cero.";
    } elseif (!$id_categoria_nuevo || $id_categoria_nuevo < 1) {
        $error = "Selecciona una categoría válida.";
    } else {
        // Validar la categoría seleccionada.
        $consulta_categoria = $conexion->prepare(
            "SELECT id_categoria
             FROM categorias
             WHERE id_categoria = ? AND activo = 1"
        );
        $consulta_categoria->bind_param("i", $id_categoria_nuevo);
        $consulta_categoria->execute();

        $categoria_valida =
            $consulta_categoria->get_result()->num_rows > 0;

        $consulta_categoria->close();

        if (!$categoria_valida) {
            $error = "La categoría seleccionada no es válida.";
        } else {
            // Procesar una imagen solo si se seleccionó un archivo.
            if (
                isset($_FILES["imagen"]) &&
                $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {
                $archivo = $_FILES["imagen"];

                if ($archivo["error"] !== UPLOAD_ERR_OK) {
                    $error = "No se pudo recibir la imagen.";
                } elseif ($archivo["size"] > 5 * 1024 * 1024) {
                    $error = "La imagen no puede superar los 5 MB.";
                } else {
                    // Comprobar el tipo real del archivo.
                    $informacion = new finfo(FILEINFO_MIME_TYPE);
                    $tipo = $informacion->file($archivo["tmp_name"]);

                    $tipos_permitidos = [
                        "image/jpeg" => "jpg",
                        "image/png"  => "png",
                        "image/webp" => "webp"
                    ];

                    if (!isset($tipos_permitidos[$tipo])) {
                        $error = "Usa una imagen JPG, PNG o WEBP.";
                    } elseif (
                        getimagesize($archivo["tmp_name"]) === false
                    ) {
                        $error = "El archivo seleccionado no es una imagen válida.";
                    } else {
                        $carpeta_imagenes = __DIR__ . "/../img/productos/";

                        // Crear la carpeta si no existe.
                        if (
                            !is_dir($carpeta_imagenes) &&
                            !mkdir($carpeta_imagenes, 0755, true)
                        ) {
                            $error = "No se pudo crear la carpeta de imágenes.";
                        } else {
                            // Generar un nombre único y seguro.
                            $nombre_archivo = bin2hex(random_bytes(16))
                                . "."
                                . $tipos_permitidos[$tipo];

                            $destino = $carpeta_imagenes . $nombre_archivo;

                            if (
                                move_uploaded_file(
                                    $archivo["tmp_name"],
                                    $destino
                                )
                            ) {
                                $imagen = "img/productos/" . $nombre_archivo;
                            } else {
                                $error = "No se pudo guardar la imagen.";
                            }
                        }
                    }
                }
            }

            // Guardar los cambios si no hubo errores.
            if ($error === "") {
                $actualizar = $conexion->prepare(
                    "UPDATE productos
                     SET nombre = ?,
                         descripcion = ?,
                         precio = ?,
                         id_categoria = ?,
                         destacado = ?,
                         imagen = ?
                     WHERE id_producto = ?"
                );

                $actualizar->bind_param(
                    "ssiiisi",
                    $nombre,
                    $descripcion,
                    $precio,
                    $id_categoria_nuevo,
                    $destacado,
                    $imagen,
                    $id_producto
                );

                if ($actualizar->execute()) {
                    $actualizar->close();
                    header("Location: productos.php?editado=1");
                    exit;
                } else {
                    $error = "No se pudieron guardar los cambios.";
                    $actualizar->close();
                }
            }
        }
    }

    // Conservar los valores del formulario si ocurre un error.
    $producto["nombre"] = $nombre;
    $producto["descripcion"] = $descripcion;
    $producto["precio"] = $precio ?: "";
    $producto["id_categoria"] = $id_categoria_nuevo ?: "";
    $producto["destacado"] = $destacado;
}

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
    <title>Editar producto | Pizzas Los Navíos</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 30px 15px;
            color: #222;
        }

        .contenedor-edicion {
            max-width: 650px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #00000012;
        }

        h1 {
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 16px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea,
        select {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .opcion-destacado {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .opcion-destacado label {
            margin-top: 16px;
        }

        .imagen-actual {
            display: block;
            max-width: 220px;
            max-height: 180px;
            object-fit: cover;
            margin-top: 10px;
            border-radius: 8px;
        }

        .boton-guardar {
            margin-top: 22px;
            padding: 12px 18px;
            border: 0;
            border-radius: 6px;
            background: #a51d24;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .enlace-volver {
            display: inline-block;
            margin-top: 18px;
            color: #a51d24;
        }

        .mensaje-error {
            background: #ffe5e5;
            color: #9b111e;
            padding: 12px;
            border-radius: 6px;
        }

        .ayuda {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <main class="contenedor-edicion">
        <h1>Editar producto</h1>

        <p>
            Modifica los datos de
            <strong><?= escapar($producto["nombre"]) ?></strong>.
        </p>

        <?php if ($error !== ""): ?>
            <p class="mensaje-error"><?= escapar($error) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
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
                required
                value="<?= escapar($producto["nombre"]) ?>">

            <label for="descripcion">Descripción</label>
            <textarea
                id="descripcion"
                name="descripcion"
                required><?= escapar($producto["descripcion"]) ?></textarea>

            <label for="precio">Precio en pesos chilenos (CLP)</label>
            <input
                type="number"
                id="precio"
                name="precio"
                min="1"
                step="1"
                required
                value="<?= escapar($producto["precio"]) ?>">

            <label for="id_categoria">Categoría</label>
            <select id="id_categoria" name="id_categoria" required>
                <option value="">Selecciona una categoría</option>

                <?php while ($categoria = $categorias->fetch_assoc()): ?>
                    <option
                        value="<?= escapar($categoria["id_categoria"]) ?>"
                        <?= (string) $producto["id_categoria"] ===
                            (string) $categoria["id_categoria"]
                            ? "selected"
                            : "" ?>>
                        <?= escapar($categoria["nombre"]) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="imagen">Imagen del producto</label>

            <?php if (!empty($producto["imagen"])): ?>
                <p>Imagen actual:</p>
                <img
                    class="imagen-actual"
                    src="../<?= escapar($producto["imagen"]) ?>"
                    alt="Imagen actual del producto">
            <?php else: ?>
                <p class="ayuda">Este producto todavía no tiene imagen.</p>
            <?php endif; ?>

            <input
                type="file"
                id="imagen"
                name="imagen"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

            <p class="ayuda">
                Formatos permitidos: JPG, PNG y WEBP. Máximo 5 MB.
                Déjalo vacío para conservar la imagen actual.
            </p>

            <div class="opcion-destacado">
                <input
                    type="checkbox"
                    id="destacado"
                    name="destacado"
                    value="1"
                    <?= $producto["destacado"] ? "checked" : "" ?>>
                <label for="destacado">Producto destacado</label>
            </div>

            <button class="boton-guardar" type="submit">
                Guardar cambios
            </button>
        </form>

        <a class="enlace-volver" href="productos.php">
            ← Volver a productos
        </a>
    </main>
</body>

</html>