<?php
// PÁGINA PRINCIPAL: REGISTRAR los servicios que se hicieron (las ventas del día a día)
// Se elige qué servicio se realizó, se pregunta si se hizo otro y al guardar se registra todo.
include("conexion.php");
include("configuracion.php");
include("funciones.php");

// GUARDAR EL REGISTRO (se presionó "Guardar registro" en el resumen)
if (isset($_POST["guardar_registro"])) {
    $cliente = trim($_POST["cliente"]);
    $pago = $_POST["pago"];

    // Lista con los id de los servicios elegidos (puede repetirse, ej. dos cortes)
    $servicios_elegidos = [];
    if (isset($_POST["servicios"]) && is_array($_POST["servicios"])) {
        $servicios_elegidos = $_POST["servicios"];
    }

    if (!isset($formas_pago[$pago])) {
        header("Location: index.php?error=pago");
        exit;
    }
    if ($pago == "fiado" && $cliente == "") {
        header("Location: index.php?error=cliente");
        exit;
    }

    // Se busca cada servicio para tomar su nombre y precio de la base de datos
    $lista = [];
    $total = 0;
    $nombres = "";
    foreach ($servicios_elegidos as $id) {
        $id = (int) $id;
        $sql = $conexion->prepare("SELECT * FROM servicios WHERE id = ?");
        $sql->bind_param("i", $id);
        $sql->execute();
        $servicio = $sql->get_result()->fetch_assoc();

        if ($servicio) {
            $lista[] = $servicio;
            $total = $total + $servicio["precio"];
            if ($nombres != "") {
                $nombres = $nombres . " + ";
            }
            $nombres = $nombres . $servicio["nombre"];
        }
    }

    if (count($lista) == 0) {
        header("Location: index.php?error=sin_servicios");
        exit;
    }

    // 1. Se guarda la atención (el cliente que se atendió)
    $fecha_hora = date("Y-m-d H:i:s");
    $sql = $conexion->prepare("INSERT INTO atenciones (cliente, metodo_pago, total, fecha_hora) VALUES (?, ?, ?, ?)");
    $sql->bind_param("ssds", $cliente, $pago, $total, $fecha_hora);
    $sql->execute();
    $atencion_id = $conexion->insert_id;      // número que le tocó a la atención

    // 2. Se guarda cada servicio que se le hizo
    foreach ($lista as $servicio) {
        $sql = $conexion->prepare("INSERT INTO atencion_servicios (atencion_id, servicio_id, nombre, precio) VALUES (?, ?, ?, ?)");
        $sql->bind_param("iisd", $atencion_id, $servicio["id"], $servicio["nombre"], $servicio["precio"]);
        $sql->execute();
    }

    // 3. El dinero
    if ($pago == "fiado") {
        // Fiado: todavía no entra dinero, se crea una deuda en Deudas > Por cobrar
        $fecha = date("Y-m-d");
        $sql = $conexion->prepare("INSERT INTO deudas_cobrar (atencion_id, fecha, nombre, comentario, total) VALUES (?, ?, ?, ?, ?)");
        $sql->bind_param("isssd", $atencion_id, $fecha, $cliente, $nombres, $total);
        $sql->execute();
    } else {
        // Pagó: el dinero entra a la caja
        $detalle = $nombres;
        if ($cliente != "") {
            $detalle = $detalle . " (" . $cliente . ")";
        }
        registrar_movimiento($conexion, "venta", $total, $detalle);
    }

    header("Location: index.php?aviso=registro");
    exit;
}

// SERVICIOS que se pueden elegir (los de la pestaña Servicios)
$resultado = $conexion->query("SELECT * FROM servicios ORDER BY nombre");
$servicios = [];
while ($fila = $resultado->fetch_assoc()) {
    $servicios[] = $fila;
}

// REGISTROS DE HOY, cada uno con sus servicios juntos en un texto: "Corte + Barba"
// (GROUP_CONCAT junta los nombres de los servicios de cada atención)
$hoy = date("Y-m-d 00:00:00");
$sql = $conexion->prepare("
    SELECT atenciones.*,
           GROUP_CONCAT(atencion_servicios.nombre ORDER BY atencion_servicios.id SEPARATOR ' + ') AS servicios,
           COUNT(atencion_servicios.id) AS cantidad
    FROM atenciones
    LEFT JOIN atencion_servicios ON atencion_servicios.atencion_id = atenciones.id
    WHERE atenciones.fecha_hora >= ?
    GROUP BY atenciones.id
    ORDER BY atenciones.fecha_hora DESC, atenciones.id DESC
");
$sql->bind_param("s", $hoy);
$sql->execute();
$resultado = $sql->get_result();

$registros_hoy = [];
$ventas_hoy = 0;
$servicios_hoy = 0;
while ($fila = $resultado->fetch_assoc()) {
    $registros_hoy[] = $fila;
    $ventas_hoy = $ventas_hoy + $fila["total"];
    $servicios_hoy = $servicios_hoy + $fila["cantidad"];
}

$avisos = [
    "registro" => "Registro guardado.",
];
$errores = [
    "pago" => "Elige una forma de pago.",
    "cliente" => "Para dejarlo fiado escribe el nombre del cliente.",
    "sin_servicios" => "Elige al menos un servicio.",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1C1C1E">
    <title>Registrar - <?php echo $negocio; ?> | KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>
    <?php mostrar_aviso($avisos, $errores); ?>

    <main class="contenedor">
        <!-- VENTAS DE HOY Y BOTÓN PARA REGISTRAR -->
        <div class="saldo">
            <p class="saldo-titulo">Ventas de hoy</p>
            <p class="saldo-cantidad"><?php echo dinero($ventas_hoy); ?></p>
            <p class="saldo-nota">
                <?php echo $servicios_hoy; ?> <?php if ($servicios_hoy == 1) { echo "servicio"; } else { echo "servicios"; } ?>
                ·
                <?php echo count($registros_hoy); ?> <?php if (count($registros_hoy) == 1) { echo "cliente"; } else { echo "clientes"; } ?>
            </p>
            <button type="button" class="boton boton-blanco boton-grande boton-ancho" onclick="abrirRegistro()">
                <?php echo icono("registrar"); ?> REGISTRAR SERVICIO
            </button>
        </div>

        <!-- LO QUE SE REGISTRÓ HOY -->
        <div class="tarjeta">
            <h3 class="tarjeta-titulo">
                <span class="circulo"><?php echo icono("historial"); ?></span>
                Registros de hoy
            </h3>

            <?php if (count($registros_hoy) == 0) { ?>
                <p class="vacio">Todavía no hay registros hoy. Toca "Registrar servicio" para empezar.</p>
            <?php } else { ?>
                <ul class="lista">
                    <?php foreach ($registros_hoy as $registro) { ?>
                        <li class="renglon <?php if ($registro["metodo_pago"] == "fiado") { echo "fondo-amarillo"; } else { echo "fondo-verde"; } ?>">
                            <div>
                                <p class="lista-titulo"><?php echo htmlspecialchars($registro["servicios"]); ?></p>
                                <p class="lista-fecha">
                                    <?php echo date("H:i", strtotime($registro["fecha_hora"])); ?>
                                    <?php if ($registro["cliente"] != "") { echo "· " . htmlspecialchars($registro["cliente"]); } ?>
                                </p>
                            </div>
                            <div class="etiquetas">
                                <span class="cantidad"><?php echo dinero($registro["total"]); ?></span>
                                <span class="etiqueta <?php if ($registro["metodo_pago"] == "fiado") echo "amarillo"; ?>"><?php echo $formas_pago[$registro["metodo_pago"]]; ?></span>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>

        <?php include("pie.php"); ?>
    </main>

    <!-- CUADRO PARA REGISTRAR: tiene 3 pasos y solo se ve uno a la vez -->
    <dialog id="dialogo-registro">
        <form method="post" class="dialogo-contenido" onsubmit="return revisarRegistro()">

            <!-- PASO 1: elegir el servicio -->
            <div id="paso-elegir">
                <h3>¿Qué servicio se realizó?</h3>
                <p class="descripcion">Toca el servicio. La lista se cambia en la pestaña Servicios.</p>

                <?php if (count($servicios) == 0) { ?>
                    <p class="vacio">No hay servicios todavía. <a href="servicios.php">Agrégalos aquí</a>.</p>
                <?php } ?>

                <div class="opciones-servicio">
                    <?php foreach ($servicios as $servicio) { ?>
                        <button type="button" class="opcion-servicio" onclick="elegirServicio(<?php echo $servicio["id"]; ?>)">
                            <span class="negrita"><?php echo htmlspecialchars($servicio["nombre"]); ?></span>
                            <span class="gris chico"><?php echo $servicio["duracion"]; ?> min</span>
                            <span class="precio"><?php echo dinero($servicio["precio"]); ?></span>
                        </button>
                    <?php } ?>
                </div>
            </div>

            <!-- PASO 2: preguntar si se hizo otro servicio -->
            <div id="paso-otro" class="pregunta" hidden>
                <span class="circulo enorme verde"><?php echo icono("palomita"); ?></span>
                <p class="gris" id="texto-agregado"></p>
                <h3>¿Se realizó otro servicio?</h3>
                <div class="renglon-monto">
                    <span class="gris" id="cantidad-parcial"></span>
                    <strong id="total-parcial"></strong>
                </div>
                <div class="dialogo-botones" style="width: 100%;">
                    <button type="button" class="boton boton-borde" onclick="mostrarPaso('paso-elegir')">Sí, agregar otro</button>
                    <button type="button" class="boton" onclick="mostrarResumen()">No, es todo</button>
                </div>
            </div>

            <!-- PASO 3: resumen, datos del pago y guardar -->
            <div id="paso-resumen" hidden>
                <h3>Resumen del registro</h3>
                <p class="descripcion">Revisa los servicios y guarda el registro.</p>

                <ul class="lista-elegidos" id="lista-elegidos"></ul>

                <div class="renglon-monto">
                    <span class="negrita">Total</span>
                    <strong id="total-final"></strong>
                </div>

                <label for="cliente">Cliente (opcional)</label>
                <input type="text" id="cliente" name="cliente" maxlength="100" placeholder="Nombre del cliente" autocomplete="off">

                <label for="pago">Forma de pago</label>
                <select id="pago" name="pago">
                    <?php foreach ($formas_pago as $clave => $texto) { ?>
                        <option value="<?php echo $clave; ?>"><?php echo $texto; ?></option>
                    <?php } ?>
                </select>

                <p class="texto-error" id="error-registro" hidden></p>

                <!-- Aquí JavaScript pone un campo escondido por cada servicio elegido -->
                <div id="campos-servicios"></div>

                <div class="dialogo-botones">
                    <button type="button" class="boton boton-borde" onclick="mostrarPaso('paso-elegir')">
                        <?php echo icono("mas"); ?> Agregar otro
                    </button>
                    <button type="submit" name="guardar_registro" class="boton">Guardar registro</button>
                </div>
            </div>

            <button type="button" class="cerrar" onclick="cerrarDialogo('dialogo-registro')">&times;</button>
        </form>
    </dialog>

    <script src="funciones.js"></script>
    <script>
        // Los servicios que PHP sacó de la base de datos
        let servicios = <?php echo json_encode($servicios); ?>;

        // Servicios que se van eligiendo en este registro
        let elegidos = [];

        // Abre el cuadro empezando desde el paso 1
        function abrirRegistro() {
            elegidos = [];
            document.getElementById("cliente").value = "";
            document.getElementById("pago").value = "efectivo";
            mostrarPaso("paso-elegir");
            abrirDialogo("dialogo-registro");

            // Al abrir, el navegador marca el primer servicio; se le quita para que no parezca elegido
            document.activeElement.blur();
        }

        // Muestra solo el paso que se pide y esconde los otros dos
        function mostrarPaso(paso) {
            document.getElementById("paso-elegir").hidden = (paso != "paso-elegir");
            document.getElementById("paso-otro").hidden = (paso != "paso-otro");
            document.getElementById("paso-resumen").hidden = (paso != "paso-resumen");
        }

        // Suma los precios de los servicios elegidos
        function sumarTotal() {
            let total = 0;
            for (let i = 0; i < elegidos.length; i++) {
                total = total + Number(elegidos[i].precio);
            }
            return total;
        }

        // PASO 1 → PASO 2: se agrega el servicio y se pregunta si hubo otro
        function elegirServicio(id) {
            let servicio = buscarPorId(servicios, id);
            elegidos.push(servicio);

            let texto = elegidos.length + " servicio";
            if (elegidos.length > 1) {
                texto = elegidos.length + " servicios";
            }

            document.getElementById("texto-agregado").textContent = "Se agregó: " + servicio.nombre;
            document.getElementById("cantidad-parcial").textContent = texto;
            document.getElementById("total-parcial").textContent = dinero(sumarTotal());
            mostrarPaso("paso-otro");
        }

        // PASO 3: se arma la lista de servicios elegidos con un botón para quitar cada uno
        function mostrarResumen() {
            let lista = document.getElementById("lista-elegidos");
            let campos = document.getElementById("campos-servicios");
            lista.innerHTML = "";
            campos.innerHTML = "";

            for (let i = 0; i < elegidos.length; i++) {
                // Renglón que se ve: nombre, precio y botón para quitar
                let renglon = document.createElement("li");

                let nombre = document.createElement("span");
                nombre.textContent = elegidos[i].nombre;

                let precio = document.createElement("strong");
                precio.textContent = dinero(elegidos[i].precio);

                let quitar = document.createElement("button");
                quitar.type = "button";
                quitar.className = "boton-icono rojo";
                quitar.title = "Quitar servicio";
                quitar.innerHTML = "&times;";
                quitar.onclick = function () {
                    quitarServicio(i);
                };

                renglon.appendChild(nombre);
                renglon.appendChild(precio);
                renglon.appendChild(quitar);
                lista.appendChild(renglon);

                // Campo escondido que se manda a PHP con el id del servicio
                let campo = document.createElement("input");
                campo.type = "hidden";
                campo.name = "servicios[]";
                campo.value = elegidos[i].id;
                campos.appendChild(campo);
            }

            document.getElementById("total-final").textContent = dinero(sumarTotal());
            document.getElementById("error-registro").hidden = true;
            mostrarPaso("paso-resumen");
        }

        function quitarServicio(posicion) {
            elegidos.splice(posicion, 1);      // lo quita de la lista

            if (elegidos.length == 0) {
                mostrarPaso("paso-elegir");
            } else {
                mostrarResumen();
            }
        }

        // Revisa los datos antes de guardar
        function revisarRegistro() {
            let error = document.getElementById("error-registro");
            let cliente = document.getElementById("cliente").value.trim();
            let pago = document.getElementById("pago").value;

            if (elegidos.length == 0) {
                error.textContent = "Elige al menos un servicio.";
                error.hidden = false;
                return false;
            }
            if (pago == "fiado" && cliente == "") {
                error.textContent = "Para dejarlo fiado escribe el nombre del cliente.";
                error.hidden = false;
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
