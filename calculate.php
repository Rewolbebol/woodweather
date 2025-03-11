<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Configuration - Add your API key here
$api_key = '17139ab945fb8111edc7436c20cb1940'; // Replace with your actual API key

// Location options with coordinates
$locations = [
    'Bauskas 207A' => [
        'latitude' => 56.90070445004411,
        'longitude' => 24.144126290836965,
    ],
    'Nautrēnu 24' => [
        'latitude' => 56.96230558114238,
        'longitude' => 24.301562251033424,
    ],
];

// Hardcoded Powerplant Output Data for Locations
$powerplantOutput = [
    'Bauskas 207A' => [
        '-18' => 1.61,
        '-15' => 1.58,
        '-14' => 1.58,
        '-13' => 1.61,
        '-12' => 1.42,
        '-10' => 1.71,
        '-7' => 1.69,
        '-6' => 1.64,
        '-5' => 1.69,
        '-4' => 1.65,
        '-3' => 1.66,
        '-2' => 1.62,
        '-1' => 1.64,
        '0' => 1.57,
        '1' => 1.52,
        '2' => 1.45,
        '3' => 1.36,
        '4' => 1.29,
        '5' => 1.18,
        '6' => 1.08,
        '7' => 0.98,
        '8' => 0.88,
        '9' => 0.78,
        '10' => 0.68,
        '11' => 0.61,
        '12' => 0.54,
        '13' => 0.45,
        '14' => 0.36,
        '15' => 0.31,
        '16' => 0.28,
        '17' => 0.25,
        '18' => 0.24,
        '19' => 0.23,
        '20' => 0.37,
    ],
    'Nautrēnu 24' => [
        '-21' => 1.58,
        '-20' => 1.53,
        '-19' => 1.61,
        '-18' => 1.45,
        '-17' => 1.49,
        '-16' => 1.43,
        '-15' => 1.34,
        '-14' => 1.38,
        '-13' => 1.22,
        '-12' => 1.24,
        '-11' => 1.23,
        '-10' => 1.62,
        '-9' => 1.35,
        '-8' => 1.31,
        '-7' => 1.34,
        '-6' => 1.26,
        '-5' => 1.24,
        '-4' => 1.22,
        '-3' => 1.24,
        '-2' => 1.31,
        '-1' => 1.36,
        '0' => 1.27,
        '1' => 1.18,
        '2' => 1.15,
        '3' => 1.12,
        '4' => 1.06,
        '5' => 1.01,
        '6' => 0.98,
        '7' => 0.96,
        '8' => 0.94,
        '9' => 0.94,
        '10' => 0.89,
        '11' => 0.85,
        '12' => 0.8,
        '13' => 0.77,
        '14' => 0.71,
        '15' => 0.68,
        '16' => 0.66,
        '17' => 0.62,
        '18' => 0.59,
        '19' => 0.56,
        '20' => 0.54,
    ],
];
//woodchip parameters
$woodchipEfficiency = 0.7;
$powerplantLosses = 0.12;
// Default timezone in case the API doesn't return one
$defaultTimezone = 'Europe/Riga'; // You can change this to your preferred default

// Input validation and processing
$selectedLocation = null;
$latitude = null;
$longitude = null;
$startDateTime = null;
$endDateTime = null;
$woodchipM3 = null;

// Check if a location is selected
if (isset($_POST['location']) && array_key_exists($_POST['location'], $locations)) {
    $selectedLocation = $_POST['location'];
    $latitude = $locations[$selectedLocation]['latitude'];
    $longitude = $locations[$selectedLocation]['longitude'];
} else {
    die("Error: No location selected.");
}
//get woodchip
if (isset($_POST['woodchip_m3'])) {
    $woodchipM3 = floatval($_POST['woodchip_m3']);
    if ($woodchipM3 <= 0) {
        die("Error: Woodchip volume must be greater than zero.");
    }
} else {
    die("Error: Woodchip volume is required.");
}

// API request with API key
$url = "https://api.weatherxu.com/v1/weather?lat={$latitude}&lon={$longitude}&units=metric&api_key={$api_key}";

// Using cURL for better error handling
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    die("API request failed with HTTP code: {$http_code}");
}

$data = json_decode($response, true);

if (!$data || !$data['success']) {
    $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
    die("Weather service error: {$error_message}");
}
// Get timezone from API response, or use default
$timezone = isset($data['data']['timezone']) ? $data['data']['timezone'] : $defaultTimezone;
$targetTimeZone = new DateTimeZone($timezone);

// Validate and process start date and time
if (isset($_POST['start_date']) && isset($_POST['start_time'])) {
    $startDateTimeString = $_POST['start_date'] . ' ' . $_POST['start_time'];
    // Create DateTime object with the target timezone
    $startDateTime = DateTime::createFromFormat('Y-m-d H:i', $startDateTimeString, $targetTimeZone);
    if (!$startDateTime) {
        die("Error: Invalid start date or time format.");
    }
}

// Validate and process end date and time
if (isset($_POST['end_date']) && isset($_POST['end_time'])) {
    $endDateTimeString = $_POST['end_date'] . ' ' . $_POST['end_time'];
    // Create DateTime object with the target timezone
    $endDateTime = DateTime::createFromFormat('Y-m-d H:i', $endDateTimeString, $targetTimeZone);
    if (!$endDateTime) {
        die("Error: Invalid end date or time format.");
    }
}

if ($startDateTime > $endDateTime) {
    die("Error: Start Date and Time must be before End Date and Time.");
}
//change now time to target timezone
$now = new DateTime("now", $targetTimeZone);
if ($startDateTime < $now) {
    die("Error: Start Date and Time cannot be in the past");
}

// Process and display data
$hourlyData = $data['data']['hourly']['data'];

// Group by day and filter data by time range
$grouped = [];
$startTimestamp = $startDateTime->getTimestamp();
$endTimestamp = $endDateTime->getTimestamp();
$totalHours = 0;
$totalPowerplantOutputMW = 0;
$hourlyWoodchipDuration = [];
$totalWoodchipMW = $woodchipM3 * $woodchipEfficiency * (1 - $powerplantLosses);
$allHourlyData = []; //add new array to hold hourly data with cummulative value
$hourlyCalculations = [];

foreach ($hourlyData as $hour) {
    $dt = new DateTime();
    $dt->setTimestamp($hour['forecastStart'])
        ->setTimezone($targetTimeZone);

    $dateKey = $dt->format('Y-m-d');
    if ($hour['forecastStart'] >= $startTimestamp && $hour['forecastStart'] <= $endTimestamp) {
        //change temperature if minus 0 to zero
        $temperature = round($hour['temperature']);
        if ($temperature == -0) {
            $temperature = 0;
        }
        $powerplantOutputMW = isset($powerplantOutput[$selectedLocation]["{$temperature}"]) ? $powerplantOutput[$selectedLocation]["{$temperature}"] : 0; // Default to 0 if not found

        $grouped[$dateKey][] = [
            'time' => $dt->format('H:i'),
            'temp' => $temperature,
            'timestamp' => $hour['forecastStart'],
            // Add powerplant output to the data
            'powerplantOutput' => $powerplantOutputMW,
        ];
        $allHourlyData[] = [
            'time' => $dt->format('Y-m-d H:i'),
            'temp' => $temperature,
            'powerplantOutput' => $powerplantOutputMW,
            'timestamp' => $hour['forecastStart'], // Include timestamp
        ];
        $hourlyCalculations[] = [
            'time' => $dt->format('Y-m-d H:i'),
            'powerplantOutput' => $powerplantOutputMW,
        ];
        $totalPowerplantOutputMW += $powerplantOutputMW;
        $totalHours++;
    }
}

// Calculate woodchip end time precisely
$cumulativeWoodchipNeededTotal = 0;
$lastCorrectHourTimestamp = null;
$lastCorrectHour = null; // Track the last correct hour data

foreach ($allHourlyData as $hour) {
    $cumulativeWoodchipNeededTotal += $hour['powerplantOutput'];
    $neededM3 = ($cumulativeWoodchipNeededTotal * (1 / $woodchipEfficiency) * (1 / (1 - $powerplantLosses)));

    if ($neededM3 <= $woodchipM3) {
        $lastCorrectHourTimestamp = DateTime::createFromFormat('Y-m-d H:i', $hour['time'], $targetTimeZone)->getTimestamp();
        $lastCorrectHour = $hour;
    } else {
        break; // Stop when woodchip runs out
    }
}

$woodchipEndTime = clone $startDateTime;
if ($lastCorrectHourTimestamp !== null) {
    $woodchipEndTime->setTimestamp($lastCorrectHourTimestamp);
}

// Calculate total duration with decimal precision
$diff = $woodchipEndTime->getTimestamp() - $startDateTime->getTimestamp();
//use floor and add rest of seconds to get two digits.
$hours = floor($diff / 3600);
$minutes = floor(($diff % 3600) / 60);
$seconds = $diff % 60;
$decimalHours = round(($minutes * 60 + $seconds) / 3600, 2);
$formattedWoodchipDuration = number_format($hours + $decimalHours, 2);


?>

<!DOCTYPE html>
<html>

<head>
    <title>Rezultāti</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function showTooltip(event, text) {
            var tooltip = document.getElementById('tooltip');
            tooltip.innerHTML = text;
            tooltip.style.display = 'block';
            tooltip.style.left = (event.pageX + 10) + 'px';
            tooltip.style.top = (event.pageY + 10) + 'px';
        }

        function hideTooltip() {
            var tooltip = document.getElementById('tooltip');
            tooltip.style.display = 'none';
        }

        // Function to toggle tooltip on touch devices
        function toggleTooltip(event, text) {
            var tooltip = document.getElementById('tooltip');
            if (tooltip.style.display === 'block') {
                tooltip.style.display = 'none';
            } else {
                showTooltip(event, text);
            }
            //prevent default touch behaviour
            event.preventDefault();

        }
        document.addEventListener('touchstart', function(event) {
            var tooltip = document.getElementById('tooltip');

            if (!tooltip.contains(event.target) && event.target.className !== "hour") {
                tooltip.style.display = 'none';
            }
        });
    </script>
</head>

<body>
    <div class="container">
        <div id="tooltip"></div>
        <h1>Temperatūru prognoze</h1>
        <div class="main-content">
            <div class="debug-data">
                <h2>Kalkulācijas:</h2>
                <div class="debug-hourly-data">
                    <?php
                    $debugCumulativeWoodchipNeeded = 0;
                    foreach ($hourlyCalculations as $calculation) :
                        $debugCumulativeWoodchipNeeded += $calculation['powerplantOutput'];
                        $debugNeededM3 = ($debugCumulativeWoodchipNeeded * (1 / $woodchipEfficiency) * (1 / (1 - $powerplantLosses)));

                    ?>
                        <div class="debug-hour">
                            <?= $calculation['time'] ?> - Jauda: <?= round($calculation['powerplantOutput'], 2) ?> MW - Šķeldas kopā: <?= number_format($debugNeededM3, 2) ?> m3
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="forecast-results">
                <?php if (isset($selectedLocation)) : ?>
                    <p class="forecast-location">Prognoze priekš: <strong><?= $selectedLocation ?></strong></p>
                <?php endif; ?>

                <p class="forecast-period">No <strong>
                        <?= $startDateTime->format('Y-m-d H:i') ?>
                    </strong> līdz <strong>
                        <?= $endDateTime->format('Y-m-d H:i') ?>
                    </strong> </p>
                <?php if ($totalHours == 0) : ?>
                    <p class="no-data">Nav datu priekš tāda intervālā </p>
                <?php endif; ?>
                <?php if ($lastCorrectHour !== null) : ?>
                    <p class="woodchip-duration">
                        Ar <strong><?= $woodchipM3 ?></strong> m<sup>3</sup> šķeldas pietiks uz
                        <strong><?= $formattedWoodchipDuration ?></strong> stundām, līdz
                        <strong><?= $woodchipEndTime->format("Y-m-d H:i") ?></strong>.
                    </p>

                    <div class="forecast-data">
                        <?php
                        $cumulativeWoodchipNeededTotal = 0;
                        $firstIncorrectHour = false;
                        $hoursCount = 0;
                        $startDateTimeForCounting = clone $startDateTime;
                        foreach ($grouped as $date => $hours) : ?>
                            <div class="day-container">
                                <div class="date-column">
                                    <?= date('l, F jS', strtotime($date)) ?>
                                </div>
                                <div class="hourly-bar">
                                    <?php
                                    foreach ($hours as $hour) :
                                        $currentDateTime = new DateTime();
                                        $currentDateTime->setTimestamp($hour["timestamp"])->setTimezone($targetTimeZone);
                                        $hoursCount =  ($currentDateTime->getTimestamp() - $startDateTimeForCounting->getTimestamp()) / 3600;

                                        $cumulativeWoodchipNeededTotal += $hour['powerplantOutput'];
                                        $neededM3 = ($cumulativeWoodchipNeededTotal * (1 / $woodchipEfficiency) * (1 / (1 - $powerplantLosses)));
                                        $neededM3Rounded = round($neededM3, 2);
                                        $hoursRounded = number_format($hoursCount, 2);

                                        $woodchipEndReached = $neededM3 > $woodchipM3;
                                        $woodchipEndReachedOrNext = $currentDateTime->getTimestamp() > $lastCorrectHourTimestamp && $lastCorrectHourTimestamp !== null;

                                    ?>
                                        <div class="hour <?php
                                                            if ($woodchipEndReached && $woodchipEndReachedOrNext) {
                                                                if (!$firstIncorrectHour) {
                                                                    echo "woodchip-end";
                                                                    $firstIncorrectHour = true;
                                                                } else {
                                                                    echo "woodchip-end-next";
                                                                }
                                                            }

                                                            ?>" onmouseover="showTooltip(event, 'Šķeldas daudzums līdz šai vietai: <?= $neededM3Rounded ?> m³ , stundu skaits: <?= $hoursRounded ?>')" onmouseout="hideTooltip()"
                                            ontouchstart="toggleTooltip(event, 'Šķeldas daudzums līdz šai vietai: <?= $neededM3Rounded ?> m³ , stundu skaits: <?= $hoursRounded ?>')">
                                            <div class="hour-time">
                                                <?= $hour['time'] ?>
                                            </div>
                                            <div class="hour-temp">
                                                <?= $hour['temp'] ?>°C
                                            </div>

                                            <div class="hour-powerplant">
                                                <?= $hour['powerplantOutput'] ?> MW
                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <a href="index.php" class="new-search-link">Atpakaļ uz izvelni</a>
    </div>
</body>

</html>