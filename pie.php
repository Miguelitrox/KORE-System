<!-- PIE DE PÁGINA: la firma de KORE System. Se pone al final del <main> con include("pie.php") -->
<footer class="pie">
    <p class="firma-kore">
        <img src="imagenes/logo.png" alt="Logo de KORE System">
        Hecho con <strong>KORE System</strong>
    </p>
    <?php if ($es_demo) { ?>
        <p class="chico gris">
            Esto es una DEMO: <?php echo $negocio; ?> es un negocio de ejemplo y los datos son de prueba.
            KORE System se adapta a lo que necesite cada negocio.
        </p>
    <?php } ?>
</footer>
