<?php
// API DE CITAS (la agenda)
// Sin acción: regresa todas las citas y los servicios para elegir.
// accion = guardar: agrega (id 0) o edita una cita.  accion = borrar: la elimina.
include(__DIR__ . "/../conexion.php");
include(__DIR__ . "/../funciones.php");

// GUARDAR CITA: si el id es 0 es nueva, si no, se edita la que ya existe
if (accion_pedida() == "guardar") {
    $id = (int) $_POST["id"];
    $cliente = trim($_POST["cliente"]);
    $servicio_id = (int) $_POST["servicio_id"];
    $fecha = $_POST["fecha"];
    $hora = $_POST["hora"];               // en 24 horas, ej. "16:30"
    $duracion = (int) $_POST["duracion"];  // en minutos

    if ($cliente == "") {
        responder_error("Escribe el nombre del cliente.");
    }
    // La fecha tiene que venir como 2026-11-04 y la hora como 16:30
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha) || strtotime($fecha) === false) {
        responder_error("Elige la fecha de la cita.");
    }
    if (!preg_match("/^\d{2}:\d{2}$/", $hora)) {
        responder_error("Elige la hora de la cita.");
    }
    if ($duracion <= 0) {
        responder_error("Elige la duración de la cita.");
    }

    // Se busca el servicio para guardar su nombre
    $sql = $conexion->prepare("SELECT * FROM servicios WHERE id = ?");
    $sql->bind_param("i", $servicio_id);
    $sql->execute();
    $servicio = $sql->get_result()->fetch_assoc();

    if (!$servicio) {
        responder_error("Elige un servicio de la lista.");
    }

    if ($id == 0) {
        $sql = $conexion->prepare("INSERT INTO citas (cliente, servicio_id, servicio, fecha, hora, duracion) VALUES (?, ?, ?, ?, ?, ?)");
        $sql->bind_param("sisssi", $cliente, $servicio_id, $servicio["nombre"], $fecha, $hora, $duracion);
        $sql->execute();
        responder(["ok" => true, "mensaje" => "Cita agendada."]);
    }

    $sql = $conexion->prepare("UPDATE citas SET cliente = ?, servicio_id = ?, servicio = ?, fecha = ?, hora = ?, duracion = ? WHERE id = ?");
    $sql->bind_param("sisssii", $cliente, $servicio_id, $servicio["nombre"], $fecha, $hora, $duracion, $id);
    $sql->execute();
    responder(["ok" => true, "mensaje" => "Cita actualizada."]);
}

// BORRAR CITA
if (accion_pedida() == "borrar") {
    $id = (int) $_POST["id"];

    $sql = $conexion->prepare("DELETE FROM citas WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();

    responder(["ok" => true, "mensaje" => "Cita eliminada."]);
}

// LEER todas las citas (ordenadas por fecha y hora) y los servicios para elegir
$citas = lista_de($conexion->query("SELECT * FROM citas ORDER BY fecha, hora, id"));
$servicios = lista_de($conexion->query("SELECT * FROM servicios ORDER BY nombre"));

responder(["ok" => true, "citas" => $citas, "servicios" => $servicios]);
