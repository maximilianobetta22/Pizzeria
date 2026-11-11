console.log("Pizzas Los Navíos - Página cargada correctamente.");

/* ========================================
   FILTRAR PRODUCTOS POR CATEGORÍA
======================================== */

const botonesCategoria = document.querySelectorAll(".boton-categoria");
const tarjetasProducto = document.querySelectorAll(".tarjeta-producto");

// Recorremos los botones de categorías.
botonesCategoria.forEach((boton) => {
  boton.addEventListener("click", () => {
    const categoriaSeleccionada = boton.dataset.categoria;

    // Actualizamos el botón activo.
    botonesCategoria.forEach((botonCategoria) => {
      botonCategoria.classList.remove("activo");
    });

    boton.classList.add("activo");

    // Filtramos los productos.
    tarjetasProducto.forEach((tarjeta) => {
      const categoriaProducto = tarjeta.dataset.categoria;

      if (
        categoriaSeleccionada === "todos" ||
        categoriaProducto === categoriaSeleccionada
      ) {
        tarjeta.style.display = "";
      } else {
        tarjeta.style.display = "none";
      }
    });
  });
});

/* =========================================================
   APERTURA Y CIERRE DEL CARRITO
   ========================================================= */

const botonCarrito = document.getElementById("boton-carrito");
const botonCerrarCarrito = document.getElementById("boton-cerrar-carrito");
const panelCarrito = document.getElementById("panel-carrito");
const fondoCarrito = document.getElementById("fondo-carrito");

/* ABRIR CARRITO */

function abrirCarrito() {
  panelCarrito.classList.add("activo");
  fondoCarrito.classList.add("activo");

  document.body.style.overflow = "hidden";
}

/* CERRAR CARRITO */

function cerrarCarrito() {
  panelCarrito.classList.remove("activo");
  fondoCarrito.classList.remove("activo");

  document.body.style.overflow = "";
}

/* BOTÓN DEL HEADER */

if (botonCarrito) {
  botonCarrito.addEventListener("click", function () {
    abrirCarrito();
  });
}

/* BOTÓN X */

if (botonCerrarCarrito) {
  botonCerrarCarrito.addEventListener("click", function () {
    cerrarCarrito();
  });
}

/* CLIC EN EL FONDO */

if (fondoCarrito) {
  fondoCarrito.addEventListener("click", function () {
    cerrarCarrito();
  });
}

/* TECLA ESC */

document.addEventListener("keydown", function (evento) {
  if (evento.key === "Escape") {
    cerrarCarrito();
  }
});

/* =========================================================
   AGREGAR PRODUCTOS Y CONTROLAR LAS CANTIDADES DEL CARRITO
   ========================================================= */

// Seleccionamos los elementos del HTML que vamos a actualizar.

const botonesAgregar = document.querySelectorAll(".boton-agregar");
const contadorCarrito = document.getElementById("contador-carrito");
const productosCarrito = document.getElementById("productos-carrito");
const totalCarrito = document.getElementById("total-carrito");

// Aquí guardaremos los productos que el usuario agregue.

let carrito = [];

/* =========================================================
   FORMATEAR PRECIOS
   ========================================================= */

// Convierte un precio como "$8.990" en el número 8990.

function convertirPrecioANumero(precioTexto) {
  return Number(precioTexto.replace(/\D/g, ""));
}

// Convierte un número como 8990 en "$8.990".

function formatearPrecio(precio) {
  return "$" + precio.toLocaleString("es-CL");
}

/* =========================================================
   AGREGAR PRODUCTOS
   ========================================================= */

// Recorremos los botones de cada tarjeta de producto.

botonesAgregar.forEach(function (boton) {
  boton.addEventListener("click", function () {
    const tarjeta = boton.closest(".tarjeta-producto");

    const id = boton.dataset.id;
    const nombre = tarjeta.querySelector("h3").textContent.trim();
    const precioTexto = tarjeta.querySelector(".precio-producto").textContent;

    const precio = convertirPrecioANumero(precioTexto);

    // Comprobamos si el producto ya está en el carrito.

    const productoExistente = carrito.find(function (producto) {
      return producto.id === id;
    });

    if (productoExistente) {
      // Si ya existe, aumentamos su cantidad.

      productoExistente.cantidad++;
    } else {
      // Si no existe, lo agregamos con cantidad inicial 1.

      carrito.push({
        id: id,
        nombre: nombre,
        precio: precio,
        cantidad: 1,
      });
    }

    // Actualizamos los precios, las cantidades y el contador.

    actualizarCarrito();

    // Abrimos el carrito para mostrar el producto agregado.

    abrirCarrito();
  });
});

/* =========================================================
   MOSTRAR EL CONTENIDO DEL CARRITO
   ========================================================= */

function actualizarCarrito() {
  // Limpiamos el contenido anterior para volver a dibujarlo.

  productosCarrito.replaceChildren();

  let cantidadTotal = 0;
  let precioTotal = 0;

  // Si no quedan productos, mostramos el mensaje de carrito vacío.

  if (carrito.length === 0) {
    const mensajeVacio = document.createElement("p");

    mensajeVacio.classList.add("carrito-vacio");
    mensajeVacio.textContent = "Tu carrito está vacío 🍕";

    productosCarrito.appendChild(mensajeVacio);
  }

  // Creamos un elemento visual para cada producto del carrito.

  carrito.forEach(function (producto) {
    const elemento = document.createElement("div");
    elemento.classList.add("elemento-carrito");

    // Información del producto.

    const informacion = document.createElement("div");
    informacion.classList.add("informacion-elemento-carrito");

    const nombre = document.createElement("h3");
    nombre.textContent = producto.nombre;

    const precioUnitario = document.createElement("p");
    precioUnitario.textContent =
      "Precio unitario: " + formatearPrecio(producto.precio);

    informacion.appendChild(nombre);
    informacion.appendChild(precioUnitario);

    // Controles para aumentar o disminuir la cantidad.

    const controles = document.createElement("div");
    controles.classList.add("controles-carrito");

    const botonRestar = document.createElement("button");
    botonRestar.type = "button";
    botonRestar.classList.add("boton-cantidad");
    botonRestar.dataset.accion = "restar";
    botonRestar.dataset.id = producto.id;
    botonRestar.textContent = "−";
    botonRestar.setAttribute("aria-label", "Restar una unidad");

    const cantidad = document.createElement("span");
    cantidad.classList.add("cantidad-carrito");
    cantidad.textContent = producto.cantidad;

    const botonSumar = document.createElement("button");
    botonSumar.type = "button";
    botonSumar.classList.add("boton-cantidad");
    botonSumar.dataset.accion = "sumar";
    botonSumar.dataset.id = producto.id;
    botonSumar.textContent = "+";
    botonSumar.setAttribute("aria-label", "Agregar una unidad");

    controles.appendChild(botonRestar);
    controles.appendChild(cantidad);
    controles.appendChild(botonSumar);

    informacion.appendChild(controles);

    // Calculamos el subtotal de este producto.

    const subtotal = producto.precio * producto.cantidad;

    const precioElemento = document.createElement("div");
    precioElemento.classList.add("precio-elemento-carrito");
    precioElemento.textContent = formatearPrecio(subtotal);

    // Incorporamos el producto completo al carrito.

    elemento.appendChild(informacion);
    elemento.appendChild(precioElemento);

    productosCarrito.appendChild(elemento);

    // Sumamos las unidades y los subtotales.

    cantidadTotal += producto.cantidad;
    precioTotal += subtotal;
  });

  // Actualizamos el contador de unidades de la cabecera.

  contadorCarrito.textContent = cantidadTotal;

  // Actualizamos el total de la compra.

  totalCarrito.textContent = formatearPrecio(precioTotal);
}

/* =========================================================
   BOTONES + Y − DEL CARRITO
   ========================================================= */

// Escuchamos los clics dentro del carrito, incluidos los
// botones que se crean dinámicamente al agregar productos.

productosCarrito.addEventListener("click", function (evento) {
  const boton = evento.target.closest(".boton-cantidad");

  // Si el clic no fue en un botón de cantidad, no hacemos nada.

  if (!boton || !productosCarrito.contains(boton)) {
    return;
  }

  const id = boton.dataset.id;
  const accion = boton.dataset.accion;

  const producto = carrito.find(function (elemento) {
    return elemento.id === id;
  });

  if (!producto) {
    return;
  }

  if (accion === "sumar") {
    producto.cantidad++;
  }

  if (accion === "restar") {
    producto.cantidad--;
  }

  // Si la cantidad llega a cero, quitamos el producto.

  carrito = carrito.filter(function (elemento) {
    return elemento.cantidad > 0;
  });

  // Redibujamos el carrito con los valores actualizados.

  actualizarCarrito();
});

/* ========================================
   REALIZAR PEDIDO POR WHATSAPP
======================================== */

// Buscamos el botón para realizar el pedido.
const botonPedido = document.getElementById("boton-pedido");

// Número de WhatsApp del negocio, con código de país.
const numeroWhatsApp = "56962673186";

// Al pulsar el botón, preparamos el pedido.
botonPedido.addEventListener("click", function () {
  // Evitamos continuar si el carrito está vacío.
  if (carrito.length === 0) {
    alert(
      "Tu carrito está vacío. Agrega una pizza antes de realizar el pedido.",
    );
    return;
  }

  // Creamos el encabezado del mensaje.
  let mensaje = "¡Hola! Quiero realizar el siguiente pedido:\n\n";

  // Recorremos los productos que están en el carrito.
  carrito.forEach(function (producto) {
    // Calculamos el subtotal de cada producto.
    const subtotal = producto.precio * producto.cantidad;

    mensaje +=
      "🍕 " +
      producto.nombre +
      "\n" +
      "Cantidad: " +
      producto.cantidad +
      "\n" +
      "Subtotal: " +
      formatearPrecio(subtotal) +
      "\n\n";
  });

  // Añadimos el total de la compra.
  mensaje +=
    "Total del pedido: " +
    formatearPrecio(
      carrito.reduce(function (total, producto) {
        return total + producto.precio * producto.cantidad;
      }, 0),
    );

  // Convertimos el mensaje para utilizarlo en una URL.
  const mensajeCodificado = encodeURIComponent(mensaje);

  // Abrimos WhatsApp con el pedido preparado.
  const enlaceWhatsApp =
    "https://wa.me/" + numeroWhatsApp + "?text=" + mensajeCodificado;

  window.open(enlaceWhatsApp, "_blank");
});
