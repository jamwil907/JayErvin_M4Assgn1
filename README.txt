OutCast - Standalone Weather App
===========================================

Features:
- User authentication (register/login/logout)
- Search weather by city or zip code
- 3-day forecast, wind, humidity, user-friendly conditions
- Unit toggle (Celsius/Fahrenheit)
- Offline support (shows last known data if network fails)
- Error handling for invalid input, unknown locations
- Manual and auto-refresh (every 10 minutes)
- User search history (last 5 searches)
- Data management and security (input validation, output escaping)
- Automated testing and build script (test_weather.php, build.bat)
- Tailwind CSS UI via CDN (no build tooling required)

Setup:
1. Place all files in your PHP web server directory (e.g., XAMPP's htdocs/OutCast)
2. Create a writable 'data' directory for cache and user data
3. Get a free API key from https://openweathermap.org/api
4. Edit includes/config.php and set your API key
5. Open register.php to create your first user
6. Login and use the app!

Notes:
- ZIP search defaults to US if no country code is provided (e.g., "94040" -> "94040,us"). To search in another country, use "<zip>,<cc>" like "10115,de".
- If the network is unavailable, the app displays the last cached results for the requested location.

Security:
- All user input is validated and output is escaped
- Passwords are hashed
- User data and search history are stored per user in /data/

Testing/Build:
- Run test_weather.php to test weather API
- Run build.bat for syntax checks and tests

No Composer or external PHP dependencies required.
