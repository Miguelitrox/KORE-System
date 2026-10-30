# KORE System

Sistema de control de stock y finanzas hecho con HTML, CSS, JavaScript y PHP, con base de datos MySQL en XAMPP.

Tiene tres secciones (menú de abajo):

- **Gastos**: mi dinero actual, registrar gastos e ingresos, bitácora de notas e historial de movimientos.
- **Stock**: artículos disponibles para vender, con foto opcional, precio de compra y precio estimado.
- **Ventas**: registrar ventas de contado o financiadas, historial de ventas, deudas por cobrar y deudas por pagar.

## Cómo abrirlo

1. Abre el **Panel de control de XAMPP** y presiona **Start** en **Apache** y en **MySQL**.
2. Entra a <http://localhost/phpmyadmin>, ve a la pestaña **Importar**, elige `base_datos.sql` y presiona **Importar**.
   Hazlo la primera vez y cada vez que `base_datos.sql` tenga tablas nuevas (no borra lo que ya está guardado).
3. Abre <http://localhost/nuevo-proyecto/>

La carpeta del proyecto tiene que estar dentro de `C:\xampp\htdocs\` para que XAMPP la encuentre.

## Archivos

| Archivo | Para qué sirve |
|---|---|
| `index.php` | Gastos (es la página que se abre primero) |
| `stock.php` | Stock: agregar, editar y borrar artículos |
| `ventas.php` | Ventas > Registrar venta |
| `historial_ventas.php` | Ventas > Historial |
| `por_cobrar.php` | Ventas > Deudas por cobrar (lo que me deben) |
| `por_pagar.php` | Ventas > Deudas por pagar (lo que debo) |
| `encabezado.php` | Logo, nombre y menú de abajo; se pone en todas las páginas |
| `menu_ventas.php` | Título y pestañas de Ventas; se pone en las 4 páginas de Ventas |
| `funciones.php` | Funciones de PHP que se repiten (dinero, fechas, avisos, íconos, guardar movimientos) |
| `funciones.js` | Funciones de JavaScript que se repiten (abrir/cerrar cuadros, avisos, dinero) |
| `conexion.php` | Datos para conectarse a MySQL de XAMPP |
| `estilos.css` | Colores y diseño (los colores están al inicio, en `:root`) |
| `base_datos.sql` | Crea la base de datos `nuevo_proyecto` y sus tablas |
| `imagenes/logo.png` | Logo de KORE System |
| `fotos/` | Aquí se guardan las fotos de los artículos (se crea sola al subir la primera) |

## Cómo se conecta todo

- Cada venta marca el artículo como vendido: deja de salir en Stock y aparece en el Historial de ventas.
- Una venta **de contado** suma todo el precio a "Mi dinero actual".
- Una venta **financiada** suma solo el pago inicial y crea una deuda en **Por Cobrar**.
- Abonar en **Por Cobrar** suma al dinero; abonar en **Por Pagar** resta.
- Todo lo que mueve el dinero queda en el **Historial** de la página de Gastos.
- Las deudas que se terminan de pagar salen de la lista.
