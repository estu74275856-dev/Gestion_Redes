<?php

namespace App\Services;

use App\Repositories\DispositivoRepository;
use App\Repositories\MonitoreoRepository;
use InvalidArgumentException;

class MonitoreoService
{
    private DispositivoRepository $dispositivoRepo;
    private MonitoreoRepository $monitoreoRepo;

    public function __construct(
        ?DispositivoRepository $dispositivoRepo = null,
        ?MonitoreoRepository $monitoreoRepo = null
    ) {
        $this->dispositivoRepo = $dispositivoRepo ?? new DispositivoRepository();
        $this->monitoreoRepo = $monitoreoRepo ?? new MonitoreoRepository();
    }

    /**
     * HU3: Monitoreo en tiempo real de un dispositivo individual
     */
    public function pingDispositivo(string $id): array
    {
        $dispositivo = $this->dispositivoRepo->findById($id);
        if (!$dispositivo) {
            throw new InvalidArgumentException("Dispositivo con ID {$id} no encontrado para monitoreo.");
        }

        $resultado = $this->verificarConectividad($dispositivo['ip']);

        // Actualizar el estado en la base de datos
        $this->dispositivoRepo->updateEstado($id, $resultado['estado']);

        // Registrar en el historial de monitoreo
        $monitorId = $this->monitoreoRepo->registrar(
            $id,
            $resultado['estado'],
            $resultado['latencia_ms'] === null ? null : (float)$resultado['latencia_ms']
        );
        $fechaMonitoreo = date('Y-m-d H:i:s');

        return [
            'mon_id' => $monitorId,
            'dis_id' => $id,
            'mon_conexion' => $resultado['estado'],
            'mon_latencia' => $resultado['latencia_ms'],
            'mon_ucpu' => null,
            'mon_umem' => null,
            'mon_fec' => $fechaMonitoreo,
            'dispositivo_id' => $id,
            'nombre'         => $dispositivo['nombre'],
            'ip'             => $dispositivo['ip'],
            'estado'         => $resultado['estado'],
            'latencia_ms'    => $resultado['latencia_ms'],
            'detalles'       => $resultado['detalles'],
            'fecha'          => $fechaMonitoreo
        ];
    }

    /**
     * HU3: Monitoreo en tiempo real de toda la red
     */
    public function monitorearTodos(): array
    {
        $dispositivos = $this->dispositivoRepo->findAll();
        $resultados = [];

        foreach ($dispositivos as $disp) {
            $resultados[] = $this->pingDispositivo((string)$disp['id']);
        }

        return [
            'total_verificados' => count($resultados),
            'dispositivos'      => $resultados,
            'metricas'          => $this->dispositivoRepo->getMetricas()
        ];
    }

    public function obtenerUltimosMonitoreos(int $limit = 20): array
    {
        return $this->monitoreoRepo->findUltimos($limit);
    }

    public function obtenerHistorialDispositivo(string $dispositivoId, int $limit = 10): array
    {
        return $this->monitoreoRepo->findPorDispositivo($dispositivoId, $limit);
    }

    /**
     * Ejecuta una comprobación de red hacia la IP (Socket ping o ICMP adaptativo para Windows/Linux)
     */
    private function verificarConectividad(string $ip): array
    {
        $startTime = microtime(true);
        $estado = 'fuera_de_linea';
        $latenciaMs = null;
        $detalles = '';

        // Intento 1: Socket rápido en puertos comunes (80, 443, 22, 445, 8080) o ICMP
        $puertos = [80, 443, 22, 445, 8080, 53];
        $socketAbierto = false;

        foreach ($puertos as $puerto) {
            $fp = @fsockopen($ip, $puerto, $errno, $errstr, 0.4);
            if ($fp) {
                fclose($fp);
                $socketAbierto = true;
                $latenciaMs = (int)round((microtime(true) - $startTime) * 1000);
                $estado = ($latenciaMs > 300) ? 'advertencia' : 'en_linea';
                $detalles = "Conexión exitosa por puerto {$puerto}. Latencia: {$latenciaMs}ms";
                break;
            }
        }

        // Intento 2: Si los puertos están cerrados o filtrados, ejecutar comando ping ICMP del sistema
        if (!$socketAbierto) {
            $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
            $cmd = $isWindows 
                ? "ping -n 1 -w 800 " . escapeshellarg($ip)
                : "ping -c 1 -W 1 " . escapeshellarg($ip);

            $output = [];
            $returnVar = 0;
            @exec($cmd, $output, $returnVar);

            $latenciaMs = (int)round((microtime(true) - $startTime) * 1000);

            if ($returnVar === 0) {
                $estado = ($latenciaMs > 300) ? 'advertencia' : 'en_linea';
                $detalles = "Respuesta a ICMP Echo (Ping). Latencia: {$latenciaMs}ms";
            } else {
                $estado = 'fuera_de_linea';
                $latenciaMs = null;
                $detalles = "Sin respuesta a ICMP/Sockets. Equipo inalcanzable o bloqueado por firewall.";
            }
        }

        return [
            'estado'      => $estado,
            'latencia_ms' => $latenciaMs,
            'detalles'    => $detalles
        ];
    }
}
