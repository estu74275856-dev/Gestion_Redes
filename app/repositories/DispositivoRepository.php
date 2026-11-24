<?php

namespace App\Repositories;

use Config\Database;
use App\support\IdGenerator;
use App\support\SchemaCodes;
use PDO;

class DispositivoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM dispositivos ORDER BY dis_feing DESC";
        $stmt = $this->db->query($sql);
        return array_map([$this, 'mapDevice'], $stmt->fetchAll());
    }

    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM dispositivos WHERE dis_id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ? $this->mapDevice($result) : null;
    }

    public function findByIp(string $ip): ?array
    {
        $sql = "SELECT * FROM dispositivos WHERE dis_ip = :ip LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ip' => $ip]);
        $result = $stmt->fetch();
        return $result ? $this->mapDevice($result) : null;
    }

    public function create(array $data): string
    {
        $id = IdGenerator::generateUnique('D', 5, function (string $candidate): bool {
            $stmt = $this->db->prepare('SELECT 1 FROM dispositivos WHERE dis_id = :id');
            $stmt->execute(['id' => $candidate]);
            return $stmt->fetchColumn() !== false;
        });
        $sql = "INSERT INTO dispositivos
                    (dis_id, dis_nombre, dis_tipo, dis_ip, dis_ubicacion, dis_mac, dis_sisop, dis_obs, dis_stat)
                VALUES
                    (:dis_id, :dis_nombre, :dis_tipo, :dis_ip, :dis_ubicacion, :dis_mac, :dis_sisop, :dis_obs, :dis_stat)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'dis_id' => $id,
            'dis_nombre' => $data['dis_nombre'],
            'dis_tipo' => SchemaCodes::deviceType($data['dis_tipo'] ?? 'OT'),
            'dis_ip' => $data['dis_ip'],
            'dis_ubicacion' => $data['dis_ubicacion'] ?? null,
            'dis_mac' => $data['dis_mac'] ?? null,
            'dis_sisop' => $data['dis_sisop'] ?? null,
            'dis_obs' => $data['dis_obs'] ?? null,
            'dis_stat' => SchemaCodes::deviceStatusCode($data['dis_stat'] ?? 'I'),
        ]);

        return $id;
    }

    public function update(string $id, array $data): bool
    {
        $sql = "UPDATE dispositivos
                SET dis_nombre = :dis_nombre,
                    dis_tipo = :dis_tipo,
                    dis_ip = :dis_ip,
                    dis_ubicacion = :dis_ubicacion,
                    dis_mac = :dis_mac,
                    dis_sisop = :dis_sisop,
                    dis_obs = :dis_obs,
                    dis_stat = :dis_stat
                WHERE dis_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'dis_nombre' => $data['dis_nombre'],
            'dis_tipo' => SchemaCodes::deviceType($data['dis_tipo'] ?? 'OT'),
            'dis_ip' => $data['dis_ip'],
            'dis_ubicacion' => $data['dis_ubicacion'] ?? null,
            'dis_mac' => $data['dis_mac'] ?? null,
            'dis_sisop' => $data['dis_sisop'] ?? null,
            'dis_obs' => $data['dis_obs'] ?? null,
            'dis_stat' => SchemaCodes::deviceStatusCode($data['dis_stat'] ?? 'I'),
        ]);
    }

    public function updateEstado(string $id, string $estado): bool
    {
        $sql = "UPDATE dispositivos SET dis_stat = :estado WHERE dis_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'estado' => SchemaCodes::deviceStatusCode($estado),
        ]);
    }

    public function delete(string $id): bool
    {
        $sql = "DELETE FROM dispositivos WHERE dis_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getMetricas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN dis_stat = 'A' THEN 1 ELSE 0 END), 0) as en_linea,
                    COALESCE(SUM(CASE WHEN dis_stat = 'F' THEN 1 ELSE 0 END), 0) as fuera_de_linea,
                    COALESCE(SUM(CASE WHEN dis_stat = 'W' THEN 1 ELSE 0 END), 0) as advertencia,
                    COALESCE(SUM(CASE WHEN dis_stat = 'I' THEN 1 ELSE 0 END), 0) as desconocido
                FROM dispositivos";
        $stmt = $this->db->query($sql);
        return $stmt->fetch() ?: [
            'total' => 0,
            'en_linea' => 0,
            'fuera_de_linea' => 0,
            'advertencia' => 0,
            'desconocido' => 0
        ];
    }

    private function mapDevice(array $device): array
    {
        return array_merge($device, [
            'id' => $device['dis_id'],
            'codigo' => $device['dis_id'],
            'nombre' => $device['dis_nombre'],
            'tipo' => $device['dis_tipo'],
            'ip' => $device['dis_ip'],
            'ubicacion' => $device['dis_ubicacion'],
            'mac' => $device['dis_mac'],
            'sistema_operativo' => $device['dis_sisop'],
            'observaciones' => $device['dis_obs'],
            'estado' => SchemaCodes::deviceStatus($device['dis_stat']),
            'created_at' => $device['dis_feing'],
            'updated_at' => $device['dis_femod'],
        ]);
    }
}
