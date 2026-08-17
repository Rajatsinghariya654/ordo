# Ordo — Task Management + AI + Geo-Spatial System

Ordo is a full-stack Laravel 12 web application built to manage workspace-based task workflows with AI assistance, location-aware planning, and admin governance. It is designed for both personal productivity and team collaboration in a modern business environment.

---

## Project Overview

Ordo brings together several core capabilities in one application:

- Workspace-based collaboration for teams
- Kanban task management with task groups and statuses
- AI-powered task creation and smart planning
- Location-aware task management using maps and geocoding
- Admin-level moderation and governance controls
- Activity tracking, analytics, and reporting

This project is structured to support real-world task coordination, operational visibility, and enterprise-style user management.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Database | MySQL / MariaDB / SQLite-compatible Laravel setup |
| Authentication | Laravel session auth |
| AI | Google Gemini API |
| Maps | Leaflet.js + OpenStreetMap |
| Geocoding | Nominatim |
| Routing | OSRM |
| Charts | Chart.js |
| Icons | Blade Heroicons |

---

## Key Features

### 1. Workspace and Team Management
- Create and manage workspaces
- Join workspaces using access codes
- Invite and manage members
- Approve or reject member requests
- Update member roles and remove members when needed

### 2. Task Management
- Create, update, and delete tasks
- Organize tasks into groups
- Move tasks across status columns
- Add subtasks, attachments, and deadlines
- Track task activity and timeline data

### 3. AI-Powered Features
- Parse natural-language task intent
- Generate task content from plain English prompts
- Suggest task details such as title, description, priority, and due date
- AI-assisted generation of task groups and recurring planning
- Store AI logs with metadata and execution details

### 4. Geo-Spatial Task Features
- Add location to tasks using map pins
- Search places by name via geocoding
- Use current device location
- Show nearby tasks within selectable radius ranges
- Display walking distance and time to each task

### 5. Admin and Governance Controls
- User suspension, blocking, and reactivation
- Admin access requests and approvals
- Account switch requests between personal and business profile states
- Moderation activity logs
- System settings configuration
- Maintenance mode for platform restrictions

### 6. Analytics and Monitoring
- Personal analytics dashboard
- Admin analytics for AI and platform usage
- Activity feed for user actions
- Completion, workload, and time-based reporting

---

## Screenshots

Below are real screenshots from the application, organized by user role and feature area.

### Admin Screens

![Admin Dashboard](public/Screenshotes/adminUsers/Screenshot%202026-08-17%20132622.png)

![Admin User Management](public/Screenshotes/adminUsers/Screenshot%202026-08-17%20132637.png)

![Admin Logs and Settings](public/Screenshotes/adminUsers/Screenshot%202026-08-17%20132742.png)

### Business User Screens

![Business Dashboard](public/Screenshotes/businessUsers/Screenshot%202026-08-17%20131919.png)

![Business Task Board](public/Screenshotes/businessUsers/Screenshot%202026-08-17%20132004.png)

![Business Workspace View](public/Screenshotes/businessUsers/Screenshot%202026-08-17%20132239.png)

### Personal User Screens

![Personal Dashboard](public/Screenshotes/personalUsers/Screenshot%202026-08-17%20130801.png)

![Personal Task Activity](public/Screenshotes/personalUsers/Screenshot%202026-08-17%20130829.png)

![Personal Nearby Tasks](public/Screenshotes/personalUsers/Screenshot%202026-08-17%20131145.png)

---

## Project Structure

The application follows a standard Laravel MVC architecture with additional business logic for workspace management, AI integrations, and moderation workflows.

```bash
ordo/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php
│   │   │   ├── AiEngineController.php
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── TaskController.php
│   │   │   ├── TaskGroupController.php
│   │   │   └── WorkspaceController.php
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   ├── AccountSwitchRequest.php
│   │   ├── ActivityLog.php
│   │   ├── AdminAccessRequest.php
│   │   ├── AiLog.php
│   │   ├── ModerationLog.php
│   │   ├── SystemSetting.php
│   │   ├── Task.php
│   │   ├── TaskAttachment.php
│   │   ├── TaskGroup.php
│   │   ├── TaskSubtask.php
│   │   ├── User.php
│   │   └── Workspace.php
│   ├── Policies/
│   │   └── TaskPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Traits/
│       └── GeocodesLocations.php
├── bootstrap/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── services.php
│   └── session.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── Screenshotes/
│   │   ├── adminUsers/
│   │   ├── businessUsers/
│   │   └── personalUsers/
│   ├── images/
│   ├── hot/
│   ├── index.php
│   └── storage/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── admin/
│       ├── auth/
│       ├── dashboard/
│       ├── layouts/
│       ├── profile/
│       ├── tasks/
│       └── ...
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── artisan
├── composer.json
├── package.json
├── phpunit.xml
├── README.md
├── vite.config.js
└── ...
```

### Module Responsibilities

- `app/Http/Controllers` handles all route logic for dashboard, auth, admin, tasks, AI, and profile flows.
- `app/Models` defines the domain entities like users, tasks, workspaces, logs, and requests.
- `app/Policies` controls authorization rules for task access and update permissions.
- `database/migrations` manages database schema changes and feature-specific tables.
- `resources/views` contains all Blade templates for login, dashboards, admin pages, task views, and user profiles.
- `routes/web.php` contains the main application routing structure and middleware protection.
- `public/Screenshotes` stores UI screenshots for documentation and review.
- `tests/` is reserved for project-level automated testing coverage.

---

## Local Setup

### 1. Clone the repository

```bash
git clone <repository-url>
cd ordo
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Create environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure `.env`

Update your database and AI configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ordo_db
DB_USERNAME=root
DB_PASSWORD=

GEMINI_API_KEY=your_gemini_api_key
ADMIN_SIGNUP_CODE=your_secret_admin_code
```

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Start the app

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Open the project here:

```bash
http://127.0.0.1:8000
```

---

## Testing

```bash
php artisan test
```

---

## Notes

- This project is built as a Laravel application for professional task and workspace management.
- AI integration is used to simplify task capture and planning.
- Geo-location features make the system more practical for field-based or location-aware workflows.
- Admin controls and activity tracking provide governance for multi-user environments.

---

## License

This project is open-source and is licensed under the MIT License.

---

## Credits

Built as a PHP development project with Laravel, focused on task collaboration, AI-assisted productivity, and operational control.
