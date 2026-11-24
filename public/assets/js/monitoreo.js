(function () {
    'use strict';

    var App = window.App;
    var devices = [];
    var previewMode = false;
    var detailsModal;
    var sampleDevices = [
        { id: 'D0001', codigo: 'D0001', nombre: 'Switch principal', ip: '192.168.1.10', tipo: 'SW', estado: 'en_linea' },
        { id: 'D0002', codigo: 'D0002', nombre: 'Router de acceso', ip: '192.168.1.1', tipo: 'RT', estado: 'en_linea' },
        { id: 'D0003', codigo: 'D0003', nombre: 'Firewall perimetral', ip: '192.168.1.2', tipo: 'FW', estado: 'fuera_de_linea' },
        { id: 'D0004', codigo: 'D0004', nombre: 'Punto de acceso — Recepción', ip: '192.168.1.20', tipo: 'AP', estado: 'en_linea' },
        { id: 'D0005', codigo: 'D0005', nombre: 'Switch de respaldo', ip: '192.168.1.11', tipo: 'SW', estado: 'desconocido' },
        { id: 'D0006', codigo: 'D0006', nombre: 'Router de laboratorio', ip: '192.168.1.3', tipo: 'RT', estado: 'en_linea' },
        { id: 'D0011', codigo: 'D0011', nombre: 'Punto de acceso — Sala 3', ip: '192.168.1.21', tipo: 'AP', estado: 'en_linea' }
    ];
    var typeLabels = { RT: 'Router', SW: 'Switch', AP: 'Punto de acceso', FW: 'Firewall', SV: 'Servidor', PC: 'Host / PC' };

    function codeFor(device) {
        return String(device.codigo || device.dis_id || device.id || '');
    }

    function stateFor(state) {
        var value = String(state || '').toLowerCase();
        if (['en_linea', 'activo', '1'].indexOf(value) !== -1) return { text: 'Activo', className: 'state-active', count: 'active' };
        if (['fuera_de_linea', 'advertencia', 'falla', 'm'].indexOf(value) !== -1) return { text: 'En falla', className: 'state-failure', count: 'failure' };
        return { text: 'Inactivo', className: 'state-inactive', count: 'inactive' };
    }

    function renderDevices() {
        var body = document.getElementById('monitorDevicesTable');
        if (!devices.length) {
            body.innerHTML = '<tr><td colspan="5"><div class="empty-state">No hay dispositivos disponibles para monitorear.</div></td></tr>';
        } else {
            body.innerHTML = devices.map(function (device) {
                var state = stateFor(device.estado);
                return '<tr data-monitor-code="' + App.escapeHtml(codeFor(device).toLowerCase()) + '">' +
                    '<td><span class="device-code">' + App.escapeHtml(codeFor(device)) + '</span></td>' +
                    '<td><span class="device-name">' + App.escapeHtml(device.nombre || 'Sin nombre') + '</span><span class="secondary-line">' + App.escapeHtml(typeLabels[device.tipo] || device.tipo || 'Dispositivo') + '</span></td>' +
                    '<td class="ip-address">' + App.escapeHtml(device.ip || '—') + '</td>' +
                    '<td><span class="state-pill ' + state.className + '">' + state.text + '</span></td>' +
                    '<td><button class="text-link btn-icon" type="button" data-monitor-action="details" data-device-id="' + App.escapeHtml(device.id) + '"><i class="bi bi-eye"></i>Ver estado</button></td></tr>';
            }).join('');
        }

        var counts = { active: 0, inactive: 0, failure: 0 };
        devices.forEach(function (device) { counts[stateFor(device.estado).count] += 1; });
        document.getElementById('monitorDeviceCount').textContent = devices.length + (devices.length === 1 ? ' dispositivo en monitoreo' : ' dispositivos en monitoreo');
        document.getElementById('monitorRowsCount').textContent = devices.length + (devices.length === 1 ? ' dispositivo mostrado' : ' dispositivos mostrados');
        document.getElementById('monitorActiveCount').textContent = counts.active;
        document.getElementById('monitorInactiveCount').textContent = counts.inactive;
        document.getElementById('monitorFailureCount').textContent = counts.failure;
    }

    async function loadDevices() {
        try {
            var result = App.unwrap(await App.apiRequest('/api/dispositivos'));
            if (!Array.isArray(result)) throw new Error('El endpoint no devolvió una lista de dispositivos.');
            devices = result;
            previewMode = false;
        } catch (error) {
            if (!devices.length) devices = sampleDevices.map(function (device) { return Object.assign({}, device, { _preview: true }); });
            previewMode = true;
            App.showAlert('Vista previa con datos de ejemplo: no se pudo consultar la API. Las comprobaciones reales no están disponibles.', 'warning');
        }
        renderDevices();
    }

    function setDetailsSummary(device) {
        var state = stateFor(device.estado);
        document.getElementById('monitorDetailsTitle').textContent = 'Estado · ' + codeFor(device);
        document.getElementById('monitorDetailsSummary').innerHTML =
            '<div><span class="device-code">' + App.escapeHtml(codeFor(device)) + '</span><h3>' + App.escapeHtml(device.nombre || 'Dispositivo') + '</h3><span class="ip-address">' + App.escapeHtml(device.ip || '—') + ' · ' + App.escapeHtml(typeLabels[device.tipo] || device.tipo || 'Dispositivo') + '</span></div>' +
            '<span class="state-pill ' + state.className + '">' + state.text + '</span>';
    }

    function renderHistory(rows, preview) {
        var host = document.getElementById('monitorHistoryList');
        if (!rows.length) {
            host.innerHTML = '<div class="empty-state">No hay registros de monitoreo para este dispositivo.</div>';
            return;
        }
        host.innerHTML = rows.map(function (row) {
            var state = stateFor(row.estado);
            var measurements = [row.latencia_ms == null ? 'Latencia no disponible' : row.latencia_ms + ' ms'];
            if (row.cpu_usage != null) measurements.push('CPU ' + row.cpu_usage + '%');
            if (row.memory_usage != null) measurements.push('Memoria ' + row.memory_usage + '%');
            var date = row.fecha_monitoreo || row.mon_fec;
            return '<div class="history-row"><span class="network-indicator ' + (state.count === 'active' ? 'online' : (state.count === 'failure' ? 'offline' : '')) + '"></span><div><strong class="small">' + state.text + (preview ? ' · Ejemplo' : '') + '</strong><small>' + App.escapeHtml(App.formatDate(date)) + '</small></div><span class="mono small text-secondary">' + App.escapeHtml(measurements.join(' · ')) + '</span></div>';
        }).join('');
    }

    async function openDetails(device) {
        setDetailsSummary(device);
        detailsModal.show();
        var host = document.getElementById('monitorHistoryList');
        host.innerHTML = '<div class="loading-state"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Cargando historial…</div>';
        if (device._preview || previewMode) {
            renderHistory([
                { estado: device.estado, latencia_ms: 8, fecha_monitoreo: '2026-11-23 09:42:00' },
                { estado: 'en_linea', latencia_ms: 7, fecha_monitoreo: '2026-11-23 09:27:00' }
            ], true);
            return;
        }
        try {
            var result = App.unwrap(await App.apiRequest('/api/monitoreo/dispositivo/' + encodeURIComponent(device.id)));
            renderHistory(Array.isArray(result) ? result : [], false);
        } catch (error) {
            host.innerHTML = '<div class="alert alert-soft-warning mb-0">No se pudo cargar el historial: ' + App.escapeHtml(error.message) + '</div>';
        }
    }

    async function searchDevice(event) {
        event.preventDefault();
        var value = document.getElementById('monitorDeviceCode').value.trim().toLowerCase();
        if (!value) {
            App.showAlert('Ingresa el código del dispositivo para realizar la búsqueda.', 'warning');
            return;
        }
        var device = devices.find(function (item) {
            return codeFor(item).toLowerCase() === value;
        });
        if (!device) {
            App.showAlert('No se encontró un dispositivo con el código ' + value.toUpperCase() + '.', 'warning');
            return;
        }
        if (device._preview || previewMode) {
            App.showAlert('Modo de vista previa: no se ejecutó una comprobación real del dispositivo.', 'warning');
            openDetails(device);
            return;
        }
        var button = event.submitter || document.querySelector('#monitorSearchForm button[type="submit"]');
        button.disabled = true;
        try {
            var result = App.unwrap(await App.apiRequest('/api/monitoreo/ping/' + encodeURIComponent(device.id), { method: 'POST' }));
            if (result && result.estado) device.estado = result.estado;
            renderDevices();
            App.showAlert('Comprobación de ' + codeFor(device) + ' completada.', 'success');
            await openDetails(device);
        } catch (error) {
            App.showAlert('No se pudo comprobar el dispositivo: ' + error.message, 'warning');
        } finally {
            button.disabled = false;
        }
    }

    async function pingAll(button) {
        if (previewMode || devices.some(function (device) { return device._preview; })) {
            App.showAlert('Modo de vista previa: no se ejecutó una comprobación real de la red.', 'warning');
            return;
        }
        button.disabled = true;
        try {
            var result = App.unwrap(await App.apiRequest('/api/monitoreo/ping-all', { method: 'POST' }));
            await loadDevices();
            var checked = result && result.total_verificados != null ? result.total_verificados : devices.length;
            App.showAlert('Comprobación completada: ' + checked + ' dispositivos verificados.', 'success');
        } catch (error) {
            App.showAlert('No se pudo completar la comprobación general: ' + error.message, 'warning');
        } finally {
            button.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        detailsModal = new bootstrap.Modal(document.getElementById('monitorDetailsModal'));
        document.getElementById('monitorSearchForm').addEventListener('submit', searchDevice);
        document.getElementById('pingAllButton').addEventListener('click', function (event) { pingAll(event.currentTarget); });
        document.getElementById('monitorDevicesTable').addEventListener('click', function (event) {
            var button = event.target.closest('[data-monitor-action="details"]');
            if (!button) return;
            var device = devices.find(function (item) { return String(item.id) === String(button.dataset.deviceId); });
            if (device) openDetails(device);
        });
        loadDevices().then(function () {
            var code = new URLSearchParams(window.location.search).get('codigo');
            if (code) {
                document.getElementById('monitorDeviceCode').value = code;
                document.getElementById('monitorSearchForm').requestSubmit();
            }
        });
    });
}());
