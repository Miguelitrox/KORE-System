<?php
// VENTAS > POR PAGAR: lo que yo debo.
// En cuanto una deuda se termina de pagar, sale de esta lista.
include("conexion.php");
include("funciones.php");

// GUARDAR DEUDA: si el id es 0 es nueva, si no, se edita una que ya existe
if (isset($_POST["guardar_deuda"])) {
    $id = (int) $_POST["id"];
    $fecha = $_POST["fecha"];
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $total = round((float) $_POST["total"], 2);

    if ($nombre == "") {
        header("Location: por_pagar.php?error=nombre");
        exit;
    }
    if ($total <= 0) {
        header("Location: por_pagar.php?error=total");
        exit;
    }
    if ($fecha == "") {
        $fecha = date("Y-m-d");
    }

    if ($id == 0) {
        $sql = $conexion->prepare("INSERT INTO deudas_pagar (fecha, nombre, descripcion, total) VALUES (?, ?, ?, ?)");
        $sql->bind_param("sssd", $fecha, $nombre, $descripcion, $total);
        $sql->execute();
        $aviso = "guardada";
    } else {
        $sql = $conexion->prepare("SELECT * FROM deudas_pagar WHERE id = ?");
        $sql->bind_param("i", $id);
        $sql->execute();
        $deuda = $sql->get_result()->fetch_assoc();

        if (!$deuda) {
            header("Location: por_pagar.php?error=no_existe");
            exit;
        }
        if ($total < $deuda["abonado"]) {
            header("Location: por_pagar.php?error=menor");
            exit;
        }

        $estado = "pendiente";
        if ($deuda["abonado"] >= $total) {
            $estado = "pagada";
        }

        $sql = $conexion->prepare("UPDATE deudas_pagar SET fecha = ?, nombre = ?, descripcion = ?, total = ?, estado = ? WHERE id = ?");
        $sql->bind_param("sssdsi", $fecha, $nombre, $descripcion, $total, $estado, $id);
        $sql->execute();
        $aviso = "actualizada";
    }

    header("Location: por_pagar.php?aviso=" . $aviso);
    exit;
}

// BORRAR DEUDA
if (isset($_POST["borrar_deuda"])) {
    $id = (int) $_POST["id"];

    $sql = $conexion->prepare("DELETE FROM deudas_pagar WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();

    header("Location: por_pagar.php?aviso=borrada");
    exit;
}

// ABONAR: pagué una parte (o todo) de lo que debo
if (isset($_POST["abonar"])) {
    $id = (int) $_POST["id"];
    $monto = round((float) $_POST["monto"], 2);

    $sql = $conexion->prepare("SELECT * FROM deudas_pagar WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();
    $deuda = $sql->get_result()->fetch_assoc();

    if (!$deuda) {
        header("Location: por_pagar.php?error=no_existe");
        exit;
    }

    $pendiente = round($deuda["total"] - $deuda["abonado"], 2);
    if ($monto <= 0) {
        header("Location: por_pagar.php?error=monto");
        exit;
    }
    if ($monto > $pendiente) {
        header("Location: por_pagar.php?error=mayor");
        exit;
    }

    $abonado = $deuda["abonado"] + $monto;
    $estado = "pendiente";
    if ($monto >= $pendiente) {
        $estado = "pagada";
    }

    $sql = $conexion->prepare("UPDATE deudas_pagar SET abonado = ?, estado = ? WHERE id = ?");
    $sql->bind_param("dsi", $abonado, $estado, $id);
    $sql->execute();

    // El dinero que pagué sale de mi saldo
    registrar_movimiento($conexion, "abono_pagar", $monto, "Abono a deuda: " . $deuda["nombre"]);

    header("Location: por_pagar.php?aviso=abono");
    exit;
}

// LEER las deudas que siguen pendientes y sacar los totales
$resultado = $conexion->query("SELECT * FROM deudas_pagar WHERE estado = 'pendiente' ORDER BY id");
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
    "nombre" => "Escribe a quién le debes.",
    "total" => "El importe debe ser mayor a cero.",
    "menor" => "El importe no puede ser menor que lo ya abonado.",
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
    <title>Deudas a pagar - KORE System</title>
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
                <span class="circulo grande"><?php echo icono("pagar"); ?></span>
                <div>
                    <h2>Deudas a pagar</h2>
                    <p class="gris chico">Lo que debes</p>
                </div>
            </div>
            <button type="button" class="boton" onclick="nuevaDeuda()">
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
                <p class="gris chico">Abonado</p>
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
                <div class="tarjeta vacio">No hay deudas registradas.</div>
            <?php } ?>

            <?php foreach ($deudas as $fila) { ?>
                <div class="tarjeta articulo">
                    <div class="renglon">
                        <div>
                            <p class="negrita cortar"><?php echo htmlspecialchars($fila["nombre"]); ?></p>
                            <p class="gris chico cortar"><?php echo htmlspecialchars($fila["descripcion"]); ?></p>
                        </div>
                        <div class="celda-flex">
                            <span class="etiqueta pendiente">Pendiente</span>
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
                    </div>

                    <div class="tres-campos">
                        <div>
                            <p class="nombre-dato gris">Total</p>
                            <p class="negrita"><?php echo dinero($fila["total"]); ?></p>
                        </div>
                        <div>
                            <p class="nombre-dato gris">Abonado</p>
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
                        <th>Fecha</th>
                        <th>Detalle a pagar</th>
                        <th>Descripción</th>
                        <th class="derecha">Importe</th>
                        <th class="derecha">Abonado</th>
                        <th class="derecha">Pendiente</th>
                        <th>Estado</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($deudas) == 0) { ?>
                        <tr>
                            <td colspan="9" class="vacio">No hay deudas registradas.</td>
                        </tr>
                    <?php } ?>

                    <?php foreach ($deudas as $fila) { ?>
                        <tr>
                            <td><?php echo fecha_corta($fila["fecha"]); ?></td>
                            <td class="negrita cortar"><?php echo htmlspecialchars($fila["nombre"]); ?></td>
                            <td class="gris cortar">
                                <?php if ($fila["descripcion"] != "") { echo htmlspecialchars($fila["descripcion"]); } else { echo "—"; } ?>
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
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
                <?php if (count($deudas) > 0) { ?>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="derecha">TOTALES:</td>
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
            <h3 id="deuda-titulo">Nueva deuda a pagar</h3>
            <p class="descripcion" id="deuda-descripcion">Registra una deuda pendiente.</p>

            <input type="hidden" name="id" id="deuda-id" value="0">

            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" required>

            <label for="nombre">A quién le debo</label>
            <input type="text" id="nombre" name="nombre" placeholder="A quién o qué" maxlength="150" required autocomplete="off">

            <label for="descripcion">Descripción</label>
            <input type="text" id="descripcion" name="descripcion" placeholder="Descripción" maxlength="255" autocomplete="off">

            <label for="total">Importe</label>
            <input type="number" id="total" name="total" step="0.01" min="0.01" placeholder="0.00" inputmode="decimal" required>

            <div class="dialogo-botones">
                <button type="submit" name="guardar_deuda" class="boton boton-ancho" id="deuda-boton">Guardar deuda</button>
            </div>
        </form>
    </dialog>

    <!-- CUADRO PARA ABONAR -->
    <dialog id="dialogo-abono">
        <form method="post" class="dialogo-contenido">
            <button type="button" class="cerrar" onclick="cerrarDialogo('dialogo-abono')">&times;</button>
            <h3>Abonar deuda</h3>
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
            document.getElementById("descripcion").value = "";
            document.getElementById("total").value = "";

            document.getElementById("deuda-titulo").textContent = "Nueva deuda a pagar";
            document.getElementById("deuda-descripcion").textContent = "Registra una deuda pendiente.";
            document.getElementById("deuda-boton").textContent = "Guardar deuda";
            abrirDialogo("dialogo-deuda");
        }

        function editarDeuda(id) {
            let deuda = buscarPorId(deudas, id);

            document.getElementById("deuda-id").value = deuda.id;
            document.getElementById("fecha").value = deuda.fecha;
            document.getElementById("nombre").value = deuda.nombre;
            document.getElementById("descripcion").value = deuda.descripcion;
            document.getElementById("total").value = deuda.total;

            document.getElementById("deuda-titulo").textContent = "Editar deuda a pagar";
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
