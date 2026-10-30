<?php
// CONEXIÓN A LA BASE DE DATOS (MySQL de XAMPP)
// Se usa en cualquier página que necesite la base de datos con: include("conexion.php");

// Datos de XAMPP: por defecto el usuario es "root" y no tiene contraseña
$servidor   = "localhost";
$usuario    = "root";
$clave      = "";
$base_datos = "nuevo_proyecto";

// Hora de México (XAMPP trae por defecto la hora de Alemania)
date_default_timezone_set("America/Mexico_City");

try {
    $conexion = new mysqli($servidor, $usuario, $clave, $base_datos);
} catch (Exception $e) {
    // Si falla, se detiene la página y se muestra qué pasó
    die("<p>No se pudo conectar a la base de datos: " . $e->getMessage() . "</p>" .
        "<p>Revisa que MySQL esté encendido (Start) en el panel de XAMPP " .
        "y que ya hayas importado base_datos.sql en phpMyAdmin.</p>");
}

// Para que los acentos y la ñ se guarden bien
$conexion->set_charset("utf8mb4");
