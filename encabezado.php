<?php
// Nombre del archivo que se está viendo (ej. "servicios.php"), para pintar su botón en el menú
$pagina_actual = basename($_SERVER["PHP_SELF"]);

// Las 2 páginas de Deudas pintan el mismo botón "Deudas"
$paginas_deudas = ["por_cobrar.php", "por_pagar.php"];
?>
<!-- ENCABEZADO: se pone en todas las páginas con include("encabezado.php") -->
<!-- Usa los datos de configuracion.php -->

<?php if ($es_demo) { ?>
    <!-- BARRA DE DEMO: avisa que esto es una demostración de KORE System -->
    <div class="demo-barra">
        <div class="contenedor demo-contenido">
            <img src="imagenes/logo.png" alt="Logo de KORE System" class="logo-kore-mini">
            <p>
                <strong>DEMO</strong> de KORE System
                <span class="solo-pantalla-grande">· un sistema que se adapta a cualquier negocio</span>
            </p>
        </div>
    </div>
<?php } ?>

<header>
    <div class="contenedor encabezado">
        <!-- Logo del negocio (en la demo es un ícono de tijeras) -->
        <span class="logo-negocio"><?php echo icono("tijeras"); ?></span>
        <div>
            <h1>
                <?php echo $negocio; ?>
                <?php if ($es_demo) { ?>
                    <span class="etiqueta-demo">DEMO</span>
                <?php } ?>
            </h1>
            <p><?php echo $descripcion_negocio; ?></p>
        </div>
    </div>
    <!-- Franja de poste de barbería -->
    <div class="franja-barberia"></div>
</header>

<!-- MENÚ DE ABAJO (como en una app de celular): las 5 secciones del sistema -->
<nav class="menu">
    <div class="contenedor">
        <a href="index.php" class="<?php if ($pagina_actual == "index.php") echo "activo"; ?>">
            <span><?php echo icono("registrar"); ?></span>
            Registrar
        </a>
        <a href="servicios.php" class="<?php if ($pagina_actual == "servicios.php") echo "activo"; ?>">
            <span><?php echo icono("tijeras"); ?></span>
            Servicios
        </a>
        <a href="reportes.php" class="<?php if ($pagina_actual == "reportes.php") echo "activo"; ?>">
            <span><?php echo icono("grafica"); ?></span>
            Reportes
        </a>
        <a href="caja.php" class="<?php if ($pagina_actual == "caja.php") echo "activo"; ?>">
            <span><?php echo icono("cartera"); ?></span>
            Caja
        </a>
        <a href="por_cobrar.php" class="<?php if (in_array($pagina_actual, $paginas_deudas)) echo "activo"; ?>">
            <span><?php echo icono("monedas"); ?></span>
            Deudas
        </a>
    </div>
</nav>
