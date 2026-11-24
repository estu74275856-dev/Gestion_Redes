<?php

namespace App\Controllers\Api;

use App\Services\MonitoreoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class MonitoreoApiController extends BaseApiController
{
    private MonitoreoService $service;

    public function __construct(?MonitoreoService $service = null)
    {
        $this->service = $service ?? new MonitoreoService();
    }

    /**
     * HU3: Monitoreo en tiempo real de todos los dispositivos
     * POST /api/monitoreo/ping-all
     */
    public function pingAll(Request $request, Response $response): Response
    {
        try {
            $resultado = $this->service->monitorearTodos();
            return $this->json($response, $resultado);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * HU3: Monitoreo en tiempo real de un dispositivo individual (Ping manual o bajo demanda)
     * POST /api/monitoreo/ping/{id}
     */
    public function ping(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $resultado = $this->service->pingDispositivo($id);
            return $this->json($response, $resultado);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * Historial general de monitoreo reciente
     * GET /api/monitoreo/historial
     */
    public function historial(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            $historial = $this->service->obtenerUltimosMonitoreos($limit);
            return $this->json($response, $historial);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * Historial de monitoreo para un dispositivo específico
     * GET /api/monitoreo/dispositivo/{id}
     */
    public function historialDispositivo(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $historial = $this->service->obtenerHistorialDispositivo($id);
            return $this->json($response, $historial);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 400);
        }
    }
}
