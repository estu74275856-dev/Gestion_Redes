<?php

namespace App\Models;

class Monitoreo
{
    public string $mon_id;
    public string $dis_id;
    public string $mon_conexion;
    public ?float $mon_latencia;
    public ?float $mon_ucpu;
    public ?float $mon_umem;
    public ?string $mon_fec;

    public function __construct(array $data = [])
    {
        $this->mon_id = $data['mon_id'] ?? '';
        $this->dis_id = $data['dis_id'] ?? '';
        $this->mon_conexion = $data['mon_conexion'] ?? 'desconocido';
        $this->mon_latencia = isset($data['mon_latencia']) ? (float)$data['mon_latencia'] : null;
        $this->mon_ucpu = isset($data['mon_ucpu']) ? (float)$data['mon_ucpu'] : null;
        $this->mon_umem = isset($data['mon_umem']) ? (float)$data['mon_umem'] : null;
        $this->mon_fec = $data['mon_fec'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'mon_id' => $this->mon_id,
            'dis_id' => $this->dis_id,
            'mon_conexion' => $this->mon_conexion,
            'mon_latencia' => $this->mon_latencia,
            'mon_ucpu' => $this->mon_ucpu,
            'mon_umem' => $this->mon_umem,
            'mon_fec' => $this->mon_fec,
        ];
    }
}
