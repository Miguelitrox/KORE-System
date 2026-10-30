// FUNCIONES DE JAVASCRIPT QUE SE USAN EN TODAS LAS PÁGINAS
// Se pone al final de cada página con: <script src="funciones.js"></script>

// El aviso de "guardado", "borrado", etc. se esconde solo después de 3 segundos
setTimeout(function () {
    let aviso = document.getElementById("aviso");
    if (aviso) {
        aviso.hidden = true;
    }
}, 3000);

// Abrir y cerrar los cuadros de diálogo (<dialog>)
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

// Muestra una cantidad como dinero: 1500 → $1,500.00
function dinero(cantidad) {
    return "$" + Number(cantidad).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Busca un elemento por su id en una lista que viene de PHP (artículos, deudas...)
function buscarPorId(lista, id) {
    for (let i = 0; i < lista.length; i++) {
        if (lista[i].id == id) {
            return lista[i];
        }
    }
    return null;
}

// Pregunta antes de borrar. El nombre de lo que se borra viene en data-nombre del formulario.
function confirmarBorrado(formulario, que) {
    return confirm("¿Eliminar " + que + "?\n\nSe eliminará permanentemente \"" + formulario.dataset.nombre + "\". Esta acción no se puede deshacer.");
}
