<?php
// PÁGINA DE SERVICIOS: la lista de servicios que ofrece el negocio con su precio.
// Son las opciones que salen al registrar en la página principal.
include("conexion.php");
include("configuracion.php");
include("funciones.php");

// GUARDAR SERVICIO: si el id es 0 es uno nuevo, si no, se edita el que ya existe
if (isset($_POST["guardar_servicio"])) {
    $id = (int) $_POST["id"];
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $duracion = (int) $_POST["duracion"];
    $precio = round((float) $_POST["precio"], 2);

    if ($nombre == "") {
        header("Location: servicios.php?error=vacio");
        exit;
    }
    if ($precio <= 0) {
        header("Location: servicios.php?error=precio");
        exit;
    }
    if ($duracion < 0) {
        $duracion = 0;
    }

    if ($id == 0) {
        $sql = $conexion->prepare("INSERT INTO servicios (nombre, descripcion, duracion, precio) VALUES (?, ?, ?, ?)");
        $sql->bind_param("ssid", $nombre, $descripcion, $duracion, $precio);
        $sql->execute();
        $aviso = "guardado";
    } else {
        $sql = $conexion->prepare("UPDATE servicios SET nombre = ?, descripcion = ?, duracion = ?, precio = ? WHERE id = ?");
        $sql->bind_param("ssidi", $nombre, $descripcion, $duracion, $precio, $id);
        $sql->execute();
        $aviso = "actualizado";
    }

    header("Location: servicios.php?aviso=" . $aviso);
    exit;
}

// BORRAR SERVICIO
// Los registros que ya se hicieron no se pierden: guardan su propio nombre y precio.
if (isset($_POST["borrar_servicio"])) {
    $id = (int) $_POST["id"];

    $sql = $conexion->prepare("DELETE FROM servicios WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();

    header("Location: servicios.php?aviso=borrado");
    exit;
}

// LEER los servicios
$resultado = $conexion->query("SELECT * FROM servicios ORDER BY nombre");
$servicios = [];
while ($fila = $resultado->fetch_assoc()) {
    $servicios[] = $fila;
}

$avisos = [
    "guardado" => "Servicio agregado.",
    "actualizado" => "Servicio actualizado.",
    "borrado" => "Servicio eliminado.",
];
$errores = [
    "vacio" => "Escribe el nombre del servicio.",
    "precio" => "El precio debe ser mayor a cero.",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1C1C1E">
    <title>Servicios - <?php echo $negocio; ?> | KORE System</title>
    <link rel="icon" href="imagenes/logo.png">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <?php include("encabezado.php"); ?>
    <?php mostrar_aviso($avisos, $errores); ?>

    <main class="contenedor">
        <div class="titulo-seccion">
            <div class="titulo-con-icono">
                <span class="circulo grande"><?php echo icono("tijeras"); ?></span>
                <div>
                    <h2>Servicios</h2>
                    <p class="gris chico">
                        <?php echo count($servicios); ?>
                        <?php if (count($servicios) == 1) { echo "servicio"; } else { echo "servicios"; } ?>
                        · son las opciones que salen al registrar
                    </p>
                </div>
            </div>
            <button type="button" class="boton" onclick="nuevoServicio()">
                <?php echo icono("mas"); ?> Agregar
            </button>
        </div>

        <?php if (count($servicios) == 0) { ?>
            <div class="tarjeta vacio">No hay servicios. Toca "Agregar" para empezar.</div>
        <?php } ?>

        <!-- UNA TARJETA POR SERVICIO (1, 2 o 3 columnas según la pantalla) -->
        <div class="cuadricula">
            <?php foreach ($servicios as $fila) { ?>
                <div class="tarjeta ficha">
                    <div class="renglon">
                        <div class="titulo-con-icono">
                            <span class="circulo"><?php echo icono("tijeras"); ?></span>
                            <div>
                                <p class="negrita cortar"><?php echo htmlspecialchars($fila["nombre"]); ?></p>
                                <p class="gris chico cortar"><?php echo htmlspecialchars($fila["descripcion"]); ?></p>
                            </div>
                        </div>
                        <div class="celda-flex">
                            <button type="button" class="boton-icono" title="Editar servicio" onclick="editarServicio(<?php echo $fila["id"]; ?>)">
                                <?php echo icono("lapiz"); ?>
                            </button>
                            <form method="post" class="en-linea" data-nombre="<?php echo htmlspecialchars($fila["nombre"]); ?>" onsubmit="return confirmarBorrado(this, 'este servicio')">
                                <input type="hidden" name="id" value="<?php echo $fila["id"]; ?>">
                                <button type="submit" name="borrar_servicio" class="boton-icono rojo" title="Eliminar servicio">
                                    <?php echo icono("basura"); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="renglon-gris">
                        <span class="con-icono gris"><?php echo icono("reloj"); ?> <?php echo $fila["duracion"]; ?> min</span>
                        <strong class="precio"><?php echo dinero($fila["precio"]); ?></strong>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php include("pie.php"); ?>
    </main>

    <!-- CUADRO PARA AGREGAR O EDITAR UN SERVICIO -->
    <dialog id="dialogo-servicio">
        <form method="post" class="dialogo-contenido">
            <h3 id="servicio-titulo">Nuevo servicio</h3>
            <p class="descripcion" id="servicio-descripcion">Agrega un servicio para poder elegirlo al registrar.</p>

            <input type="hidden" name="id" id="servicio-id" value="0">

            <label for="nombre">Nombre del servicio</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Corte de cabello" maxlength="100" required autocomplete="off">

            <label for="descripcion">Descripción (opcional)</label>
            <input type="text" id="descripcion" name="descripcion" placeholder="Ej. Corte clásico con máquina y tijera" maxlength="255" autocomplete="off">

            <div class="dos-campos">
                <div>
                    <label for="duracion">Duración (min)</label>
                    <input type="number" id="duracion" name="duracion" step="1" min="0" placeholder="30" inputmode="numeric">
                </div>
                <div>
                    <label for="precio">Precio</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0.01" placeholder="0.00" inputmode="decimal" required>
                </div>
            </div>

            <div class="dialogo-botones">
                <button type="submit" name="guardar_servicio" class="boton boton-ancho" id="servicio-boton">Guardar servicio</button>
            </div>

            <button type="button" class="cerrar" onclick="cerrarDialogo('dialogo-servicio')">&times;</button>
        </form>
    </dialog>

    <script src="funciones.js"></script>
    <script>
        // Los servicios que PHP sacó de la base de datos, para llenar el cuadro al editar
        let servicios = <?php echo json_encode($servicios); ?>;

        // Abre el cuadro vacío para agregar un servicio nuevo
        function nuevoServicio() {
            document.getElementById("servicio-id").value = 0;
            document.getElementById("nombre").value = "";
            document.getElementById("descripcion").value = "";
            document.getElementById("duracion").value = 30;
            document.getElementById("precio").value = "";

            document.getElementById("servicio-titulo").textContent = "Nuevo servicio";
            document.getElementById("servicio-descripcion").textContent = "Agrega un servicio para poder elegirlo al registrar.";
            document.getElementById("servicio-boton").textContent = "Guardar servicio";
            abrirDialogo("dialogo-servicio");
        }

        // Abre el cuadro con los datos del servicio para cambiarlos
        function editarServicio(id) {
            let servicio = buscarPorId(servicios, id);

            document.getElementById("servicio-id").value = servicio.id;
            document.getElementById("nombre").value = servicio.nombre;
            document.getElementById("descripcion").value = servicio.descripcion;
            document.getElementById("duracion").value = servicio.duracion;
            document.getElementById("precio").value = servicio.precio;

            document.getElementById("servicio-titulo").textContent = "Editar servicio";
            document.getElementById("servicio-descripcion").textContent = "Cambia el nombre, la duración o el precio. Los registros que ya se hicieron no cambian.";
            document.getElementById("servicio-boton").textContent = "Guardar cambios";
            abrirDialogo("dialogo-servicio");
        }
    </script>
</body>
</html>
