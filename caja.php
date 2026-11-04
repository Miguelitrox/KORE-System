<?php
// PÁGINA DE CAJA
// Muestra el dinero en caja, registra gastos e ingresos extra, guarda notas y enseña el historial.
// Las ventas que se registran en la página principal entran aquí solas.
include("conexion.php");
include("configuracion.php");
include("funciones.php");

// GUARDAR UN GASTO O INGRESO (viene del cuadro de confirmación)
if (isset($_POST["guardar_movimiento"])) {
    $tipo = $_POST["tipo"];             // "gasto" o "ingreso"
    $monto = (float) $_POST["monto"];
    $detalle = trim($_POST["detalle"]);

    if (($tipo != "gasto" && $tipo != "ingreso") || $monto <= 0 || $detalle == "") {
        header("Location: caja.php?error=incompleto");
        exit;
    }

    registrar_movimiento($conexion, $tipo, $monto, $detalle);

    header("Location: caja.php?aviso=" . $tipo);
    exit;
}

// GUARDAR LAS NOTAS DE LA BITÁCORA
if (isset($_POST["guardar_notas"])) {
    $notas = $_POST["notas"];

    $sql = $conexion->prepare("UPDATE control_gastos SET notas = ? WHERE id = 1");
    $sql->bind_param("s", $notas);
    $sql->execute();

    header("Location: caja.php?notas=guardadas");
    exit;
}

// LEER el dinero en caja y las notas (siempre es la fila con id 1)
$resultado = $conexion->query("SELECT * FROM control_gastos WHERE id = 1");
$control = $resultado->fetch_assoc();
$dinero_actual = $control["dinero_actual"];
$notas = $control["notas"];

// HISTORIAL: se eligen cuántos días hacia atrás se quieren ver
$rangos = [
    3 => "Últimos 3 días",
    7 => "Última semana",
    14 => "Últimas 2 semanas",
    21 => "Últimas 3 semanas",
    30 => "Último mes",
    90 => "Últimos 3 meses",
    180 => "Últimos 6 meses",
    365 => "Último año",
];

$dias = 3;
if (isset($_GET["dias"]) && isset($rangos[$_GET["dias"]])) {
    $dias = (int) $_GET["dias"];
}
$desde = date("Y-m-d H:i:s", strtotime("-" . $dias . " days"));

$sql = $conexion->prepare("SELECT * FROM movimientos WHERE fecha_hora >= ? ORDER BY fecha_hora DESC, id DESC");
$sql->bind_param("s", $desde);
$sql->execute();
$movimientos = $sql->get_result();

// Mensajes que salen arriba después de guardar
$avisos = [
    "gasto" => "Gasto registrado.",
    "ingreso" => "Ingreso registrado.",
];
$errores = [
    "incompleto" => "Escribe una cantidad mayor a 0 y el detalle del movimiento.",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1C1C1E">
    <title>Caja - <?php echo $negocio; ?> | KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>
    <?php mostrar_aviso($avisos, $errores); ?>

    <main class="contenedor">
        <!-- DINERO EN CAJA -->
        <div class="saldo">
            <p class="saldo-titulo">Dinero en caja</p>
            <p class="saldo-cantidad"><?php echo dinero($dinero_actual); ?></p>
            <p class="saldo-nota">
                <?php if ($dinero_actual >= 0) { echo "Saldo disponible"; } else { echo "Saldo en negativo"; } ?>
            </p>
        </div>

        <!-- REGISTRAR GASTO E INGRESO -->
        <!-- Al presionar el botón (o Enter) se abre un cuadro para escribir el detalle -->
        <div class="dos-columnas">
            <div class="tarjeta">
                <h3 class="tarjeta-titulo">
                    <span class="circulo rojo"><?php echo icono("baja"); ?></span>
                    Registrar gasto
                </h3>
                <form onsubmit="abrirMovimiento('gasto'); return false;">
                    <div class="campo-dinero grande">
                        <span>$</span>
                        <input type="number" id="monto-gasto" step="0.01" min="0" placeholder="0.00" inputmode="decimal">
                    </div>
                    <br>
                    <button type="submit" class="boton boton-grande boton-ancho boton-rojo">
                        <?php echo icono("menos"); ?> Restar de la caja
                    </button>
                </form>
            </div>

            <div class="tarjeta">
                <h3 class="tarjeta-titulo">
                    <span class="circulo verde"><?php echo icono("sube"); ?></span>
                    Registrar ingreso extra
                </h3>
                <form onsubmit="abrirMovimiento('ingreso'); return false;">
                    <div class="campo-dinero grande">
                        <span>$</span>
                        <input type="number" id="monto-ingreso" step="0.01" min="0" placeholder="0.00" inputmode="decimal">
                    </div>
                    <br>
                    <button type="submit" class="boton boton-grande boton-ancho boton-verde">
                        <?php echo icono("mas"); ?> Sumar a la caja
                    </button>
                </form>
            </div>
        </div>

        <!-- BITÁCORA DE ANOTACIONES -->
        <div class="tarjeta">
            <h3 class="tarjeta-titulo">
                <span class="circulo"><?php echo icono("libreta"); ?></span>
                Bitácora de anotaciones
                <?php if (isset($_GET["notas"])) { ?>
                    <span class="guardado" id="aviso"><?php echo icono("palomita"); ?> Guardado</span>
                <?php } ?>
            </h3>
            <form method="post">
                <textarea name="notas" placeholder="Escribe aquí tus notas: pedidos pendientes, pagos, recordatorios..."><?php echo htmlspecialchars($notas); ?></textarea>
                <br><br>
                <button type="submit" name="guardar_notas" class="boton boton-borde">Guardar notas</button>
            </form>
        </div>

        <!-- HISTORIAL DE MOVIMIENTOS DE CAJA -->
        <div class="tarjeta">
            <div class="tarjeta-encabezado">
                <h3 class="tarjeta-titulo">
                    <span class="circulo"><?php echo icono("historial"); ?></span>
                    Movimientos de caja
                </h3>
                <!-- Al cambiar la opción se recarga la página con los días elegidos -->
                <form method="get">
                    <select name="dias" class="select-chico" onchange="this.form.submit()">
                        <?php foreach ($rangos as $numero => $texto) { ?>
                            <option value="<?php echo $numero; ?>" <?php if ($numero == $dias) echo "selected"; ?>><?php echo $texto; ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>

            <?php if ($movimientos->num_rows == 0) { ?>
                <p class="vacio">No hay movimientos en este periodo.</p>
            <?php } else { ?>
                <ul class="lista">
                    <?php while ($movimiento = $movimientos->fetch_assoc()) { ?>
                        <?php if (es_ingreso($movimiento["tipo"])) { ?>
                            <li class="renglon fondo-verde">
                                <div>
                                    <p class="lista-titulo"><?php echo htmlspecialchars($movimiento["detalle"]); ?></p>
                                    <p class="lista-fecha"><?php echo fecha_hora($movimiento["fecha_hora"]); ?></p>
                                </div>
                                <span class="cantidad texto-verde">+<?php echo dinero($movimiento["monto"]); ?></span>
                            </li>
                        <?php } else { ?>
                            <li class="renglon fondo-rojo">
                                <div>
                                    <p class="lista-titulo"><?php echo htmlspecialchars($movimiento["detalle"]); ?></p>
                                    <p class="lista-fecha"><?php echo fecha_hora($movimiento["fecha_hora"]); ?></p>
                                </div>
                                <span class="cantidad texto-rojo">-<?php echo dinero($movimiento["monto"]); ?></span>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>

        <?php include("pie.php"); ?>
    </main>

    <!-- CUADRO PARA CONFIRMAR EL GASTO O INGRESO -->
    <dialog id="dialogo-movimiento">
        <form method="post" class="dialogo-contenido">
            <h3 id="movimiento-titulo">Confirmar gasto</h3>
            <p class="descripcion" id="movimiento-descripcion"></p>

            <div class="renglon-monto">
                <span class="gris" id="movimiento-etiqueta">Monto a restar</span>
                <strong id="movimiento-monto">$0.00</strong>
            </div>

            <label for="movimiento-detalle" id="movimiento-detalle-titulo">Detalle del gasto</label>
            <input type="text" id="movimiento-detalle" name="detalle" maxlength="255" required autocomplete="off" autofocus>

            <input type="hidden" name="tipo" id="movimiento-tipo">
            <input type="hidden" name="monto" id="movimiento-valor">

            <div class="dialogo-botones">
                <button type="button" class="boton boton-borde" onclick="cerrarDialogo('dialogo-movimiento')">Cancelar</button>
                <button type="submit" name="guardar_movimiento" class="boton" id="movimiento-boton">Guardar movimiento</button>
            </div>

            <button type="button" class="cerrar" onclick="cerrarDialogo('dialogo-movimiento')">&times;</button>
        </form>
    </dialog>

    <script src="funciones.js"></script>
    <script>
        // Abre el cuadro de confirmación con la cantidad que se escribió
        // tipo = "gasto" o "ingreso"
        function abrirMovimiento(tipo) {
            let campo = document.getElementById("monto-" + tipo);
            let monto = parseFloat(campo.value);

            if (isNaN(monto) || monto <= 0) {
                campo.focus();
                return;
            }

            document.getElementById("movimiento-tipo").value = tipo;
            document.getElementById("movimiento-valor").value = monto;
            document.getElementById("movimiento-monto").textContent = dinero(monto);
            document.getElementById("movimiento-detalle").value = "";

            let detalle = document.getElementById("movimiento-detalle");
            let boton = document.getElementById("movimiento-boton");
            let cantidad = document.getElementById("movimiento-monto");

            if (tipo == "gasto") {
                document.getElementById("movimiento-titulo").textContent = "Confirmar gasto";
                document.getElementById("movimiento-descripcion").textContent = "Este dinero se restará de la caja y quedará en los movimientos.";
                document.getElementById("movimiento-etiqueta").textContent = "Monto a restar";
                document.getElementById("movimiento-detalle-titulo").textContent = "Detalle del gasto";
                detalle.placeholder = "Ej. Navajas, renta, luz, productos...";
                cantidad.className = "texto-rojo";
                boton.className = "boton boton-rojo";
            } else {
                document.getElementById("movimiento-titulo").textContent = "Confirmar ingreso";
                document.getElementById("movimiento-descripcion").textContent = "Este dinero se sumará a la caja y quedará en los movimientos.";
                document.getElementById("movimiento-etiqueta").textContent = "Monto a sumar";
                document.getElementById("movimiento-detalle-titulo").textContent = "Detalle del ingreso";
                detalle.placeholder = "Ej. Propina, venta de cera o shampoo...";
                cantidad.className = "texto-verde";
                boton.className = "boton boton-verde";
            }

            abrirDialogo("dialogo-movimiento");
        }
    </script>
</body>
</html>
