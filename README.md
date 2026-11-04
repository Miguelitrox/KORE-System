# KORE System — Demo "Barbería Sami's"

**KORE System** es un sistema de ventas, citas, caja y deudas hecho en su mayoría con **HTML, CSS y JavaScript**. PHP solo se usa para leer y guardar en la base de datos MySQL de XAMPP.
Es una "página blanca": se adapta a lo que necesite cada negocio.

Esta versión es una **DEMO** adaptada a una barbería de ejemplo, **Barbería Sami's**. El negocio y los datos son de prueba.

## Secciones (menú de abajo)

| Sección | Página | Para qué sirve |
|---|---|---|
| **Registrar** | `index.html` | Página principal. Botón REGISTRAR: se elige qué servicio se realizó, pregunta si se hizo otro y al guardar se registra todo (venta, caja y, si es fiado, la deuda). |
| **Servicios** | `servicios.html` | Lista de servicios con duración y precio. Son las opciones que salen al registrar y al agendar citas. |
| **Reportes** | `reportes.html` | Total vendido, clientes, ticket promedio, servicios más realizados, ventas por forma de pago e historial. |
| **Citas** | `citas.html` | La agenda: calendario del mes, próximas citas, agregar/editar/borrar, botón para Google Calendar y dictado por voz. |
| **Caja** | `caja.html` | Dinero en caja, gastos e ingresos extra, bitácora de notas y movimientos. |
| **Deudas** | `por_cobrar.html` y `por_pagar.html` | Lo que te deben (fiados y préstamos) y lo que debes (proveedores, renta...). |

## Cómo abrirlo

1. Abre el **Panel de control de XAMPP** y presiona **Start** en **Apache** y en **MySQL**.
2. Entra a <http://localhost/phpmyadmin>, ve a la pestaña **Importar**, elige `base_datos.sql` y presiona **Importar**.
   Crea la base de datos `kore_barberia` con 7 servicios de ejemplo. Se puede importar varias veces sin borrar lo guardado.
3. Abre <http://localhost/nuevo-proyecto/>

Importante: las páginas se abren desde `http://localhost/...`, no con doble clic en el archivo, porque piden los datos a la carpeta `api`.
El dictado por voz funciona en Chrome y Edge (en Firefox no aparece el botón del micrófono).

## Cómo funciona por dentro

- Cada página `.html` tiene su diseño y su JavaScript. Al abrir, JavaScript le pide los datos a un archivo de la carpeta `api` con `fetch()` y dibuja las listas.
- Los archivos de `api` son PHP chiquitos que leen o guardan en MySQL y contestan en JSON: `{ "ok": true, ... }` o `{ "ok": false, "error": "..." }`.
- Para guardar se manda el formulario con una `accion` (guardar, borrar, abonar...).

## Cómo adaptarlo a otro negocio

1. `configuracion.php`: nombre del negocio, texto de abajo del nombre, formas de pago y `$es_demo` (`true` muestra los avisos de DEMO). JavaScript los recibe por `api/configuracion.php`, así que solo se cambian aquí.
2. `estilos.css`: los "COLORES DEL NEGOCIO" al inicio, en `:root`. Los colores de KORE System no se cambian.
3. `conexion.php` y `base_datos.sql`: el nombre de la base de datos (cada negocio tiene la suya).

## Archivos

| Archivo | Para qué sirve |
|---|---|
| `index.html`, `servicios.html`, `reportes.html`, `citas.html`, `caja.html`, `por_cobrar.html`, `por_pagar.html` | Las páginas |
| `componentes.js` | Barra de DEMO, nombre del negocio, menú de abajo, pie "Hecho con KORE System", pestañas de Deudas e íconos |
| `funciones.js` | Funciones de JavaScript que se repiten (pedir datos, avisos, cuadros, dinero, fechas) |
| `estilos.css` | Colores y diseño (letra Geist) |
| `api/configuracion.php` | Le pasa a JavaScript los datos de `configuracion.php` |
| `api/registrar.php` | Servicios para elegir, registros de hoy y guardar un registro |
| `api/servicios.php` | Listar, agregar, editar y borrar servicios |
| `api/reportes.php` | Totales, servicios más realizados, formas de pago e historial |
| `api/citas.php` | Listar, agregar, editar y borrar citas |
| `api/caja.php` | Dinero en caja, movimientos, gastos, ingresos y notas |
| `api/deudas_cobrar.php`, `api/deudas_pagar.php` | Deudas: listar, agregar, editar, borrar y abonar |
| `configuracion.php` | Datos del negocio (nombre, formas de pago, si es demo) |
| `conexion.php` | Datos para conectarse a MySQL de XAMPP |
| `funciones.php` | Funciones de PHP que usan los archivos de `api` |
| `base_datos.sql` | Crea la base de datos `kore_barberia`, sus tablas y los servicios de ejemplo |
| `imagenes/logo.png` | Logo de KORE System |

## Cómo se conecta todo

- Al registrar, cada servicio guarda su nombre y precio de ese día: si después cambias el precio, los reportes no cambian.
- Si el cliente paga (efectivo, tarjeta o transferencia), el dinero entra a **Caja**.
- Si queda **fiado**, no entra dinero: se crea una deuda en **Deudas > Por cobrar**. Al abonarla, el dinero entra a Caja.
- Abonar en **Por pagar** resta de la Caja.
- Las deudas que se terminan de pagar salen de la lista.
- Las **citas** eligen el servicio de la pestaña Servicios y toman su duración. Lo que se dicta por voz abre el formulario ya llenado para revisarlo antes de guardar.
