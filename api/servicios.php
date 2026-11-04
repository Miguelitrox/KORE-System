<?php
// API DE SERVICIOS
// Sin acción: regresa la lista de servicios.
// accion = guardar: agrega (id 0) o edita un servicio.  accion = borrar: lo elimina.
include(__DIR__ . "/../conexion.php");
include(__DIR__ . "/../funciones.php");

// GUARDAR SERVICIO: si el id es 0 es uno nuevo, si no, se edita el que ya existe
if (accion_pedida() == "guardar") {
    $id = (int) $_POST["id"];
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $duracion = (int) $_POST["duracion"];
    $precio = round((float) $_POST["precio"], 2);

    if ($nombre == "") {
        responder_error("Escribe el nombre del servicio.");
    }
    if ($precio <= 0) {
        responder_error("El precio debe ser mayor a cero.");
    }
    if ($duracion < 0) {
        $duracion = 0;
    }

    if ($id == 0) {
        $sql = $conexion->prepare("INSERT INTO servicios (nombre, descripcion, duracion, precio) VALUES (?, ?, ?, ?)");
        $sql->bind_param("ssid", $nombre, $descripcion, $duracion, $precio);
        $sql->execute();
        responder(["ok" => true, "mensaje" => "Servicio agregado."]);
    } else {
        $sql = $conexion->prepare("UPDATE servicios SET nombre = ?, descripcion = ?, duracion = ?, precio = ? WHERE id = ?");
        $sql->bind_param("ssidi", $nombre, $descripcion, $duracion, $precio, $id);
        $sql->execute();
        responder(["ok" => true, "mensaje" => "Servicio actualizado."]);
    }
}

// BORRAR SERVICIO
// Los registros que ya se hicieron no se pierden: guardan su propio nombre y precio.
if (accion_pedida() == "borrar") {
    $id = (int) $_POST["id"];

    $sql = $conexion->prepare("DELETE FROM servicios WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();

    responder(["ok" => true, "mensaje" => "Servicio eliminado."]);
}

// LEER los servicios
$servicios = lista_de($conexion->query("SELECT * FROM servicios ORDER BY nombre"));

responder(["ok" => true, "servicios" => $servicios]);
