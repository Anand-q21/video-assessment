<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'Railway deployment is working!',
    'php_version' => phpversion(),
    'timestamp' => date('c')
]);
