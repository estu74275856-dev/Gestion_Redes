<?php

namespace App\Models;

class Incidencia
{
    public string $incid_id;
    public string $dis_id;
    public string $incid_titulo;
    public string $incid_desc;
    public string $incid_tipo;
    public string $incid_prioridad;
    public string $incid_stat;
    public string $incid_origen;
    public ?string $incid_feing;
    public ?string $incid_fesol;

    public function __construct(array $data = [])
    {
        $this->incid_id = $data['incid_id'] ?? '';
        $this->dis_id = $data['dis_id'] ?? '';
        $this->incid_titulo = trim($data['incid_titulo'] ?? '');
        $this->incid_desc = trim($data['incid_desc'] ?? '');
        $this->incid_tipo = $data['incid_tipo'] ?? 'OT';
        $this->incid_prioridad = $data['incid_prioridad'] ?? 'MD';
        $this->incid_stat = $data['incid_stat'] ?? 'A';
        $this->incid_origen = $data['incid_origen'] ?? 'U';
        $this->incid_feing = $data['incid_feing'] ?? null;
        $this->incid_fesol = $data['incid_fesol'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'incid_id' => $this->incid_id,
            'dis_id' => $this->dis_id,
            'incid_titulo' => $this->incid_titulo,
            'incid_desc' => $this->incid_desc,
            'incid_tipo' => $this->incid_tipo,
            'incid_prioridad' => $this->incid_prioridad,
            'incid_stat' => $this->incid_stat,
            'incid_origen' => $this->incid_origen,
            'incid_feing' => $this->incid_feing,
            'incid_fesol' => $this->incid_fesol,
        ];
    }
}
