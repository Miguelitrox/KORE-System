<?php
// API DE REGISTRAR (página principal)
// Sin acción: regresa los servicios para elegir y los registros de hoy.
// accion = guardar: guarda la atención con sus servicios (y la venta o el fiado).
include(__DIR__ . "/../conexion.php");
include(__DIR__ . "/../configuracion.php");
include(__DIR__ . "/../funciones.php");

// GUARDAR EL REGISTRO (se presionó "Guardar registro" en el resumen)
if (accion_pedida() == "guardar") {
    $cliente = trim($_POST["cliente"]);
    $pago = $_POST["pago"];

    // Lista con los id de los servicios elegidos (puede repetirse, ej. dos cortes)
    $servicios_elegidos = [];
    if (isset($_POST["servicios"]) && is_array($_POST["servicios"])) {
        $servicios_elegidos = $_POST["servicios"];
    }

    if (!isset($formas_pago[$pago])) {
        responder_error("Elige una forma de pago.");
    }
    if ($pago == "fiado" && $cliente == "") {
        responder_error("Para dejarlo fiado escribe el nombre del cliente.");
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
        responder_error("Elige al menos un servicio.");
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

    responder(["ok" => true, "mensaje" => "Registro guardado."]);
}

// LEER: servicios que se pueden elegir (los de la pestaña Servicios)
$servicios = lista_de($conexion->query("SELECT * FROM servicios ORDER BY nombre"));

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
$registros = lista_de($sql->get_result());

$ventas_hoy = 0;
$servicios_hoy = 0;
foreach ($registros as $registro) {
    $ventas_hoy = $ventas_hoy + $registro["total"];
    $servicios_hoy = $servicios_hoy + $registro["cantidad"];
}

responder([
    "ok" => true,
    "servicios" => $servicios,
    "registros" => $registros,
    "ventas_hoy" => $ventas_hoy,
    "servicios_hoy" => $servicios_hoy,
]);
