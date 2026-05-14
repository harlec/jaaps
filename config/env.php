<?php
/**
 * Cargador de variables de entorno desde archivo .env
 * Busca el archivo .env en la raíz del proyecto.
 * No requiere Composer ni librerías externas.
 */

declare(strict_types=1);

(static function (): void {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    // Ruta al .env en la raíz del proyecto (un nivel arriba de /config/)
    $envFile = dirname(__DIR__) . '/.env';

    if (!is_file($envFile)) {
        return; // En producción las vars pueden venir del servidor (Apache SetEnv, etc.)
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorar comentarios
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Solo procesar líneas con formato CLAVE=VALOR
        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Quitar comillas simples o dobles opcionales del valor
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // No sobreescribir variables ya definidas en el entorno del servidor
        if (!isset($_ENV[$name]) && getenv($name) === false) {
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }
})();
