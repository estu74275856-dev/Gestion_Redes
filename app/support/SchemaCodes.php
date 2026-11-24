<?php

namespace App\support;

use InvalidArgumentException;
use UnexpectedValueException;

final class SchemaCodes
{
    public const DEVICE_TYPES = [
        'AP' => 'Punto de acceso',
        'FW' => 'Firewall',
        'OT' => 'Otro',
        'PC' => 'Host / PC',
        'RT' => 'Router',
        'SV' => 'Servidor',
        'SW' => 'Switch',
    ];

    public const INCIDENT_TYPES = [
        'HW' => 'Hardware',
        'NW' => 'Red',
        'OT' => 'Otro',
        'SW' => 'Software',
    ];

    public const INCIDENT_PRIORITIES = [
        'AL' => 'alta',
        'BJ' => 'baja',
        'CR' => 'critica',
        'MD' => 'media',
    ];

    public const INCIDENT_STATUSES = [
        'A' => 'abierta',
        'P' => 'en_proceso',
        'R' => 'resuelta',
    ];

    public const INCIDENT_ORIGINS = [
        'S' => 'Sistema',
        'U' => 'Usuario',
    ];

    private const DEVICE_STATUSES = [
        'A' => 'en_linea',
        'F' => 'fuera_de_linea',
        'I' => 'desconocido',
        'W' => 'advertencia',
    ];

    public static function deviceType(string $value): string
    {
        $value = strtoupper(trim($value));
        $aliases = [
            'ROUTER' => 'RT',
            'SWITCH' => 'SW',
            'PUNTO DE ACCESO' => 'AP',
            'ACCESS POINT' => 'AP',
            'FIREWALL' => 'FW',
            'SERVIDOR' => 'SV',
            'SERVER' => 'SV',
            'HOST' => 'PC',
            'HOST / PC' => 'PC',
            'OTRO' => 'OT',
            'OTHER' => 'OT',
        ];
        return self::requireCode($aliases[$value] ?? $value, self::DEVICE_TYPES, 'tipo de dispositivo');
    }

    public static function deviceStatusCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $aliases = [
            'ACTIVO' => 'A',
            'EN_LINEA' => 'A',
            'DESCONOCIDO' => 'I',
            'INACTIVO' => 'I',
            'FALLA' => 'F',
            'FUERA_DE_LINEA' => 'F',
            'ADVERTENCIA' => 'W',
        ];
        $code = $aliases[$value] ?? $value;
        if (!array_key_exists($code, self::DEVICE_STATUSES)) {
            throw new InvalidArgumentException('Estado de dispositivo inválido.');
        }
        return $code;
    }

    public static function deviceStatus(string $code): string
    {
        $code = strtoupper(trim($code));
        if (!array_key_exists($code, self::DEVICE_STATUSES)) {
            throw new UnexpectedValueException('El dispositivo contiene un código de estado desconocido.');
        }
        return self::DEVICE_STATUSES[$code];
    }

    public static function incidentType(string $value): string
    {
        return self::requireCode($value, self::INCIDENT_TYPES, 'tipo de incidencia');
    }

    public static function incidentPriorityCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $aliases = ['ALTA' => 'AL', 'BAJA' => 'BJ', 'CRITICA' => 'CR', 'MEDIA' => 'MD'];
        $code = $aliases[$value] ?? $value;
        if (!array_key_exists($code, self::INCIDENT_PRIORITIES)) {
            throw new InvalidArgumentException('Prioridad de incidencia inválida.');
        }
        return $code;
    }

    public static function incidentPriority(string $code): string
    {
        $code = strtoupper(trim($code));
        if (!array_key_exists($code, self::INCIDENT_PRIORITIES)) {
            throw new UnexpectedValueException('La incidencia contiene un código de prioridad desconocido.');
        }
        return self::INCIDENT_PRIORITIES[$code];
    }

    public static function incidentStatusCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $aliases = ['ABIERTA' => 'A', 'EN_PROCESO' => 'P', 'RESUELTA' => 'R'];
        $code = $aliases[$value] ?? $value;
        if (!array_key_exists($code, self::INCIDENT_STATUSES)) {
            throw new InvalidArgumentException('Estado de incidencia inválido.');
        }
        return $code;
    }

    public static function incidentStatus(string $code): string
    {
        $code = strtoupper(trim($code));
        if (!array_key_exists($code, self::INCIDENT_STATUSES)) {
            throw new UnexpectedValueException('La incidencia contiene un código de estado desconocido.');
        }
        return self::INCIDENT_STATUSES[$code];
    }

    public static function incidentOrigin(string $value): string
    {
        return self::requireCode($value, self::INCIDENT_ORIGINS, 'origen de incidencia');
    }

    private static function requireCode(string $value, array $codes, string $field): string
    {
        $code = strtoupper(trim($value));
        if (!array_key_exists($code, $codes)) {
            throw new InvalidArgumentException('Código de ' . $field . ' inválido.');
        }
        return $code;
    }
}
