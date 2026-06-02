<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$storage_file = 'keys.json';

if (!isset($_GET['key'])) {
    echo json_encode(['status' => 'error', 'message' => 'API Key is required']);
    exit();
}

$input_key = $_GET['key'];
$data = file_exists($storage_file) ? json_decode(file_get_contents($storage_file), true) : [];

$found_key = null;
foreach ($data as $item) {
    if ($item['key'] === $input_key) {
        $found_key = $item;
        break;
    }
}

if ($found_key) {
    $today = strtotime(date('Y-m-d'));
    $expiry = strtotime($found_key['expiry']);

    if ($expiry >= $today) {
        echo json_encode([
            'status' => 'success',
            'valid' => true,
            'expiry_date' => $found_key['expiry'],
            'message' => 'Access Granted'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'valid' => false,
            'message' => 'Key has expired'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'valid' => false,
        'message' => 'Invalid API Key'
    ]);
}