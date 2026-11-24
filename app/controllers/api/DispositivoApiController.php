<?php

namespace App\Controllers\Api;

use App\Services\DispositivoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class DispositivoApiController extends BaseApiController
{
    private DispositivoService $service;

    public function __construct(?DispositivoService $service = null)
    {
        $this->service = $service ?? new DispositivoService();
    }

    /**
     * Listar todos los dispositivos
     * GET /api/dispositivos
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $dispositivos = $this->service->obtenerTodos();
            return $this->json($response, $dispositivos);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * Obtener un dispositivo por ID
     * GET /api/dispositivos/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $dispositivo = $this->service->obtenerPorId($id);
            return $this->json($response, $dispositivo);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 404);
        }
    }

    /**
     * HU1: Registrar Dispositivo
     * POST /api/dispositivos
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = (array)($request->getParsedBody() ?? []);
            $nuevo = $this->service->registrar($data);
            return $this->json($response, [
                'mensaje' => 'Dispositivo registrado con éxito.',
                'dispositivo' => $nuevo
            ], 201);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 422);
        }
    }

    /**
     * HU2: Actualizar Dispositivo
     * PUT /api/dispositivos/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $data = (array)($request->getParsedBody() ?? []);
            $actualizado = $this->service->actualizar($id, $data);
            return $this->json($response, [
                'mensaje' => 'Dispositivo actualizado con éxito.',
                'dispositivo' => $actualizado
            ], 200);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 422);
        }
    }

    /**
     * Eliminar Dispositivo
     * DELETE /api/dispositivos/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'];
            $this->service->eliminar($id);
            return $this->json($response, [
                'mensaje' => "Dispositivo con ID {$id} eliminado correctamente."
            ], 200);
        } catch (Throwable $e) {
            return $this->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * Obtener métricas rápidas de dispositivos
     * GET /api/dispositivos/metricas
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
