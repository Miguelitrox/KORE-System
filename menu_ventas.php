<!-- TÍTULO Y PESTAÑAS DE VENTAS: se pone en las 4 páginas de Ventas con include("menu_ventas.php") -->
<!-- Usa $pagina_actual, que viene de encabezado.php -->
<div class="titulo-con-icono">
    <span class="circulo grande"><?php echo icono("carrito"); ?></span>
    <div>
        <h2>Ventas</h2>
        <p class="gris chico">Ventas, cobros y deudas del negocio</p>
    </div>
</div>

<div class="pestanas">
    <a href="ventas.php" class="<?php if ($pagina_actual == "ventas.php") echo "activa"; ?>">
        <?php echo icono("carrito"); ?> Registrar Venta
    </a>
    <a href="historial_ventas.php" class="<?php if ($pagina_actual == "historial_ventas.php") echo "activa"; ?>">
        <?php echo icono("historial"); ?> Historial
    </a>
    <a href="por_cobrar.php" class="<?php if ($pagina_actual == "por_cobrar.php") echo "activa"; ?>">
        <?php echo icono("monedas"); ?> Por Cobrar
    </a>
    <a href="por_pagar.php" class="<?php if ($pagina_actual == "por_pagar.php") echo "activa"; ?>">
        <?php echo icono("pagar"); ?> Por Pagar
    </a>
</div>
