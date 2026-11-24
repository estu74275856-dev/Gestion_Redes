(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showAlert(message, type, target) {
        var host = target || document.getElementById('globalAlerts');
        if (!host) return;
        host.innerHTML = '<div class="alert alert-' + escapeHtml(type || 'danger') + ' alert-dismissible fade show" role="alert">' +
            escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
    }

    async function apiRequest(url, options) {
        var requestOptions = Object.assign({ headers: { Accept: 'application/json' } }, options || {});
        if (requestOptions.body && typeof requestOptions.body !== 'string') {
            requestOptions.headers = Object.assign({}, requestOptions.headers, { 'Content-Type': 'application/json' });
            requestOptions.body = JSON.stringify(requestOptions.body);
        }

        var response;
        try {
            response = await fetch(url, requestOptions);
        } catch (error) {
            throw new Error('No se pudo conectar con la API. Comprueba que el servidor esté disponible.');
        }

        var payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('La API devolvió una respuesta no válida. Inténtalo de nuevo más tarde.');
        }

        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'No se pudo completar la solicitud (' + response.status + ').');
        }
        return payload;
    }

    function unwrap(payload) {
        return payload && Object.prototype.hasOwnProperty.call(payload, 'data') ? payload.data : payload;
    }

    function formatDate(value) {
        if (!value) return '—';
        var date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('es');
    }

    function connectionBadge(state) {
        var values = {
            en_linea: ['state-active', 'Activo'],
            fuera_de_linea: ['state-failure', 'En falla'],
            advertencia: ['state-failure', 'En falla'],
            desconocido: ['state-inactive', 'Inactivo'],
            '1': ['state-active', 'Activo'],
            '0': ['state-inactive', 'Inactivo'],
            M: ['state-inactive', 'Inactivo']
        };
        var badge = values[String(state)] || ['state-inactive', String(state || 'Inactivo')];
        return '<span class="state-pill ' + badge[0] + '">' + escapeHtml(badge[1]) + '</span>';
    }

    function incidentStatusBadge(state) {
        var values = {
            abierta: ['state-failure', 'Abierta'],
            en_proceso: ['state-pending', 'En proceso'],
            resuelta: ['state-active', 'Resuelta']
        };
        var badge = values[String(state)] || ['state-inactive', String(state || 'Sin estado')];
        return '<span class="state-pill ' + badge[0] + '">' + escapeHtml(badge[1]) + '</span>';
    }

    function priorityBadge(value) {
        var normalized = String(value || '').toLowerCase();
        var values = {
            alta: ['priority-high', 'Alta'], critica: ['priority-high', 'Crítica'], al: ['priority-high', 'Alta'],
            media: ['priority-medium', 'Media'], md: ['priority-medium', 'Media'],
            baja: ['priority-low', 'Baja'], bj: ['priority-low', 'Baja']
        };
        var badge = values[normalized] || ['priority-low', String(value || '—')];
        return '<span class="priority-pill ' + badge[0] + '">' + escapeHtml(badge[1]) + '</span>';
    }

    function setHealthState(state, label, title) {
        document.querySelectorAll('[data-health-dot]').forEach(function (dot) {
            dot.classList.remove('online', 'offline', 'warning');
            if (state) dot.classList.add(state);
        });
        document.querySelectorAll('[data-health-label]').forEach(function (node) {
            node.textContent = label;
            if (title) node.setAttribute('title', title);
            else node.removeAttribute('title');
        });
    }

    async function checkHealth() {
        var badge = document.getElementById('apiHealthBadge');
        if (!badge) return;
        try {
            var response = await fetch('/api/health', { headers: { Accept: 'application/json' } });
            var health = await response.json();
            if (!response.ok || health.status !== 'online') throw new Error('API no disponible');

            if (health.database && health.database.connected) {
                badge.innerHTML = '<span class="network-indicator online"></span><span>API conectada</span>';
                badge.removeAttribute('title');
                setHealthState('online', 'Conectada');
            } else {
                var message = health.database && health.database.message ? health.database.message : 'La base de datos no está disponible.';
                badge.innerHTML = '<span class="network-indicator warning"></span><span>Base de datos desconectada</span>';
                badge.title = message;
                setHealthState('warning', 'Base de datos desconectada', message);
            }
        } catch (error) {
            badge.innerHTML = '<span class="network-indicator offline"></span><span>API inaccesible</span>';
            badge.title = error.message || 'No fue posible consultar el estado del servicio.';
            setHealthState('offline', 'API inaccesible', badge.title);
        }
    }

    window.App = {
        apiRequest: apiRequest,
        unwrap: unwrap,
        escapeHtml: escapeHtml,
        showAlert: showAlert,
        formatDate: formatDate,
        connectionBadge: connectionBadge,
        incidentStatusBadge: incidentStatusBadge,
        priorityBadge: priorityBadge
    };

    document.addEventListener('DOMContentLoaded', checkHealth);
}());
