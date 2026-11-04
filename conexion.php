<?php
// CONEXIÓN A LA BASE DE DATOS (MySQL de XAMPP)
// La usan los archivos de la carpeta api con: include(__DIR__ . "/../conexion.php");

// Datos de XAMPP: por defecto el usuario es "root" y no tiene contraseña
$servidor   = "localhost";
$usuario    = "root";
$clave      = "";
$base_datos = "kore_barberia";      // cada negocio que usa KORE System tiene su propia base de datos

// Hora de México (XAMPP trae por defecto la hora de Alemania)
date_default_timezone_set("America/Mexico_City");

// Si algo falla con la base de datos, se le avisa a JavaScript con un mensaje (en JSON)
// en lugar de mostrar el error de PHP
function error_de_base_de_datos($error) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "ok" => false,
        "error" => "Error en la base de datos: " . $error->getMessage() .
                   ". Revisa que MySQL esté encendido en XAMPP y que ya importaste base_datos.sql.",
    ]);
    exit;
}
set_exception_handler("error_de_base_de_datos");

$conexion = new mysqli($servidor, $usuario, $clave, $base_datos);

// Para que los acentos y la ñ se guarden bien
$conexion->set_charset("utf8mb4");
