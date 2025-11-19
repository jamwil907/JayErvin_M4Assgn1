<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Load helper functions
require_once __DIR__ . '/includes/functions.php';


$weather = null;
$error = '';
$result_html = '';
// Handle unit preference in session
if (isset($_GET['unit'])) {
    $_SESSION['unit'] = $_GET['unit'] === 'imperial' ? 'imperial' : 'metric';
}
$unit = isset($_SESSION['unit']) ? $_SESSION['unit'] : 'metric';
if (isset($_GET['location'])) {
    $location = htmlspecialchars(trim($_GET['location']));
    // Save search to user history
    $user = $_SESSION['user'];
    $history_file = __DIR__ . '/data/history_' . md5($user) . '.json';
    $data_dir = __DIR__ . '/data';
    if (!is_dir($data_dir)) { @mkdir($data_dir, 0777, true); }
    $history = file_exists($history_file) ? json_decode(@file_get_contents($history_file), true) : [];
    if (!is_array($history)) { $history = []; }
    array_unshift($history, [
        'location' => $location,
        'unit' => $unit,
        'time' => time()
    ]);
    $history = array_slice($history, 0, 5); // keep last 5
    @file_put_contents($history_file, json_encode($history));

    $weather = fetch_weather($location, $unit);
    if (isset($weather['error'])) {
        $error = $weather['error'];
    } elseif (isset($weather['data'])) {
        $data = $weather['data'];
        $cached = !empty($weather['cached']);
        // Parse current weather (first item)
        $current = $data['list'][0];
        $city = $data['city']['name'] . ', ' . $data['city']['country'];
        $desc = ucfirst($current['weather'][0]['description']);
        $icon = $current['weather'][0]['icon'];
        $temp = round($current['main']['temp']);
        $wind = $current['wind']['speed'];
        $humidity = $current['main']['humidity'];
        $unit_label = $unit === 'imperial' ? '°F' : '°C';
        $wind_unit = $unit === 'imperial' ? 'mph' : 'm/s';
    $feels = isset($current['main']['feels_like']) ? round($current['main']['feels_like']) : $temp;
    $pressure = isset($current['main']['pressure']) ? intval($current['main']['pressure']) : null;
    $sunrise = isset($data['city']['sunrise']) ? date('g:i a', $data['city']['sunrise']) : '';
    $sunset = isset($data['city']['sunset']) ? date('g:i a', $data['city']['sunset']) : '';

    $result_html .= "<div class='bg-white/90 rounded-2xl ring-1 ring-blue-100 p-6 shadow-xl'>";
    $result_html .= "<div class='flex items-center justify-between mb-4'>";
    $result_html .= "<h2 class='text-2xl font-bold text-blue-700'>$city</h2>";
    if ($cached) { $result_html .= "<span class='text-xs text-yellow-800 bg-yellow-100 border border-yellow-300 px-2 py-1 rounded'>Offline cache</span>"; }
    $result_html .= "</div>";
    $result_html .= "<div class='flex items-center gap-4 mb-4'>";
    $result_html .= "<img src='https://openweathermap.org/img/wn/$icon@2x.png' alt='$desc' class='w-16 h-16'>";
    $result_html .= "<div class='flex-1'><div class='text-4xl font-extrabold text-blue-600'>$temp$unit_label</div><div class='text-blue-900 font-semibold'>$desc</div></div>";
    $result_html .= "<div class='grid grid-cols-2 gap-3 text-sm text-blue-800'>";
    $result_html .= "<div class='bg-blue-50 rounded-lg px-3 py-2'><div class='font-semibold'>Feels like</div><div class='font-bold'>$feels$unit_label</div></div>";
    $result_html .= "<div class='bg-blue-50 rounded-lg px-3 py-2'><div class='font-semibold'>Wind</div><div class='font-bold'>$wind $wind_unit</div></div>";
    $result_html .= "<div class='bg-blue-50 rounded-lg px-3 py-2'><div class='font-semibold'>Humidity</div><div class='font-bold'>$humidity%</div></div>";
    if ($pressure) { $result_html .= "<div class='bg-blue-50 rounded-lg px-3 py-2'><div class='font-semibold'>Pressure</div><div class='font-bold'>{$pressure} hPa</div></div>"; }
    if ($sunrise && $sunset) {
      $result_html .= "<div class='bg-blue-50 rounded-lg px-3 py-2 col-span-2 text-center'><span class='font-semibold'>Sunrise</span> $sunrise <span class='mx-2 text-blue-400'>•</span> <span class='font-semibold'>Sunset</span> $sunset</div>";
    }
    $result_html .= "</div></div>";

    // 3-day forecast (group by day)
    $result_html .= "<h3 class='font-semibold text-blue-700 mt-6 mb-3'>3-Day Forecast</h3>";
    $result_html .= "<div class='grid grid-cols-1 sm:grid-cols-3 gap-3'>";
        $days = [];
        foreach ($data['list'] as $item) {
            $dt = $item['dt'];
            $day = date('l', $dt);
            $date = date('Y-m-d', $dt);
            if (!isset($days[$date])) {
                $days[$date] = [
                    'day' => $day,
          'temps' => [],
          'icons' => [],
          'descs' => [],
          'items' => [],
                ];
            }
            $days[$date]['temps'][] = $item['main']['temp'];
            $days[$date]['icons'][] = $item['weather'][0]['icon'];
            $days[$date]['descs'][] = $item['weather'][0]['description'];
      $days[$date]['items'][] = $item;
        }
        $i = 0;
        foreach ($days as $date => $info) {
            if ($i++ >= 3) break;
      $min_temp = round(min($info['temps']));
      $max_temp = round(max($info['temps']));
      // choose icon around midday if present
      $chosen_icon = $info['icons'][0];
      $chosen_desc = ucfirst($info['descs'][0]);
      foreach ($info['items'] as $it) {
        $h = intval(date('G', $it['dt']));
        if ($h >= 11 && $h <= 14) { $chosen_icon = $it['weather'][0]['icon']; $chosen_desc = ucfirst($it['weather'][0]['description']); break; }
      }
      $result_html .= "<div class='bg-blue-50 rounded-xl px-4 py-3 shadow-sm flex items-center gap-3'>";
      $result_html .= "<div class='flex-1'><div class='font-semibold text-blue-800'>{$info['day']}</div><div class='text-xs text-blue-500'>$date</div></div>";
      $result_html .= "<img src='https://openweathermap.org/img/wn/$chosen_icon.png' alt='$chosen_desc' class='w-8 h-8'>";
      $result_html .= "<div class='text-blue-900'>$chosen_desc</div>";
      $result_html .= "<div class='ml-auto font-bold text-blue-700'>$max_temp$unit_label <span class='text-blue-400 font-semibold'>/ $min_temp$unit_label</span></div>";
      $result_html .= "</div>";
        }
    $result_html .= "</div>"; // grid

    // Hourly playbook (next 24h)
    $result_html .= "<h3 class='font-semibold text-blue-700 mt-6 mb-2'>Next 24 hours</h3>";
    $result_html .= "<div class='overflow-x-auto'><div class='flex gap-3 pb-2'>";
    $slice = array_slice($data['list'], 0, 8);
    foreach ($slice as $it) {
      $t = date('g a', $it['dt']);
      $ti = round($it['main']['temp']);
      $ic = $it['weather'][0]['icon'];
      $ds = ucfirst($it['weather'][0]['description']);
      $result_html .= "<div class='min-w-[110px] bg-blue-50 rounded-lg px-3 py-2 text-center shadow-sm'>";
      $result_html .= "<div class='text-xs text-blue-600'>$t</div>";
      $result_html .= "<img src='https://openweathermap.org/img/wn/$ic.png' alt='$ds' class='w-8 h-8 mx-auto'>";
      $result_html .= "<div class='font-bold text-blue-700'>$ti$unit_label</div>";
      $result_html .= "</div>";
    }
    $result_html .= "</div></div>";

    $result_html .= "</div>"; // card end
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OutCast Weather App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-200 via-cyan-100 to-blue-300 min-h-screen py-10 px-4">
    <div class="max-w-3xl mx-auto bg-white/95 backdrop-blur-sm rounded-3xl shadow-xl p-10 relative border border-blue-100">

        <!-- Logout -->
        <a href="logout.php"
           class="absolute top-6 right-6 text-blue-700 bg-blue-100 hover:bg-blue-200 px-4 py-1.5 rounded-lg font-medium text-sm transition">
            Logout (<?php echo htmlspecialchars($_SESSION['user']); ?>)
        </a>

        <!-- Title -->
        <h1 class="text-4xl font-bold text-center text-blue-800 mb-8 tracking-tight">
            OutCast Weather App
        </h1>

        <!-- Search Form -->
        <form id="weather-form"
              method="get"
              action="index.php"
              class="flex flex-col md:flex-row items-center gap-4 mb-8">

            <div class="flex flex-col w-full">
                <label class="text-blue-900 font-semibold mb-1">Location</label>
                <input type="text"
                       name="location"
                       required
                       value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>"
                       placeholder="Enter city or ZIP..."
                       class="w-full px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-900 focus:ring-2 focus:ring-blue-400 outline-none shadow-sm">
            </div>

            <div class="flex flex-col">
                <label class="text-blue-900 font-semibold mb-1">Units</label>
                <select name="unit"
                        class="px-4 py-2.5 rounded-xl border border-blue-200 bg-blue-50 text-blue-900 shadow-sm">
                    <option value="metric" <?php if($unit==='metric') echo 'selected'; ?>>°C</option>
                    <option value="imperial" <?php if($unit==='imperial') echo 'selected'; ?>>°F</option>
                </select>
            </div>

            <div class="flex gap-2 mt-6 md:mt-7">
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl shadow font-semibold">
                    Get Weather
                </button>

                <button type="button"
                        onclick="manualRefresh()"
                        class="bg-cyan-500 hover:bg-cyan-600 text-white px-5 py-2.5 rounded-xl shadow font-semibold">
                    Refresh
                </button>
            </div>
        </form>

        <!-- Auto-refresh JS -->
        <script>
            function manualRefresh() {
                document.getElementById('weather-form').submit();
            }
            setInterval(() => {
                document.getElementById('weather-form').submit();
            }, 600000);
        </script>

        <!-- Weather Results -->
        <div id="weather-result">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl mb-4 shadow">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($result_html): ?>
                <div class="mb-8">
                    <?php echo $result_html; ?>
                </div>
            <?php endif; ?>

            <!-- Last Searches -->
            <?php
            $user = $_SESSION['user'];
            $history_file = __DIR__ . '/data/history_' . md5($user) . '.json';
            $history = file_exists($history_file) ? json_decode(@file_get_contents($history_file), true) : [];
            if (!is_array($history)) { $history = []; }

            if ($history):
            ?>
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 shadow-sm">
                <h3 class="font-semibold text-blue-700 mb-3 text-lg">Recent Searches</h3>
                <ul class="divide-y divide-blue-100">
                    <?php foreach ($history as $h): ?>
                        <li class="py-2 flex justify-between text-blue-900">
                            <span><?php echo htmlspecialchars($h['location']); ?> (<?php echo $h['unit']==='imperial'?'°F':'°C'; ?>)</span>
                            <span class="text-xs text-blue-400">
                                <?php echo date('Y-m-d H:i', $h['time']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
