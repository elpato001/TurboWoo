/**
 * TURBOWOO - Lógica Frontend
 * Maneja la actualización en tiempo real de pedidos y la impresión térmica.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. DETECTOR DE PÁGINA
    // Solo ejecutamos el auto-refresco si estamos en la página de pedidos
    const contenedorPedidos = document.getElementById('contenedor-pedidos-live');
    
    if (contenedorPedidos) {
        console.log('⚡ TurboWoo: Monitor de pedidos iniciado.');
        iniciarMonitorPedidos();
    }

    // 2. LISTENERS GLOBALES
    // Para botones que se cargan dinámicamente (AJAX), usamos delegación de eventos
    document.body.addEventListener('click', function(e) {
        // Si hacen clic en un botón de imprimir ticket
        if (e.target.closest('.btn-imprimir')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-imprimir');
            const idPedido = btn.dataset.id;
            abrirTicketTermico(idPedido);
        }
    });
});

/**
 * ---------------------------------------------------------
 * MÓDULO DE PEDIDOS EN TIEMPO REAL
 * ---------------------------------------------------------
 */
let ultimoTotalPedidos = 0;
let primeraCarga = true;
// Sonido de notificación (opcional: agrega un archivo beep.mp3 en assets/img o usa una URL externa)
const audioNotificacion = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg'); 

function iniciarMonitorPedidos() {
    cargarPedidos(); // Carga inmediata

    // Programar recarga cada 30 segundos
    setInterval(() => {
        cargarPedidos();
    }, 30000);
}

function cargarPedidos() {
    const contenedor = document.getElementById('contenedor-pedidos-live');
    const indicadorCarga = document.getElementById('loading-indicator'); // Opcional si lo agregas al HTML

    if(indicadorCarga) indicadorCarga.style.display = 'block';

    fetch('api_pedidos.php')
        .then(response => {
            if (!response.ok) throw new Error('Error en la red');
            return response.text(); // Esperamos HTML listo para insertar
        })
        .then(html => {
            // Insertamos el HTML recibido
            contenedor.innerHTML = html;
            
            // Lógica para detectar NUEVOS pedidos
            // Contamos cuántas filas de pedido hay
            const nuevosPedidos = contenedor.querySelectorAll('tr.fila-pedido').length;

            // Si no es la primera carga y hay más pedidos que antes -> SONIDO
            if (!primeraCarga && nuevosPedidos > ultimoTotalPedidos) {
                notificarNuevoPedido();
            }

            ultimoTotalPedidos = nuevosPedidos;
            primeraCarga = false;
        })
        .catch(error => {
            console.error('Error al cargar pedidos:', error);
            contenedor.innerHTML = '<div class="alert alert-warning">⚠️ Error de conexión con WooCommerce. Reintentando...</div>';
        })
        .finally(() => {
            if(indicadorCarga) indicadorCarga.style.display = 'none';
        });
}

function notificarNuevoPedido() {
    // Reproducir sonido
    audioNotificacion.play().catch(e => console.log("El navegador bloqueó el sonido automático (interactúa con la página primero)."));
    
    // Cambiar título de la pestaña temporalmente
    document.title = "🔔 ¡NUEVO PEDIDO! - TurboWoo";
    setTimeout(() => {
        document.title = "Pedidos - TurboWoo";
    }, 5000);
}

/**
 * ---------------------------------------------------------
 * MÓDULO DE IMPRESIÓN TÉRMICA
 * ---------------------------------------------------------
 */
function abrirTicketTermico(idPedido) {
    // Calculamos centro de la pantalla para abrir el popup
    const w = 400;
    const h = 600;
    const left = (window.screen.width / 2) - (w / 2);
    const top = (window.screen.height / 2) - (h / 2);

    const url = `ticket.php?id=${idPedido}`;
    
    // Abrimos ventana emergente limpia
    const ventanaTicket = window.open(
        url, 
        'TicketTermico', 
        `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=${w}, height=${h}, top=${top}, left=${left}`
    );

    // Nota: El archivo ticket.php debe tener un <script>window.print()</script> al final 
    // para que lance el diálogo de impresión automáticamente al cargar.
}

/**
 * ---------------------------------------------------------
 * UTILIDADES DE UI (EDITOR DE PRODUCTOS)
 * ---------------------------------------------------------
 */
// Resalta la fila cuando cambias un precio en local
const inputsEdicion = document.querySelectorAll('.input-edit');
inputsEdicion.forEach(input => {
    input.addEventListener('change', function() {
        const fila = this.closest('tr');
        fila.classList.add('modified'); // Añade fondo amarillo (definido en CSS)
        
        // Buscar el indicador de estado y ponerlo en "Pendiente"
        const estadoBadge = fila.querySelector('.estado-sync');
        if(estadoBadge) {
            estadoBadge.innerHTML = '<span class="status-dot status-pending"></span> <small>Editado</small>';
        }
    });
});