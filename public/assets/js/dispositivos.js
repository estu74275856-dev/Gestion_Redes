(function () {
    'use strict';

    var App = window.App;
    var devices = [];
    var previewMode = false;
    var currentDevice = null;
    var justCreatedId = null;
    var viewMode = 'grid';
    var formMode = 'create';
    var monitorStates = {};
    var typeLabels = { RT: 'Router', SW: 'Switch', AP: 'Punto de acceso', FW: 'Firewall', SV: 'Servidor', PC: 'Host / PC', OT: 'Otro' };
    var typeIcons = { RT: 'bi-router', SW: 'bi-hdd-network', AP: 'bi-wifi', FW: 'bi-shield-check', SV: 'bi-server', PC: 'bi-pc-display' };
    var sampleDevices = [
        { id: 'D0001', codigo: 'D0001', nombre: 'Switch principal', ip: '192.168.1.10', tipo: 'SW', ubicacion: 'Centro de datos', estado: 'en_linea', sistema_operativo: 'SwitchOS', observaciones: 'Distribución de red LAN' },
        { id: 'D0002', codigo: 'D0002', nombre: 'Router de acceso', ip: '192.168.1.1', tipo: 'RT', ubicacion: 'Rack principal', estado: 'en_linea', sistema_operativo: 'RouterOS', observaciones: 'Enrutamiento de red' },
        { id: 'D0003', codigo: 'D0003', nombre: 'Firewall perimetral', ip: '192.168.1.2', tipo: 'FW', ubicacion: 'Sala de servidores', estado: 'fuera_de_linea', sistema_operativo: 'Linux', observaciones: 'Seguridad perimetral' },
        { id: 'D0004', codigo: 'D0004', nombre: 'Punto de acceso — Recepción', ip: '192.168.1.20', tipo: 'AP', ubicacion: 'Recepción', estado: 'en_linea', sistema_operativo: 'OpenWrt', observaciones: 'Cobertura inalámbrica' },
        { id: 'D0005', codigo: 'D0005', nombre: 'Switch de respaldo', ip: '192.168.1.11', tipo: 'SW', ubicacion: 'Rack secundario', estado: 'desconocido', sistema_operativo: 'SwitchOS', observaciones: 'Redundancia LAN' },
        { id: 'D0006', codigo: 'D0006', nombre: 'Router de laboratorio', ip: '192.168.1.3', tipo: 'RT', ubicacion: 'Laboratorio', estado: 'en_linea', sistema_operativo: 'RouterOS', observaciones: 'Red de pruebas' }
    ];

    function deviceCode(device) {
        return String(device.codigo || device.dis_id || device.id || '');
    }

    function statusInfo(state) {
        var value = String(state || '').toLowerCase();
        if (['en_linea', 'activo', 'a'].indexOf(value) !== -1) return { label: 'Activo', className: 'state-active', formValue: 'A' };
        if (['fuera_de_linea', 'falla', 'f'].indexOf(value) !== -1) return { label: 'En falla', className: 'state-failure', formValue: 'F' };
        if (['advertencia', 'w'].indexOf(value) !== -1) return { label: 'Advertencia', className: 'state-pending', formValue: 'W' };
        return { label: 'Inactivo', className: 'state-inactive', formValue: 'I' };
    }

    function connectivityInfo(device) {
        var result = monitorStates[String(device.id)] || {};
        var state = result.estado || device.estado || 'desconocido';
        var values = {
            en_linea: { label: 'En línea', className: 'state-active' },
            fuera_de_linea: { label: 'Fuera de línea', className: 'state-failure' },
            advertencia: { label: 'Advertencia', className: 'state-pending' }
        };
        var info = values[state] || { label: 'Desconocido', className: 'state-inactive' };
        return {
            label: info.label,
            className: info.className,
            latency: result.latencia_ms == null ? 'Latencia sin medir' : result.latencia_ms + ' ms'
        };
    }

    function escape(value) {
        return App.escapeHtml(value == null || value === '' ? '—' : value);
    }

    function notify(message, type) {
        App.showAlert(message, type || 'info');
    }

    function showView(name) {
        document.querySelectorAll('.flow-view').forEach(function (view) {
            view.classList.toggle('d-none', view.id !== name);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showInventoryAlert(message, type) {
        var notice = document.getElementById('devicePreviewNotice');
        notice.className = 'alert alert-soft-' + (type || 'warning');
        notice.textContent = message;
        notice.classList.remove('d-none');
    }

    function renderTypeFilter() {
        var select = document.getElementById('deviceTypeFilter');
        var current = select.value;
        var available = {};
        devices.forEach(function (device) {
            if (device.tipo) available[device.tipo] = typeLabels[device.tipo] || device.tipo;
        });
        select.innerHTML = '<option value="">Todos los tipos</option>' + Object.keys(available).sort().map(function (type) {
            return '<option value="' + App.escapeHtml(type) + '">' + App.escapeHtml(available[type]) + '</option>';
        }).join('');
        if (available[current]) select.value = current;
    }

    function visibleDevices() {
        var query = document.getElementById('deviceSearch').value.trim().toLowerCase();
        var type = document.getElementById('deviceTypeFilter').value;
        return devices.filter(function (device) {
            var searchable = [deviceCode(device), device.nombre, device.ip, device.tipo, device.ubicacion, device.sistema_operativo, device.observaciones].join(' ').toLowerCase();
            return (!query || searchable.indexOf(query) !== -1) && (!type || device.tipo === type);
        });
    }

    function renderDeviceCard(device) {
        var status = statusInfo(device.estado);
        var code = deviceCode(device);
        var icon = typeIcons[device.tipo] || 'bi-hdd-network';
        var isNew = String(device.id) === String(justCreatedId);
        return '<article class="device-card' + (isNew ? ' newly-added' : '') + '">' +
            '<div class="device-card-top"><span class="device-type-icon"><i class="bi ' + icon + '" aria-hidden="true"></i></span><span class="state-pill ' + status.className + '">' + status.label + '</span></div>' +
            '<div class="device-identity"><span class="device-code">' + escape(code) + '</span>' + (isNew ? '<span class="new-tag">NUEVO</span>' : '') +
            '<h3 title="' + escape(device.nombre) + '">' + escape(device.nombre || 'Sin nombre') + '</h3><span class="ip-address">' + escape(device.ip) + '</span></div>' +
            '<div class="device-meta"><span><i class="bi bi-geo-alt" aria-hidden="true"></i>' + escape(device.ubicacion || 'Ubicación no registrada') + '</span><span><i class="bi bi-cpu" aria-hidden="true"></i>' + escape(device.sistema_operativo || 'Sistema operativo no registrado') + '</span><span><i class="bi bi-card-text" aria-hidden="true"></i>' + escape(device.observaciones || 'Sin observaciones') + '</span></div>' +
            '<div class="device-card-bottom"><span>' + escape(typeLabels[device.tipo] || device.tipo || 'Dispositivo') + '</span><button class="text-link" type="button" data-device-action="details" data-device-id="' + escape(device.id) + '">Ver dispositivo ↗</button></div>' +
            '</article>';
    }

    function renderDeviceRow(device) {
        var status = statusInfo(device.estado);
        var connectivity = connectivityInfo(device);
        var id = escape(device.id);
        return '<tr><td><span class="device-code">' + escape(deviceCode(device)) + '</span></td>' +
            '<td><span class="device-name">' + escape(device.nombre || 'Sin nombre') + '</span></td>' +
            '<td class="ip-address">' + escape(device.ip) + '</td>' +
            '<td>' + escape(typeLabels[device.tipo] || device.tipo || '—') + '</td>' +
            '<td>' + escape(device.ubicacion || '—') + '</td>' +
            '<td><span class="state-pill ' + status.className + '">' + status.label + '</span></td>' +
            '<td><span class="state-pill ' + connectivity.className + '">' + connectivity.label + '</span><span class="secondary-line">' + escape(connectivity.latency) + '</span></td>' +
            '<td><div class="d-flex flex-wrap gap-2">' +
                '<button class="btn btn-sm btn-light btn-icon device-row-action" type="button" data-device-action="ping" data-device-id="' + id + '" aria-label="Comprobar ping de ' + escape(device.nombre) + '"><i class="bi bi-arrow-repeat"></i>Ping</button>' +
                '<button class="text-link" type="button" data-device-action="edit" data-device-id="' + id + '" aria-label="Editar dispositivo ' + escape(device.nombre) + '"><i class="bi bi-pencil"></i>Editar</button>' +
                '<button class="text-link text-danger" type="button" data-device-action="delete" data-device-id="' + id + '" aria-label="Eliminar dispositivo ' + escape(device.nombre) + '"><i class="bi bi-trash3"></i>Eliminar</button>' +
                '<button class="text-link" type="button" data-device-action="details" data-device-id="' + id + '">Ver</button>' +
            '</div></td></tr>';
    }

    function renderDevices() {
        var visible = visibleDevices();
        var totalText = visible.length + (visible.length === 1 ? ' dispositivo mostrado' : ' dispositivos mostrados');
        document.getElementById('deviceCount').textContent = devices.length + (devices.length === 1 ? ' dispositivo registrado' : ' dispositivos registrados');
        document.getElementById('deviceGrid').innerHTML = visible.length
            ? visible.map(renderDeviceCard).join('')
            : '<div class="empty-state">No se encontraron dispositivos con esos criterios.</div>';
        document.getElementById('deviceListBody').innerHTML = visible.length
            ? visible.map(renderDeviceRow).join('')
            : '<tr><td colspan="8"><div class="empty-state">No se encontraron dispositivos con esos criterios.</div></td></tr>';
        document.getElementById('deviceList').setAttribute('aria-label', totalText);
        document.getElementById('deviceGrid').classList.toggle('d-none', viewMode !== 'grid');
        document.getElementById('deviceList').classList.toggle('d-none', viewMode !== 'list');
    }

    async function loadDevices() {
        try {
            var result = App.unwrap(await App.apiRequest('/api/dispositivos'));
            if (!Array.isArray(result)) throw new Error('El endpoint no devolvió una lista de dispositivos.');
            devices = result;
            previewMode = false;
            document.getElementById('devicePreviewNotice').classList.add('d-none');
        } catch (error) {
            previewMode = true;
            if (!devices.length) devices = sampleDevices.map(function (device) { return Object.assign({}, device, { _preview: true }); });
            showInventoryAlert('Vista previa con datos de ejemplo: no se pudo conectar con el inventario real. Las acciones de demostración no se guardan en la base de datos.', 'warning');
        }
        renderTypeFilter();
        renderDevices();
    }

    function setViewMode(mode) {
        viewMode = mode;
        document.getElementById('gridViewButton').classList.toggle('active', mode === 'grid');
        document.getElementById('listViewButton').classList.toggle('active', mode === 'list');
        document.getElementById('gridViewButton').setAttribute('aria-pressed', mode === 'grid' ? 'true' : 'false');
        document.getElementById('listViewButton').setAttribute('aria-pressed', mode === 'list' ? 'true' : 'false');
        renderDevices();
    }

    function resetForm() {
        var form = document.getElementById('deviceForm');
        form.reset();
        document.getElementById('deviceBackendId').value = '';
        document.getElementById('deviceCode').readOnly = true;
        document.getElementById('deviceCodeLock').classList.add('d-none');
        document.getElementById('deviceCodeHint').textContent = 'El sistema genera un código de 5 caracteres al guardar.';
        document.getElementById('deviceFormAlert').className = 'd-none';
        document.getElementById('ipConflictCard').classList.remove('visible');
        document.getElementById('deviceFormStatus').textContent = 'Completa todos los campos para continuar.';
        clearValidation();
        document.querySelectorAll('#deviceType option[data-dynamic]').forEach(function (option) { option.remove(); });
    }

    function setFormMode(mode, device) {
        resetForm();
        formMode = mode;
        currentDevice = device || null;
        var editing = mode === 'edit' && device;
        document.querySelector('#deviceFormBack span').textContent = editing
            ? 'Volver al detalle de ' + deviceCode(device)
            : 'Volver a dispositivos';
        document.getElementById('deviceFormTitle').textContent = editing ? 'Editar dispositivo' : 'Registrar dispositivo';
        document.getElementById('deviceFormSubtitle').textContent = editing
            ? (device.nombre || 'Dispositivo') + ' · Revisa la información y guarda los cambios.'
            : 'Completa los datos para incorporar el equipo al inventario.';
        document.getElementById('formChecklistTitle').textContent = editing ? 'Antes de guardar' : 'Antes de registrar';
        document.getElementById('formAsideTitle').textContent = editing ? 'Decisión de diseño' : 'Un registro, varias vistas';
        document.getElementById('formAsideText').textContent = editing
            ? 'El código se conserva y no se puede editar.'
            : 'Después del registro, el equipo estará disponible en monitoreo e incidencias.';
        document.getElementById('saveDeviceButton').innerHTML = editing
            ? '<i class="bi bi-check2"></i>Guardar cambios'
            : '<i class="bi bi-plus-lg"></i>Registrar dispositivo';
        if (editing) {
            document.getElementById('deviceBackendId').value = device.id;
            var code = document.getElementById('deviceCode');
            code.value = deviceCode(device);
            code.readOnly = true;
            document.getElementById('deviceCodeLock').classList.remove('d-none');
            document.getElementById('deviceCodeHint').textContent = 'Identificador conservado durante la edición.';
            document.getElementById('deviceName').value = device.nombre || '';
            selectExistingValue('deviceType', device.tipo, typeLabels[device.tipo] || device.tipo);
            document.getElementById('deviceIp').value = device.ip || '';
            document.getElementById('deviceLocation').value = device.ubicacion || '';
            document.getElementById('deviceMac').value = device.mac || '';
            document.getElementById('deviceOs').value = device.sistema_operativo || '';
            document.getElementById('deviceObservations').value = device.observaciones || '';
            document.getElementById('deviceStatus').value = statusInfo(device.estado).formValue;
        }
        showView('deviceFormView');
    }

    function selectExistingValue(id, value, label) {
        var select = document.getElementById(id);
        if (!value) return;
        if (!Array.prototype.some.call(select.options, function (option) { return option.value === String(value); })) {
            var option = new Option(label || value, value);
            option.dataset.dynamic = 'true';
            select.add(option);
        }
        select.value = value;
    }

    function clearValidation() {
        document.querySelectorAll('#deviceForm .is-invalid').forEach(function (field) { field.classList.remove('is-invalid'); });
        document.querySelectorAll('#deviceForm .field-error').forEach(function (error) { error.classList.remove('visible'); });
        document.getElementById('deviceIpIcon').classList.add('d-none');
    }

    function ipIsValid(value) {
        var address = value.trim();
        var octets = address.split('.');
        if (octets.length === 4 && octets.every(function (octet) {
            return /^(0|[1-9]\d{0,2})$/.test(octet) && Number(octet) >= 0 && Number(octet) <= 255;
        })) return true;

        if (address.length > 45 || !/^[0-9a-f:]+$/i.test(address) || address.indexOf(':') === -1) return false;
        var compressed = address.indexOf('::') !== -1;
        if (compressed && address.indexOf('::') !== address.lastIndexOf('::')) return false;
        var sections = address.split('::');
        var groups = [];
        sections.forEach(function (section) {
            if (section) groups = groups.concat(section.split(':'));
        });
        if (!groups.every(function (group) { return group.length > 0 && group.length <= 4; })) return false;
        return compressed ? groups.length < 8 : groups.length === 8;
    }

    function duplicateIp(ip, currentId) {
        var normalized = ip.trim();
        return devices.find(function (device) {
            return String(device.id) !== String(currentId || '') && String(device.ip || '').trim().toLowerCase() === normalized.toLowerCase();
        });
    }

    function showConflict(device) {
        document.getElementById('ipConflictCard').classList.add('visible');
        document.getElementById('conflictCode').textContent = deviceCode(device);
        document.getElementById('conflictName').textContent = device.nombre || 'Dispositivo';
        document.getElementById('conflictIp').textContent = device.ip || '';
        document.getElementById('deviceIp').classList.add('is-invalid');
        document.getElementById('deviceIpIcon').classList.remove('d-none');
        document.querySelector('[data-error-for="deviceIp"]').textContent = 'Esta dirección IP ya está asignada a otro equipo.';
        document.querySelector('[data-error-for="deviceIp"]').classList.add('visible');
    }

    function showFormError(title, detail, type) {
        var host = document.getElementById('deviceFormAlert');
        host.className = 'alert alert-soft-' + (type || 'danger');
        host.innerHTML = '<h2 class="alert-heading"><i class="bi bi-exclamation-circle me-1"></i>' + App.escapeHtml(title) + '</h2><p class="alert-detail">' + App.escapeHtml(detail) + '</p>';
    }

    function validateForm() {
        clearValidation();
        document.getElementById('ipConflictCard').classList.remove('visible');
        var requiredFields = ['deviceName', 'deviceIp', 'deviceType', 'deviceStatus'];
        var invalid = [];
        requiredFields.forEach(function (id) {
            var field = document.getElementById(id);
            var isInvalid = !field.value.trim();
            if (id === 'deviceIp' && field.value.trim() && !ipIsValid(field.value)) isInvalid = true;
            if (isInvalid) {
                field.classList.add('is-invalid');
                var error = document.querySelector('[data-error-for="' + id + '"]');
                if (error) {
                    if (id === 'deviceIp' && field.value.trim()) error.textContent = 'La dirección IPv4 o IPv6 no es válida.';
                    error.classList.add('visible');
                }
                invalid.push(field);
            }
        });

        var conflict = document.getElementById('deviceIp').value.trim()
            ? duplicateIp(document.getElementById('deviceIp').value, document.getElementById('deviceBackendId').value)
            : null;
        if (conflict) {
            showConflict(conflict);
            invalid.push(document.getElementById('deviceIp'));
            showFormError('Esta dirección IP ya está en uso', conflict.ip + ' pertenece a ' + deviceCode(conflict) + ' · ' + (conflict.nombre || 'otro dispositivo') + '. El dispositivo no se registró.', 'danger');
        }

        if (invalid.length && !conflict) {
            var count = invalid.length;
            showFormError('Revisa los ' + count + ' campos señalados', 'Completa los campos obligatorios y corrige el formato de la dirección IP.', 'danger');
        }
        document.getElementById('deviceFormStatus').textContent = invalid.length ? 'Corrige los campos para continuar.' : 'Todos los campos están completos.';
        return invalid.length === 0;
    }

    function formPayload() {
        return {
            dis_nombre: document.getElementById('deviceName').value.trim(),
            dis_ip: document.getElementById('deviceIp').value.trim(),
            dis_tipo: document.getElementById('deviceType').value,
            dis_ubicacion: document.getElementById('deviceLocation').value.trim(),
            dis_mac: document.getElementById('deviceMac').value.trim(),
            dis_sisop: document.getElementById('deviceOs').value.trim(),
            dis_obs: document.getElementById('deviceObservations').value.trim(),
            dis_stat: document.getElementById('deviceStatus').value
        };
    }

    function localPreviewSave(payload, editingId) {
        function asPreviewDevice(id) {
            return {
                id: id,
                codigo: id,
                dis_id: id,
                dis_nombre: payload.dis_nombre,
                nombre: payload.dis_nombre,
                dis_tipo: payload.dis_tipo,
                tipo: payload.dis_tipo,
                dis_ubicacion: payload.dis_ubicacion,
                ubicacion: payload.dis_ubicacion,
                dis_ip: payload.dis_ip,
                ip: payload.dis_ip,
                dis_mac: payload.dis_mac,
                mac: payload.dis_mac,
                dis_sisop: payload.dis_sisop,
                sistema_operativo: payload.dis_sisop,
                dis_obs: payload.dis_obs,
                observaciones: payload.dis_obs,
                dis_stat: payload.dis_stat,
                estado: { A: 'en_linea', I: 'desconocido', F: 'fuera_de_linea', W: 'advertencia' }[payload.dis_stat]
            };
        }

        if (editingId) {
            var existing = devices.find(function (device) { return String(device.id) === String(editingId); });
            if (existing) Object.assign(existing, asPreviewDevice(editingId));
            currentDevice = existing || currentDevice;
        } else {
            var nextId = 'D' + Date.now().toString(36).slice(-4).toUpperCase();
            var newDevice = Object.assign(asPreviewDevice(nextId), { _preview: true });
            devices.unshift(newDevice);
            justCreatedId = nextId;
            currentDevice = newDevice;
        }
        renderTypeFilter();
        renderDevices();
        document.getElementById('deviceForm').reset();
        showInventoryAlert('Vista previa: el cambio se muestra solo durante esta sesión y no se guardó en la base de datos.', 'warning');
        showView('devicesInventoryView');
    }

    async function saveDevice(event) {
        event.preventDefault();
        if (!validateForm()) return;
        var backendId = document.getElementById('deviceBackendId').value;
        var payload = formPayload();
        if (previewMode || (currentDevice && currentDevice._preview)) {
            localPreviewSave(payload, backendId);
            return;
        }
        var button = document.getElementById('saveDeviceButton');
        button.disabled = true;
        try {
            var response = await App.apiRequest(backendId ? '/api/dispositivos/' + encodeURIComponent(backendId) : '/api/dispositivos', {
                method: backendId ? 'PUT' : 'POST',
                body: payload
            });
            var data = App.unwrap(response) || {};
            var savedDevice = data.dispositivo || currentDevice || {};
            if (savedDevice.id != null) justCreatedId = String(savedDevice.id);
            currentDevice = savedDevice;
            await loadDevices();
            if (backendId) {
                openDetails(currentDevice, 'Cambios guardados correctamente.');
            } else {
                resetForm();
                currentDevice = null;
                formMode = 'create';
                showView('devicesInventoryView');
                var code = deviceCode(savedDevice);
                var name = savedDevice.nombre || payload.nombre;
                var ip = savedDevice.ip || payload.ip;
                var banner = document.getElementById('deviceSuccessBanner');
                banner.innerHTML = '<div class="d-flex align-items-start gap-2"><i class="bi bi-check-circle-fill mt-1"></i><div><h2 class="alert-heading">Dispositivo registrado correctamente</h2><p class="alert-detail">' + App.escapeHtml(code + ' · ' + name + ' · ' + ip) + ' ya está en el inventario y disponible para monitoreo, incidencias y mantenimientos.</p><a class="text-link" href="/monitoreo?codigo=' + encodeURIComponent(code) + '">Ver ' + App.escapeHtml(code) + ' en monitoreo →</a></div></div>';
                banner.classList.remove('d-none');
            }
        } catch (error) {
            var message = error.message || 'No se pudo guardar el dispositivo.';
            var conflict = /ip|dirección|direccion/i.test(message) && /uso|asignad|duplicad/i.test(message);
            if (conflict) {
                var found = duplicateIp(payload.ip, backendId);
                if (found) showConflict(found);
                showFormError('Esta dirección IP ya está en uso', message, 'danger');
            } else {
                showFormError('No se guardaron los cambios', message, 'danger');
                notify('No se pudo guardar el dispositivo: ' + message, 'warning');
            }
        } finally {
            button.disabled = false;
        }
    }

    function openDetails(device, successMessage) {
        currentDevice = device;
        var code = deviceCode(device);
        var status = statusInfo(device.estado);
        document.getElementById('detailDeviceName').textContent = device.nombre || 'Dispositivo';
        document.getElementById('detailDeviceSubtitle').textContent = code + ' · Ficha del dispositivo';
        document.getElementById('deviceDetailSuccess').classList.toggle('d-none', !successMessage);
        document.getElementById('deviceDetailSuccess').innerHTML = successMessage
            ? '<i class="bi bi-check-circle me-1"></i><strong>Cambios guardados correctamente</strong><p class="alert-detail mb-0">' + App.escapeHtml('La información de ' + code + ' se actualizó en el inventario.') + '</p>'
            : '';
        var fields = [
            ['Código', code], ['Nombre', device.nombre], ['Tipo de dispositivo', typeLabels[device.tipo] || device.tipo],
            ['Dirección IP', device.ip], ['Ubicación', device.ubicacion], ['Dirección MAC', device.mac], ['Sistema operativo', device.sistema_operativo],
            ['Observaciones', device.observaciones], ['Fecha de ingreso', device.dis_feing], ['Última modificación', device.dis_femod]
        ];
        document.getElementById('deviceDetailFields').innerHTML = fields.map(function (field) {
            return '<div class="detail-item"><dt>' + App.escapeHtml(field[0]) + '</dt><dd class="' + (field[0] === 'Código' || field[0] === 'Dirección IPv4' ? 'mono' : '') + '">' + escape(field[1]) + '</dd></div>';
        }).join('');
        document.getElementById('detailDeviceStatus').innerHTML = '<span class="state-pill ' + status.className + '">' + status.label + '</span>';
        document.getElementById('detailDeviceIp').textContent = device.ip || '—';
        var monitorUrl = '/monitoreo?codigo=' + encodeURIComponent(code);
        document.getElementById('monitorDeviceLink').href = monitorUrl;
        document.getElementById('detailMonitorAction').href = monitorUrl;
        document.getElementById('detailMonitorState').textContent = 'Estado operativo: ' + status.label;
        document.getElementById('detailIncidentsAction').href = '/incidencias?dispositivo=' + encodeURIComponent(device.id);
        document.getElementById('deviceLastMovement').textContent = device.dis_femod
            ? 'Última modificación de ' + code + ': ' + App.formatDate(device.dis_femod) + '.'
            : 'Alta de ' + code + ' · ' + (device.nombre || 'Dispositivo') + ' incorporado al inventario con la IP ' + (device.ip || 'no registrada') + '.';
        document.getElementById('detailIncidentCount').textContent = 'Consulta las incidencias asociadas.';
        showView('deviceDetailView');
        if (!device._preview && device.id != null) {
            App.apiRequest('/api/dispositivos/' + encodeURIComponent(device.id) + '/incidencias').then(function (response) {
                var rows = App.unwrap(response);
                document.getElementById('detailIncidentCount').textContent = Array.isArray(rows)
                    ? (rows.length ? rows.length + (rows.length === 1 ? ' incidencia asociada' : ' incidencias asociadas') : 'Sin incidencias asociadas')
                    : 'Consulta las incidencias asociadas.';
            }).catch(function () {
                document.getElementById('detailIncidentCount').textContent = 'Incidencias no disponibles.';
            });
        } else if (device._preview) {
            document.getElementById('detailIncidentCount').textContent = 'Datos de ejemplo';
        }
    }

    function currentIsPreview() {
        return Boolean(currentDevice && currentDevice._preview);
    }

    async function handleDelete() {
        if (!currentDevice || !window.confirm('¿Eliminar ' + deviceCode(currentDevice) + ' · ' + currentDevice.nombre + '?')) return;
        if (currentIsPreview()) {
            devices = devices.filter(function (device) { return String(device.id) !== String(currentDevice.id); });
            currentDevice = null;
            renderTypeFilter();
            renderDevices();
            showInventoryAlert('Vista previa: el dispositivo se quitó de la lista de demostración, sin modificar la base de datos.', 'warning');
            showView('devicesInventoryView');
            return;
        }
        try {
            await App.apiRequest('/api/dispositivos/' + encodeURIComponent(currentDevice.id), { method: 'DELETE' });
            currentDevice = null;
            await loadDevices();
            showInventoryAlert('Dispositivo eliminado correctamente.', 'success');
            showView('devicesInventoryView');
        } catch (error) {
            notify('No se pudo eliminar el dispositivo: ' + error.message, 'danger');
        }
    }

    async function pingDevice(button, device) {
        if (previewMode || device._preview) {
            notify('Vista previa: no se ejecutó una comprobación real del dispositivo.', 'warning');
            return;
        }
        var originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Comprobando</span>';
        try {
            var result = App.unwrap(await App.apiRequest('/api/monitoreo/ping/' + encodeURIComponent(device.id), { method: 'POST' }));
            monitorStates[String(device.id)] = {
                estado: result.estado || 'desconocido',
                latencia_ms: result.latencia_ms == null ? null : result.latencia_ms
            };
            if (result.estado) device.estado = result.estado;
            renderDevices();
            notify('Ping de ' + deviceCode(device) + ': ' + (result.latencia_ms == null ? 'sin respuesta' : result.latencia_ms + ' ms') + '.', result.estado === 'en_linea' ? 'success' : 'warning');
        } catch (error) {
            notify('No se pudo comprobar el dispositivo: ' + error.message, 'danger');
        } finally {
            if (button.isConnected) {
                button.disabled = false;
                button.innerHTML = originalContent;
            }
        }
    }

    async function pingAllDevices(button) {
        if (previewMode) {
            notify('Vista previa: no se ejecutó un escaneo real de la red.', 'warning');
            return;
        }
        var originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Escaneando…';
        try {
            var result = App.unwrap(await App.apiRequest('/api/monitoreo/ping-all', { method: 'POST' }));
            (result.dispositivos || []).forEach(function (item) {
                monitorStates[String(item.dis_id || item.dispositivo_id)] = {
                    estado: item.estado || 'desconocido',
                    latencia_ms: item.latencia_ms == null ? null : item.latencia_ms
                };
            });
            await loadDevices();
            notify('Escaneo completado: ' + (result.total_verificados || 0) + ' dispositivos verificados.', 'success');
        } catch (error) {
            notify('No se pudo completar el escaneo de la red: ' + error.message, 'danger');
        } finally {
            button.disabled = false;
            button.innerHTML = originalContent;
        }
    }

    async function showHistory() {
        if (!currentDevice) return;
        if (currentDevice._preview) {
            notify('Historial de ejemplo: la API no está disponible en este momento.', 'warning');
            return;
        }
        try {
            var result = App.unwrap(await App.apiRequest('/api/monitoreo/dispositivo/' + encodeURIComponent(currentDevice.id)));
            var rows = Array.isArray(result) ? result : [];
            var historyText = rows.length
                ? rows.slice(0, 3).map(function (row) { return (row.estado || 'Comprobación') + ' · ' + App.formatDate(row.fecha_monitoreo) + (row.latencia_ms == null ? '' : ' · ' + row.latencia_ms + ' ms'); }).join(' | ')
                : 'No hay registros de monitoreo.';
            document.getElementById('deviceLastMovement').textContent = historyText;
        } catch (error) {
            notify('No se pudo cargar el historial: ' + error.message, 'warning');
        }
    }

    function handleAction(event) {
        var button = event.target.closest('[data-device-action]');
        if (!button) return;
        var action = button.dataset.deviceAction;
        if (action === 'back-inventory' || action === 'cancel-form') {
            if (formMode === 'edit' && currentDevice) openDetails(currentDevice);
            else showView('devicesInventoryView');
            return;
        }
        if (action === 'delete-current') {
            handleDelete();
            return;
        }
        if (action === 'delete') {
            currentDevice = devices.find(function (item) { return String(item.id) === String(button.dataset.deviceId); }) || null;
            handleDelete();
            return;
        }
        if (action === 'ping') {
            var pingTarget = devices.find(function (item) { return String(item.id) === String(button.dataset.deviceId); });
            if (pingTarget) pingDevice(button, pingTarget);
            return;
        }
        if (action === 'edit-current') {
            if (currentDevice) setFormMode('edit', currentDevice);
            return;
        }
        if (action === 'show-history') {
            showHistory();
            return;
        }
        var device = devices.find(function (item) { return String(item.id) === String(button.dataset.deviceId); });
        if (!device) return;
        if (action === 'details') openDetails(device);
        if (action === 'edit') setFormMode('edit', device);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('createDeviceButton').addEventListener('click', function () {
            setFormMode('create');
        });
        document.getElementById('deviceForm').addEventListener('submit', saveDevice);
        document.getElementById('deviceSearch').addEventListener('input', renderDevices);
        document.getElementById('deviceTypeFilter').addEventListener('change', renderDevices);
        document.getElementById('gridViewButton').addEventListener('click', function () { setViewMode('grid'); });
        document.getElementById('listViewButton').addEventListener('click', function () { setViewMode('list'); });
        document.getElementById('pingAllDevicesButton').addEventListener('click', function (event) { pingAllDevices(event.currentTarget); });
        document.getElementById('deviceIp').addEventListener('input', function () {
            this.classList.remove('is-invalid');
            document.getElementById('ipConflictCard').classList.remove('visible');
            document.querySelector('[data-error-for="deviceIp"]').classList.remove('visible');
            var existing = this.value.trim() && ipIsValid(this.value) ? duplicateIp(this.value, document.getElementById('deviceBackendId').value) : null;
            if (existing) showConflict(existing);
        });
        document.addEventListener('click', handleAction);
        loadDevices();

        var requestedCode = new URLSearchParams(window.location.search).get('codigo');
        if (requestedCode) document.getElementById('deviceSearch').value = requestedCode;
    });
}());
