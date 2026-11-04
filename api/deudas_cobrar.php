<?php
// API DE DEUDAS POR COBRAR (lo que me deben)
// Aquí salen los registros que quedaron fiados (se crean solos al registrar) y las deudas que agrego a mano.
// Sin acción: regresa las deudas pendientes. Acciones: guardar, borrar, abonar.
include(__DIR__ . "/../conexion.php");
include(__DIR__ . "/../funciones.php");

// GUARDAR DEUDA: si el id es 0 es nueva, si no, se edita una que ya existe
if (accion_pedida() == "guardar") {
    $id = (int) $_POST["id"];
    $fecha = $_POST["fecha"];
    $nombre = trim($_POST["nombre"]);
    $comentario = trim($_POST["comentario"]);
    $total = round((float) $_POST["total"], 2);

    if ($nombre == "") {
        responder_error("Escribe quién te debe.");
    }
    if ($total <= 0) {
        responder_error("El monto total debe ser mayor a cero.");
    }
    if ($fecha == "") {
        $fecha = date("Y-m-d");
    }

    if ($id == 0) {
        // atencion_id se queda vacío (NULL) porque no viene de un registro fiado
        $sql = $conexion->prepare("INSERT INTO deudas_cobrar (fecha, nombre, comentario, total) VALUES (?, ?, ?, ?)");
        $sql->bind_param("sssd", $fecha, $nombre, $comentario, $total);
        $sql->execute();
        responder(["ok" => true, "mensaje" => "Deuda registrada."]);
    }

    // Buscamos la deuda para revisar que se pueda editar
    $sql = $conexion->prepare("SELECT * FROM deudas_cobrar WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();
    $deuda = $sql->get_result()->fetch_assoc();

    if (!$deuda || $deuda["atencion_id"] != null) {
        responder_error("No se puede editar una deuda que viene de un registro fiado.");
    }
    if ($total < $deuda["abonado"]) {
        responder_error("El total no puede ser menor que lo ya abonado.");
    }

    $estado = "pendiente";
    if ($deuda["abonado"] >= $total) {
        $estado = "pagada";
    }

    $sql = $conexion->prepare("UPDATE deudas_cobrar SET fecha = ?, nombre = ?, comentario = ?, total = ?, estado = ? WHERE id = ?");
    $sql->bind_param("sssdsi", $fecha, $nombre, $comentario, $total, $estado, $id);
    $sql->execute();
    responder(["ok" => true, "mensaje" => "Deuda actualizada."]);
}

// BORRAR DEUDA (solo las agregadas a mano; las de un registro fiado no se pueden borrar)
if (accion_pedida() == "borrar") {
    $id = (int) $_POST["id"];

    $sql = $conexion->prepare("DELETE FROM deudas_cobrar WHERE id = ? AND atencion_id IS NULL");
    $sql->bind_param("i", $id);
    $sql->execute();

    if ($sql->affected_rows == 0) {
        responder_error("No se puede eliminar una deuda que viene de un registro fiado.");
    }
    responder(["ok" => true, "mensaje" => "Deuda eliminada."]);
}

// ABONAR: me pagaron una parte (o todo) de la deuda
if (accion_pedida() == "abonar") {
    $id = (int) $_POST["id"];
    $monto = round((float) $_POST["monto"], 2);

    $sql = $conexion->prepare("SELECT * FROM deudas_cobrar WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();
    $deuda = $sql->get_result()->fetch_assoc();

    if (!$deuda) {
        responder_error("La deuda no existe.");
    }

    $pendiente = round($deuda["total"] - $deuda["abonado"], 2);
    if ($monto <= 0) {
        responder_error("El monto a abonar debe ser mayor a cero.");
    }
    if ($monto > $pendiente) {
        responder_error("El monto a abonar no puede ser mayor al saldo pendiente.");
    }

    $abonado = $deuda["abonado"] + $monto;
    $estado = "pendiente";
    if ($monto >= $pendiente) {
        $estado = "pagada";
    }

    $sql = $conexion->prepare("UPDATE deudas_cobrar SET abonado = ?, estado = ? WHERE id = ?");
    $sql->bind_param("dsi", $abonado, $estado, $id);
    $sql->execute();

    // El dinero que me pagaron entra a la caja
    $detalle = "Cobro de deuda: " . $deuda["nombre"];
    if ($deuda["atencion_id"] != null) {
        $detalle = "Cobro de fiado: " . $deuda["nombre"];
    }
    registrar_movimiento($conexion, "cobro_deuda", $monto, $detalle);

    responder(["ok" => true, "mensaje" => "Abono registrado."]);
}

// LEER las deudas que siguen pendientes y sacar los totales
$deudas = lista_de($conexion->query("SELECT * FROM deudas_cobrar WHERE estado = 'pendiente' ORDER BY id"));

$suma_total = 0;
$suma_abonado = 0;
foreach ($deudas as $deuda) {
    $suma_total = $suma_total + $deuda["total"];
    $suma_abonado = $suma_abonado + $deuda["abonado"];
}

responder([
    "ok" => true,
    "deudas" => $deudas,
    "suma_total" => $suma_total,
    "suma_abonado" => $suma_abonado,
    "suma_pendiente" => $suma_total - $suma_abonado,
]);
