<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Utilidad de red para validación de direcciones IP y rangos CIDR (IPv4 e IPv6).
 * Ajuste Final de Asistencia — Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class NetworkUtil
{
    /**
     * Determina si una dirección IP pertenece a una lista de redes o CIDRs permitidos.
     *
     * @param string $ip Dirección IP del cliente.
     * @param string|array<int, string> $allowedNetworks Redes o CIDRs permitidos (cadena separada por comas o array).
     * @return bool True si la IP está dentro de al menos una red permitida.
     */
    public static function isIpInAllowedNetworks(string $ip, string|array $allowedNetworks): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return false;
        }

        $networks = is_array($allowedNetworks)
            ? $allowedNetworks
            : explode(',', $allowedNetworks);

        foreach ($networks as $cidr) {
            $cidr = trim((string) $cidr);
            if ($cidr === '') {
                continue;
            }

            if (self::isIpInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina si una dirección IP (IPv4 o IPv6) pertenece a un rango CIDR o IP específica.
     *
     * @param string $ip Dirección IP evaluada.
     * @param string $cidr Notación CIDR (ej. 192.168.1.0/24, 10.0.0.0/8, 127.0.0.1/32, ::1/128) o IP simple.
     * @return bool True si la IP coincide con el CIDR.
     */
    public static function isIpInCidr(string $ip, string $cidr): bool
    {
        $ip = trim($ip);
        $cidr = trim($cidr);

        if ($ip === '' || $cidr === '') {
            return false;
        }

        // 1. Evaluación IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (str_contains($cidr, '/')) {
                [$subnet, $maskStr] = explode('/', $cidr, 2);
                if (!is_numeric($maskStr)) {
                    return false;
                }
                $mask = (int) $maskStr;
                if ($mask < 0 || $mask > 32) {
                    return false;
                }
            } else {
                $subnet = $cidr;
                $mask = 32;
            }

            if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return false;
            }

            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }

            if ($mask === 0) {
                return true;
            }

            $netmask = ~((1 << (32 - $mask)) - 1);
            return (($ipLong & $netmask) === ($subnetLong & $netmask));
        }

        // 2. Evaluación IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (str_contains($cidr, '/')) {
                [$subnet, $maskStr] = explode('/', $cidr, 2);
                if (!is_numeric($maskStr)) {
                    return false;
                }
                $mask = (int) $maskStr;
                if ($mask < 0 || $mask > 128) {
                    return false;
                }
            } else {
                $subnet = $cidr;
                $mask = 128;
            }

            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            if ($ipBin === false || $subnetBin === false) {
                return false;
            }

            $bytes = (int) ($mask / 8);
            $bits = $mask % 8;

            if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }

            if ($bits > 0) {
                $ipByte = ord($ipBin[$bytes]);
                $subnetByte = ord($subnetBin[$bytes]);
                $byteMask = ~((1 << (8 - $bits)) - 1) & 0xFF;
                return (($ipByte & $byteMask) === ($subnetByte & $byteMask));
            }

            return true;
        }

        return false;
    }
}
