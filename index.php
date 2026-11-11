<?php

require_once "config/conexion.php";


/*
|--------------------------------------------------------------------------
| OBTENER CATEGORÍAS
|--------------------------------------------------------------------------
*/

$sqlCategorias = "
    SELECT
        id_categoria,
        nombre,
        descripcion
    FROM categorias
    WHERE activo = TRUE
    ORDER BY id_categoria ASC
";

$resultadoCategorias = $conexion->query($sqlCategorias);


/*
|--------------------------------------------------------------------------
| OBTENER PRODUCTOS
|--------------------------------------------------------------------------
*/

$sqlProductos = "
    SELECT
        p.id_producto,
        p.id_categoria,
        p.nombre,
        p.descripcion,
        p.precio,
        p.imagen,
        p.disponible,
        p.destacado,
        c.nombre AS categoria
    FROM productos p
    INNER JOIN categorias c
        ON p.id_categoria = c.id_categoria
    WHERE p.disponible = TRUE
    AND c.activo = TRUE
    ORDER BY p.destacado DESC, p.id_producto ASC
";

$resultadoProductos = $conexion->query($sqlProductos);


/*
|--------------------------------------------------------------------------
| GUARDAR PRODUCTOS
|--------------------------------------------------------------------------
*/

$productos = [];

while ($producto = $resultadoProductos->fetch_assoc()) {

    $productos[] = $producto;
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Pizzas Los Navíos
    </title>

    <meta
        name="description"
        content="Pizzas Los Navíos - Pizzas, tablas, bebidas y mucho más.">

    <link
        rel="stylesheet"
        href="css/estilos.css?v=3">

</head>


<body>


    <!-- =====================================================
     CABECERA
===================================================== -->

    <header class="cabecera">

        <nav class="barra-navegacion">

            <div class="contenedor">


                <!-- LOGO -->

                <a
                    href="#inicio"
                    class="logo">

                    <span class="logo-icono">
                        🍕
                    </span>

                    <span class="logo-texto">
                        Pizzas Los Navíos
                    </span>

                </a>


                <!-- NAVEGACIÓN -->

                <div class="enlaces-navegacion">

                    <a href="#inicio">
                        Inicio
                    </a>

                    <a href="#carta">
                        Carta
                    </a>

                    <a href="#nosotros">
                        Nosotros
                    </a>

                    <a href="#contacto">
                        Contacto
                    </a>
                    <a href="#ubicacion">
                        Ubicación
                    </a>

                </div>


                <!-- CARRITO -->

                <button
                    type="button"
                    class="boton-carrito"
                    id="boton-carrito"
                    aria-label="Abrir carrito">

                    <span class="icono-carrito">
                        🛒
                    </span>

                    <span
                        class="contador-carrito"
                        id="contador-carrito">
                        0
                    </span>

                </button>


            </div>

        </nav>

    </header>



    <!-- =====================================================
     CONTENIDO PRINCIPAL
===================================================== -->

    <main>


        <!-- =====================================================
     HERO
===================================================== -->

        <section
            class="hero"
            id="inicio">

            <div class="contenedor">

                <div class="contenido-hero">


                    <span class="etiqueta-seccion">
                        PIZZAS LOS NAVÍOS
                    </span>


                    <h1>

                        El sabor que

                        <span>
                            une a todos
                        </span>

                    </h1>


                    <p>

                        Pizzas preparadas con cariño,
                        ingredientes deliciosos y ese
                        sabor que te hace volver.

                    </p>


                    <div class="botones-hero">


                        <a
                            href="#carta"
                            class="boton-principal">

                            Ver nuestra carta

                        </a>


                        <a
                            href="tel:+56962673186"
                            class="boton-secundario">

                            Llámanos

                        </a>


                    </div>


                </div>

            </div>

        </section>



        <!-- =====================================================
     CARTA
===================================================== -->

        <section
            class="seccion-carta"
            id="carta">

            <div class="contenedor">


                <!-- ENCABEZADO -->

                <div class="encabezado-seccion">

                    <span class="etiqueta-seccion">
                        NUESTRA CARTA
                    </span>

                    <h2>
                        Elige tu favorito
                    </h2>

                    <p>
                        Descubre nuestras pizzas,
                        acompañamientos, bebidas y tablas.
                    </p>

                </div>



                <!-- CATEGORÍAS -->

                <div class="categorias-carta">

                    <button
                        type="button"
                        class="boton-categoria activo"
                        data-categoria="todos">

                        Todos

                    </button>

                    <?php if ($resultadoCategorias && $resultadoCategorias->num_rows > 0): ?>

                        <?php while ($categoria = $resultadoCategorias->fetch_assoc()): ?>

                            <button
                                type="button"
                                class="boton-categoria"
                                data-categoria="<?php echo $categoria["id_categoria"]; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $categoria["nombre"]
                                );
                                ?>

                            </button>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </div>



                <!-- PRODUCTOS -->

                <div class="cuadricula-productos">


                    <?php if (count($productos) > 0): ?>


                        <?php foreach ($productos as $producto): ?>


                            <article
                                class="tarjeta-producto"
                                data-categoria="<?php echo $producto["id_categoria"]; ?>">


                                <!-- IMAGEN -->

                                <div class="imagen-producto">


                                    <?php if (!empty($producto["imagen"])): ?>

                                        <img
                                            src="<?php echo htmlspecialchars($producto["imagen"]); ?>"
                                            alt="<?php echo htmlspecialchars($producto["nombre"]); ?>">

                                    <?php else: ?>

                                        <div class="imagen-producto-placeholder">

                                            🍕

                                        </div>

                                    <?php endif; ?>


                                    <?php if ($producto["destacado"]): ?>

                                        <span class="etiqueta-destacado">
                                            Favorita
                                        </span>

                                    <?php endif; ?>


                                </div>



                                <!-- INFORMACIÓN -->

                                <div class="contenido-producto">


                                    <span class="categoria-producto">

                                        <?php
                                        echo htmlspecialchars(
                                            $producto["categoria"]
                                        );
                                        ?>

                                    </span>


                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $producto["nombre"]
                                        );
                                        ?>

                                    </h3>


                                    <p>

                                        <?php
                                        echo htmlspecialchars(
                                            $producto["descripcion"]
                                        );
                                        ?>

                                    </p>



                                    <div class="pie-producto">


                                        <strong class="precio-producto">

                                            $
                                            <?php
                                            echo number_format(
                                                $producto["precio"],
                                                0,
                                                ",",
                                                "."
                                            );
                                            ?>

                                        </strong>



                                        <button
                                            type="button"
                                            class="boton-agregar"
                                            data-id="<?php echo $producto["id_producto"]; ?>"
                                            aria-label="Agregar producto">

                                            +

                                        </button>


                                    </div>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="sin-productos">

                            <p>
                                No hay productos disponibles
                                en este momento.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>


            </div>

        </section>

        <!-- UBICACIÓN -->
        <section class="seccion-ubicacion" id="ubicacion">

            <div class="contenedor-ubicacion">

                <div class="contenido-ubicacion">

                    <span class="etiqueta-seccion">VISÍTANOS</span>

                    <h2>¿Dónde estamos? 📍</h2>

                    <p>
                        Encuéntranos en Los Navíos, La Florida.
                    </p>

                    <p class="direccion">
                        📍 Recalada Ote. 11590, La Florida, Región Metropolitana
                    </p>

                    <a
                        href="https://www.google.com/maps/search/?api=1&query=Recalada+Ote.+11590,+La+Florida,+Chile"
                        target="_blank"
                        class="boton-mapa">
                        📍 Ver en Google Maps
                    </a>

                </div>

                <div class="mapa">
                    <iframe
                        src="https://www.google.com/maps?q=Recalada%20Ote.%2011590,%20La%20Florida,%20Chile&output=embed"
                        width="100%"
                        height="400"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>

            </div>

        </section>


        <!-- =====================================================
         RESEÑAS
         ===================================================== -->

        <section class="seccion-resenas" id="resenas">

            <div class="contenedor">

                <div class="encabezado-seccion">
                    <span class="etiqueta-seccion">LO QUE DICEN NUESTROS CLIENTES</span>

                    <h2>Clientes felices, pizzas felices</h2>

                    <p>
                        La opinión de nuestros clientes es lo más importante.
                    </p>
                </div>


                <div class="resumen-resenas">

                    <div class="puntuacion-resenas">

                        <strong>5,0</strong>

                        <div class="estrellas-resenas">
                            ★★★★★
                        </div>

                        <span>8 reseñas en Google</span>

                    </div>

                </div>


                <div class="cuadricula-resenas">

                    <article class="tarjeta-resena">

                        <div class="cabecera-resena">

                            <div class="avatar-resena">
                                R
                            </div>

                            <div>
                                <h3>Rodrigo Andrés</h3>
                                <span>Cliente</span>
                            </div>

                        </div>

                        <div class="estrellas-resena">
                            ★★★★★
                        </div>

                        <p>
                            Todos los años pido tablas, y nunca han fallado
                            en calidad ni precio, hacen muy buenas comidas.
                        </p>

                    </article>


                    <article class="tarjeta-resena">

                        <div class="cabecera-resena">

                            <div class="avatar-resena">
                                M
                            </div>

                            <div>
                                <h3>Maria Jose Barriga</h3>
                                <span>Cliente</span>
                            </div>

                        </div>

                        <div class="estrellas-resena">
                            ★★★★★
                        </div>

                        <p>
                            Cocinan súper rico, es barato y atienden súper bien.
                        </p>

                    </article>


                    <article class="tarjeta-resena">

                        <div class="cabecera-resena">

                            <div class="avatar-resena">
                                J
                            </div>

                            <div>
                                <h3>Jimmy Riveros Reyes</h3>
                                <span>Cliente</span>
                            </div>

                        </div>

                        <div class="estrellas-resena">
                            ★★★★★
                        </div>

                        <p>
                            Muy buenas y buen precio...
                        </p>

                    </article>

                </div>

            </div>

        </section>
        <!-- =====================================================
     NOSOTROS
===================================================== -->

        <section
            class="seccion-nosotros"
            id="nosotros">

            <div class="contenedor">

                <div class="contenido-nosotros">


                    <span class="etiqueta-seccion">
                        SOBRE NOSOTROS
                    </span>


                    <h2>
                        Hecho con sabor,
                        hecho para compartir
                    </h2>


                    <p>
                        En Pizzas Los Navíos queremos entregar
                        una experiencia rica, cercana y
                        accesible para nuestros vecinos.
                    </p>


                    <p>
                        Nuestro objetivo es que cada pedido
                        llegue con la calidad y el sabor que
                        nuestros clientes esperan.
                    </p>


                </div>

            </div>

        </section>



        <!-- =====================================================
     CONTACTO
===================================================== -->

        <section
            class="seccion-contacto"
            id="contacto">

            <div class="contenedor">


                <div class="encabezado-seccion">

                    <span class="etiqueta-seccion">
                        CONTÁCTANOS
                    </span>

                    <h2>
                        ¿Tienes alguna pregunta?
                    </h2>

                </div>



                <div class="informacion-contacto">


                    <div class="tarjeta-contacto">

                        <span class="icono-contacto">
                            📞
                        </span>

                        <h3>
                            Teléfono
                        </h3>

                        <a href="tel:+56962673186">
                            +56 9 6267 3186
                        </a>

                    </div>



                    <div class="tarjeta-contacto">

                        <span class="icono-contacto">
                            📍
                        </span>

                        <h3>
                            Dirección
                        </h3>

                        <p>
                            Recalada Ote. 11590
                            <br>
                            La Florida, Región Metropolitana
                        </p>

                    </div>



                    <div class="tarjeta-contacto">

                        <span class="icono-contacto">
                            🕐
                        </span>

                        <h3>
                            Horario
                        </h3>

                        <p>
                            Atención según horario del local
                        </p>

                    </div>


                </div>


            </div>

        </section>


    </main>



    <!-- =====================================================
     PIE DE PÁGINA
===================================================== -->

    <footer class="pie-pagina">

        <div class="contenedor">

            <p>

                © <?php echo date("Y"); ?>

                Pizzas Los Navíos.

                Todos los derechos reservados.

            </p>

        </div>

    </footer>



    <!-- =====================================================
     FONDO DEL CARRITO
===================================================== -->

    <div
        class="fondo-carrito"
        id="fondo-carrito"></div>



    <!-- =====================================================
     PANEL DEL CARRITO
===================================================== -->

    <aside
        class="panel-carrito"
        id="panel-carrito">


        <!-- ENCABEZADO DEL CARRITO -->

        <div class="encabezado-carrito">


            <div>

                <span class="etiqueta-seccion">
                    TU PEDIDO
                </span>

                <h2>
                    Mi carrito
                </h2>

            </div>


            <button
                type="button"
                class="boton-cerrar-carrito"
                id="boton-cerrar-carrito"
                aria-label="Cerrar carrito">

                ×

            </button>


        </div>



        <!-- PRODUCTOS DEL CARRITO -->

        <div
            class="productos-carrito"
            id="productos-carrito">

            <p class="carrito-vacio">

                Tu carrito está vacío 🍕

            </p>

        </div>



        <!-- RESUMEN -->

        <div class="resumen-carrito">


            <div class="fila-total">

                <span>
                    Total
                </span>

                <strong id="total-carrito">
                    $0
                </strong>

            </div>


            <button
                type="button"
                class="boton-pedido"
                id="boton-pedido">

                Pedir por WhatsApp

            </button>


        </div>


    </aside>



    <!-- =====================================================
     JAVASCRIPT
===================================================== -->

    <script
        src="js/app.js?v=3"></script>


</body>

</html>