<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\Api\DispositivoApiController;
use App\Controllers\Api\MonitoreoApiController;
use App\Controllers\Api\IncidenciaApiController;
use Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    // Diagnóstico de salud del servicio y de la conexión a la base de datos
    $app->get('/api/health', function (Request $request, Response $response) {
        $dbStatus = Database::testConnection();
        $payload = json_encode([
            'status' => 'online',
            'timestamp' => date('c'),
            'database' => $dbStatus
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Grupo de endpoints REST de la API
    $app->group('/api', function (RouteCollectorProxy $group) {

        // ==========================================================
        // HISTORIA DE USUARIO 1 y 2: Registrar y Actualizar Dispositivos
        // ==========================================================
        $group->group('/dispositivos', function (RouteCollectorProxy $dispGroup) {
            $dispGroup->get('', [DispositivoApiController::class, 'index']);                     // Listar todos
            $dispGroup->get('/metricas', [DispositivoApiController::class, 'metrics']);          // Métricas de estados
            $dispGroup->get('/{id:[A-Za-z0-9]+}', [DispositivoApiController::class, 'show']);    // Obtener por ID
            $dispGroup->post('', [DispositivoApiController::class, 'create']);                   // HU1: Registrar dispositivo
            $dispGroup->put('/{id:[A-Za-z0-9]+}', [DispositivoApiController::class, 'update']);  // HU2: Actualizar dispositivo
            $dispGroup->delete('/{id:[A-Za-z0-9]+}', [DispositivoApiController::class, 'delete']); // Eliminar dispositivo
            $dispGroup->get('/{id:[A-Za-z0-9]+}/incidencias', [IncidenciaApiController::class, 'porDispositivo']); // Incidencias de este equipo
        });

        // ==========================================================
        // HISTORIA DE USUARIO 3: Monitoreo en Tiempo Real
        // ==========================================
        $group->group('/monitoreo', function (RouteCollectorProxy $monGroup) {
            $monGroup->post('/ping-all', [MonitoreoApiController::class, 'pingAll']);             // HU3: Ping a toda la red
            $monGroup->post('/ping/{id:[A-Za-z0-9]+}', [MonitoreoApiController::class, 'ping']);   // HU3: Ping a un equipo
            $monGroup->get('/historial', [MonitoreoApiController::class, 'historial']);            // Historial general reciente
            $monGroup->get('/dispositivo/{id:[A-Za-z0-9]+}', [MonitoreoApiController::class, 'historialDispositivo']); // Historial por equipo
        });

        // ==========================================================
        // HISTORIA DE USUARIO 4: Registro de Incidencias
        // ==========================================================
        $group->group('/incidencias', function (RouteCollectorProxy $incGroup) {
            $incGroup->get('', [IncidenciaApiController::class, 'index']);                         // Listar incidencias
            $incGroup->get('/metricas', [IncidenciaApiController::class, 'metrics']);              // Resumen de incidencias
            $incGroup->get('/{id:[A-Za-z0-9]+}', [IncidenciaApiController::class, 'show']);       // Detalle de una incidencia
            $incGroup->post('', [IncidenciaApiController::class, 'create']);                        // HU4: Registrar incidencia
            $incGroup->patch('/{id:[A-Za-z0-9]+}/estado', [IncidenciaApiController::class, 'cambiarEstado']); // Actualizar estado (abierta, en proceso, resuelta)
        });
    });
};
