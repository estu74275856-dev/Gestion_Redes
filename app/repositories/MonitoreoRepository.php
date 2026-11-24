<?php

namespace App\Repositories;

use Config\Database;
use App\support\IdGenerator;
use PDO;

class MonitoreoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function registrar(
        string $dispositivoId,
        string $estado,
        ?float $latenciaMs,
        ?float $cpuUsage = null,
        ?float $memoryUsage = null
    ): string {
        $id = IdGenerator::generateUnique('M', 8, function (string $candidate): bool {
            $stmt = $this->db->prepare('SELECT 1 FROM monitoreos WHERE mon_id = :id');
            $stmt->execute(['id' => $candidate]);
            return $stmt->fetchColumn() !== false;
        });
        $sql = "INSERT INTO monitoreos
                    (mon_id, dis_id, mon_conexion, mon_latencia, mon_ucpu, mon_umem)
                VALUES
                    (:mon_id, :dis_id, :mon_conexion, :mon_latencia, :mon_ucpu, :mon_umem)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'mon_id' => $id,
            'dis_id' => $dispositivoId,
            'mon_conexion' => $estado,
            'mon_latencia' => $latenciaMs,
            'mon_ucpu' => $cpuUsage,
            'mon_umem' => $memoryUsage,
        ]);

        return $id;
    }

    public function findUltimos(int $limit = 20): array
    {
        $sql = "SELECT m.*, m.mon_id AS id, m.dis_id AS dispositivo_id,
                       m.mon_conexion AS estado, m.mon_latencia AS latencia_ms,
                       m.mon_ucpu AS cpu_usage, m.mon_umem AS memory_usage,
                       m.mon_fec AS fecha_monitoreo,
                       d.dis_nombre AS dispositivo_nombre, d.dis_ip AS dispositivo_ip,
                       d.dis_tipo AS dispositivo_tipo
                FROM monitoreos m
                INNER JOIN dispositivos d ON d.dis_id = m.dis_id
                ORDER BY m.mon_fec DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findPorDispositivo(string $dispositivoId, int $limit = 10): array
    {
        $sql = "SELECT monitoreos.*, mon_id AS id, dis_id AS dispositivo_id,
                       mon_conexion AS estado, mon_latencia AS latencia_ms,
                       mon_ucpu AS cpu_usage, mon_umem AS memory_usage,
                       mon_fec AS fecha_monitoreo
                FROM monitoreos
                WHERE dis_id = :dispositivo_id
                ORDER BY mon_fec DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dispositivo_id', $dispositivoId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
