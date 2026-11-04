<?php
// Le pasa a JavaScript los datos del negocio que están en configuracion.php
// (así el nombre del negocio solo se cambia en un lugar).
// Se pone en cada página, antes de los demás scripts, con:
// <script src="api/configuracion.php"></script>
include(__DIR__ . "/../configuracion.php");      // __DIR__ es la carpeta donde está este archivo

$datos = [
    "negocio" => $negocio,
    "descripcion" => $descripcion_negocio,
    "es_demo" => $es_demo,
    "formas_pago" => $formas_pago,
];

// Este archivo no regresa JSON: escribe una variable de JavaScript llamada CONFIG
header("Content-Type: application/javascript; charset=utf-8");
echo "const CONFIG = " . json_encode($datos) . ";";
