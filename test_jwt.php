<?php
// Simple test script to verify JWT authentication
require_once 'vendor/autoload.php';

$baseUrl = 'http://localhost:8000/api';

// Test registration
$registerData = [
    'email' => 'test@example.com',
    'username' => 'testuser',
    'firstName' => 'Test',
    'password' => 'password123'
];

echo "Testing Registration...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/register');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registerData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Registration Response: " . $response . "\n\n";

// Test login
$loginData = [
    'email' => 'test@example.com',
    'password' => 'password123'
];

echo "Testing Login...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Login Response: " . $response . "\n\n";

$loginResponse = json_decode($response, true);
if (isset($loginResponse['data']['access_token'])) {
    $token = $loginResponse['data']['access_token'];
    
    // Test authenticated endpoint
    echo "Testing authenticated endpoint (/me)...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/me');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "Authenticated Response: " . $response . "\n";
} else {
    echo "Login failed, no token received\n";
}