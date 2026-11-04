<!-- PESTAÑAS DE DEUDAS: se pone en las 2 páginas de Deudas con include("menu_deudas.php") -->
<!-- Usa $pagina_actual, que viene de encabezado.php -->
<div class="pestanas">
    <a href="por_cobrar.php" class="<?php if ($pagina_actual == "por_cobrar.php") echo "activa"; ?>">
        <?php echo icono("cobrar"); ?> Por cobrar
    </a>
    <a href="por_pagar.php" class="<?php if ($pagina_actual == "por_pagar.php") echo "activa"; ?>">
        <?php echo icono("pagar"); ?> Por pagar
    </a>
</div>
