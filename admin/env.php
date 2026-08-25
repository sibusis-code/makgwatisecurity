<?php
/**
 * Minimal .env loader — no Composer/third-party deps in this project.
 * Reads KEY=VALUE lines from the .env file at the site root into
 * getenv()/$_ENV so admin/config.php can read them. Safe to include
 * multiple times; silently no-ops if .env doesn't exist yet.
 */
function load_env(string $path): void {
    static $loaded = false;
    if ($loaded || !file_exists($path)) return;
    $loaded = true;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Strip matching surrounding quotes, if present
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, string $default = ''): string {
    $v = getenv($key);
    return $v !== false ? $v : $default;
}
