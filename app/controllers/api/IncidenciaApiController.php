<?php

namespace App\Controllers\Api;

use App\Services\IncidenciaService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class IncidenciaApiController extends BaseApiController
{
    private IncidenciaService $service;

    public function __construct(?IncidenciaService $service = null)
    {
        $this->service = $service ?? new IncidenciaService();
    }

    /**
     * Listar todas las incidencias
     * GET /api/incidencias
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $incidencias = $this->service->obtenerTodas();
            return $this->json($response, $incidencias);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * Obtener una incidencia por ID
     * GET /api/incidencias/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $incidencia = $this->service->obtenerPorId($id);
            return $this->json($response, $incidencia);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 404);
        }
    }

    /**
     * HU4: Registro de Incidencias
     * POST /api/incidencias
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = (array)($request->getParsedBody() ?? []);
            $nueva = $this->service->registrar($data);
            return $this->json($response, [
                'mensaje' => 'Incidencia registrada con éxito.',
                'incidencia' => $nueva
            ], 201);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 422);
        }
    }

    /**
     * Actualizar estado de una incidencia
     * PATCH /api/incidencias/{id}/estado
     */
    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $body = (array)($request->getParsedBody() ?? []);
            $nuevoEstado = $body['incid_stat'] ?? '';
            $actualizada = $this->service->actualizarEstado($id, $nuevoEstado);

            return $this->json($response, [
                'mensaje' => 'Estado de incidencia actualizado.',
                'incidencia' => $actualizada
            ], 200);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 422);
        }
    }

    /**
     * Obtener incidencias asociadas a un dispositivo
     * GET /api/dispositivos/{id}/incidencias
     */
    public function porDispositivo(Request $request, Response $response, array $args): Response
    {
        try {
            $dispositivoId = $args['id'];
            $incidencias = $this->service->obtenerPorDispositivo($dispositivoId);
            return $this->json($response, $incidencias);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * Métricas de incidencias (abiertas, en proceso, resueltas)
     * GET /api/incidencias/metricas
     */
    public function metrics(Request $request, Response $response): Response
    {
        try {
            $metricas = $this->service->obtenerMetricas();
            return $this->json($response, $metricas);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 500);
        }
    }
}
