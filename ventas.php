<?php
// VENTAS > REGISTRAR VENTA
// Primero se elige si la venta es de contado o financiada y luego el producto del Stock.
include("conexion.php");
include("funciones.php");

// REGISTRAR LA VENTA
if (isset($_POST["registrar_venta"])) {
    $tipo = $_POST["tipo_venta"];        // "contado" o "financiada"
    $stock_id = (int) $_POST["stock_id"];
    $precio_venta = round((float) $_POST["precio_venta"], 2);
    $comentario = trim($_POST["comentario"]);

    // El pago inicial solo existe en las ventas financiadas
    $pago_inicial = 0;
    if ($tipo == "financiada") {
        $pago_inicial = round((float) $_POST["pago_inicial"], 2);
    }

    if ($tipo != "contado" && $tipo != "financiada") {
        header("Location: ventas.php");
        exit;
    }

    // Buscamos el artículo para saber cuánto costó y revisar que no esté vendido
    $sql = $conexion->prepare("SELECT * FROM stock WHERE id = ?");
    $sql->bind_param("i", $stock_id);
    $sql->execute();
    $articulo = $sql->get_result()->fetch_assoc();

    $error = "";
    if (!$articulo) {
        $error = "no_existe";
    } else if ($articulo["vendido"] == 1) {
        $error = "vendido";
    } else if ($precio_venta <= 0) {
        $error = "precio";
    } else if ($pago_inicial < 0 || $pago_inicial > $precio_venta) {
        $error = "inicial";
    }

    if ($error != "") {
        header("Location: ventas.php?tipo=" . $tipo . "&error=" . $error);
        exit;
    }

    // 1. El artículo se marca como vendido (ya no sale en Stock)
    $sql = $conexion->prepare("UPDATE stock SET vendido = 1 WHERE id = ?");
    $sql->bind_param("i", $stock_id);
    $sql->execute();

    // 2. Se guarda la venta
    $fecha_hora = date("Y-m-d H:i:s");
    $sql = $conexion->prepare("INSERT INTO ventas (stock_id, tipo_venta, precio_compra, precio_venta, comentario, fecha_hora) VALUES (?, ?, ?, ?, ?, ?)");
    $sql->bind_param("isddss", $stock_id, $tipo, $articulo["precio_compra"], $precio_venta, $comentario, $fecha_hora);
    $sql->execute();
    $venta_id = $conexion->insert_id;      // número que le tocó a la venta

    // Nombre que sale en el historial y en las deudas: "Bolsa · Marca"
    $nombre = $articulo["articulo"];
    if ($articulo["marca"] != "") {
        $nombre = $nombre . " · " . $articulo["marca"];
    }

    if ($tipo == "contado") {
        // 3. De contado: entra todo el dinero a mi saldo
        registrar_movimiento($conexion, "venta_contado", $precio_venta, "Venta de contado: " . $nombre);
    } else {
        // 3. Financiada: se crea la deuda por cobrar y solo entra el pago inicial
        $estado = "pendiente";
        if ($pago_inicial >= $precio_venta) {
            $estado = "pagada";
        }
        $fecha = date("Y-m-d");
        $sql = $conexion->prepare("INSERT INTO deudas_cobrar (venta_id, fecha, nombre, comentario, total, abonado, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $sql->bind_param("isssdds", $venta_id, $fecha, $nombre, $comentario, $precio_venta, $pago_inicial, $estado);
        $sql->execute();

        $detalle = "Venta financiada: " . $nombre;
        if ($comentario != "") {
            $detalle = $detalle . " (" . $comentario . ")";
        }
        registrar_movimiento($conexion, "venta_financiada", $pago_inicial, $detalle);
    }

    header("Location: ventas.php?aviso=venta");
    exit;
}

// Tipo de venta elegido (si todavía no se elige, se muestran las dos opciones)
$tipo = "";
if (isset($_GET["tipo"]) && ($_GET["tipo"] == "contado" || $_GET["tipo"] == "financiada")) {
    $tipo = $_GET["tipo"];
}

// Productos que se pueden vender (los que no se han vendido)
$resultado = $conexion->query("SELECT id, articulo, detalle, marca, precio_compra, precio_estimado FROM stock WHERE vendido = 0 ORDER BY articulo");
$productos = [];
while ($fila = $resultado->fetch_assoc()) {
    $productos[] = $fila;
}

$avisos = [
    "venta" => "Venta registrada.",
];
$errores = [
    "no_existe" => "El producto no existe.",
    "vendido" => "El producto ya fue vendido.",
    "precio" => "El precio de venta debe ser mayor a cero.",
    "inicial" => "El pago inicial no puede ser negativo ni mayor al precio de venta.",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#BD2E73">
    <title>Registrar venta - KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>
    <?php mostrar_aviso($avisos, $errores); ?>

    <main class="contenedor">
        <?php include("menu_ventas.php"); ?>

        <?php if ($tipo == "") { ?>
            <!-- PASO 1: elegir el tipo de venta -->
            <div class="dos-columnas">
                <a href="ventas.php?tipo=contado" class="tarjeta opcion">
                    <span class="circulo enorme"><?php echo icono("billete"); ?></span>
                    <div>
                        <p class="negrita">Venta de contado</p>
                        <p class="gris chico">El cliente paga el total al momento.</p>
                    </div>
                </a>
                <a href="ventas.php?tipo=financiada" class="tarjeta opcion">
                    <span class="circulo enorme amarillo"><?php echo icono("monedas"); ?></span>
                    <div>
                        <p class="negrita">Venta financiada</p>
                        <p class="gris chico">El cliente paga un inicial y abona después.</p>
                    </div>
                </a>
            </div>
        <?php } else { ?>
            <!-- PASO 2: elegir el producto y el precio -->
            <a href="ventas.php" class="boton boton-fantasma">
                <?php echo icono("regresar"); ?> Cambiar tipo de venta
            </a>

            <div class="tarjeta">
                <h3 class="tarjeta-titulo">
                    <?php if ($tipo == "contado") { ?>
                        <span class="circulo"><?php echo icono("billete"); ?></span>
                        Venta de contado
                    <?php } else { ?>
                        <span class="circulo amarillo"><?php echo icono("monedas"); ?></span>
                        Venta financiada
                    <?php } ?>
                </h3>

                <form method="post" onsubmit="return confirmarVenta()">
                    <input type="hidden" name="tipo_venta" value="<?php echo $tipo; ?>">

                    <label for="producto">Producto</label>
                    <select id="producto" name="stock_id" required onchange="elegirProducto()">
                        <option value="">Selecciona un producto del Stock...</option>
                        <?php foreach ($productos as $producto) { ?>
                            <option value="<?php echo $producto["id"]; ?>">
                                <?php
                                echo htmlspecialchars($producto["articulo"]);
                                if ($producto["marca"] != "") {
                                    echo " · " . htmlspecialchars($producto["marca"]);
                                }
                                echo " — " . dinero($producto["precio_estimado"]);
                                ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php if (count($productos) == 0) { ?>
                        <p class="gris chico" style="margin-top: 8px;">No hay productos disponibles en Stock.</p>
                    <?php } ?>

                    <!-- Se muestra cuando se elige un producto -->
                    <div class="caja-producto" id="caja-producto" hidden>
                        <div class="renglon">
                            <div>
                                <p class="negrita cortar" id="producto-nombre"></p>
                                <p class="gris chico cortar" id="producto-detalle"></p>
                            </div>
                            <span class="etiqueta"><?php echo icono("caja"); ?> 1 disponible</span>
                        </div>

                        <div class="dos-campos">
                            <div class="mini">
                                <p>Costo de compra</p>
                                <p class="negrita" id="producto-compra"></p>
                            </div>
                            <div class="mini">
                                <p>Precio sugerido</p>
                                <p class="negrita" id="producto-sugerido"></p>
                            </div>
                        </div>

                        <label for="precio-venta">Precio final de venta</label>
                        <div class="campo-dinero">
                            <span>$</span>
                            <input type="number" id="precio-venta" name="precio_venta" step="0.01" min="0" inputmode="decimal" required>
                        </div>

                        <?php if ($tipo == "financiada") { ?>
                            <label for="pago-inicial">Pago inicial</label>
                            <div class="campo-dinero">
                                <span>$</span>
                                <input type="number" id="pago-inicial" name="pago_inicial" step="0.01" min="0" placeholder="0.00" inputmode="decimal">
                            </div>
                        <?php } ?>

                        <label for="comentario">Comentario (opcional)</label>
                        <?php if ($tipo == "contado") { ?>
                            <input type="text" id="comentario" name="comentario" maxlength="255" placeholder="Ej. Venta por Facebook" autocomplete="off">
                        <?php } else { ?>
                            <input type="text" id="comentario" name="comentario" maxlength="255" placeholder="Ej. Juan, pendiente de liquidar" autocomplete="off">
                        <?php } ?>

                        <p class="texto-error" id="error-venta" hidden></p>

                        <br><br>
                        <button type="submit" name="registrar_venta" class="boton boton-ancho">Registrar venta</button>
                    </div>
                </form>
            </div>
        <?php } ?>
    </main>

    <script src="funciones.js"></script>
    <script>
        // Los productos que PHP sacó del Stock
        let productos = <?php echo json_encode($productos); ?>;
        let tipoVenta = "<?php echo $tipo; ?>";
        let productoElegido = null;

        // Cuando se elige un producto se muestran sus datos y se llena el precio con el sugerido
        function elegirProducto() {
            let id = document.getElementById("producto").value;
            productoElegido = buscarPorId(productos, id);

            if (productoElegido == null) {
                document.getElementById("caja-producto").hidden = true;
                return;
            }

            let detalle = productoElegido.marca;
            if (detalle == "") {
                detalle = "Sin marca";
            }
            if (productoElegido.detalle != "") {
                detalle = detalle + " · " + productoElegido.detalle;
            }

            document.getElementById("producto-nombre").textContent = productoElegido.articulo;
            document.getElementById("producto-detalle").textContent = detalle;
            document.getElementById("producto-compra").textContent = dinero(productoElegido.precio_compra);
            document.getElementById("producto-sugerido").textContent = dinero(productoElegido.precio_estimado);

            // El precio de venta empieza con el precio sugerido, pero se puede cambiar
            if (Number(productoElegido.precio_estimado) > 0) {
                document.getElementById("precio-venta").value = productoElegido.precio_estimado;
            } else {
                document.getElementById("precio-venta").value = "";
            }

            document.getElementById("error-venta").hidden = true;
            document.getElementById("caja-producto").hidden = false;
        }

        // Revisa los datos y pregunta antes de registrar la venta
        function confirmarVenta() {
            let precio = parseFloat(document.getElementById("precio-venta").value);
            let error = document.getElementById("error-venta");

            if (isNaN(precio) || precio <= 0) {
                error.textContent = "El precio de venta debe ser mayor a cero.";
                error.hidden = false;
                return false;
            }

            if (tipoVenta == "financiada") {
                let inicial = parseFloat(document.getElementById("pago-inicial").value);
                if (isNaN(inicial)) {
                    inicial = 0;
                }
                if (inicial < 0 || inicial > precio) {
                    error.textContent = "El pago inicial no puede ser negativo ni mayor al precio de venta.";
                    error.hidden = false;
                    return false;
                }
            }

            return confirm("¿Confirmar esta venta?\n\nSe registrará la venta de \"" + productoElegido.articulo + "\" por " + dinero(precio) + ". Esta acción cambiará el estado del artículo en Stock y no se puede deshacer.");
        }
    </script>
</body>
</html>
