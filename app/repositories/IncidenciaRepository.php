<?php

namespace App\Repositories;

use Config\Database;
use App\support\IdGenerator;
use App\support\SchemaCodes;
use PDO;

class IncidenciaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT i.*, i.incid_id AS id, i.dis_id AS dispositivo_id,
                       i.incid_titulo AS titulo, i.incid_desc AS descripcion,
                       i.incid_prioridad AS prioridad,
                       i.incid_stat AS estado_codigo,
                       i.incid_origen AS origen,
                       i.incid_feing AS fecha_reporte, i.incid_fesol AS fecha_cierre,
                       d.dis_nombre AS dispositivo_nombre, d.dis_ip AS dispositivo_ip,
                       d.dis_tipo AS dispositivo_tipo
                FROM incidencias i 
                INNER JOIN dispositivos d ON d.dis_id = i.dis_id
                ORDER BY i.incid_feing DESC";
        $stmt = $this->db->query($sql);
        return array_map([$this, 'mapIncident'], $stmt->fetchAll());
    }

    public function findById(string $id): ?array
    {
        $sql = "SELECT i.*, i.incid_id AS id, i.dis_id AS dispositivo_id,
                       i.incid_titulo AS titulo, i.incid_desc AS descripcion,
                       i.incid_prioridad AS prioridad,
                       i.incid_stat AS estado_codigo,
                       i.incid_origen AS origen,
                       i.incid_feing AS fecha_reporte, i.incid_fesol AS fecha_cierre,
                       d.dis_nombre AS dispositivo_nombre, d.dis_ip AS dispositivo_ip,
                       d.dis_tipo AS dispositivo_tipo
                FROM incidencias i 
                INNER JOIN dispositivos d ON d.dis_id = i.dis_id
                WHERE i.incid_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ? $this->mapIncident($result) : null;
    }

    public function findPorDispositivo(string $dispositivoId): array
    {
        $sql = "SELECT i.*, i.incid_id AS id, i.dis_id AS dispositivo_id,
                       i.incid_titulo AS titulo, i.incid_desc AS descripcion,
                       i.incid_prioridad AS prioridad,
                       i.incid_stat AS estado_codigo,
                       i.incid_origen AS origen,
                       i.incid_feing AS fecha_reporte, i.incid_fesol AS fecha_cierre
                FROM incidencias i
                WHERE i.dis_id = :dispositivo_id
                ORDER BY i.incid_feing DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dispositivo_id' => $dispositivoId]);
        return array_map([$this, 'mapIncident'], $stmt->fetchAll());
    }

    public function create(array $data): string
    {
        $id = IdGenerator::generateUnique('I', 10, function (string $candidate): bool {
            $stmt = $this->db->prepare('SELECT 1 FROM incidencias WHERE incid_id = :id');
            $stmt->execute(['id' => $candidate]);
            return $stmt->fetchColumn() !== false;
        });
        $sql = "INSERT INTO incidencias
                    (incid_id, dis_id, incid_titulo, incid_desc, incid_tipo,
                     incid_prioridad, incid_stat, incid_origen)
                VALUES
                    (:incid_id, :dis_id, :incid_titulo, :incid_desc, :incid_tipo,
                     :incid_prioridad, :incid_stat, :incid_origen)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'incid_id' => $id,
            'dis_id' => $data['dis_id'],
            'incid_titulo' => $data['incid_titulo'],
            'incid_desc' => $data['incid_desc'],
            'incid_tipo' => $data['incid_tipo'],
            'incid_prioridad' => SchemaCodes::incidentPriorityCode($data['incid_prioridad']),
            'incid_stat' => SchemaCodes::incidentStatusCode($data['incid_stat']),
            'incid_origen' => SchemaCodes::incidentOrigin($data['incid_origen']),
        ]);

        return $id;
    }

    public function update(string $id, array $data): bool
    {
        $sql = "UPDATE incidencias
                SET incid_titulo = :incid_titulo,
                    incid_desc = :incid_desc,
                    incid_tipo = :incid_tipo,
                    incid_prioridad = :incid_prioridad,
                    incid_stat = :incid_stat,
                    incid_origen = :incid_origen,
                    incid_fesol = :incid_fesol
                WHERE incid_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'incid_titulo' => $data['incid_titulo'],
            'incid_desc' => $data['incid_desc'],
            'incid_tipo' => $data['incid_tipo'],
            'incid_prioridad' => SchemaCodes::incidentPriorityCode($data['incid_prioridad']),
            'incid_stat' => SchemaCodes::incidentStatusCode($data['incid_stat']),
            'incid_origen' => SchemaCodes::incidentOrigin($data['incid_origen']),
            'incid_fesol' => $data['incid_fesol'] ?? null,
        ]);
    }

    public function cambiarEstado(string $id, string $estado): bool
    {
        $statusCode = SchemaCodes::incidentStatusCode($estado);
        $fechaCierre = $statusCode === 'R' ? date('Y-m-d H:i:s') : null;
        $sql = "UPDATE incidencias
                SET incid_stat = :estado, incid_fesol = :fecha_cierre
                WHERE incid_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'estado' => $statusCode,
            'fecha_cierre' => $fechaCierre,
        ]);
    }

    public function getMetricas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN incid_stat = 'A' THEN 1 ELSE 0 END), 0) as abiertas,
                    COALESCE(SUM(CASE WHEN incid_stat = 'P' THEN 1 ELSE 0 END), 0) as en_proceso,
                    COALESCE(SUM(CASE WHEN incid_stat = 'R' THEN 1 ELSE 0 END), 0) as resueltas
                FROM incidencias";
        $stmt = $this->db->query($sql);
        return $stmt->fetch() ?: [
            'total' => 0,
            'abiertas' => 0,
            'en_proceso' => 0,
            'resueltas' => 0
        ];
    }

    private function mapIncident(array $incident): array
    {
        return array_merge($incident, [
            'id' => $incident['incid_id'],
            'dispositivo_id' => $incident['dis_id'],
            'titulo' => $incident['incid_titulo'],
            'descripcion' => $incident['incid_desc'],
            'gravedad' => SchemaCodes::incidentPriority($incident['incid_prioridad']),
            'estado' => SchemaCodes::incidentStatus($incident['incid_stat']),
            'fecha_reporte' => $incident['incid_feing'],
            'fecha_cierre' => $incident['incid_fesol'],
        ]);
    }
}
