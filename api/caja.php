<?php
// API DE CAJA
// Sin acción: regresa el dinero en caja, las notas y los movimientos (api/caja.php?dias=3).
// accion = movimiento: guarda un gasto o ingreso.  accion = notas: guarda la bitácora.
include(__DIR__ . "/../conexion.php");
include(__DIR__ . "/../funciones.php");

// GUARDAR UN GASTO O INGRESO (viene del cuadro de confirmación)
if (accion_pedida() == "movimiento") {
    $tipo = $_POST["tipo"];             // "gasto" o "ingreso"
    $monto = (float) $_POST["monto"];
    $detalle = trim($_POST["detalle"]);

    if (($tipo != "gasto" && $tipo != "ingreso") || $monto <= 0 || $detalle == "") {
        responder_error("Escribe una cantidad mayor a 0 y el detalle del movimiento.");
    }

    registrar_movimiento($conexion, $tipo, $monto, $detalle);

    if ($tipo == "gasto") {
        responder(["ok" => true, "mensaje" => "Gasto registrado."]);
    }
    responder(["ok" => true, "mensaje" => "Ingreso registrado."]);
}

// GUARDAR LAS NOTAS DE LA BITÁCORA
if (accion_pedida() == "notas") {
    $notas = $_POST["notas"];

    $sql = $conexion->prepare("UPDATE control_gastos SET notas = ? WHERE id = 1");
    $sql->bind_param("s", $notas);
    $sql->execute();

    responder(["ok" => true]);
}

// LEER el dinero en caja y las notas (siempre es la fila con id 1)
$control = $conexion->query("SELECT * FROM control_gastos WHERE id = 1")->fetch_assoc();

// MOVIMIENTOS: los últimos días que se pidan
$dias_permitidos = [3, 7, 14, 21, 30, 90, 180, 365];

$dias = 3;
if (isset($_GET["dias"]) && in_array((int) $_GET["dias"], $dias_permitidos)) {
    $dias = (int) $_GET["dias"];
}
$desde = date("Y-m-d H:i:s", strtotime("-" . $dias . " days"));

$sql = $conexion->prepare("SELECT * FROM movimientos WHERE fecha_hora >= ? ORDER BY fecha_hora DESC, id DESC");
$sql->bind_param("s", $desde);
$sql->execute();
$movimientos = lista_de($sql->get_result());

responder([
    "ok" => true,
    "dinero_actual" => $control["dinero_actual"],
    "notas" => $control["notas"],
    "movimientos" => $movimientos,
]);
