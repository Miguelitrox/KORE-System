<?php
// FUNCIONES DE PHP QUE USAN LOS ARCHIVOS DE LA CARPETA api
// Se pone con: include(__DIR__ . "/../funciones.php");

// Manda la respuesta a JavaScript en formato JSON y termina
// Ej. responder(["ok" => true, "servicios" => $servicios]);
function responder($datos) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($datos);
    exit;
}

// Responde con un error; JavaScript lo muestra en el aviso rojo
function responder_error($mensaje) {
    responder(["ok" => false, "error" => $mensaje]);
}

// Qué se pidió hacer (guardar, borrar, abonar...). Si no viene nada, solo se leen los datos.
function accion_pedida() {
    if (isset($_POST["accion"])) {
        return $_POST["accion"];
    }
    return "";
}

// Guarda un movimiento en el historial de Caja y actualiza "Dinero en caja".
// Los gastos y los abonos a deudas que yo debo RESTAN; todo lo demás SUMA.
function registrar_movimiento($conexion, $tipo, $monto, $detalle) {
    $fecha_hora = date("Y-m-d H:i:s");
    $sql = $conexion->prepare("INSERT INTO movimientos (tipo, monto, detalle, fecha_hora) VALUES (?, ?, ?, ?)");
    $sql->bind_param("sdss", $tipo, $monto, $detalle, $fecha_hora);      // s = texto, d = número con decimales
    $sql->execute();

    if ($tipo == "gasto" || $tipo == "abono_pagar") {
        $monto = -$monto;
    }
    $sql = $conexion->prepare("UPDATE control_gastos SET dinero_actual = dinero_actual + ? WHERE id = 1");
    $sql->bind_param("d", $monto);
    $sql->execute();
}

// Pasa el resultado de una consulta a una lista (para mandarla en JSON)
function lista_de($resultado) {
    $lista = [];
    while ($fila = $resultado->fetch_assoc()) {
        $lista[] = $fila;
    }
    return $lista;
}
