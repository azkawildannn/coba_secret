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
	<title>Simple News Viewer</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
			padding: 20px;
		}
		
		.container {
			max-width: 1200px;
			margin: 0 auto;
		}
		
		header {
			background: white;
			padding: 30px;
			border-radius: 12px;
			box-shadow: 0 10px 30px rgba(0,0,0,0.2);
			margin-bottom: 30px;
		}
		
		h1 {
			color: #333;
			margin-bottom: 20px;
			font-size: 2.5em;
		}
		
		.search-form {
			display: grid;
			grid-template-columns: 1fr 150px 120px;
			gap: 12px;
			align-items: end;
		}
		
		.form-group {
			display: flex;
			flex-direction: column;
		}
		
		label {
			font-weight: 600;
			color: #555;
			margin-bottom: 8px;
			font-size: 0.95em;
		}
		
		input {
			padding: 10px 12px;
			border: 2px solid #e0e0e0;
			border-radius: 6px;
			font-size: 1em;
			transition: all 0.3s;
		}
		
		input:focus {
			outline: none;
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}
		
		button {
			padding: 10px 20px;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			border: none;
			border-radius: 6px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s;
			font-size: 1em;
		}
		
		button:hover {
			transform: translateY(-2px);
			box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
		}
		
		button:active {
			transform: translateY(0);
		}
		
		.news-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}
		
		.card {
			background: white;
			border-radius: 12px;
			overflow: hidden;
			box-shadow: 0 4px 15px rgba(0,0,0,0.1);
			transition: all 0.3s;
			display: flex;
			flex-direction: column;
		}
		
		.card:hover {
			transform: translateY(-5px);
			box-shadow: 0 8px 25px rgba(0,0,0,0.15);
		}
		
		.card-image {
			width: 100%;
			height: 200px;
			overflow: hidden;
			background: #f0f0f0;
		}
		
		.card-image img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		
		.card-content {
			padding: 20px;
			flex: 1;
			display: flex;
			flex-direction: column;
		}
		
		.card h3 {
			color: #333;
			margin-bottom: 12px;
			font-size: 1.1em;
			line-height: 1.4;
			min-height: 52px;
		}
		
		.card-source {
			font-size: 0.85em;
			color: #999;
			margin-bottom: 10px;
			display: flex;
			align-items: center;
		}
		
		.card-source::before {
			content: "📰";
			margin-right: 6px;
		}
		
		.card-description {
			color: #666;
			font-size: 0.95em;
			line-height: 1.5;
			margin-bottom: 15px;
			flex: 1;
		}
		
		.card-link {
			display: inline-block;
			color: #667eea;
			text-decoration: none;
			font-weight: 600;
			transition: all 0.3s;
		}
		
		.card-link:hover {
			color: #764ba2;
			text-decoration: underline;
		}
		
		.error-message {
			background: #fee;
			border-left: 4px solid #f00;
			padding: 20px;
			border-radius: 6px;
			color: #c33;
			margin-bottom: 20px;
		}
		
		.empty-message {
			background: white;
			padding: 40px;
			text-align: center;
			border-radius: 12px;
			color: #999;
		}
		
		.empty-message p {
			font-size: 1.1em;
		}
		
		@media (max-width: 768px) {
			.search-form {
				grid-template-columns: 1fr;
			}
			
			h1 {
				font-size: 1.8em;
			}
			
			.news-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
</head>
<body>
	<div class="container">
		<header>
			<h1>📰 Simple News Viewer</h1>
			<form method="get" class="search-form">
				<div class="form-group">
					<label for="q">Search News</label>
					<input type="text" id="q" name="q" placeholder="Enter keyword..." value="<?php echo htmlspecialchars($query); ?>">
				</div>
				<div class="form-group">
					<label for="country">Country</label>
					<input type="text" id="country" name="country" placeholder="us" value="<?php echo htmlspecialchars($country); ?>" maxlength="2">
				</div>
				<button type="submit">Search</button>
			</form>
		</header>

		<?php
		if ($httpCode !== 200) {
			echo '<div class="error-message">';
			echo '<h2>❌ API Error (HTTP ' . intval($httpCode) . ')</h2>';
			if (is_array($data) && !empty($data['message'])) {
				echo '<p>' . htmlspecialchars($data['message']) . '</p>';
			} else {
				echo '<pre>' . htmlspecialchars(substr($resp, 0, 500)) . '...</pre>';
			}
			echo '</div>';
			exit;
		}

		if (!is_array($data) || empty($data['articles'])) {
			echo '<div class="empty-message"><p>🔍 No articles found. Try searching with different keywords!</p></div>';
			exit;
		}

		echo '<div class="news-grid">';
		foreach ($data['articles'] as $a) {
			echo '<article class="card">';
			
			if (!empty($a['urlToImage'])) {
				echo '<div class="card-image"><img src="' . htmlspecialchars($a['urlToImage']) . '" alt="News image"></div>';
			} else {
				echo '<div class="card-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>';
			}
			
			echo '<div class="card-content">';
			echo '<h3>' . htmlspecialchars($a['title'] ?? 'Untitled') . '</h3>';
			
			if (!empty($a['source']['name'])) {
				echo '<div class="card-source">' . htmlspecialchars($a['source']['name']) . '</div>';
			}
			
			if (!empty($a['description'])) {
				echo '<p class="card-description">' . htmlspecialchars($a['description']) . '</p>';
			}
			
			if (!empty($a['url'])) {
				echo '<a href="' . htmlspecialchars($a['url']) . '" target="_blank" rel="noopener" class="card-link">Read Full Article →</a>';
			}
			
			echo '</div>';
			echo '</article>';
		}
		echo '</div>';
		?>
	</div>
</body>
</html>
