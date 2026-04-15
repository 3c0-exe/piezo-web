===============================================================================
PIEZO DASHBOARD — NEW DEVICE SETUP GUIDE
===============================================================================
Run these steps in order on any new Windows machine with XAMPP installed.
===============================================================================

PRE-REQUISITES (install these first if not present)
-------------------------------------------------------
1. XAMPP         → https://www.apachefriends.org  (PHP 8.2+, MySQL, Apache)
2. Composer      → https://getcomposer.org/download
3. Node.js 18+   → https://nodejs.org
4. Git           → https://git-scm.com (optional, if cloning from repo)

Verify installs by opening a terminal and running:
   php -v
   composer -V
   node -v
   npm -v

===============================================================================
STEP 1 — START XAMPP SERVICES
===============================================================================
Open XAMPP Control Panel and click START on:
   - Apache
   - MySQL

===============================================================================
STEP 2 — CREATE THE DATABASE
===============================================================================
Open phpMyAdmin at: http://localhost/phpmyadmin

Create a new database:
   Name:      piezo_dashboard
   Collation: utf8mb4_unicode_ci

===============================================================================
STEP 3 — PLACE PROJECT FILES
===============================================================================
Copy or clone the project into your XAMPP htdocs folder:
   C:\xampp\htdocs\piezo-dashboard\

===============================================================================
STEP 4 — INSTALL PHP DEPENDENCIES
===============================================================================
Open a terminal inside the project folder:
   cd C:\xampp\htdocs\piezo-dashboard

Run:
   composer install

===============================================================================
STEP 5 — CONFIGURE ENVIRONMENT FILE
===============================================================================
Copy the example env file:
   copy .env.example .env

Then open .env and set these values:
   APP_NAME="Piezo Dashboard"
   APP_URL=http://localhost:8000
   APP_DEBUG=true

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=piezo_dashboard
   DB_USERNAME=root
   DB_PASSWORD=

   SESSION_DRIVER=database
   SESSION_LIFETIME=120

   ESP_API_KEY=piezo-esp32-secret-key-2024

===============================================================================
STEP 6 — GENERATE APP KEY
===============================================================================
   php artisan key:generate

===============================================================================
STEP 7 — RUN MIGRATIONS AND SEED DATABASE
===============================================================================
   php artisan migrate:fresh --seed

This will create all 5 tables and seed:
   - Admin user:      admin@piezo.local / piezo@admin2024
   - System settings: singleton row (id=1, tracking off)
   - Sample students: Juan dela Cruz, Maria Santos, Jose Reyes

Verify in phpMyAdmin that these tables exist:
   students, energy_logs, system_settings, charging_sessions, event_logs

===============================================================================
STEP 8 — INSTALL NODE DEPENDENCIES AND BUILD ASSETS
===============================================================================
   npm install
   npm run build

This compiles Tailwind CSS and JS into public/build/

===============================================================================
STEP 9 — START THE DEVELOPMENT SERVER
===============================================================================
   php artisan serve

The app will be available at: http://localhost:8000

IMPORTANT: Always use localhost:8000 in your browser.
           Do NOT use 127.0.0.1:8000 — Chrome will block API fetch calls.

===============================================================================
STEP 10 — LOG IN
===============================================================================
Open your browser and go to: http://localhost:8000/login

   Email:    admin@piezo.local
   Password: piezo@admin2024

===============================================================================
STEP 11 — TEST WITH THE SIMULATOR (no hardware needed)
===============================================================================
Open a SECOND terminal in the project folder and run:

   php artisan piezo:simulate --interval=3 --url=http://localhost:8000

Then in the browser:
   1. Go to Dashboard
   2. Select a student from the Active Student picker → click Set
   3. Click Start Tracking
   4. Watch the simulator push data and the dashboard update live

===============================================================================
QUICK REFERENCE — USEFUL COMMANDS
===============================================================================
Start server:              php artisan serve
Run simulator:             php artisan piezo:simulate --interval=3 --url=http://localhost:8000
Fresh DB + reseed:         php artisan migrate:fresh --seed
Clear all caches:          php artisan optimize:clear
Rebuild frontend assets:   npm run build
Watch assets (dev mode):   npm run dev

===============================================================================
QUICK REFERENCE — ROUTES
===============================================================================
Login page:                http://localhost:8000/login
Live Dashboard:            http://localhost:8000/dashboard
Student Management:        http://localhost:8000/students
Reports:                   http://localhost:8000/reports

ESP32 API (needs X-ESP-Key header):
   POST  http://localhost:8000/api/data        ← sensor payload
   GET   http://localhost:8000/api/command     ← tracking on/off check
   POST  http://localhost:8000/api/connect     ← device boot log

Dashboard poll (browser session):
   GET   http://localhost:8000/api/dashboard-data

===============================================================================
QUICK REFERENCE — DEFAULT CREDENTIALS
===============================================================================
   Admin email:    admin@piezo.local
   Admin password: piezo@admin2024
   ESP32 API key:  piezo-esp32-secret-key-2024 (X-ESP-Key header)

===============================================================================
TROUBLESHOOTING
===============================================================================
PROBLEM: Dashboard metrics not updating / fetch errors in browser console
FIX:     Make sure you are using localhost:8000 NOT 127.0.0.1:8000

PROBLEM: SQLSTATE sessions table not found
FIX:     Run: php artisan migrate:fresh --seed

PROBLEM: Class not found / autoload errors
FIX:     Run: composer install
              php artisan optimize:clear

PROBLEM: Tailwind styles not loading / page looks unstyled
FIX:     Run: npm install && npm run build

PROBLEM: ESP32 API returns 401 Unauthorized
FIX:     Make sure X-ESP-Key header matches ESP_API_KEY in .env
         Default key: piezo-esp32-secret-key-2024

PROBLEM: Migration fails with foreign key error
FIX:     Check migration file order — students must run before
         energy_logs, system_settings, and charging_sessions.
         Rename files so students = 100001, energy_logs = 100002,
         system_settings = 100003, charging_sessions = 100004,
         event_logs = 100005

PROBLEM: Port 8000 already in use
FIX:     Run on a different port: php artisan serve --port=8080
         Then update APP_URL in .env to http://localhost:8080

===============================================================================
PROJECT STRUCTURE (key files)
===============================================================================
app/
  Console/Commands/SimulatePiezo.php         ← simulator command
  Http/Controllers/
    Auth/LoginController.php
    Api/Esp32Controller.php                  ← ESP32 API
    Api/DashboardController.php              ← dashboard poll API
    DashboardController.php                  ← web dashboard
    StudentController.php
    ReportController.php
  Http/Middleware/EspApiKey.php              ← X-ESP-Key guard
  Models/
    Student.php, EnergyLog.php, SystemSetting.php,
    ChargingSession.php, EventLog.php
  Services/AnalyticsService.php              ← pace + ETA calculations

resources/views/
  layouts/app.blade.php                      ← master dark sidebar layout
  auth/login.blade.php
  dashboard/index.blade.php                  ← live dashboard
  students/index.blade.php
  students/create.blade.php
  reports/index.blade.php

routes/
  web.php                                    ← all browser routes
  api.php                                    ← ESP32 + dashboard poll routes

database/
  migrations/                                ← all 5 table migrations
  seeders/DatabaseSeeder.php

===============================================================================
END OF SETUP GUIDE
===============================================================================