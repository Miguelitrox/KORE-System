<?php
// API DE REPORTES: cuánto se vendió, qué servicios se hicieron más,
// cómo pagaron los clientes y el historial de los registros.
// Se pide con: api/reportes.php?dias=7
include(__DIR__ . "/../conexion.php");
include(__DIR__ . "/../funciones.php");

// Días que se pueden pedir (1 = hoy, 7 = hoy y los 6 días anteriores...)
$dias_permitidos = [1, 7, 30, 90, 180, 365];

$dias = 7;
if (isset($_GET["dias"]) && in_array((int) $_GET["dias"], $dias_permitidos)) {
    $dias = (int) $_GET["dias"];
}
// Desde las 00:00 del primer día
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
$registros = lista_de($sql->get_result());

$total_vendido = 0;
$total_servicios = 0;
foreach ($registros as $registro) {
    $total_vendido = $total_vendido + $registro["total"];
    $total_servicios = $total_servicios + $registro["cantidad"];
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
$mas_realizados = lista_de($sql->get_result());

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
$por_pago = lista_de($sql->get_result());

responder([
    "ok" => true,
    "registros" => $registros,
    "total_vendido" => $total_vendido,
    "total_servicios" => $total_servicios,
    "promedio" => $promedio,
    "mas_realizados" => $mas_realizados,
    "por_pago" => $por_pago,
]);
