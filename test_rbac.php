<?php

function apiRequest($method, $url, $data = [], $token = null) {
    $ch = curl_init('http://localhost:8000/api' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['status' => $status, 'body' => json_decode($result, true)];
}

echo "1. Logging in as Master Admin...\n";
$master = apiRequest('POST', '/login', ['email' => 'diluklakshan01@gmail.com', 'password' => 'Chinthaka@2002']);
$masterToken = $master['body']['token'];
echo "Master Admin Output Status: {$master['status']}\n";

echo "2. Logging in as Admin (lakshan)...\n";
// Assuming password is password from seeder, or we create one
$admin = apiRequest('POST', '/login', ['email' => 'mjcdlakshanbuss@gmail.com', 'password' => 'password']);
if ($admin['status'] !== 200) {
    // try default pass if changed
    $admin = apiRequest('POST', '/login', ['email' => 'mjcdlakshanbuss@gmail.com', 'password' => 'Chinthaka@2002']);
}
if ($admin['status'] !== 200) die("Could not login as admin\n");
$adminToken = $admin['body']['token'];
$adminId = $admin['body']['user']['id'];
echo "Admin Output Status: {$admin['status']}, Admin ID: $adminId\n";

echo "3. Admin trying to POST /resources (Has permission by default)...\n";
$res1 = apiRequest('POST', '/resources', ['name' => 'test'], $adminToken);
echo "Status: {$res1['status']} (Expected: 422 or 201)\n";

echo "4. Master Admin revoking 'manage_resources' permission from Admin...\n";
$revoke = apiRequest('POST', "/users/{$adminId}/permissions", [
    'permission_slug' => 'manage_resources', 'is_allowed' => false
], $masterToken);
echo "Revoke Output Status: {$revoke['status']}\n";

echo "5. Admin trying to POST /resources again (Using SAME token, expecting 403)...\n";
$res2 = apiRequest('POST', '/resources', ['name' => 'test'], $adminToken);
echo "Status: {$res2['status']} - JSON: " . json_encode($res2['body']) . "\n";

echo "6. Restoring permission to clean up test...\n";
apiRequest('POST', "/users/{$adminId}/permissions", [
    'permission_slug' => 'manage_resources', 'is_allowed' => true
], $masterToken);
echo "Done.\n";
