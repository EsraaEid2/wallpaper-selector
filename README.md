# wallpaper-selector script

@ Author : Esra'a Eid
@ Email : esraa.eidd2@gmail.com

Overview


This PHP script determines the appropriate wallpaper based on the time of day at a given latitude and longitude. It fetches timezone and sunrise/sunset data from external APIs and selects an image accordingly.

How It Works

1- The script accepts latitude and longitude as command-line arguments.

2- It retrieves the timezone of the location using TimeZoneDB API.

3- It fetches sunrise and sunset times for two consecutive days from Sunrise-Sunset API to handle boundary cases.

4- It determines whether the current local time is during morning, noon, evening, sunrise, sunset, or night.

5- The script outputs the name of the corresponding wallpaper image.

Usage

Run the script with latitude and longitude:

php wallpaper.php LATITUDE LONGITUDE

Example:

php wallpaper.php 40.7128 -74.0060

Dependencies

- PHP (CLI)
- Internet connection to access APIs

APIs Used

1- TimeZoneDB API: Fetches the timezone of the given location.

Endpoint: http://api.timezonedb.com/v2.1/get-time-zone?key=YOUR_KEY&format=json&by=position&lat=LATITUDE&lng=LONGITUDE

Response Example:

{
  "status": "OK",
  "zoneName": "America/New_York"
}

2- Sunrise-Sunset API: Retrieves sunrise and sunset times.

Endpoint: https://api.sunrise-sunset.org/json?lat=LATITUDE&lng=LONGITUDE&formatted=0&date=DATE

Response Example:

{
  "status": "OK",
  "results": {
    "sunrise": "2024-03-18T10:30:00+00:00"
    "sunset": "2024-03-18T22:15:00+00:00"
  }
}

Possible Outputs

Depending on the time of day, the script outputs one of the following image filenames:

- sunrise.png
- morning.png
- noon.png
- evening.png
- sunset.png
- night.png

Error Handling

If API requests fail, it defaults to night.png.

If timezone is invalid, it logs an error.

Uses error_log() to log issues for debugging.
