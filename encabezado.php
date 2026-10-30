<?php
// Nombre del archivo que se está viendo (ej. "stock.php"), para pintar su botón en el menú
$pagina_actual = basename($_SERVER["PHP_SELF"]);

// Las 4 páginas de Ventas pintan el mismo botón "Ventas"
$paginas_ventas = ["ventas.php", "historial_ventas.php", "por_cobrar.php", "por_pagar.php"];
?>
<!-- ENCABEZADO: se pone en todas las páginas con include("encabezado.php") -->
<header>
    <div class="contenedor encabezado">
        <img src="imagenes/logo.png" alt="Logo de KORE System" class="logo">
        <div>
            <h1>KORE System</h1>
            <p>Control de Stock y Finanzas</p>
        </div>
    </div>
</header>

<!-- MENÚ DE ABAJO (como en una app de celular) -->
<nav class="menu">
    <div class="contenedor">
        <a href="index.php" class="<?php if ($pagina_actual == "index.php") echo "activo"; ?>">
            <span><?php echo icono("cartera"); ?></span>
            Gastos
        </a>
        <a href="stock.php" class="<?php if ($pagina_actual == "stock.php") echo "activo"; ?>">
            <span><?php echo icono("caja"); ?></span>
            Stock
        </a>
        <a href="ventas.php" class="<?php if (in_array($pagina_actual, $paginas_ventas)) echo "activo"; ?>">
            <span><?php echo icono("carrito"); ?></span>
            Ventas
        </a>
    </div>
</nav>
