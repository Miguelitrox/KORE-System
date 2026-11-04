// FUNCIONES DE JAVASCRIPT QUE SE USAN EN TODAS LAS PÁGINAS
// Se pone al final de cada página con: <script src="funciones.js"></script>

// ---------- CUADROS DE DIÁLOGO (<dialog>) ----------

function abrirDialogo(id) {
    document.getElementById(id).showModal();
}

function cerrarDialogo(id) {
    document.getElementById(id).close();
}

// Si se da clic afuera del cuadro (en la parte oscura), también se cierra
let dialogos = document.querySelectorAll("dialog");
for (let i = 0; i < dialogos.length; i++) {
    dialogos[i].addEventListener("click", function (evento) {
        if (evento.target == dialogos[i]) {
            dialogos[i].close();
        }
    });
}

// Muestra un error dentro de un cuadro de diálogo (texto vacío = lo esconde)
function mostrarError(id, texto) {
    let error = document.getElementById(id);
    error.textContent = texto;
    error.hidden = (texto == "");
}

// ---------- PEDIR Y MANDAR DATOS AL SERVIDOR (carpeta api) ----------

// Sin datos solo lee (GET). Con datos (un FormData) manda la información (POST).
// Siempre regresa un objeto con "ok": true o "ok": false y "error".
async function pedir(direccion, datos) {
    let opciones = {};
    if (datos) {
        opciones = { method: "POST", body: datos };
    }

    try {
        let respuesta = await fetch(direccion, opciones);
        return await respuesta.json();
    } catch (error) {
        return { ok: false, error: "No se pudo conectar con el servidor. Revisa que Apache y MySQL estén encendidos en XAMPP." };
    }
}

// ---------- AVISO FLOTANTE ("Servicio agregado", "Deuda eliminada"...) ----------

let temporizadorAviso = null;

// esError = true lo pinta de rojo. Se esconde solo después de 3 segundos.
function mostrarAviso(texto, esError) {
    let aviso = document.getElementById("aviso");
    if (!aviso) {
        aviso = document.createElement("p");
        aviso.id = "aviso";
        document.body.appendChild(aviso);
    }

    aviso.textContent = texto;
    aviso.className = "aviso";
    if (esError) {
        aviso.className = "aviso error";
    }
    aviso.hidden = false;

    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(function () {
        aviso.hidden = true;
    }, 3000);
}

// Muestra el mensaje que regresó el servidor: verde si salió bien, rojo si hubo error
function avisoDeRespuesta(respuesta) {
    if (respuesta.ok) {
        mostrarAviso(respuesta.mensaje);
    } else {
        mostrarAviso(respuesta.error, true);
    }
}

// ---------- TEXTOS, FECHAS Y DINERO ----------

// Muestra una cantidad como dinero: 1500 → $1,500.00
function dinero(cantidad) {
    return "$" + Number(cantidad).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// "1 servicio" o "3 servicios"
function plural(cantidad, uno, varios) {
    if (cantidad == 1) {
        return cantidad + " " + uno;
    }
    return cantidad + " " + varios;
}

const MESES_CORTOS = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];

// "2026-10-30 15:45:00" → "30 oct 2026, 15:45"
function fechaHora(texto) {
    let partes = texto.split(" ");
    let fecha = partes[0].split("-");
    return fecha[2] + " " + MESES_CORTOS[Number(fecha[1]) - 1] + " " + fecha[0] + ", " + partes[1].substring(0, 5);
}

// "2026-10-30 15:45:00" → "15:45"
function soloHora(texto) {
    return texto.split(" ")[1].substring(0, 5);
}

// "2026-10-30" → "30/10/2026"
function fechaCorta(texto) {
    let fecha = texto.split("-");
    return fecha[2] + "/" + fecha[1] + "/" + fecha[0];
}

// Una fecha de JavaScript como texto "2026-11-04" (con la fecha de la computadora)
// (no se usa toISOString porque da la fecha de Londres y en la noche sale el día siguiente)
function fechaTexto(fecha) {
    let mes = String(fecha.getMonth() + 1).padStart(2, "0");
    let dia = String(fecha.getDate()).padStart(2, "0");
    return fecha.getFullYear() + "-" + mes + "-" + dia;
}

// Fecha de hoy, como "2026-11-04"
function hoy() {
    return fechaTexto(new Date());
}

// ---------- AYUDAS PARA ARMAR LA PÁGINA ----------

// Crea una etiqueta de HTML con su clase y su texto. Ej. crear("p", "negrita", "Hola")
// El texto se pone con textContent, así lo que escriba el usuario nunca se toma como código.
function crear(etiqueta, clase, texto) {
    let elemento = document.createElement(etiqueta);
    if (clase) {
        elemento.className = clase;
    }
    if (texto != undefined) {
        elemento.textContent = texto;
    }
    return elemento;
}

// Busca un elemento por su id en una lista (servicios, deudas, citas...)
function buscarPorId(lista, id) {
    for (let i = 0; i < lista.length; i++) {
        if (lista[i].id == id) {
            return lista[i];
        }
    }
    return null;
}

// Pregunta antes de borrar algo
function confirmarBorrado(que, nombre) {
    return confirm("¿Eliminar " + que + "?\n\nSe eliminará permanentemente \"" + nombre + "\". Esta acción no se puede deshacer.");
}

// ¿El movimiento de caja suma dinero? Los gastos y los abonos a lo que debo restan.
function esIngreso(tipo) {
    return tipo != "gasto" && tipo != "abono_pagar";
}
