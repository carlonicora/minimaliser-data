<?php
namespace CarloNicora\Minimalism\MinimaliserData\Factories;

class Pluraliser
{
    public static function singular(
        string $name,
    ): string {
        $response = $name;

        if (strtolower(substr($name, strlen($name)-3)) === 'ies') {
            $response = substr($name, 0, -3) . 'y';
        } elseif (strtolower(substr($name, strlen($name)-4)) === 'sses') {
            $response = substr($name, 0, -2);
        } elseif (strtolower(substr($name, strlen($name)-4)) === 'zzes') {
            $response = substr($name, 0, -3);
        } elseif (strtolower(substr($name, strlen($name)-3)) === 'ses') {
            $response = substr($name, 0, -2);
        } elseif (strtolower(substr($name, strlen($name)-2)) === 'es') {
            $response = substr($name, 0, -1);
        } elseif (strtolower(substr($name, strlen($name)-1)) === 's') {
            $response = substr($name, 0, -1);
        }

        return $response;
    }

    public static function plural(
        string $name,
    ): string {
        $response = $name;

        if (strtolower(substr($name, strlen($name)-1)) === 'y') {
            $response = substr($response, 0, -1) . 'ies';
        } elseif (strtolower(substr($name, strlen($name)-2)) === 'iz') {
            $response = substr($response, 0, -2) . 'zes';
        } elseif (strtolower(substr($name, strlen($name)-1)) !== 's') {
            $response = $name . 's';
        }

        return $response;
    }
}