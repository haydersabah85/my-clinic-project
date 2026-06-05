<?php

if (!function_exists('clinic_is_private_host')) {
    function clinic_is_private_host(string $host): bool
    {
        $host = trim(strtolower($host));

        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        if (strpos($host, ':') !== false) {
            $host = explode(':', $host, 2)[0];
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (preg_match('/^10\./', $host)) {
                return true;
            }

            if (preg_match('/^192\.168\./', $host)) {
                return true;
            }

            if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host)) {
                return true;
            }
        }

        return str_ends_with($host, '.local');
    }
}

if (!function_exists('clinic_detect_local_environment')) {
    function clinic_detect_local_environment(): bool
    {
        $requestHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $serverAddress = $_SERVER['SERVER_ADDR'] ?? '';

        return clinic_is_private_host($requestHost) || clinic_is_private_host($serverAddress);
    }
}

if (!function_exists('clinic_connect_from_profiles')) {
    function clinic_connect_from_profiles(array $localDb, array $onlineDb): array
    {
        $isLocal = clinic_detect_local_environment();
        $profile = $isLocal ? $localDb : $onlineDb;

        $connection = mysqli_connect(
            $profile['host'] ?? 'localhost',
            $profile['user'] ?? '',
            $profile['pass'] ?? '',
            $profile['name'] ?? ''
        );

        if (!$connection) {
            die('Database connection failed.');
        }

        mysqli_set_charset($connection, 'utf8mb4');

        return [$connection, $isLocal];
    }
}

return clinic_connect_from_profiles($localDb ?? [], $onlineDb ?? []);
