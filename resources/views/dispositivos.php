<?php
$titulo = $titulo ?? 'Dispositivos · Optisys';
$pageScript = '/assets/js/dispositivos.js';
require __DIR__ . '/layout/header.php';
?>
<section id="devicesInventoryView" class="flow-view">
    <header class="page-heading">
        <div>
            <h1>Dispositivos</h1>
            <p>Tu infraestructura de red, organizada en un solo lugar.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-light btn-icon" type="button" id="pingAllDevicesButton"><i class="bi bi-arrow-repeat"></i>Escanear toda la red</button>
            <button class="btn btn-primary btn-icon" type="button" id="createDeviceButton"><i class="bi bi-plus-lg"></i>Registrar dispositivo</button>
        </div>
    </header>

    <div id="deviceSuccessBanner" class="alert alert-soft-success d-none" role="status" aria-live="polite"></div>
    <div id="devicePreviewNotice" class="alert alert-soft-warning d-none" role="status"></div>

    <section class="device-toolbar-card" aria-label="Buscar y filtrar dispositivos">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6 col-xl-5">
                <label class="device-toolbar-label" for="deviceSearch">Buscar en el inventario</label>
                <div class="search-control"><i class="bi bi-search" aria-hidden="true"></i><input id="deviceSearch" class="form-control" type="search" placeholder="Código, nombre o dirección IP" autocomplete="off"></div>
            </div>
            <div class="col-8 col-md-4 col-xl-3">
                <label class="device-toolbar-label" for="deviceTypeFilter">Tipo de dispositivo</label>
                <select id="deviceTypeFilter" class="form-select"><option value="">Todos los tipos</option></select>
            </div>
            <div class="col-4 col-md-2 col-xl-4 d-flex justify-content-end">
                <div class="view-toggle" role="group" aria-label="Tipo de vista">
                    <button class="active" type="button" id="gridViewButton" aria-label="Vista cuadrícula" aria-pressed="true"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                    <button type="button" id="listViewButton" aria-label="Vista lista" aria-pressed="false"><i class="bi bi-list-ul"></i></button>
                </div>
            </div>
        </div>
    </section>

    <div class="inventory-summary">
        <strong id="deviceCount">Cargando dispositivos…</strong>
        <div class="status-legend"><span class="legend-label">Estados:</span><span class="state-pill state-active">Activo</span><span class="state-pill state-inactive">Inactivo</span><span class="state-pill state-failure">En falla</span></div>
    </div>

    <div id="deviceGrid" class="device-grid" aria-live="polite">
        <div class="loading-state"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Cargando inventario…</div>
    </div>
    <div id="deviceList" class="device-list-wrap d-none">
        <div class="table-responsive">
            <table class="table app-table">
                <thead><tr><th>ID</th><th>NOMBRE</th><th>IP</th><th>TIPO</th><th>UBICACIÓN</th><th>ESTADO</th><th>CONECTIVIDAD</th><th>ACCIONES</th></tr></thead>
                <tbody id="deviceListBody"></tbody>
            </table>
        </div>
    </div>
</section>

<section id="deviceFormView" class="flow-view d-none">
    <button class="page-back" id="deviceFormBack" type="button" data-device-action="back-inventory"><i class="bi bi-arrow-left"></i><span>Volver a dispositivos</span></button>
    <header class="page-heading">
        <div><h1 id="deviceFormTitle">Registrar dispositivo</h1><p id="deviceFormSubtitle">Completa los datos para incorporar el equipo al inventario.</p></div>
    </header>
    <div id="deviceFormAlert" class="d-none" role="alert" aria-live="polite"></div>

    <form id="deviceForm" novalidate>
        <input type="hidden" id="deviceBackendId">
        <div class="flow-form-layout">
            <div class="flow-form-main">
                <section class="form-section">
                    <h2>Identificación y red</h2>
                    <p>Los campos con * son obligatorios.</p>
                    <div class="section-fields">
                        <div class="field">
                            <label class="form-label" for="deviceCode">Código</label>
                            <div class="input-icon-wrap"><input id="deviceCode" class="form-control code-input" maxlength="5" placeholder="Se asigna al guardar" readonly><i class="bi bi-lock" id="deviceCodeLock" aria-hidden="true"></i></div>
                            <small class="form-hint" id="deviceCodeHint">El sistema genera un código de 5 caracteres al guardar.</small>
                        </div>
                        <div class="field">
                            <label class="form-label" for="deviceName">Nombre / identificador *</label>
                            <input id="deviceName" class="form-control" maxlength="100" placeholder="Ej. Punto de acceso — Sala 3" required>
                            <small class="field-error" data-error-for="deviceName">Ingresa el nombre del dispositivo.</small>
                        </div>
                        <div class="field">
                            <label class="form-label" for="deviceIp">Dirección IP *</label>
                            <div class="input-icon-wrap"><input id="deviceIp" class="form-control mono" maxlength="50" placeholder="192.168.1.21" autocomplete="off" required><i class="bi bi-exclamation-circle d-none" id="deviceIpIcon" aria-hidden="true"></i></div>
                            <small class="form-hint" id="deviceIpHint">IPv4 o IPv6. Cada dirección debe ser única.</small>
                            <small class="field-error" data-error-for="deviceIp">Ingresa una dirección IP válida.</small>
                        </div>
                        <div class="field">
                            <label class="form-label" for="deviceType">Tipo de dispositivo *</label>
                            <select id="deviceType" class="form-select" required>
                                <option value="AP">Punto de acceso</option><option value="RT">Router</option><option value="SW">Switch</option><option value="FW">Firewall</option><option value="SV">Servidor</option><option value="PC">Host / PC</option><option value="OT">Otro</option>
                            </select>
                            <small class="field-error" data-error-for="deviceType">Selecciona un tipo de dispositivo.</small>
                        </div>
                        <div class="field field-span-2">
                            <label class="form-label" for="deviceLocation">Ubicación</label>
                            <input id="deviceLocation" class="form-control" maxlength="120" placeholder="Ej. Laboratorio · Rack 2">
                        </div>
                    </div>
                </section>
                <section class="form-section">
                    <h2>Información del dispositivo</h2>
                    <p>Completa los campos definidos para el inventario.</p>
                    <div class="section-fields">
                        <div class="field">
                            <label class="form-label" for="deviceMac">Dirección MAC</label>
                            <input id="deviceMac" class="form-control mono" maxlength="20" placeholder="00:1A:2B:3C:4D:5E">
                        </div>
                        <div class="field">
                            <label class="form-label" for="deviceOs">Sistema operativo</label>
                            <input id="deviceOs" class="form-control" maxlength="100" placeholder="Ej. RouterOS 7">
                        </div>
                        <div class="field">
                            <label class="form-label" for="deviceObservations">Observaciones</label>
                            <input id="deviceObservations" class="form-control" maxlength="250" placeholder="Notas del dispositivo">
                        </div>
                        <div class="field">
                            <label class="form-label" for="deviceStatus">Estado operativo *</label>
                            <select id="deviceStatus" class="form-select" required><option value="A">Activo</option><option value="I">Inactivo</option><option value="F">En falla</option><option value="W">Advertencia</option></select>
                            <small class="form-hint">Códigos del inventario: A, I, F o W.</small>
                        </div>
                    </div>
                </section>
                <div class="form-footer">
                    <span class="form-footer-note" id="deviceFormStatus">Completa todos los campos para continuar.</span>
                    <div class="form-footer-actions">
                        <button class="btn btn-light" type="button" data-device-action="cancel-form">Cancelar</button>
                        <button class="btn btn-primary btn-icon" type="submit" id="saveDeviceButton"><i class="bi bi-plus-lg"></i>Registrar dispositivo</button>
                    </div>
                </div>
            </div>
            <aside class="flow-form-aside">
                <section class="help-card">
                    <h2><i class="bi bi-clipboard2-check me-1 text-primary"></i><span id="formChecklistTitle">Antes de registrar</span></h2>
                    <p>Cada equipo necesita un código y una IP únicos dentro del inventario.</p>
                    <ul class="check-list"><li><i class="bi bi-check2"></i>Completa todos los campos.</li><li><i class="bi bi-check2"></i>Usa una dirección IPv4 válida.</li><li><i class="bi bi-check2"></i>Verifica que la IP esté disponible.</li></ul>
                </section>
                <section class="help-card blue-help">
                    <h2><i class="bi bi-diagram-3 me-1"></i><span id="formAsideTitle">Un registro, varias vistas</span></h2>
                    <p id="formAsideText">Después del registro, el equipo estará disponible en monitoreo, incidencias y mantenimientos.</p>
                </section>
                <section id="ipConflictCard" class="ip-conflict-card" aria-live="polite">
                    <div class="conflict-label">IP OCUPADA</div>
                    <span class="conflict-code mono" id="conflictCode"></span>
                    <div class="conflict-card-title" id="conflictName"></div>
                    <div class="conflict-ip mono" id="conflictIp"></div>
                    <p class="form-hint mb-0 mt-2">No se permite compartir una IP entre dispositivos.</p>
                </section>
            </aside>
        </div>
    </form>
</section>

<section id="deviceDetailView" class="flow-view d-none">
    <button class="page-back" type="button" data-device-action="back-inventory"><i class="bi bi-arrow-left"></i>Volver a dispositivos</button>
    <header class="page-heading">
        <div><h1 id="detailDeviceName">Dispositivo</h1><p id="detailDeviceSubtitle" class="mono"></p></div>
        <div class="d-flex gap-2">
            <button class="btn btn-light btn-icon" type="button" data-device-action="delete-current"><i class="bi bi-trash3"></i><span class="d-none d-sm-inline">Eliminar</span></button>
            <button class="btn btn-primary btn-icon" type="button" data-device-action="edit-current"><i class="bi bi-pencil"></i>Editar dispositivo</button>
        </div>
    </header>
    <div id="deviceDetailSuccess" class="alert alert-soft-success d-none" role="status"></div>
    <section class="detail-overview">
        <article class="detail-card">
            <h2>Información del dispositivo</h2><p class="form-hint">Identificación, conectividad y asignación del equipo.</p>
            <dl class="detail-grid" id="deviceDetailFields"></dl>
        </article>
        <article class="detail-card detail-state-card">
            <h2><i class="bi bi-activity me-1 text-primary"></i>Estado operativo</h2>
            <div class="mt-2" id="detailDeviceStatus"></div>
            <p class="form-hint mb-0 mt-3">IP consultada</p><span id="detailDeviceIp" class="ip-address"></span>
            <a class="btn btn-light btn-icon" id="monitorDeviceLink" href="/monitoreo"><i class="bi bi-arrow-up-right"></i>Ver en monitoreo</a>
        </article>
    </section>
    <section class="mt-4">
        <h2 class="h6 fw-semibold mb-0">Gestión del dispositivo</h2>
        <div class="management-grid">
            <article class="management-card"><i class="bi bi-activity"></i><h3>Monitoreo</h3><p id="detailMonitorState">Estado operativo: —</p><a id="detailMonitorAction" class="text-link" href="/monitoreo">Consultar estado →</a></article>
            <article class="management-card"><i class="bi bi-exclamation-triangle"></i><h3>Incidencias</h3><p id="detailIncidentCount">Consulta las incidencias asociadas.</p><a id="detailIncidentsAction" class="text-link" href="/incidencias">Ver incidencias →</a></article>
            <article class="management-card"><i class="bi bi-wrench-adjustable"></i><h3>Mantenimientos</h3><p>Sin tareas programadas</p><span class="text-link text-secondary">Ver mantenimientos →</span></article>
            <article class="management-card"><i class="bi bi-clock-history"></i><h3>Historial</h3><p>Último movimiento del equipo</p><button class="text-link" type="button" data-device-action="show-history">Ver historial →</button></article>
        </div>
    </section>
    <article class="movement-card">
        <h3><i class="bi bi-clock-history"></i>Último movimiento</h3>
        <p id="deviceLastMovement">No hay movimientos recientes registrados.</p>
        <small>Los registros mostrados dependen de la información disponible en la API.</small>
    </article>
</section>
<?php require __DIR__ . '/layout/footer.php'; ?>
