<?php
// Simple PHP news viewer that reads the API key from an environment
// variable named NEWS_API_KEY (or from a local .env for testing).

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

$apiKey = getenv('NEWS_API_KEY');
if (!$apiKey && file_exists(__DIR__ . '/.env')) {
	$env = load_env_file(__DIR__ . '/.env');
	if (!empty($env['NEWS_API_KEY'])) $apiKey = $env['NEWS_API_KEY'];
}

// Don't print sensitive values. If no key, show helpful message.
if (!$apiKey) {
	echo "<h2>API Key not found</h2>";
	echo "<p>Please set the environment variable <code>NEWS_API_KEY</code> or create a local <code>.env</code> file with <code>NEWS_API_KEY=your_key_here</code> for local testing.</p>";
	echo "<p>See <code>README.md</code> for instructions to add a GitHub secret named <code>NEWS_API_KEY</code>.</p>";
	exit;
}

// Basic parameters (can be adjusted in the form below)
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$country = isset($_GET['country']) ? trim($_GET['country']) : 'us';

// Use NewsAPI.org endpoint by default; change if you use another provider.
$endpoint = 'https://newsapi.org/v2/top-headlines';
$params = [];
if ($query !== '') $params['q'] = $query;
else $params['country'] = $country;

$url = $endpoint . (empty($params) ? '' : '?' . http_build_query($params));

// Make request via cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Send API key and identify the client via HTTP headers.
$headers = [
	'X-Api-Key: ' . $apiKey,
	'User-Agent: SimpleNewsViewer/1.0',
	'Accept: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// Also set CURLOPT_USERAGENT as some servers check that curl option.
curl_setopt($ch, CURLOPT_USERAGENT, 'SimpleNewsViewer/1.0');
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($resp === false) {
	$err = curl_error($ch);
	curl_close($ch);
	echo "<h2>Request failed</h2><p>cURL error: " . htmlspecialchars($err) . "</p>";
	exit;
}
curl_close($ch);

$data = json_decode($resp, true);

?><!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Simple News</title>
	<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:18px} .card{border:1px solid #ddd;padding:12px;margin:8px 0;border-radius:6px} img{max-width:200px;height:auto}</style>
</head>
<body>
	<h1>Simple News Viewer</h1>
	<form method="get">
		<label>Search query: <input name="q" value="<?php echo htmlspecialchars($query); ?>"></label>
		<label style="margin-left:8px">Country: <input name="country" value="<?php echo htmlspecialchars($country); ?>" maxlength="2"></label>
		<button type="submit">Search / Refresh</button>
	</form>

	<?php
	if ($httpCode !== 200) {
		echo "<h2>API Error (HTTP " . intval($httpCode) . ")</h2>";
		if (is_array($data) && !empty($data['message'])) {
			echo "<p>Message: " . htmlspecialchars($data['message']) . "</p>";
		} else {
			echo "<pre>" . htmlspecialchars($resp) . "</pre>";
		}
		exit;
	}

	if (!is_array($data) || empty($data['articles'])) {
		echo "<p>No articles found.</p>";
		exit;
	}

	foreach ($data['articles'] as $a) {
		echo '<div class="card">';
		echo '<h3>' . htmlspecialchars($a['title'] ?? 'Untitled') . '</h3>';
		if (!empty($a['urlToImage'])) echo '<img src="' . htmlspecialchars($a['urlToImage']) . '" alt="">';
		if (!empty($a['description'])) echo '<p>' . htmlspecialchars($a['description']) . '</p>';
		if (!empty($a['source']['name'])) echo '<p><strong>Source:</strong> ' . htmlspecialchars($a['source']['name']) . '</p>';
		if (!empty($a['url'])) echo '<p><a href="' . htmlspecialchars($a['url']) . '" target="_blank">Read full article</a></p>';
		echo '</div>';
	}
	?>
</body>
</html>
