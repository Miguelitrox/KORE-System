<?php
// PÁGINA DE REPORTES: cuánto se vendió, qué servicios se hicieron más,
// cómo pagaron los clientes y el historial de todos los registros.
include("conexion.php");
include("configuracion.php");
include("funciones.php");

$rangos = [
    1 => "Hoy",
    7 => "Últimos 7 días",
    30 => "Últimos 30 días",
    90 => "Últimos 3 meses",
    180 => "Últimos 6 meses",
    365 => "Último año",
];

$dias = 7;
if (isset($_GET["dias"]) && isset($rangos[$_GET["dias"]])) {
    $dias = (int) $_GET["dias"];
}
// Desde las 00:00 del primer día (1 = solo hoy, 7 = hoy y los 6 días anteriores)
$desde = date("Y-m-d 00:00:00", strtotime("-" . ($dias - 1) . " days"));

// 1. HISTORIAL: cada atención con sus servicios juntos y, si fue fiado, su deuda
$sql = $conexion->prepare("
    SELECT atenciones.*,
           GROUP_CONCAT(atencion_servicios.nombre ORDER BY atencion_servicios.id SEPARATOR ' + ') AS servicios,
           COUNT(atencion_servicios.id) AS cantidad,
           deudas_cobrar.estado AS estado_fiado
    FROM atenciones
    LEFT JOIN atencion_servicios ON atencion_servicios.atencion_id = atenciones.id
    LEFT JOIN deudas_cobrar ON deudas_cobrar.atencion_id = atenciones.id
    WHERE atenciones.fecha_hora >= ?
    GROUP BY atenciones.id
    ORDER BY atenciones.fecha_hora DESC, atenciones.id DESC
");
$sql->bind_param("s", $desde);
$sql->execute();
$resultado = $sql->get_result();

$registros = [];
$total_vendido = 0;
$total_servicios = 0;
while ($fila = $resultado->fetch_assoc()) {
    $registros[] = $fila;
    $total_vendido = $total_vendido + $fila["total"];
    $total_servicios = $total_servicios + $fila["cantidad"];
}

// Ticket promedio = lo que gasta en promedio cada cliente
$promedio = 0;
if (count($registros) > 0) {
    $promedio = $total_vendido / count($registros);
}

// 2. SERVICIOS MÁS REALIZADOS en el periodo
$sql = $conexion->prepare("
    SELECT atencion_servicios.nombre, COUNT(*) AS veces, SUM(atencion_servicios.precio) AS total
    FROM atencion_servicios
    JOIN atenciones ON atenciones.id = atencion_servicios.atencion_id
    WHERE atenciones.fecha_hora >= ?
    GROUP BY atencion_servicios.nombre
    ORDER BY veces DESC, total DESC
");
$sql->bind_param("s", $desde);
$sql->execute();
$mas_realizados = $sql->get_result();

// 3. VENTAS POR FORMA DE PAGO en el periodo
$sql = $conexion->prepare("
    SELECT metodo_pago, COUNT(*) AS veces, SUM(total) AS total
    FROM atenciones
    WHERE fecha_hora >= ?
    GROUP BY metodo_pago
    ORDER BY total DESC
");
$sql->bind_param("s", $desde);
$sql->execute();
$por_pago = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1C1C1E">
    <title>Reportes - <?php echo $negocio; ?> | KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>

    <main class="contenedor">
        <div class="titulo-seccion">
            <div class="titulo-con-icono">
                <span class="circulo grande"><?php echo icono("grafica"); ?></span>
                <div>
                    <h2>Reportes</h2>
                    <p class="gris chico">Ventas y servicios del negocio</p>
                </div>
            </div>
            <!-- Al cambiar la opción se recarga la página con los días elegidos -->
            <form method="get">
                <select name="dias" class="select-chico" onchange="this.form.submit()">
                    <?php foreach ($rangos as $numero => $texto) { ?>
                        <option value="<?php echo $numero; ?>" <?php if ($numero == $dias) echo "selected"; ?>><?php echo $texto; ?></option>
                    <?php } ?>
                </select>
            </form>
        </div>

        <!-- RESUMEN DEL PERIODO -->
        <div class="resumen">
            <div class="tarjeta chica">
                <p class="gris chico">Total vendido</p>
                <p class="numero texto-verde"><?php echo dinero($total_vendido); ?></p>
            </div>
            <div class="tarjeta chica">
                <p class="gris chico">Clientes atendidos</p>
                <p class="numero"><?php echo count($registros); ?></p>
            </div>
            <div class="tarjeta chica">
                <p class="gris chico">Servicios realizados</p>
                <p class="numero"><?php echo $total_servicios; ?></p>
            </div>
            <div class="tarjeta chica">
                <p class="gris chico">Ticket promedio</p>
                <p class="numero"><?php echo dinero($promedio); ?></p>
            </div>
        </div>

        <div class="dos-columnas">
            <!-- SERVICIOS MÁS REALIZADOS -->
            <div class="tarjeta">
                <h3 class="tarjeta-titulo">
                    <span class="circulo"><?php echo icono("tijeras"); ?></span>
                    Servicios más realizados
                </h3>
                <?php if ($mas_realizados->num_rows == 0) { ?>
                    <p class="vacio">Sin servicios en este periodo.</p>
                <?php } else { ?>
                    <ol class="ranking">
                        <?php $posicion = 1; ?>
                        <?php while ($fila = $mas_realizados->fetch_assoc()) { ?>
                            <li>
                                <span class="posicion"><?php echo $posicion; ?></span>
                                <span class="nombre-ranking">
                                    <?php echo htmlspecialchars($fila["nombre"]); ?>
                                    <span class="gris chico">· <?php echo $fila["veces"]; ?> <?php if ($fila["veces"] == 1) { echo "vez"; } else { echo "veces"; } ?></span>
                                </span>
                                <strong class="cantidad"><?php echo dinero($fila["total"]); ?></strong>
                            </li>
                            <?php $posicion = $posicion + 1; ?>
                        <?php } ?>
                    </ol>
                <?php } ?>
            </div>

            <!-- VENTAS POR FORMA DE PAGO -->
            <div class="tarjeta">
                <h3 class="tarjeta-titulo">
                    <span class="circulo"><?php echo icono("cartera"); ?></span>
                    Por forma de pago
                </h3>
                <?php if ($por_pago->num_rows == 0) { ?>
                    <p class="vacio">Sin ventas en este periodo.</p>
                <?php } else { ?>
                    <ul class="ranking">
                        <?php while ($fila = $por_pago->fetch_assoc()) { ?>
                            <li>
                                <span class="nombre-ranking">
                                    <?php echo $formas_pago[$fila["metodo_pago"]]; ?>
                                    <span class="gris chico">· <?php echo $fila["veces"]; ?> <?php if ($fila["veces"] == 1) { echo "cliente"; } else { echo "clientes"; } ?></span>
                                </span>
                                <strong class="cantidad <?php if ($fila["metodo_pago"] == "fiado") echo "texto-amarillo"; ?>"><?php echo dinero($fila["total"]); ?></strong>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
        </div>

        <!-- HISTORIAL DE REGISTROS -->
        <div class="tarjeta">
            <h3 class="tarjeta-titulo">
                <span class="circulo"><?php echo icono("historial"); ?></span>
                Historial de ventas
            </h3>

            <?php if (count($registros) == 0) { ?>
                <p class="vacio">No hay ventas en este periodo.</p>
            <?php } else { ?>
                <ul class="lista">
                    <?php foreach ($registros as $registro) { ?>
                        <?php $fiado = ($registro["metodo_pago"] == "fiado"); ?>
                        <li class="renglon <?php if ($fiado) { echo "fondo-amarillo"; } else { echo "fondo-verde"; } ?>">
                            <div>
                                <p class="lista-titulo"><?php echo htmlspecialchars($registro["servicios"]); ?></p>
                                <p class="lista-fecha">
                                    <?php echo fecha_hora($registro["fecha_hora"]); ?>
                                    <?php if ($registro["cliente"] != "") { echo "· " . htmlspecialchars($registro["cliente"]); } ?>
                                </p>
                            </div>
                            <div class="etiquetas">
                                <span class="cantidad"><?php echo dinero($registro["total"]); ?></span>
                                <?php if ($fiado) { ?>
                                    <?php if ($registro["estado_fiado"] == "pagada") { ?>
                                        <span class="etiqueta pagada">Fiado · Pagado</span>
                                    <?php } else { ?>
                                        <span class="etiqueta pendiente">Fiado · Pendiente</span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span class="etiqueta"><?php echo $formas_pago[$registro["metodo_pago"]]; ?></span>
                                <?php } ?>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>

        <?php include("pie.php"); ?>
    </main>

    <script src="funciones.js"></script>
</body>
</html>
