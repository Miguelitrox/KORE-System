<?php
// FUNCIONES QUE SE USAN EN VARIAS PÁGINAS
// Se pone en cada página con: include("funciones.php");

// Muestra una cantidad como dinero: 1500 → $1,500.00
function dinero($cantidad) {
    return "$" . number_format($cantidad, 2);
}

// Muestra una fecha corta: 2026-10-30 → 30/10/2026
function fecha_corta($fecha) {
    return date("d/m/Y", strtotime($fecha));
}

// Muestra fecha y hora en español: 2026-10-30 15:45:00 → 30 oct 2026, 15:45
function fecha_hora($fecha) {
    $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
    $tiempo = strtotime($fecha);
    return date("d", $tiempo) . " " . $meses[date("n", $tiempo) - 1] . " " . date("Y, H:i", $tiempo);
}

// Guarda un movimiento en el Historial y actualiza "Mi dinero actual".
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

// ¿El movimiento suma dinero? (sale en verde en el Historial)
function es_ingreso($tipo) {
    return $tipo != "gasto" && $tipo != "abono_pagar";
}

// Muestra el mensaje que llega en la dirección de la página.
// Ej. stock.php?aviso=guardado muestra $avisos["guardado"] en verde
// y stock.php?error=vacio muestra $errores["vacio"] en rojo.
function mostrar_aviso($avisos, $errores) {
    if (isset($_GET["aviso"]) && isset($avisos[$_GET["aviso"]])) {
        echo '<p class="aviso" id="aviso">' . $avisos[$_GET["aviso"]] . '</p>';
    } else if (isset($_GET["error"]) && isset($errores[$_GET["error"]])) {
        echo '<p class="aviso error" id="aviso">' . $errores[$_GET["error"]] . '</p>';
    }
}

// Íconos copiados de lucide.dev. Uso: echo icono("caja");
function icono($nombre) {
    $iconos = [
        "cartera"   => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        "caja"      => '<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"/><path d="m7.5 4.27 9 5.15"/>',
        "carrito"   => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        "baja"      => '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>',
        "sube"      => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        "libreta"   => '<path d="M13.4 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7.4"/><path d="M2 6h4"/><path d="M2 10h4"/><path d="M2 14h4"/><path d="M2 18h4"/><path d="M21.378 5.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/>',
        "historial" => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        "mas"       => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        "menos"     => '<path d="M5 12h14"/>',
        "lapiz"     => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
        "basura"    => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
        "foto"      => '<path d="M16 5h6"/><path d="M19 2v6"/><path d="M21 11.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7.5"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/><circle cx="9" cy="9" r="2"/>',
        "billete"   => '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
        "monedas"   => '<path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 16 6 6"/><circle cx="16" cy="9" r="2.9"/><circle cx="6" cy="5" r="3"/>',
        "regresar"  => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        "cobrar"    => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="m8 12 4 4 4-4"/>',
        "pagar"     => '<circle cx="12" cy="12" r="10"/><path d="m16 12-4-4-4 4"/><path d="M12 16V8"/>',
        "palomita"  => '<path d="M20 6 9 17l-5-5"/>',
    ];

    return '<svg class="icono" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $iconos[$nombre] . '</svg>';
}
