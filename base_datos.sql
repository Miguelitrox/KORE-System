-- BASE DE DATOS DE KORE SYSTEM - DEMO "BARBERÍA SAMI'S"
-- Cómo usarlo: entra a http://localhost/phpmyadmin, pestaña "Importar",
-- elige este archivo y presiona "Importar" (o copia todo y pégalo en la pestaña "SQL").
-- Se puede importar varias veces: no borra nada de lo que ya está guardado.

CREATE DATABASE IF NOT EXISTS kore_barberia
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE kore_barberia;

-- CAJA: mi dinero actual y las notas de la bitácora (solo hay una fila, la del id 1)
CREATE TABLE IF NOT EXISTS control_gastos (
    id INT PRIMARY KEY,
    dinero_actual DECIMAL(15, 2) NOT NULL DEFAULT 0,
    notas TEXT
);

INSERT IGNORE INTO control_gastos (id, dinero_actual, notas) VALUES (1, 0, '');

-- HISTORIAL DE CAJA: cada venta, gasto, ingreso, cobro y abono que mueve el dinero
-- tipo: venta, gasto, ingreso, cobro_deuda, abono_pagar
CREATE TABLE IF NOT EXISTS movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    monto DECIMAL(15, 2) NOT NULL,
    detalle TEXT NOT NULL,
    fecha_hora DATETIME NOT NULL
);

-- SERVICIOS que ofrece el negocio (se eligen al registrar)
CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    duracion INT NOT NULL DEFAULT 30,          -- en minutos
    precio DECIMAL(15, 2) NOT NULL DEFAULT 0
);

-- Servicios de ejemplo para la demo (se pueden cambiar o borrar en la pestaña Servicios)
INSERT IGNORE INTO servicios (id, nombre, descripcion, duracion, precio) VALUES
    (1, 'Corte de cabello', 'Corte clásico o moderno con máquina y tijera', 30, 150),
    (2, 'Corte y barba', 'Corte de cabello más arreglo de barba', 50, 220),
    (3, 'Arreglo de barba', 'Perfilado y recorte con navaja', 20, 100),
    (4, 'Afeitado clásico', 'Toalla caliente, espuma y navaja', 30, 130),
    (5, 'Corte infantil', 'Para niños menores de 12 años', 25, 120),
    (6, 'Diseño de ceja', 'Limpieza y perfilado de ceja', 10, 50),
    (7, 'Lavado y peinado', 'Lavado, masaje y peinado con producto', 15, 80);

-- ATENCIONES: cada vez que se registra a un cliente (puede llevar varios servicios)
-- metodo_pago: efectivo, tarjeta, transferencia o fiado (paga después)
CREATE TABLE IF NOT EXISTS atenciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente VARCHAR(100) NOT NULL DEFAULT '',
    metodo_pago VARCHAR(20) NOT NULL,
    total DECIMAL(15, 2) NOT NULL,
    fecha_hora DATETIME NOT NULL
);

-- LOS SERVICIOS DE CADA ATENCIÓN
-- Se guarda el nombre y el precio que tenían ese día: así los reportes no cambian
-- si después se cambia el precio o se borra el servicio.
CREATE TABLE IF NOT EXISTS atencion_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atencion_id INT NOT NULL,
    servicio_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(15, 2) NOT NULL
);

-- DEUDAS POR COBRAR (lo que me deben)
-- atencion_id tiene el número de la atención que quedó fiada; si está vacío (NULL) es una deuda agregada a mano.
-- estado: "pendiente" o "pagada"
CREATE TABLE IF NOT EXISTS deudas_cobrar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atencion_id INT NULL,
    fecha DATE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    comentario TEXT NOT NULL,
    total DECIMAL(15, 2) NOT NULL,
    abonado DECIMAL(15, 2) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
);

-- DEUDAS POR PAGAR (lo que yo debo: proveedores, renta...)
CREATE TABLE IF NOT EXISTS deudas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    total DECIMAL(15, 2) NOT NULL,
    abonado DECIMAL(15, 2) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
);
