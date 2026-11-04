<?php
// CONFIGURACIÓN DEL NEGOCIO
// KORE System es una "página blanca" que se adapta a cada negocio.
// Para usarlo con otro cliente solo se cambian estos datos, los colores del
// negocio al inicio de estilos.css y la base de datos en conexion.php.
// Se pone en cada página con: include("configuracion.php");

$negocio = "Barbería Sami's";                          // nombre que sale arriba
$descripcion_negocio = "Servicios, caja y reportes";    // texto chiquito debajo del nombre

// true = es una demostración (sale la barra y la etiqueta de DEMO)
// false = ya es el sistema real del cliente
$es_demo = true;

// Formas de pago que se pueden elegir al registrar
// "fiado" no suma a la caja: crea una deuda en Deudas > Por cobrar
$formas_pago = [
    "efectivo" => "Efectivo",
    "tarjeta" => "Tarjeta",
    "transferencia" => "Transferencia",
    "fiado" => "Fiado (paga después)",
];
