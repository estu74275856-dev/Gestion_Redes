<?php

namespace App\Services;

use App\Repositories\DispositivoRepository;
use App\Repositories\IncidenciaRepository;
use App\support\SchemaCodes;
use InvalidArgumentException;

class IncidenciaService
{
    private IncidenciaRepository $incidenciaRepo;
    private DispositivoRepository $dispositivoRepo;

    public function __construct(
        ?IncidenciaRepository $incidenciaRepo = null,
        ?DispositivoRepository $dispositivoRepo = null
    ) {
        $this->incidenciaRepo = $incidenciaRepo ?? new IncidenciaRepository();
        $this->dispositivoRepo = $dispositivoRepo ?? new DispositivoRepository();
    }

    public function obtenerTodas(): array
    {
        return $this->incidenciaRepo->findAll();
    }

    public function obtenerPorId(string $id): ?array
    {
        $incidencia = $this->incidenciaRepo->findById($id);
        if (!$incidencia) {
            throw new InvalidArgumentException("La incidencia con ID {$id} no fue encontrada.");
        }
        return $incidencia;
    }

    public function obtenerPorDispositivo(string $dispositivoId): array
    {
        return $this->incidenciaRepo->findPorDispositivo($dispositivoId);
    }

    /**
     * HU4: Registro de Incidencias
     */
    public function registrar(array $data): array
    {
        if (empty($data['dis_id'])) {
            throw new InvalidArgumentException("Debe seleccionar un dispositivo afectado.");
        }

        $dispId = trim((string)$data['dis_id']);
        $dispositivo = $this->dispositivoRepo->findById($dispId);
        if (!$dispositivo) {
            throw new InvalidArgumentException("El dispositivo seleccionado (ID: {$dispId}) no existe.");
        }

        $data['incid_titulo'] = trim($data['incid_titulo'] ?? '');
        $data['incid_desc'] = trim($data['incid_desc'] ?? '');
        if ($data['incid_titulo'] === '') {
            throw new InvalidArgumentException("El título de la incidencia es obligatorio.");
        }

        if ($data['incid_desc'] === '') {
            throw new InvalidArgumentException("La descripción de la incidencia es obligatoria.");
        }

        $data['dis_id'] = $dispId;
        $data['incid_tipo'] = SchemaCodes::incidentType($data['incid_tipo'] ?? 'OT');
        $data['incid_prioridad'] = SchemaCodes::incidentPriorityCode($data['incid_prioridad'] ?? 'MD');
        $data['incid_stat'] = SchemaCodes::incidentStatusCode($data['incid_stat'] ?? 'A');
        $data['incid_origen'] = SchemaCodes::incidentOrigin($data['incid_origen'] ?? 'U');

        $id = $this->incidenciaRepo->create($data);
        return $this->incidenciaRepo->findById($id);
    }

    public function actualizarEstado(string $id, string $estado): array
    {
        $this->obtenerPorId($id);

        $estado = SchemaCodes::incidentStatusCode($estado);

        $this->incidenciaRepo->cambiarEstado($id, $estado);
        return $this->incidenciaRepo->findById($id);
    }

    public function obtenerMetricas(): array
    {
        return $this->incidenciaRepo->getMetricas();
    }
}
