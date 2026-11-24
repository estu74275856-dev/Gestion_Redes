<?php

namespace App\Services;

use App\Repositories\DispositivoRepository;
use App\support\SchemaCodes;
use InvalidArgumentException;

class DispositivoService
{
    private DispositivoRepository $repository;

    public function __construct(?DispositivoRepository $repository = null)
    {
        $this->repository = $repository ?? new DispositivoRepository();
    }

    public function obtenerTodos(): array
    {
        return $this->repository->findAll();
    }

    public function obtenerPorId(string $id): ?array
    {
        $dispositivo = $this->repository->findById($id);
        if (!$dispositivo) {
            throw new InvalidArgumentException("El dispositivo con ID {$id} no fue encontrado.");
        }
        return $dispositivo;
    }

    /**
     * HU1: Registrar Dispositivo
     */
    public function registrar(array $data): array
    {
        $data = $this->normalizarDatos($data);

        $existente = $this->repository->findByIp($data['dis_ip']);
        if ($existente) {
            throw new InvalidArgumentException("La dirección IP '{$data['dis_ip']}' ya está asignada al dispositivo '{$existente['nombre']}'.");
        }

        $id = $this->repository->create($data);
        return $this->repository->findById($id);
    }

    /**
     * HU2: Actualizar Dispositivo
     */
    public function actualizar(string $id, array $data): array
    {
        $dispositivoActual = $this->obtenerPorId($id);
        $data['dis_stat'] = $data['dis_stat'] ?? $dispositivoActual['dis_stat'];
        $data['dis_ubicacion'] = $data['dis_ubicacion'] ?? $data['ubicacion'] ?? $dispositivoActual['ubicacion'] ?? '';
        $data = $this->normalizarDatos($data);

        if ($data['dis_ip'] !== $dispositivoActual['dis_ip']) {
            $existente = $this->repository->findByIp($data['dis_ip']);
            if ($existente && (string)$existente['id'] !== $id) {
                throw new InvalidArgumentException("La dirección IP '{$data['dis_ip']}' ya está en uso por otro dispositivo.");
            }
        }

        $this->repository->update($id, $data);
        return $this->repository->findById($id);
    }

    public function eliminar(string $id): bool
    {
        $this->obtenerPorId($id);
        return $this->repository->delete($id);
    }

    public function obtenerMetricas(): array
    {
        return $this->repository->getMetricas();
    }

    private function normalizarDatos(array $data): array
    {
        $data['dis_nombre'] = trim((string)($data['dis_nombre'] ?? $data['nombre'] ?? ''));
        $data['dis_ip'] = trim((string)($data['dis_ip'] ?? $data['ip'] ?? ''));
        $data['dis_tipo'] = SchemaCodes::deviceType((string)($data['dis_tipo'] ?? $data['tipo'] ?? 'OT'));
        $data['dis_ubicacion'] = trim((string)($data['dis_ubicacion'] ?? $data['ubicacion'] ?? ''));
        $data['dis_stat'] = SchemaCodes::deviceStatusCode((string)($data['dis_stat'] ?? $data['estado'] ?? 'I'));
        $data['dis_mac'] = trim((string)($data['dis_mac'] ?? $data['mac'] ?? ''));
        $data['dis_sisop'] = trim((string)($data['dis_sisop'] ?? $data['sistema_operativo'] ?? ''));
        $data['dis_obs'] = trim((string)($data['dis_obs'] ?? $data['observaciones'] ?? ''));

        if ($data['dis_nombre'] === '') {
            throw new InvalidArgumentException("El nombre del dispositivo es obligatorio.");
        }

        if ($data['dis_ip'] === '') {
            throw new InvalidArgumentException("La dirección IP es obligatoria.");
        }

        $ip = $data['dis_ip'];
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("La dirección IP '{$ip}' no tiene un formato válido (IPv4 o IPv6).");
        }

        if ($data['dis_mac'] !== '') {
            $mac = $data['dis_mac'];
            if (!filter_var($mac, FILTER_VALIDATE_MAC)) {
                throw new InvalidArgumentException("La dirección MAC '{$mac}' no tiene un formato válido (ej: 00:1A:2B:3C:4D:5E).");
            }
        }

        $data['dis_mac'] = $data['dis_mac'] === '' ? null : $data['dis_mac'];
        $data['dis_sisop'] = $data['dis_sisop'] === '' ? null : $data['dis_sisop'];
        $data['dis_obs'] = $data['dis_obs'] === '' ? null : $data['dis_obs'];
        $data['dis_ubicacion'] = $data['dis_ubicacion'] === '' ? null : $data['dis_ubicacion'];
        return $data;
    }
}
