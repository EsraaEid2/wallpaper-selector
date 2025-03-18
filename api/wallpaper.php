#!/usr/bin/env php
<?php
require_once('config.php');

// Validate arguments
if ($argc < 3) {
    fwrite(STDERR, "Usage: php wallpaper.php LATITUDE LONGITUDE\n");
    exit(1);
}

$latitude = $argv[1];
$longitude = $argv[2];

// 1. Get timezone data
$timezoneApiUrl = "http://api.timezonedb.com/v2.1/get-time-zone?" . 
                "key=6OR2Q2GXI650&format=json&by=position&lat=$latitude&lng=$longitude";
$timezoneResponse = @file_get_contents($timezoneApiUrl);

if (!$timezoneResponse) {
    error_log("Timezone API request failed");
    echo "night.png";
    exit;
}

$timezoneData = json_decode($timezoneResponse, true);

if (!$timezoneData || ($timezoneData['status'] ?? '') !== 'OK') {
    error_log("Timezone API error: ".json_encode($timezoneData));
    echo "night.png";
    exit;
}

try {
    $localTimezone = new DateTimeZone($timezoneData['zoneName']);
} catch (Exception $e) {
    error_log("Invalid timezone: ".$timezoneData['zoneName'] ?? 'unknown');
    echo "night.png";
    exit;
}

// 2. Get current LOCAL time
try {
    $now = new DateTime('now', $localTimezone);
    error_log("Actual Local Now: ".$now->format('Y-m-d H:i:s T'));
} catch (Exception $e) {
    error_log("DateTime error: ".$e->getMessage());
    echo "night.png";
    exit;
}

// 3. Get sunrise/sunset for TWO days to handle date boundaries
$sunTimes = [];
$datesToCheck = [
    $now->format('Y-m-d'),
    $now->modify('+1 day')->format('Y-m-d')
];

foreach ($datesToCheck as $date) {
    try {
        // Convert local date to UTC
        $utcDate = (new DateTime($date, $localTimezone))
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format('Y-m-d');
        
        $sunApiUrl = "https://api.sunrise-sunset.org/json?lat=$latitude&lng=$longitude&formatted=0&date=$utcDate";
        $sunResponse = @file_get_contents($sunApiUrl);

        if ($sunResponse) {
            $sunData = json_decode($sunResponse, true);
            if ($sunData && ($sunData['status'] ?? '') === 'OK') {
                $sunriseUtc = new DateTime($sunData['results']['sunrise'], new DateTimeZone('UTC'));
                $sunsetUtc = new DateTime($sunData['results']['sunset'], new DateTimeZone('UTC'));
                
                $sunriseUtc->setTimezone($localTimezone);
                $sunsetUtc->setTimezone($localTimezone);
                
                $sunTimes[] = [
                    'sunrise' => $sunriseUtc,
                    'sunset' => $sunsetUtc
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Date handling error: ".$e->getMessage());
    }
}

// 4. Find correct sun period
$currentTime = new DateTime('now', $localTimezone);
$isNight = true;
$wallpaper = "night.png";

foreach ($sunTimes as $period) {
    if ($currentTime >= $period['sunrise'] && $currentTime <= $period['sunset']) {
        $isNight = false;
        
        // Calculate time windows
        $sunriseWindowEnd = clone $period['sunrise'];
        $sunriseWindowEnd->add(new DateInterval('PT30M'));
        
        $sunsetWindowStart = clone $period['sunset'];
        $sunsetWindowStart->sub(new DateInterval('PT30M'));
        
        if ($currentTime <= $sunriseWindowEnd) {
            $wallpaper = "sunrise.png";
        } elseif ($currentTime >= $sunsetWindowStart) {
            $wallpaper = "sunset.png";
        } else {
            $morningEnd = clone $period['sunrise'];
            $morningEnd->add(new DateInterval('PT4H'));
            
            $eveningStart = clone $period['sunset'];
            $eveningStart->sub(new DateInterval('PT4H'));
            
            if ($currentTime <= $morningEnd) {
                $wallpaper = "morning.png";
            } elseif ($currentTime >= $eveningStart) {
                $wallpaper = "evening.png";
            } else {
                $wallpaper = "noon.png";
            }
        }
        break;
    }
}

echo $wallpaper;