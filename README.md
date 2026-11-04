# KORE System — Demo "Barbería Sami's"

**KORE System** es un sistema de ventas, caja y deudas hecho con HTML, CSS, JavaScript y PHP, con base de datos MySQL en XAMPP.
Es una "página blanca": se adapta a lo que necesite cada negocio.

Esta versión es una **DEMO** adaptada a una barbería de ejemplo, **Barbería Sami's**. El negocio y los datos son de prueba.

## Secciones (menú de abajo)

| Sección | Página | Para qué sirve |
|---|---|---|
| **Registrar** | `index.php` | Página principal. Botón REGISTRAR: se elige qué servicio se realizó, pregunta si se hizo otro y al guardar se registra todo (venta, caja y, si es fiado, la deuda). |
| **Servicios** | `servicios.php` | Lista de servicios con duración y precio. Son las opciones que salen al registrar. |
| **Reportes** | `reportes.php` | Total vendido, clientes, ticket promedio, servicios más realizados, ventas por forma de pago e historial. |
| **Caja** | `caja.php` | Dinero en caja, gastos e ingresos extra, bitácora de notas y movimientos. |
| **Deudas** | `por_cobrar.php` y `por_pagar.php` | Lo que te deben (fiados y préstamos) y lo que debes (proveedores, renta...). |

## Cómo abrirlo

1. Abre el **Panel de control de XAMPP** y presiona **Start** en **Apache** y en **MySQL**.
2. Entra a <http://localhost/phpmyadmin>, ve a la pestaña **Importar**, elige `base_datos.sql` y presiona **Importar**.
   Crea la base de datos `kore_barberia` con 7 servicios de ejemplo. Se puede importar varias veces sin borrar lo guardado.
3. Abre <http://localhost/nuevo-proyecto/>

La carpeta del proyecto tiene que estar dentro de `C:\xampp\htdocs\` para que XAMPP la encuentre.

## Cómo adaptarlo a otro negocio

1. `configuracion.php`: nombre del negocio, texto de abajo del nombre, formas de pago y `$es_demo` (`true` muestra los avisos de DEMO).
2. `estilos.css`: los "COLORES DEL NEGOCIO" al inicio, en `:root`. Los colores de KORE System no se cambian.
3. `conexion.php` y `base_datos.sql`: el nombre de la base de datos (cada negocio tiene la suya).

## Archivos

| Archivo | Para qué sirve |
|---|---|
| `configuracion.php` | Datos del negocio (nombre, formas de pago, si es demo) |
| `encabezado.php` | Barra de DEMO, nombre del negocio y menú de abajo; se pone en todas las páginas |
| `pie.php` | Firma "Hecho con KORE System"; se pone al final de todas las páginas |
| `menu_deudas.php` | Pestañas Por cobrar / Por pagar |
| `funciones.php` | Funciones de PHP que se repiten (dinero, fechas, avisos, íconos, movimientos de caja) |
| `funciones.js` | Funciones de JavaScript que se repiten (abrir/cerrar cuadros, avisos, dinero) |
| `conexion.php` | Datos para conectarse a MySQL de XAMPP |
| `estilos.css` | Colores y diseño |
| `base_datos.sql` | Crea la base de datos `kore_barberia`, sus tablas y los servicios de ejemplo |
| `imagenes/logo.png` | Logo de KORE System |

## Cómo se conecta todo

- Al registrar, cada servicio guarda su nombre y precio de ese día: si después cambias el precio, los reportes no cambian.
- Si el cliente paga (efectivo, tarjeta o transferencia), el dinero entra a **Caja**.
- Si queda **fiado**, no entra dinero: se crea una deuda en **Deudas > Por cobrar**. Al abonarla, el dinero entra a Caja.
- Abonar en **Por pagar** resta de la Caja.
- Las deudas que se terminan de pagar salen de la lista.
