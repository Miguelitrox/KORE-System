<?php
// PÁGINA DE STOCK: los artículos que tengo disponibles para vender.
// Cuando un artículo se vende (en Ventas) ya no aparece aquí, se va al Historial de ventas.
include("conexion.php");
include("funciones.php");

// GUARDAR ARTÍCULO: si el id es 0 es uno nuevo, si no, se edita el que ya existe
if (isset($_POST["guardar_articulo"])) {
    $id = (int) $_POST["id"];
    $fecha = $_POST["fecha"];
    $articulo = trim($_POST["articulo"]);
    $detalle = trim($_POST["detalle"]);
    $marca = trim($_POST["marca"]);
    $precio_compra = (float) $_POST["precio_compra"];
    $precio_estimado = (float) $_POST["precio_estimado"];

    if ($articulo == "") {
        header("Location: stock.php?error=vacio");
        exit;
    }
    if ($fecha == "") {
        $fecha = date("Y-m-d");
    }

    // FOTO (opcional): el archivo se guarda en la carpeta "fotos" y en la base de datos solo su nombre
    // error 4 = no se eligió ninguna foto
    $foto = "";
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] != 4) {
        $extension = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));

        if ($_FILES["foto"]["error"] != 0 || !in_array($extension, ["jpg", "jpeg", "png", "webp"])) {
            header("Location: stock.php?error=foto");
            exit;
        }

        if (!is_dir("fotos")) {
            mkdir("fotos");
        }
        $foto = "fotos/" . time() . "_" . rand(1000, 9999) . "." . $extension;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $foto);
    }

    if ($id == 0) {
        $sql = $conexion->prepare("INSERT INTO stock (fecha, articulo, detalle, marca, precio_compra, precio_estimado, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $sql->bind_param("ssssdds", $fecha, $articulo, $detalle, $marca, $precio_compra, $precio_estimado, $foto);
        $sql->execute();
        $aviso = "guardado";
    } else {
        // Solo se pueden editar artículos que no se han vendido
        $sql = $conexion->prepare("UPDATE stock SET fecha = ?, articulo = ?, detalle = ?, marca = ?, precio_compra = ?, precio_estimado = ? WHERE id = ? AND vendido = 0");
        $sql->bind_param("ssssddi", $fecha, $articulo, $detalle, $marca, $precio_compra, $precio_estimado, $id);
        $sql->execute();

        // La foto solo se cambia si se eligió una nueva
        if ($foto != "") {
            $sql = $conexion->prepare("UPDATE stock SET foto = ? WHERE id = ? AND vendido = 0");
            $sql->bind_param("si", $foto, $id);
            $sql->execute();
        }
        $aviso = "actualizado";
    }

    header("Location: stock.php?aviso=" . $aviso);
    exit;
}

// BORRAR ARTÍCULO (solo si no se ha vendido)
if (isset($_POST["borrar_articulo"])) {
    $id = (int) $_POST["id"];

    $sql = $conexion->prepare("DELETE FROM stock WHERE id = ? AND vendido = 0");
    $sql->bind_param("i", $id);
    $sql->execute();

    header("Location: stock.php?aviso=borrado");
    exit;
}

// LEER los artículos disponibles y sacar los totales
$resultado = $conexion->query("SELECT * FROM stock WHERE vendido = 0 ORDER BY id");
$articulos = [];
$total_compras = 0;
$total_estimado = 0;
while ($fila = $resultado->fetch_assoc()) {
    $articulos[] = $fila;
    $total_compras = $total_compras + $fila["precio_compra"];
    $total_estimado = $total_estimado + $fila["precio_estimado"];
}
$total_ganancia = $total_estimado - $total_compras;

$avisos = [
    "guardado" => "Artículo registrado.",
    "actualizado" => "Artículo actualizado.",
    "borrado" => "Artículo eliminado.",
];
$errores = [
    "vacio" => "Escribe el nombre del artículo.",
    "foto" => "No se pudo subir la foto. Usa una imagen JPG, PNG o WEBP de menos de 40 MB.",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#BD2E73">
    <title>Stock - KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>
    <?php mostrar_aviso($avisos, $errores); ?>

    <main class="contenedor">
        <div class="titulo-seccion">
            <div class="titulo-con-icono">
                <span class="circulo grande"><?php echo icono("caja"); ?></span>
                <div>
                    <h2>Stock</h2>
                    <p class="gris chico">
                        <?php echo count($articulos); ?>
                        <?php if (count($articulos) == 1) { echo "artículo disponible"; } else { echo "artículos disponibles"; } ?>
                    </p>
                </div>
            </div>
            <button type="button" class="boton" onclick="nuevoArticulo()">
                <?php echo icono("mas"); ?> Agregar
            </button>
        </div>

        <!-- RESUMEN -->
        <div class="resumen">
            <div class="tarjeta chica">
                <p class="gris chico">Compras</p>
                <p class="numero"><?php echo dinero($total_compras); ?></p>
            </div>
            <div class="tarjeta chica">
                <p class="gris chico">Ganancia est.</p>
                <p class="numero <?php if ($total_ganancia >= 0) { echo "texto-verde"; } else { echo "texto-rosa"; } ?>"><?php echo dinero($total_ganancia); ?></p>
            </div>
        </div>

        <!-- VISTA CELULAR: una tarjeta por artículo -->
        <div class="solo-celular">
            <?php if (count($articulos) == 0) { ?>
                <div class="tarjeta vacio">
                    No hay artículos. Toca "Agregar" para empezar.
                </div>
            <?php } ?>

            <?php foreach ($articulos as $fila) { ?>
                <?php $ganancia = $fila["precio_estimado"] - $fila["precio_compra"]; ?>
                <div class="tarjeta articulo">
                    <button type="button" class="boton-icono boton-editar" title="Editar artículo" onclick="editarArticulo(<?php echo $fila["id"]; ?>)">
                        <?php echo icono("lapiz"); ?>
                    </button>

                    <div class="articulo-foto">
                        <?php if ($fila["foto"] != "") { ?>
                            <img src="<?php echo htmlspecialchars($fila["foto"]); ?>" alt="Foto de <?php echo htmlspecialchars($fila["articulo"]); ?>">
                        <?php } else { ?>
                            <span class="gris chico">Sin foto</span>
                        <?php } ?>
                    </div>

                    <div class="renglon">
                        <div>
                            <p class="negrita cortar"><?php echo htmlspecialchars($fila["articulo"]); ?></p>
                            <p class="chico cortar"><?php echo htmlspecialchars($fila["detalle"]); ?></p>
                        </div>
                        <form method="post" class="en-linea" data-nombre="<?php echo htmlspecialchars($fila["articulo"]); ?>" onsubmit="return confirmarBorrado(this, 'este artículo')">
                            <input type="hidden" name="id" value="<?php echo $fila["id"]; ?>">
                            <button type="submit" name="borrar_articulo" class="boton-icono rosa" title="Eliminar artículo">
                                <?php echo icono("basura"); ?>
                            </button>
                        </form>
                    </div>

                    <div class="datos">
                        <div>
                            <p class="nombre-dato">Fecha</p>
                            <p class="dato"><?php echo fecha_corta($fila["fecha"]); ?></p>
                        </div>
                        <div>
                            <p class="nombre-dato">Marca</p>
                            <p class="dato"><?php echo htmlspecialchars($fila["marca"]); ?></p>
                        </div>
                        <div>
                            <p class="nombre-dato">Compra</p>
                            <p class="dato derecha"><?php echo dinero($fila["precio_compra"]); ?></p>
                        </div>
                        <div>
                            <p class="nombre-dato">V. Estimada</p>
                            <p class="dato derecha"><?php echo dinero($fila["precio_estimado"]); ?></p>
                        </div>
                    </div>

                    <div class="renglon-gris">
                        <span>Ganancia est.</span>
                        <strong class="<?php if ($ganancia >= 0) { echo "texto-verde"; } else { echo "texto-rosa"; } ?>"><?php echo dinero($ganancia); ?></strong>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- VISTA COMPUTADORA: tabla -->
        <div class="solo-computadora tabla-caja">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Artículo</th>
                        <th>Detalle</th>
                        <th>Marca</th>
                        <th class="derecha">Compra</th>
                        <th class="derecha">V. Estimada</th>
                        <th class="derecha">Gan. Est.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($articulos) == 0) { ?>
                        <tr>
                            <td colspan="8" class="vacio">No hay artículos registrados.</td>
                        </tr>
                    <?php } ?>

                    <?php foreach ($articulos as $fila) { ?>
                        <?php $ganancia = $fila["precio_estimado"] - $fila["precio_compra"]; ?>
                        <tr>
                            <td><?php echo fecha_corta($fila["fecha"]); ?></td>
                            <td>
                                <div class="celda-flex">
                                    <?php if ($fila["foto"] != "") { ?>
                                        <img src="<?php echo htmlspecialchars($fila["foto"]); ?>" alt="" class="miniatura">
                                    <?php } ?>
                                    <button type="button" class="boton-icono" title="Editar artículo" onclick="editarArticulo(<?php echo $fila["id"]; ?>)">
                                        <?php echo icono("lapiz"); ?>
                                    </button>
                                    <span class="cortar"><?php echo htmlspecialchars($fila["articulo"]); ?></span>
                                </div>
                            </td>
                            <td class="cortar"><?php echo htmlspecialchars($fila["detalle"]); ?></td>
                            <td class="cortar"><?php echo htmlspecialchars($fila["marca"]); ?></td>
                            <td class="derecha"><?php echo dinero($fila["precio_compra"]); ?></td>
                            <td class="derecha"><?php echo dinero($fila["precio_estimado"]); ?></td>
                            <td class="derecha negrita <?php if ($ganancia >= 0) { echo "texto-verde"; } else { echo "texto-rosa"; } ?>"><?php echo dinero($ganancia); ?></td>
                            <td>
                                <form method="post" class="en-linea" data-nombre="<?php echo htmlspecialchars($fila["articulo"]); ?>" onsubmit="return confirmarBorrado(this, 'este artículo')">
                                    <input type="hidden" name="id" value="<?php echo $fila["id"]; ?>">
                                    <button type="submit" name="borrar_articulo" class="boton-icono rosa" title="Eliminar artículo">
                                        <?php echo icono("basura"); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
                <?php if (count($articulos) > 0) { ?>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="derecha">TOTALES:</td>
                            <td class="derecha"><?php echo dinero($total_compras); ?></td>
                            <td class="derecha"><?php echo dinero($total_estimado); ?></td>
                            <td class="derecha <?php if ($total_ganancia >= 0) { echo "texto-verde"; } else { echo "texto-rosa"; } ?>"><?php echo dinero($total_ganancia); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php } ?>
            </table>
        </div>
    </main>

    <!-- CUADRO PARA AGREGAR O EDITAR UN ARTÍCULO -->
    <!-- enctype="multipart/form-data" es necesario para poder mandar la foto -->
    <dialog id="dialogo-articulo">
        <form method="post" enctype="multipart/form-data" class="dialogo-contenido">
            <button type="button" class="cerrar" onclick="cerrarDialogo('dialogo-articulo')">&times;</button>
            <h3 id="articulo-titulo">Nuevo artículo</h3>
            <p class="descripcion" id="articulo-descripcion">Registra un artículo en tu inventario.</p>

            <input type="hidden" name="id" id="articulo-id" value="0">

            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" required>

            <label for="articulo">Artículo</label>
            <input type="text" id="articulo" name="articulo" placeholder="Nombre del artículo" maxlength="150" required autocomplete="off">

            <label for="detalle">Detalle</label>
            <input type="text" id="detalle" name="detalle" placeholder="Descripción / talla / color" maxlength="255" autocomplete="off">

            <label>Foto (opcional)</label>
            <label for="foto" class="elegir-foto">
                <img id="vista-previa" src="" alt="Vista previa del artículo" hidden>
                <span class="foto-vacia" id="foto-vacia"><?php echo icono("foto"); ?></span>
                <span class="gris chico">Toca para elegir una imagen</span>
            </label>
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" hidden onchange="verFoto(this)">

            <label for="marca">Marca</label>
            <input type="text" id="marca" name="marca" placeholder="Marca del artículo" maxlength="100" autocomplete="off">

            <div class="dos-campos">
                <div>
                    <label for="precio-compra">Compra</label>
                    <input type="number" id="precio-compra" name="precio_compra" step="0.01" min="0" placeholder="0.00" inputmode="decimal">
                </div>
                <div>
                    <label for="precio-estimado">V. Estimada</label>
                    <input type="number" id="precio-estimado" name="precio_estimado" step="0.01" min="0" placeholder="0.00" inputmode="decimal">
                </div>
            </div>

            <div class="dialogo-botones">
                <button type="submit" name="guardar_articulo" class="boton boton-ancho" id="articulo-boton">Guardar artículo</button>
            </div>
        </form>
    </dialog>

    <script src="funciones.js"></script>
    <script>
        // Los artículos que PHP sacó de la base de datos, para llenar el cuadro al editar
        let articulos = <?php echo json_encode($articulos); ?>;
        let hoy = "<?php echo date("Y-m-d"); ?>";

        // Abre el cuadro vacío para registrar un artículo nuevo
        function nuevoArticulo() {
            document.getElementById("articulo-id").value = 0;
            document.getElementById("fecha").value = hoy;
            document.getElementById("articulo").value = "";
            document.getElementById("detalle").value = "";
            document.getElementById("marca").value = "";
            document.getElementById("precio-compra").value = "";
            document.getElementById("precio-estimado").value = "";
            document.getElementById("foto").value = "";
            mostrarFoto("");

            document.getElementById("articulo-titulo").textContent = "Nuevo artículo";
            document.getElementById("articulo-descripcion").textContent = "Registra un artículo en tu inventario.";
            document.getElementById("articulo-boton").textContent = "Guardar artículo";
            abrirDialogo("dialogo-articulo");
        }

        // Abre el cuadro con los datos del artículo para cambiarlos
        function editarArticulo(id) {
            let articulo = buscarPorId(articulos, id);

            document.getElementById("articulo-id").value = articulo.id;
            document.getElementById("fecha").value = articulo.fecha;
            document.getElementById("articulo").value = articulo.articulo;
            document.getElementById("detalle").value = articulo.detalle;
            document.getElementById("marca").value = articulo.marca;
            document.getElementById("precio-compra").value = articulo.precio_compra;
            document.getElementById("precio-estimado").value = articulo.precio_estimado;
            document.getElementById("foto").value = "";
            mostrarFoto(articulo.foto);

            document.getElementById("articulo-titulo").textContent = "Editar artículo";
            document.getElementById("articulo-descripcion").textContent = "Modifica los datos o cambia la foto del artículo.";
            document.getElementById("articulo-boton").textContent = "Guardar cambios";
            abrirDialogo("dialogo-articulo");
        }

        // Cuando se elige una foto, se muestra en el cuadrito antes de guardarla
        function verFoto(campo) {
            if (campo.files.length > 0) {
                mostrarFoto(URL.createObjectURL(campo.files[0]));
            }
        }

        function mostrarFoto(direccion) {
            let imagen = document.getElementById("vista-previa");
            let vacia = document.getElementById("foto-vacia");

            if (direccion == "") {
                imagen.hidden = true;
                vacia.hidden = false;
            } else {
                imagen.src = direccion;
                imagen.hidden = false;
                vacia.hidden = true;
            }
        }
    </script>
</body>
</html>
