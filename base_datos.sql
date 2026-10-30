-- BASE DE DATOS DE KORE SYSTEM
-- Cómo usarlo: entra a http://localhost/phpmyadmin, pestaña "Importar",
-- elige este archivo y presiona "Importar" (o copia todo y pégalo en la pestaña "SQL").
-- Se puede importar varias veces: no borra nada de lo que ya está guardado.

CREATE DATABASE IF NOT EXISTS nuevo_proyecto
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE nuevo_proyecto;

-- MI DINERO ACTUAL y las notas de la bitácora (solo hay una fila, la del id 1)
CREATE TABLE IF NOT EXISTS control_gastos (
    id INT PRIMARY KEY,
    dinero_actual DECIMAL(15, 2) NOT NULL DEFAULT 0,
    notas TEXT
);

INSERT IGNORE INTO control_gastos (id, dinero_actual, notas) VALUES (1, 0, '');

-- HISTORIAL: cada gasto, ingreso, venta, cobro y abono que mueve el dinero
-- tipo: gasto, ingreso, venta_contado, venta_financiada, cobro_deuda, abono_pagar
CREATE TABLE IF NOT EXISTS movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    monto DECIMAL(15, 2) NOT NULL,
    detalle VARCHAR(255) NOT NULL,
    fecha_hora DATETIME NOT NULL
);

-- STOCK: un artículo por fila. Cuando se vende, vendido pasa a 1 y ya no sale en Stock.
CREATE TABLE IF NOT EXISTS stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    articulo VARCHAR(150) NOT NULL,
    detalle VARCHAR(255) NOT NULL DEFAULT '',
    marca VARCHAR(100) NOT NULL DEFAULT '',
    precio_compra DECIMAL(15, 2) NOT NULL DEFAULT 0,
    precio_estimado DECIMAL(15, 2) NOT NULL DEFAULT 0,
    foto VARCHAR(255) NOT NULL DEFAULT '',
    vendido TINYINT NOT NULL DEFAULT 0
);

-- VENTAS: tipo_venta es "contado" o "financiada"
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_id INT NOT NULL,
    tipo_venta VARCHAR(20) NOT NULL,
    precio_compra DECIMAL(15, 2) NOT NULL,
    precio_venta DECIMAL(15, 2) NOT NULL,
    comentario VARCHAR(255) NOT NULL DEFAULT '',
    fecha_hora DATETIME NOT NULL
);

-- DEUDAS POR COBRAR (lo que me deben)
-- venta_id tiene el número de la venta financiada; si está vacío (NULL) es una deuda agregada a mano.
-- estado: "pendiente" o "pagada"
CREATE TABLE IF NOT EXISTS deudas_cobrar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NULL,
    fecha DATE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    comentario VARCHAR(255) NOT NULL DEFAULT '',
    total DECIMAL(15, 2) NOT NULL,
    abonado DECIMAL(15, 2) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
);

-- DEUDAS POR PAGAR (lo que yo debo)
CREATE TABLE IF NOT EXISTS deudas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    total DECIMAL(15, 2) NOT NULL,
    abonado DECIMAL(15, 2) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
);
