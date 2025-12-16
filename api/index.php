<?php
// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load .env file
function load_env_file($path) {
	$pairs = [];
	$content = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if (!$content) return $pairs;
	foreach ($content as $line) {
		if (strpos(trim($line), '#') === 0) continue;
		[$k, $v] = array_pad(explode('=', $line, 2), 2, '');
		$k = trim($k); $v = trim($v);
		if ($k !== '') $pairs[$k] = $v;
	}
	return $pairs;
}

// Get API Key
$apiKey = getenv('NEWS_API_KEY');
if (!$apiKey && file_exists(__DIR__ . '/../.env')) {
	$env = load_env_file(__DIR__ . '/../.env');
	if (!empty($env['NEWS_API_KEY'])) $apiKey = $env['NEWS_API_KEY'];
}

// Check if API key exists
if (!$apiKey) {
	http_response_code(500);
	echo json_encode([
		'error' => 'API Key not found',
		'message' => 'Please set NEWS_API_KEY environment variable or add it to .env file'
	]);
	exit;
}

// Get query parameters
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$country = isset($_GET['country']) ? trim($_GET['country']) : 'us';

// Build API URL
$endpoint = 'https://newsapi.org/v2/top-headlines';
$params = [];

if ($q !== '') {
	$endpoint = 'https://newsapi.org/v2/everything';
	$params['q'] = $q;
} else {
	$params['country'] = $country;
}

$url = $endpoint . '?' . http_build_query($params);

// Make request via cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'X-Api-Key: ' . $apiKey,
	'User-Agent: SimpleNewsViewer/1.0',
	'Accept: application/json'
]);

$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Return response
http_response_code($httpCode);
echo $resp;
