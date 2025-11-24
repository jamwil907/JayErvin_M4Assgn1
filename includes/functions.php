
<?php
require_once __DIR__ . '/config.php';

function oc_log($msg) {
	$dir = __DIR__ . '/../data';
	if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
	@file_put_contents($dir . '/outcast.log', '['.date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND);
}

// Small HTTP GET helper with cURL fallback; returns ['status'=>int,'body'=>string|false]
function http_get($url, $timeout = 7) {
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_USERAGENT => 'OutCast/1.0 (+https://example.local)'
		]);
		$body = curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($body === false) { oc_log('cURL error: '.curl_error($ch)); }
		curl_close($ch);
		return ['status' => $status ?: ($body === false ? 0 : 200), 'body' => $body];
	}
	$context = stream_context_create([
		'http' => [
			'timeout' => $timeout,
			'ignore_errors' => true, // get body even on 4xx/5xx
			'header' => "User-Agent: OutCast/1.0\r\n"
		]
	]);
	$body = @file_get_contents($url, false, $context);
	$status = 0;
	if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
		$status = (int)$m[1];
	}
	return ['status' => $status, 'body' => $body];
}

// Fetch weather data from OpenWeatherMap API (3-day forecast)
function fetch_weather($location, $unit = 'metric') {
	$location = trim($location);
	if (empty($location)) return ['error' => 'Location is required.'];
	if (!defined('OPENWEATHER_API_KEY') || OPENWEATHER_API_KEY === '' || OPENWEATHER_API_KEY === 'API_KEY_HERE' || OPENWEATHER_API_KEY === 'YOUR_API_KEY_HERE') {
		return ['error' => 'Missing or invalid API key. Edit includes/config.php and set OPENWEATHER_API_KEY.'];
	}

	// Determine if input is zip code (digits with optional country code) or city
	// Accept: 12345 or 12345,us (defaults to US when only digits)
	$is_zip = preg_match('/^\d{4,}(?:,[A-Za-z]{2})?$/', $location) === 1;
	$params = [
		'appid' => OPENWEATHER_API_KEY,
		'units' => $unit,
		// 3 days x 8 (3-hour steps) = 24 entries; OWM max is 40
		'cnt' => 24,
		'lang' => 'en',
	];
	if ($is_zip) {
		if (strpos($location, ',') !== false) {
			$params['zip'] = $location; // already has country code
		} else {
			$params['zip'] = $location . ',us'; // default country
		}
	} else {
		$params['q'] = $location;
	}
	$url = OPENWEATHER_API_URL . '?' . http_build_query($params);

	$res = http_get($url, 8);
	if ($res['body'] === false || $res['status'] === 0) {
		$cache = get_cached_weather($location, $unit);
		if ($cache) return ['data' => $cache, 'cached' => true, 'error' => 'Network unavailable. Showing last known data.'];
		return ['error' => 'Unable to fetch weather data. Please check your network.'];
	}
	$data = json_decode($res['body'], true);
	if (!$data) {
		oc_log('Invalid JSON from API. Status='.$res['status'].' URL='.$url);
		return ['error' => 'Weather service returned invalid data.'];
	}
	if (isset($data['cod']) && (string)$data['cod'] !== '200') {
		$msg = $data['message'] ?? 'Weather service error.';
		if ((string)$data['cod'] === '401') $msg = 'Invalid API key. Please update includes/config.php.';
		if ((string)$data['cod'] === '404') $msg = 'Location not found. Try a different city or ZIP.';
		return ['error' => $msg];
	}
	// Cache data
	cache_weather($location, $unit, $data);
	return ['data' => $data, 'cached' => false];
}

function cache_weather($location, $unit, $data) {
	$key = md5(strtolower($location) . '_' . $unit);
	$dir = __DIR__ . '/../data';
	if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
	$file = $dir . '/weather_' . $key . '.json';
	@file_put_contents($file, json_encode(['time' => time(), 'data' => $data]));
}

function get_cached_weather($location, $unit) {
	$key = md5(strtolower($location) . '_' . $unit);
	$file = __DIR__ . '/../data/weather_' . $key . '.json';
	if (file_exists($file)) {
		$json = file_get_contents($file);
		$arr = json_decode($json, true);
		if ($arr && isset($arr['data'])) return $arr['data'];
	}
	return null;
}
