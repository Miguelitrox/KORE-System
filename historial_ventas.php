<?php
// VENTAS > HISTORIAL: todas las ventas de los últimos días que se elijan
include("conexion.php");
include("funciones.php");

$rangos = [
    7 => "Última semana",
    14 => "Últimas 2 semanas",
    21 => "Últimas 3 semanas",
    30 => "Último mes",
    90 => "Últimos 3 meses",
    180 => "Últimos 6 meses",
    365 => "Último año",
];

$dias = 7;
if (isset($_GET["dias"]) && isset($rangos[$_GET["dias"]])) {
    $dias = (int) $_GET["dias"];
}
$desde = date("Y-m-d H:i:s", strtotime("-" . $dias . " days"));

// Cada venta con el nombre del artículo y, si fue financiada, su deuda
// (LEFT JOIN = trae la venta aunque no tenga deuda)
$sql = $conexion->prepare("
    SELECT ventas.*, stock.articulo, stock.marca,
           deudas_cobrar.total, deudas_cobrar.abonado, deudas_cobrar.estado
    FROM ventas
    LEFT JOIN stock ON stock.id = ventas.stock_id
    LEFT JOIN deudas_cobrar ON deudas_cobrar.venta_id = ventas.id
    WHERE ventas.fecha_hora >= ?
    ORDER BY ventas.fecha_hora DESC, ventas.id DESC
");
$sql->bind_param("s", $desde);
$sql->execute();
$ventas = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#BD2E73">
    <title>Historial de ventas - KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>

    <main class="contenedor">
        <?php include("menu_ventas.php"); ?>

        <div class="tarjeta">
            <div class="tarjeta-encabezado">
                <h3 class="tarjeta-titulo">
                    <span class="circulo gris"><?php echo icono("historial"); ?></span>
                    Historial de Ventas
                </h3>
                <form method="get">
                    <select name="dias" class="select-chico" onchange="this.form.submit()">
                        <?php foreach ($rangos as $numero => $texto) { ?>
                            <option value="<?php echo $numero; ?>" <?php if ($numero == $dias) echo "selected"; ?>><?php echo $texto; ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>

            <?php if ($ventas->num_rows == 0) { ?>
                <p class="vacio">No hay ventas en este periodo.</p>
            <?php } else { ?>
                <ul class="lista">
                    <?php while ($venta = $ventas->fetch_assoc()) { ?>
                        <?php
                        $ganancia = $venta["precio_venta"] - $venta["precio_compra"];
                        $financiada = ($venta["tipo_venta"] == "financiada");
                        ?>
                        <li class="<?php if ($financiada) { echo "fondo-amarillo"; } else { echo "fondo-verde"; } ?>">
                            <div class="renglon">
                                <div>
                                    <p class="lista-titulo">
                                        <?php
                                        echo htmlspecialchars($venta["articulo"]);
                                        if ($venta["marca"] != "") {
                                            echo " · " . htmlspecialchars($venta["marca"]);
                                        }
                                        ?>
                                    </p>
                                    <?php if ($venta["comentario"] != "") { ?>
                                        <p class="gris chico cortar"><?php echo htmlspecialchars($venta["comentario"]); ?></p>
                                    <?php } ?>
                                </div>
                                <div class="etiquetas">
                                    <?php if ($financiada) { ?>
                                        <span class="etiqueta amarillo">Financiada</span>
                                        <?php if ($venta["estado"] == "pagada") { ?>
                                            <span class="etiqueta pagada">Pagada</span>
                                        <?php } else { ?>
                                            <span class="etiqueta pendiente">Pendiente</span>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span class="etiqueta verde">Contado</span>
                                        <span class="etiqueta pagada">Completada</span>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="renglon" style="align-items: flex-end; margin-top: 6px;">
                                <p class="lista-fecha"><?php echo fecha_hora($venta["fecha_hora"]); ?></p>
                                <div class="derecha">
                                    <p class="cantidad"><?php echo dinero($venta["precio_venta"]); ?></p>
                                    <p class="chico negrita <?php if ($ganancia >= 0) { echo "texto-verde"; } else { echo "texto-rosa"; } ?>">
                                        Ganancia: <?php echo dinero($ganancia); ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($financiada && $venta["total"] != null) { ?>
                                <div class="tres-campos detalle-deuda">
                                    <div>
                                        <p class="gris">Total</p>
                                        <p class="negrita"><?php echo dinero($venta["total"]); ?></p>
                                    </div>
                                    <div>
                                        <p class="gris">Abonado</p>
                                        <p class="negrita texto-verde"><?php echo dinero($venta["abonado"]); ?></p>
                                    </div>
                                    <div>
                                        <p class="gris">Pendiente</p>
                                        <p class="negrita texto-amarillo"><?php echo dinero($venta["total"] - $venta["abonado"]); ?></p>
                                    </div>
                                </div>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </main>

    <script src="funciones.js"></script>
</body>
</html>
