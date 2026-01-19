<?php
// Supabase storage helper for image uploads.
// Fill in your Supabase config here (server-side only).

$SUPABASE_CONFIG = [
    'url' => 'https://iinspwgtlrguudrxlbhn.supabase.co',
    'service_role_key' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImlpbnNwd2d0bHJndXVkcnhsYmhuIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2ODEwODk1MCwiZXhwIjoyMDgzNjg0OTUwfQ.z6B2H0hkVhFHLmqNcpQk2joiHeckvRblZ8xDKKNtjj0',
    'bucket' => 'product-images'
];

function supabase_is_configured(): bool {
    global $SUPABASE_CONFIG;
    $url = getenv('SUPABASE_URL') ?: $SUPABASE_CONFIG['url'];
    $key = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: $SUPABASE_CONFIG['service_role_key'];
    return $url !== '' && $key !== '';
}

function supabase_upload_image(string $tmpName, string $originalName, string $mimeType, string $folder = 'products'): string {
    global $SUPABASE_CONFIG;
    $supabaseUrl = trim(getenv('SUPABASE_URL') ?: $SUPABASE_CONFIG['url']);
    $supabaseKey = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: $SUPABASE_CONFIG['service_role_key'];
    $bucket = $SUPABASE_CONFIG['bucket'];

    if ($supabaseUrl === '' || $supabaseKey === '') {
        throw new Exception('Supabase storage is not configured.');
    }
    if (filter_var($supabaseUrl, FILTER_VALIDATE_URL) === false) {
        throw new Exception('Supabase URL is invalid. Expected full URL like https://your-project.supabase.co');
    }
    if (!function_exists('curl_init')) {
        throw new Exception('PHP cURL extension is not enabled.');
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $safeFolder = trim($folder, '/');
    $fileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $path = $safeFolder !== '' ? ($safeFolder . '/' . $fileName) : $fileName;
    $endpoint = rtrim($supabaseUrl, '/') . '/storage/v1/object/' . $bucket . '/' . $path;

    $body = file_get_contents($tmpName);
    if ($body === false) {
        throw new Exception('Failed to read uploaded file.');
    }

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey,
        'Content-Type: ' . $mimeType,
        'x-upsert: true'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Supabase upload error: ' . $error);
    }
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        throw new Exception('Supabase upload failed (HTTP ' . $status . ').');
    }

    return rtrim($supabaseUrl, '/') . '/storage/v1/object/public/' . $bucket . '/' . $path;
}

function supabase_extract_object_path(string $url, string $bucket): string {
    $trimmed = trim($url);
    if ($trimmed === '') {
        throw new Exception('Empty image URL.');
    }

    $parsed = parse_url($trimmed);
    $path = $parsed['path'] ?? $trimmed;

    $markerPublic = '/storage/v1/object/public/' . $bucket . '/';
    $marker = '/storage/v1/object/' . $bucket . '/';
    $markerSigned = '/storage/v1/object/sign/' . $bucket . '/';

    if (strpos($path, $markerPublic) !== false) {
        return ltrim(substr($path, strpos($path, $markerPublic) + strlen($markerPublic)), '/');
    }
    if (strpos($path, $markerSigned) !== false) {
        return ltrim(substr($path, strpos($path, $markerSigned) + strlen($markerSigned)), '/');
    }
    if (strpos($path, $marker) !== false) {
        return ltrim(substr($path, strpos($path, $marker) + strlen($marker)), '/');
    }

    $normalized = ltrim($path, '/');
    if (str_starts_with($normalized, $bucket . '/')) {
        return substr($normalized, strlen($bucket) + 1);
    }

    return $normalized;
}

function supabase_delete_image(string $url): bool {
    global $SUPABASE_CONFIG;
    $supabaseUrl = trim(getenv('SUPABASE_URL') ?: $SUPABASE_CONFIG['url']);
    $supabaseKey = getenv('SUPABASE_SERVICE_ROLE_KEY') ?: $SUPABASE_CONFIG['service_role_key'];
    $bucket = $SUPABASE_CONFIG['bucket'];

    if ($supabaseUrl === '' || $supabaseKey === '') {
        throw new Exception('Supabase storage is not configured.');
    }
    if (filter_var($supabaseUrl, FILTER_VALIDATE_URL) === false) {
        throw new Exception('Supabase URL is invalid. Expected full URL like https://your-project.supabase.co');
    }
    if (!function_exists('curl_init')) {
        throw new Exception('PHP cURL extension is not enabled.');
    }

    $objectPath = supabase_extract_object_path($url, $bucket);
    if ($objectPath === '') {
        throw new Exception('Unable to resolve Supabase object path.');
    }

    $endpoint = rtrim($supabaseUrl, '/') . '/storage/v1/object/' . $bucket . '/' . $objectPath;
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabaseKey,
        'apikey: ' . $supabaseKey
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Supabase delete error: ' . $error);
    }
    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        return true;
    }
    if ($status === 404) {
        return true;
    }
    throw new Exception('Supabase delete failed (HTTP ' . $status . ').');
}
