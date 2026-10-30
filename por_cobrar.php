<?php
// VENTAS > POR COBRAR: lo que me deben.
// Aquí salen las ventas financiadas (se crean solas al vender) y las deudas que agrego a mano.
// En cuanto una deuda se termina de pagar, sale de esta lista.
include("conexion.php");
include("funciones.php");

// GUARDAR DEUDA: si el id es 0 es nueva, si no, se edita una que ya existe
if (isset($_POST["guardar_deuda"])) {
    $id = (int) $_POST["id"];
    $fecha = $_POST["fecha"];
    $nombre = trim($_POST["nombre"]);
    $comentario = trim($_POST["comentario"]);
    $total = round((float) $_POST["total"], 2);

    if ($nombre == "") {
        header("Location: por_cobrar.php?error=nombre");
        exit;
    }
    if ($total <= 0) {
        header("Location: por_cobrar.php?error=total");
        exit;
    }
    if ($fecha == "") {
        $fecha = date("Y-m-d");
    }

    if ($id == 0) {
        // venta_id se queda vacío (NULL) porque no viene de una venta
        $sql = $conexion->prepare("INSERT INTO deudas_cobrar (fecha, nombre, comentario, total) VALUES (?, ?, ?, ?)");
        $sql->bind_param("sssd", $fecha, $nombre, $comentario, $total);
        $sql->execute();
        $aviso = "guardada";
    } else {
        // Buscamos la deuda para revisar que se pueda editar
        $sql = $conexion->prepare("SELECT * FROM deudas_cobrar WHERE id = ?");
        $sql->bind_param("i", $id);
        $sql->execute();
        $deuda = $sql->get_result()->fetch_assoc();

        if (!$deuda || $deuda["venta_id"] != null) {
            header("Location: por_cobrar.php?error=venta");
            exit;
        }
        if ($total < $deuda["abonado"]) {
            header("Location: por_cobrar.php?error=menor");
            exit;
        }

        $estado = "pendiente";
        if ($deuda["abonado"] >= $total) {
            $estado = "pagada";
        }

        $sql = $conexion->prepare("UPDATE deudas_cobrar SET fecha = ?, nombre = ?, comentario = ?, total = ?, estado = ? WHERE id = ?");
        $sql->bind_param("sssdsi", $fecha, $nombre, $comentario, $total, $estado, $id);
        $sql->execute();
        $aviso = "actualizada";
    }

    header("Location: por_cobrar.php?aviso=" . $aviso);
    exit;
}

// BORRAR DEUDA (solo las agregadas a mano; las de una venta no se pueden borrar)
if (isset($_POST["borrar_deuda"])) {
    $id = (int) $_POST["id"];

    $sql = $conexion->prepare("DELETE FROM deudas_cobrar WHERE id = ? AND venta_id IS NULL");
    $sql->bind_param("i", $id);
    $sql->execute();

    header("Location: por_cobrar.php?aviso=borrada");
    exit;
}

// ABONAR: me pagaron una parte (o todo) de la deuda
if (isset($_POST["abonar"])) {
    $id = (int) $_POST["id"];
    $monto = round((float) $_POST["monto"], 2);

    $sql = $conexion->prepare("SELECT * FROM deudas_cobrar WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();
    $deuda = $sql->get_result()->fetch_assoc();

    if (!$deuda) {
        header("Location: por_cobrar.php?error=no_existe");
        exit;
    }

    $pendiente = round($deuda["total"] - $deuda["abonado"], 2);
    if ($monto <= 0) {
        header("Location: por_cobrar.php?error=monto");
        exit;
    }
    if ($monto > $pendiente) {
        header("Location: por_cobrar.php?error=mayor");
        exit;
    }

    $abonado = $deuda["abonado"] + $monto;
    $estado = "pendiente";
    if ($monto >= $pendiente) {
        $estado = "pagada";
    }

    $sql = $conexion->prepare("UPDATE deudas_cobrar SET abonado = ?, estado = ? WHERE id = ?");
    $sql->bind_param("dsi", $abonado, $estado, $id);
    $sql->execute();

    // El dinero que me pagaron entra a mi saldo
    registrar_movimiento($conexion, "cobro_deuda", $monto, "Cobro de deuda: " . $deuda["nombre"]);

    header("Location: por_cobrar.php?aviso=abono");
    exit;
}

// LEER las deudas que siguen pendientes y sacar los totales
$resultado = $conexion->query("SELECT * FROM deudas_cobrar WHERE estado = 'pendiente' ORDER BY id");
$deudas = [];
$suma_total = 0;
$suma_abonado = 0;
while ($fila = $resultado->fetch_assoc()) {
    $deudas[] = $fila;
    $suma_total = $suma_total + $fila["total"];
    $suma_abonado = $suma_abonado + $fila["abonado"];
}
$suma_pendiente = $suma_total - $suma_abonado;

$avisos = [
    "guardada" => "Deuda registrada.",
    "actualizada" => "Deuda actualizada.",
    "borrada" => "Deuda eliminada.",
    "abono" => "Abono registrado.",
];
$errores = [
    "nombre" => "Escribe quién te debe.",
    "total" => "El monto total debe ser mayor a cero.",
    "venta" => "No se puede editar una deuda generada por una venta.",
    "menor" => "El total no puede ser menor que lo ya abonado.",
    "no_existe" => "La deuda no existe.",
    "monto" => "El monto a abonar debe ser mayor a cero.",
    "mayor" => "El monto a abonar no puede ser mayor al saldo pendiente.",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#BD2E73">
    <title>Deudas por cobrar - KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>
    <?php mostrar_aviso($avisos, $errores); ?>

    <main class="contenedor">
        <?php include("menu_ventas.php"); ?>

        <div class="titulo-seccion">
            <div class="titulo-con-icono">
                <span class="circulo grande verde"><?php echo icono("cobrar"); ?></span>
                <div>
                    <h2>Deudas por cobrar</h2>
                    <p class="gris chico">Lo que te deben</p>
                </div>
            </div>
            <button type="button" class="boton boton-verde" onclick="nuevaDeuda()">
                <?php echo icono("mas"); ?> Agregar
            </button>
        </div>

        <!-- RESUMEN -->
        <div class="resumen tres">
            <div class="tarjeta chica">
                <p class="gris chico">Total</p>
                <p class="numero"><?php echo dinero($suma_total); ?></p>
            </div>
            <div class="tarjeta chica">
                <p class="gris chico">Pagado</p>
                <p class="numero texto-verde"><?php echo dinero($suma_abonado); ?></p>
            </div>
            <div class="tarjeta chica">
                <p class="gris chico">Pendiente</p>
                <p class="numero texto-amarillo"><?php echo dinero($suma_pendiente); ?></p>
            </div>
        </div>

        <!-- VISTA CELULAR: una tarjeta por deuda -->
        <div class="solo-celular">
            <?php if (count($deudas) == 0) { ?>
                <div class="tarjeta vacio">No hay deudas por cobrar registradas.</div>
            <?php } ?>

            <?php foreach ($deudas as $fila) { ?>
                <div class="tarjeta articulo">
                    <div class="renglon">
                        <div>
                            <p class="negrita cortar"><?php echo htmlspecialchars($fila["nombre"]); ?></p>
                            <p class="gris chico cortar"><?php echo htmlspecialchars($fila["comentario"]); ?></p>
                        </div>
                        <div class="celda-flex">
                            <span class="etiqueta pendiente">Pendiente</span>
                            <?php if ($fila["venta_id"] == null) { ?>
                                <button type="button" class="boton-icono" title="Editar deuda" onclick="editarDeuda(<?php echo $fila["id"]; ?>)">
                                    <?php echo icono("lapiz"); ?>
                                </button>
                                <form method="post" class="en-linea" data-nombre="<?php echo htmlspecialchars($fila["nombre"]); ?>" onsubmit="return confirmarBorrado(this, 'esta deuda')">
                                    <input type="hidden" name="id" value="<?php echo $fila["id"]; ?>">
                                    <button type="submit" name="borrar_deuda" class="boton-icono rosa" title="Eliminar deuda">
                                        <?php echo icono("basura"); ?>
                                    </button>
                                </form>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="tres-campos">
                        <div>
                            <p class="nombre-dato gris">Total</p>
                            <p class="negrita"><?php echo dinero($fila["total"]); ?></p>
                        </div>
                        <div>
                            <p class="nombre-dato gris">Pagado</p>
                            <p class="negrita texto-verde"><?php echo dinero($fila["abonado"]); ?></p>
                        </div>
                        <div>
                            <p class="nombre-dato gris">Pendiente</p>
                            <p class="negrita texto-amarillo"><?php echo dinero($fila["total"] - $fila["abonado"]); ?></p>
                        </div>
                    </div>

                    <button type="button" class="boton boton-borde boton-ancho" onclick="abrirAbono(<?php echo $fila["id"]; ?>)">
                        <?php echo icono("monedas"); ?> Abonar
                    </button>
                </div>
            <?php } ?>
        </div>

        <!-- VISTA COMPUTADORA: tabla -->
        <div class="solo-computadora tabla-caja">
            <table>
                <thead>
                    <tr>
                        <th>Producto / Detalle</th>
                        <th>Comentario</th>
                        <th class="derecha">Monto total</th>
                        <th class="derecha">Pagado</th>
                        <th class="derecha">Pendiente</th>
                        <th>Estado</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($deudas) == 0) { ?>
                        <tr>
                            <td colspan="8" class="vacio">No hay deudas por cobrar registradas.</td>
                        </tr>
                    <?php } ?>

                    <?php foreach ($deudas as $fila) { ?>
                        <tr>
                            <td class="negrita cortar"><?php echo htmlspecialchars($fila["nombre"]); ?></td>
                            <td class="gris cortar">
                                <?php if ($fila["comentario"] != "") { echo htmlspecialchars($fila["comentario"]); } else { echo "—"; } ?>
                            </td>
                            <td class="derecha"><?php echo dinero($fila["total"]); ?></td>
                            <td class="derecha texto-verde"><?php echo dinero($fila["abonado"]); ?></td>
                            <td class="derecha texto-amarillo"><?php echo dinero($fila["total"] - $fila["abonado"]); ?></td>
                            <td><span class="etiqueta pendiente">Pendiente</span></td>
                            <td>
                                <button type="button" class="boton boton-borde" onclick="abrirAbono(<?php echo $fila["id"]; ?>)">
                                    <?php echo icono("monedas"); ?> Abonar
                                </button>
                            </td>
                            <td>
                                <?php if ($fila["venta_id"] == null) { ?>
                                    <div class="celda-flex">
                                        <button type="button" class="boton-icono" title="Editar deuda" onclick="editarDeuda(<?php echo $fila["id"]; ?>)">
                                            <?php echo icono("lapiz"); ?>
                                        </button>
                                        <form method="post" class="en-linea" data-nombre="<?php echo htmlspecialchars($fila["nombre"]); ?>" onsubmit="return confirmarBorrado(this, 'esta deuda')">
                                            <input type="hidden" name="id" value="<?php echo $fila["id"]; ?>">
                                            <button type="submit" name="borrar_deuda" class="boton-icono rosa" title="Eliminar deuda">
                                                <?php echo icono("basura"); ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
                <?php if (count($deudas) > 0) { ?>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="derecha">TOTALES:</td>
                            <td class="derecha"><?php echo dinero($suma_total); ?></td>
                            <td class="derecha texto-verde"><?php echo dinero($suma_abonado); ?></td>
                            <td class="derecha texto-amarillo"><?php echo dinero($suma_pendiente); ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                <?php } ?>
            </table>
        </div>
    </main>

    <!-- CUADRO PARA AGREGAR O EDITAR UNA DEUDA -->
    <dialog id="dialogo-deuda">
        <form method="post" class="dialogo-contenido">
            <button type="button" class="cerrar" onclick="cerrarDialogo('dialogo-deuda')">&times;</button>
            <h3 id="deuda-titulo">Nueva deuda por cobrar</h3>
            <p class="descripcion" id="deuda-descripcion">Registra dinero que te deben (no ligado a una venta).</p>

            <input type="hidden" name="id" id="deuda-id" value="0">

            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" required>

            <label for="nombre">Quién debe</label>
            <input type="text" id="nombre" name="nombre" placeholder="Nombre" maxlength="150" required autocomplete="off">

            <label for="comentario">Comentario</label>
            <input type="text" id="comentario" name="comentario" placeholder="Descripción" maxlength="255" autocomplete="off">

            <label for="total">Monto total</label>
            <input type="number" id="total" name="total" step="0.01" min="0.01" placeholder="0.00" inputmode="decimal" required>

            <div class="dialogo-botones">
                <button type="submit" name="guardar_deuda" class="boton boton-verde boton-ancho" id="deuda-boton">Guardar deuda</button>
            </div>
        </form>
    </dialog>

    <!-- CUADRO PARA ABONAR -->
    <dialog id="dialogo-abono">
        <form method="post" class="dialogo-contenido">
            <button type="button" class="cerrar" onclick="cerrarDialogo('dialogo-abono')">&times;</button>
            <h3>Abonar deuda por cobrar</h3>
            <p class="descripcion" id="abono-descripcion"></p>

            <input type="hidden" name="id" id="abono-id">

            <label for="monto">¿Cuánto desea abonar?</label>
            <input type="number" id="monto" name="monto" step="0.01" min="0.01" placeholder="0.00" inputmode="decimal" required autofocus>

            <div class="dialogo-botones">
                <button type="button" class="boton boton-borde" onclick="cerrarDialogo('dialogo-abono')">Cancelar</button>
                <button type="submit" name="abonar" class="boton">Confirmar abono</button>
            </div>
        </form>
    </dialog>

    <script src="funciones.js"></script>
    <script>
        // Las deudas que PHP sacó de la base de datos
        let deudas = <?php echo json_encode($deudas); ?>;
        let hoy = "<?php echo date("Y-m-d"); ?>";

        function nuevaDeuda() {
            document.getElementById("deuda-id").value = 0;
            document.getElementById("fecha").value = hoy;
            document.getElementById("nombre").value = "";
            document.getElementById("comentario").value = "";
            document.getElementById("total").value = "";

            document.getElementById("deuda-titulo").textContent = "Nueva deuda por cobrar";
            document.getElementById("deuda-descripcion").textContent = "Registra dinero que te deben (no ligado a una venta).";
            document.getElementById("deuda-boton").textContent = "Guardar deuda";
            abrirDialogo("dialogo-deuda");
        }

        function editarDeuda(id) {
            let deuda = buscarPorId(deudas, id);

            document.getElementById("deuda-id").value = deuda.id;
            document.getElementById("fecha").value = deuda.fecha;
            document.getElementById("nombre").value = deuda.nombre;
            document.getElementById("comentario").value = deuda.comentario;
            document.getElementById("total").value = deuda.total;

            document.getElementById("deuda-titulo").textContent = "Editar deuda por cobrar";
            document.getElementById("deuda-descripcion").textContent = "Cambia los datos de la deuda.";
            document.getElementById("deuda-boton").textContent = "Guardar cambios";
            abrirDialogo("dialogo-deuda");
        }

        function abrirAbono(id) {
            let deuda = buscarPorId(deudas, id);
            let pendiente = deuda.total - deuda.abonado;

            document.getElementById("abono-id").value = deuda.id;
            document.getElementById("abono-descripcion").textContent = deuda.nombre + " · Pendiente: " + dinero(pendiente);
            document.getElementById("monto").value = "";
            document.getElementById("monto").max = pendiente.toFixed(2);
            abrirDialogo("dialogo-abono");
        }
    </script>
</body>
</html>
