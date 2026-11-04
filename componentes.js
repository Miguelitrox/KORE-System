// PARTES QUE SE REPITEN EN TODAS LAS PÁGINAS (encabezado, menú de abajo, pie e íconos)
// Usa CONFIG, que viene de api/configuracion.php
// Se pone después de funciones.js con: <script src="componentes.js"></script>

// Íconos copiados de lucide.dev. Uso: icono("tijeras")
// En el HTML también se pueden poner así: <span data-icono="tijeras"></span>
const ICONOS = {
    tijeras: '<circle cx="6" cy="6" r="3"/><path d="M8.12 8.12 12 12"/><path d="M20 4 8.12 15.88"/><circle cx="6" cy="18" r="3"/><path d="M14.8 14.8 20 20"/>',
    registrar: '<circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/>',
    grafica: '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    calendario: '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>',
    agendar: '<path d="M8 2v4"/><path d="M16 2v4"/><path d="M21 13V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/><path d="M3 10h18"/><path d="M16 19h6"/><path d="M19 16v6"/>',
    cartera: '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
    monedas: '<path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/><path d="m2 16 6 6"/><circle cx="16" cy="9" r="2.9"/><circle cx="6" cy="5" r="3"/>',
    reloj: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    baja: '<polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>',
    sube: '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
    libreta: '<path d="M13.4 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7.4"/><path d="M2 6h4"/><path d="M2 10h4"/><path d="M2 14h4"/><path d="M2 18h4"/><path d="M21.378 5.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/>',
    historial: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
    mas: '<path d="M5 12h14"/><path d="M12 5v14"/>',
    menos: '<path d="M5 12h14"/>',
    lapiz: '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
    basura: '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
    cobrar: '<circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="m8 12 4 4 4-4"/>',
    pagar: '<circle cx="12" cy="12" r="10"/><path d="m16 12-4-4-4 4"/><path d="M12 16V8"/>',
    palomita: '<path d="M20 6 9 17l-5-5"/>',
    izquierda: '<path d="m15 18-6-6 6-6"/>',
    derecha: '<path d="m9 18 6-6-6-6"/>',
    microfono: '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/>',
    detener: '<rect width="14" height="14" x="5" y="5" rx="2"/>',
};

function icono(nombre) {
    return '<svg class="icono" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + ICONOS[nombre] + '</svg>';
}

// Evita que un texto se tome como código HTML (por ejemplo, el nombre del negocio)
function escapar(texto) {
    return String(texto).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

// Archivo que se está viendo (ej. "citas.html"), para pintar su botón en el menú
let paginaActual = location.pathname.split("/").pop();
if (paginaActual == "") {
    paginaActual = "index.html";
}

// LAS 6 SECCIONES DEL MENÚ DE ABAJO
const SECCIONES = [
    { archivo: "index.html", texto: "Registrar", icono: "registrar" },
    { archivo: "servicios.html", texto: "Servicios", icono: "tijeras" },
    { archivo: "reportes.html", texto: "Reportes", icono: "grafica" },
    { archivo: "citas.html", texto: "Citas", icono: "calendario" },
    { archivo: "caja.html", texto: "Caja", icono: "cartera" },
    { archivo: "por_cobrar.html", texto: "Deudas", icono: "monedas" },
];

// ¿Esta sección es la que se está viendo? (las 2 páginas de Deudas pintan el botón "Deudas")
function esSeccionActual(seccion) {
    if (seccion.archivo == "por_cobrar.html") {
        return paginaActual == "por_cobrar.html" || paginaActual == "por_pagar.html";
    }
    return paginaActual == seccion.archivo;
}

// ENCABEZADO: barra de DEMO, nombre del negocio y menú de abajo
// Se pone donde está <div id="encabezado"></div>
function dibujarEncabezado() {
    let html = "";

    if (CONFIG.es_demo) {
        html = html +
            '<div class="demo-barra">' +
            '    <div class="contenedor demo-contenido">' +
            '        <img src="imagenes/logo.png" alt="Logo de KORE System" class="logo-kore-mini">' +
            '        <p><strong>DEMO</strong> de KORE System <span class="solo-pantalla-grande">· un sistema que se adapta a cualquier negocio</span></p>' +
            '    </div>' +
            '</div>';
    }

    let etiquetaDemo = "";
    if (CONFIG.es_demo) {
        etiquetaDemo = ' <span class="etiqueta-demo">DEMO</span>';
    }

    html = html +
        '<header>' +
        '    <div class="contenedor encabezado">' +
        '        <span class="logo-negocio">' + icono("tijeras") + '</span>' +
        '        <div>' +
        '            <h1>' + escapar(CONFIG.negocio) + etiquetaDemo + '</h1>' +
        '            <p>' + escapar(CONFIG.descripcion) + '</p>' +
        '        </div>' +
        '    </div>' +
        '    <div class="franja-barberia"></div>' +
        '</header>';

    // Menú de abajo
    html = html + '<nav class="menu"><div class="contenedor">';
    for (let i = 0; i < SECCIONES.length; i++) {
        let clase = "";
        if (esSeccionActual(SECCIONES[i])) {
            clase = "activo";
        }
        html = html +
            '<a href="' + SECCIONES[i].archivo + '" class="' + clase + '">' +
            '    <span>' + icono(SECCIONES[i].icono) + '</span>' +
            '    ' + SECCIONES[i].texto +
            '</a>';
    }
    html = html + '</div></nav>';

    // outerHTML cambia el <div id="encabezado"> por todo lo de arriba
    document.getElementById("encabezado").outerHTML = html;
}

// PIE: la firma de KORE System. Se pone donde está <div id="pie"></div>
function dibujarPie() {
    let lugar = document.getElementById("pie");
    if (!lugar) {
        return;
    }

    let notaDemo = "";
    if (CONFIG.es_demo) {
        notaDemo =
            '<p class="chico gris">Esto es una DEMO: ' + escapar(CONFIG.negocio) + ' es un negocio de ejemplo y los datos son de prueba. ' +
            'KORE System se adapta a lo que necesite cada negocio.</p>';
    }

    lugar.outerHTML =
        '<footer class="pie">' +
        '    <p class="firma-kore"><img src="imagenes/logo.png" alt="Logo de KORE System"> Hecho con <strong>KORE System</strong></p>' +
        notaDemo +
        '</footer>';
}

// PESTAÑAS DE DEUDAS (Por cobrar / Por pagar). Se ponen donde está <div id="pestanas-deudas"></div>
function dibujarPestanasDeudas() {
    let lugar = document.getElementById("pestanas-deudas");
    if (!lugar) {
        return;
    }

    let claseCobrar = "";
    let clasePagar = "";
    if (paginaActual == "por_cobrar.html") {
        claseCobrar = "activa";
    } else {
        clasePagar = "activa";
    }

    lugar.outerHTML =
        '<div class="pestanas">' +
        '    <a href="por_cobrar.html" class="' + claseCobrar + '">' + icono("cobrar") + ' Por cobrar</a>' +
        '    <a href="por_pagar.html" class="' + clasePagar + '">' + icono("pagar") + ' Por pagar</a>' +
        '</div>';
}

// Cambia cada <span data-icono="nombre"></span> de la página por su ícono
function ponerIconos() {
    let lugares = document.querySelectorAll("[data-icono]");
    for (let i = 0; i < lugares.length; i++) {
        lugares[i].outerHTML = icono(lugares[i].dataset.icono);
    }
}

dibujarEncabezado();
dibujarPie();
dibujarPestanasDeudas();
ponerIconos();

// Título de la pestaña del navegador: "Citas - Barbería Sami's | KORE System"
document.title = document.title + " - " + CONFIG.negocio + " | KORE System";
