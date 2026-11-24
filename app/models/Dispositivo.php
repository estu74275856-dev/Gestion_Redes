<?php

namespace App\Models;

class Dispositivo
{
    public string $dis_id;
    public string $dis_nombre;
    public string $dis_tipo;
    public string $dis_ip;
    public ?string $dis_mac;
    public ?string $dis_sisop;
    public ?string $dis_obs;
    public string $dis_stat;
    public ?string $dis_feing;
    public ?string $dis_femod;

    public function __construct(array $data = [])
    {
        $this->dis_id = $data['dis_id'] ?? '';
        $this->dis_nombre = trim($data['dis_nombre'] ?? '');
        $this->dis_tipo = $data['dis_tipo'] ?? 'OT';
        $this->dis_ip = trim($data['dis_ip'] ?? '');
        $this->dis_mac = !empty($data['dis_mac']) ? trim($data['dis_mac']) : null;
        $this->dis_sisop = !empty($data['dis_sisop']) ? trim($data['dis_sisop']) : null;
        $this->dis_obs = !empty($data['dis_obs']) ? trim($data['dis_obs']) : null;
        $this->dis_stat = $data['dis_stat'] ?? 'I';
        $this->dis_feing = $data['dis_feing'] ?? null;
        $this->dis_femod = $data['dis_femod'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'dis_id' => $this->dis_id,
            'dis_nombre' => $this->dis_nombre,
            'dis_tipo' => $this->dis_tipo,
            'dis_ip' => $this->dis_ip,
            'dis_mac' => $this->dis_mac,
            'dis_sisop' => $this->dis_sisop,
            'dis_obs' => $this->dis_obs,
            'dis_stat' => $this->dis_stat,
            'dis_feing' => $this->dis_feing,
            'dis_femod' => $this->dis_femod,
        ];
    }
}
