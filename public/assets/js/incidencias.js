(function () {
    'use strict';
    var App = window.App;
    var incidents = [];
    var incidentModal;
    var incidentsPreview = false;
    var devicesPreview = false;
    var sampleDevices = [
        { id: 'D0001', nombre: 'Switch principal', ip: '192.168.1.10' },
        { id: 'D0002', nombre: 'Router de acceso', ip: '192.168.1.1' },
        { id: 'D0003', nombre: 'Firewall perimetral', ip: '192.168.1.2' }
    ];
    var sampleIncidents = [
        { id: 'I000000301', dispositivo_id: 'D0002', dispositivo_nombre: 'Router de acceso', dispositivo_ip: '192.168.1.1', titulo: 'Conectividad intermitente', descripcion: 'Se detectan cortes breves en la conexión.', incid_tipo: 'NW', incid_prioridad: 'AL', incid_stat: 'A', incid_origen: 'S', gravedad: 'alta', estado: 'abierta', fecha_reporte: '2026-11-23 09:40:00', _preview: true },
        { id: 'I000000302', dispositivo_id: 'D0003', dispositivo_nombre: 'Firewall perimetral', dispositivo_ip: '192.168.1.2', titulo: 'Regla de acceso pendiente', descripcion: 'Revisar la política solicitada por el equipo de redes.', incid_tipo: 'SW', incid_prioridad: 'MD', incid_stat: 'P', incid_origen: 'U', gravedad: 'media', estado: 'en_proceso', fecha_reporte: '2026-11-22 15:10:00', _preview: true },
        { id: 'I000000303', dispositivo_id: 'D0001', dispositivo_nombre: 'Switch principal', dispositivo_ip: '192.168.1.10', titulo: 'Revisión de puerto', descripcion: 'Puerto verificado y normalizado.', incid_tipo: 'HW', incid_prioridad: 'BJ', incid_stat: 'R', incid_origen: 'U', gravedad: 'baja', estado: 'resuelta', fecha_reporte: '2026-11-21 11:15:00', _preview: true }
    ];
    var typeLabels = { HW: 'Hardware', NW: 'Red', OT: 'Otro', SW: 'Software' };
    var originLabels = { S: 'Sistema', U: 'Usuario' };
    var stateLabels = { A: 'Abierta', P: 'En proceso', R: 'Resuelta' };

    function updatePreviewMetrics() {
        var counts = {
            total: incidents.length,
            abiertas: incidents.filter(function (item) { return item.incid_stat === 'A'; }).length,
            en_proceso: incidents.filter(function (item) { return item.incid_stat === 'P'; }).length,
            resueltas: incidents.filter(function (item) { return item.incid_stat === 'R'; }).length
        };
        document.getElementById('incidentMetricTotal').textContent = counts.total;
        document.getElementById('incidentMetricOpen').textContent = counts.abiertas;
        document.getElementById('incidentMetricProgress').textContent = counts.en_proceso;
        document.getElementById('incidentMetricResolved').textContent = counts.resueltas;
    }

    async function loadMetrics() {
        try {
            var metrics = App.unwrap(await App.apiRequest('/api/incidencias/metricas'));
            document.getElementById('incidentMetricTotal').textContent = metrics.total == null ? '0' : metrics.total;
            document.getElementById('incidentMetricOpen').textContent = metrics.abiertas == null ? '0' : metrics.abiertas;
            document.getElementById('incidentMetricProgress').textContent = metrics.en_proceso == null ? '0' : metrics.en_proceso;
            document.getElementById('incidentMetricResolved').textContent = metrics.resueltas == null ? '0' : metrics.resueltas;
        } catch (error) {
            updatePreviewMetrics();
            App.showAlert('Vista previa: métricas de incidencias de ejemplo. ' + error.message, 'warning');
        }
    }

    function showRows() {
        var body = document.getElementById('incidentsTable');
        var filter = document.getElementById('incidentStatusFilter').value;
        var query = document.getElementById('incidentSearch').value.trim().toLowerCase();
        var visible = incidents.filter(function (item) {
            var matchesState = !filter || (filter === 'active' ? item.incid_stat !== 'R' : item.incid_stat === filter);
            var searchable = [item.id, item.titulo, item.descripcion, item.dispositivo_nombre, item.dispositivo_ip].join(' ').toLowerCase();
            return matchesState && (!query || searchable.indexOf(query) !== -1);
        });
        if (!visible.length) {
            body.innerHTML = '<tr><td colspan="9"><div class="empty-state">No hay incidencias que coincidan con los filtros.</div></td></tr>';
            return;
        }
        body.innerHTML = visible.map(function (item) {
            var deviceName = item.dispositivo_nombre || ('Dispositivo #' + item.dispositivo_id);
            return '<tr><td>' + App.escapeHtml(item.id) + '</td><td><span class="device-name">' + App.escapeHtml(item.titulo || 'Sin título') + '</span><span class="secondary-line">' + App.escapeHtml(item.descripcion || '') + '</span></td>' +
                '<td>' + App.escapeHtml(deviceName) + '<span class="secondary-line">' + App.escapeHtml(item.dispositivo_ip || '') + '</span></td>' +
                '<td>' + App.escapeHtml(typeLabels[item.incid_tipo] || item.incid_tipo || '—') + '</td><td>' + App.priorityBadge(item.gravedad) + '</td>' +
                '<td>' + App.escapeHtml(originLabels[item.incid_origen] || item.incid_origen || '—') + '</td>' +
                '<td>' + App.incidentStatusBadge(item.estado || stateLabels[item.incid_stat] || '') + '</td>' +
                '<td>' + App.escapeHtml(App.formatDate(item.fecha_reporte)) + '</td>' +
                '<td>' + (item.incid_stat === 'R'
                    ? '<span class="text-secondary small">Cerrada</span>'
                    : '<button class="btn btn-sm btn-outline-success btn-resolve-incident" type="button" data-resolve-id="' + App.escapeHtml(item.id) + '"><i class="bi bi-check2-circle me-1"></i>Resolver</button>') +
                '</td></tr>';
        }).join('');
    }

    async function loadIncidents() {
        var body = document.getElementById('incidentsTable');
        body.innerHTML = '<tr><td colspan="9"><div class="loading-state"><span class="spinner-border spinner-border-sm me-2"></span>Cargando incidencias…</div></td></tr>';
        try {
            var result = App.unwrap(await App.apiRequest('/api/incidencias'));
            incidents = Array.isArray(result) ? result : [];
            incidentsPreview = false;
            showRows();
        } catch (error) {
            incidentsPreview = true;
            incidents = sampleIncidents.map(function (item) { return Object.assign({}, item); });
            updatePreviewMetrics();
            showRows();
            App.showAlert('Vista previa con incidencias de ejemplo. Los cambios son temporales y no se guardan en la base de datos. ' + error.message, 'warning');
        }
    }

    async function loadDevices() {
        var select = document.getElementById('incid_dis_id');
        try {
            var result = App.unwrap(await App.apiRequest('/api/dispositivos'));
            var devices = Array.isArray(result) ? result : [];
            devicesPreview = false;
            select.innerHTML = '<option value="">Selecciona un dispositivo</option>' + devices.map(function (device) {
                return '<option value="' + App.escapeHtml(device.id) + '">' + App.escapeHtml(device.nombre || ('Dispositivo #' + device.id)) + ' · ' + App.escapeHtml(device.ip || '') + '</option>';
            }).join('');
            if (!devices.length) select.innerHTML = '<option value="">Registra un dispositivo primero</option>';
        } catch (error) {
            devicesPreview = true;
            select.innerHTML = '<option value="">Selecciona un dispositivo</option>' + sampleDevices.map(function (device) {
                return '<option value="' + device.id + '">' + App.escapeHtml(device.nombre) + ' · ' + App.escapeHtml(device.ip) + '</option>';
            }).join('');
            App.showAlert('Vista previa: se muestran dispositivos de ejemplo. ' + error.message, 'warning');
        }
    }

    async function resolveIncident(button) {
        var id = button.dataset.resolveId;
        var previous = incidents.find(function (item) { return String(item.id) === String(id); });
        if (!previous || previous.incid_stat === 'R') return;
        button.disabled = true;
        var originalContent = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Guardando';
        if (incidentsPreview || (previous && previous._preview)) {
            previous.incid_stat = 'R';
            previous.estado = 'resuelta';
            showRows();
            updatePreviewMetrics();
            App.showAlert('Vista previa: la incidencia se marcó como resuelta solo en esta sesión.', 'warning');
            if (button.isConnected) {
                button.disabled = false;
                button.innerHTML = originalContent;
            }
            return;
        }
        try {
            await App.apiRequest('/api/incidencias/' + encodeURIComponent(id) + '/estado', {
                method: 'PATCH',
                body: { incid_stat: 'R' }
            });
            previous.incid_stat = 'R';
            previous.estado = 'resuelta';
            showRows();
            await loadMetrics();
            App.showAlert('Incidencia marcada como resuelta.', 'success');
            await loadIncidents();
        } catch (error) {
            App.showAlert('No se pudo resolver la incidencia: ' + error.message, 'danger');
        } finally {
            if (button.isConnected) {
                button.disabled = false;
                button.innerHTML = originalContent;
            }
        }
    }

    function resetForm() {
        document.getElementById('incidentForm').reset();
        document.getElementById('incid_stat').value = 'A';
        document.getElementById('incid_prioridad').value = 'MD';
    }

    async function saveIncident(event) {
        event.preventDefault();
        var form = event.currentTarget;
        if (!form.reportValidity()) return;
        var payload = {
            dis_id: document.getElementById('incid_dis_id').value,
            incid_titulo: document.getElementById('incid_titulo').value.trim(),
            incid_desc: document.getElementById('incid_desc').value.trim(),
            incid_prioridad: document.getElementById('incid_prioridad').value,
            incid_stat: document.getElementById('incid_stat').value,
            incid_tipo: document.getElementById('incid_tipo').value.trim(),
            incid_origen: document.getElementById('incid_origen').value.trim()
        };
        if (incidentsPreview || devicesPreview) {
            var selected = document.getElementById('incid_dis_id');
            var deviceName = selected.options[selected.selectedIndex].text.split(' · ')[0];
            var id = 'I' + Date.now().toString(36).slice(-9).toUpperCase().padStart(9, '0');
            var statusCode = payload.incid_stat;
            incidents.unshift(Object.assign({
                id: id,
                incid_id: id,
                dispositivo_id: payload.dis_id,
                dispositivo_nombre: deviceName,
                dispositivo_ip: '',
                fecha_reporte: new Date().toISOString(),
                titulo: payload.incid_titulo,
                descripcion: payload.incid_desc,
                gravedad: { AL: 'alta', MD: 'media', BJ: 'baja', CR: 'critica' }[payload.incid_prioridad],
                estado: { A: 'abierta', P: 'en_proceso', R: 'resuelta' }[statusCode],
                _preview: true
            }, payload));
            incidentModal.hide();
            showRows();
            updatePreviewMetrics();
            App.showAlert('Vista previa: la incidencia se añadió temporalmente; no fue guardada en la base de datos.', 'warning');
            return;
        }
        var button = document.getElementById('saveIncidentButton');
        button.disabled = true;
        try {
            await App.apiRequest('/api/incidencias', { method: 'POST', body: payload });
            incidentModal.hide();
            App.showAlert('Incidencia registrada correctamente.', 'success');
            await loadIncidents();
            await loadMetrics();
        } catch (error) {
            App.showAlert('No se pudo registrar la incidencia: ' + error.message, 'danger');
        } finally {
            button.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        incidentModal = new bootstrap.Modal(document.getElementById('incidentModal'));
        document.getElementById('createIncidentButton').addEventListener('click', function () {
            resetForm();
            incidentModal.show();
        });
        document.getElementById('incidentForm').addEventListener('submit', saveIncident);
        document.getElementById('incidentsTable').addEventListener('click', function (event) {
            var button = event.target.closest('[data-resolve-id]');
            if (button) resolveIncident(button);
        });
        document.getElementById('incidentStatusFilter').addEventListener('change', showRows);
        document.getElementById('incidentSearch').addEventListener('input', showRows);
        document.getElementById('reloadIncidents').addEventListener('click', function () {
            loadIncidents();
            loadMetrics();
        });
        loadIncidents();
        loadMetrics();
        loadDevices();
    });
}());
