<?php
// zoho.php - Automatic Zoho CRM lead creation with detailed error logging

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('CLIENT_ID', '1000.W1ZOGCIIX44GUMUK0827B9V9ZHC12L');
define('CLIENT_SECRET', 'b7fc12526163429eda966f0f289096708eceb82983');
define('REFRESH_TOKEN_FILE', __DIR__ . '/.zoho_refresh_token');
define('AUTH_CODE', '1000.4e76abae70b1f57c7b38b8a29a401bee.5951d4e73c511f88a3255b95a1bc2f9f');
define('LOG_FILE', __DIR__ . '/zoho_debug.log');

function logDebug($message) {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

function getAccessToken() {
    if (!file_exists(REFRESH_TOKEN_FILE)) {
        logDebug('No refresh token found, exchanging auth code...');
        
        $postData = [
            'code' => AUTH_CODE,
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'redirect_uri' => 'https://yourenergybest.com/auth',
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init('https://accounts.zoho.com/oauth/v2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        logDebug('Token exchange response: ' . $response);
        if ($curlError) logDebug('CURL Error: ' . $curlError);
        
        $result = json_decode($response, true);
        
        if (isset($result['refresh_token'])) {
            file_put_contents(REFRESH_TOKEN_FILE, $result['refresh_token']);
            logDebug('Refresh token saved successfully');
            return $result['access_token'];
        } else {
            logDebug('Failed to get refresh token: ' . json_encode($result));
            return null;
        }
    }
    
    $refreshToken = trim(file_get_contents(REFRESH_TOKEN_FILE));
    logDebug('Using existing refresh token');
    
    $postData = [
        'refresh_token' => $refreshToken,
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'grant_type' => 'refresh_token'
    ];
    
    $ch = curl_init('https://accounts.zoho.com/oauth/v2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) logDebug('CURL Error getting access token: ' . $curlError);
    
    $result = json_decode($response, true);
    
    if (isset($result['access_token'])) {
        logDebug('Access token obtained successfully');
        return $result['access_token'];
    } else {
        logDebug('Failed to get access token: ' . json_encode($result));
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    logDebug('Received form submission: ' . json_encode($input));
    
    $accessToken = getAccessToken();
    
    if (!$accessToken) {
        logDebug('ERROR: Failed to obtain access token');
        http_response_code(500);
        echo json_encode(['error' => 'Authentication failed', 'log' => 'Check zoho_debug.log for details']);
        exit;
    }
    
    // Split name into first and last
    $nameParts = explode(' ', trim($input['name'] ?? 'Unknown'), 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : $nameParts[0];
    
    $leadData = [
        'data' => [[
            'First_Name' => $firstName,
            'Last_Name' => $lastName,
            'Email' => $input['email'] ?? '',
            'Phone' => $input['phone'] ?? '',
            'Zip_Code' => $input['zip'] ?? '',
            'Lead_Source' => 'Website Quote Form',
            'Description' => 'Customer Option: ' . ($input['customer_option'] ?? 'Not specified')
        ]]
    ];
    
    logDebug('Sending lead to Zoho: ' . json_encode($leadData));
    
    $ch = curl_init('https://www.zohoapis.com/crm/v2/Leads');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($leadData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Zoho-oauthtoken ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    logDebug('Zoho API response code: ' . $httpCode);
    logDebug('Zoho API response: ' . $response);
    if ($curlError) logDebug('CURL Error creating lead: ' . $curlError);
    
    if ($httpCode === 201 || $httpCode === 200) {
        logDebug('Lead created successfully!');
        echo json_encode(['success' => true]);
    } else {
        logDebug('ERROR: Failed to create lead');
        http_response_code($httpCode);
        echo json_encode([
            'error' => 'Failed to create lead', 
            'details' => json_decode($response, true),
            'log' => 'Check zoho_debug.log for details'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
