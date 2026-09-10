# MentalState API

MentalState is a Laravel API for a video-based mental-health assessment platform. Users can register with email OTP verification, upload a video, receive external machine-learning analysis, complete an eight-branch KBS assessment, and retrieve assessment reports.

The project demonstrates a backend workflow built around authentication, media processing, asynchronous jobs, external services, notifications, and reporting. It is designed to support a separate mobile or frontend client.

## Features

- Email OTP registration and verification
- JWT access tokens and refresh-token rotation
- Separate admin authentication and dashboard reporting
- MP4, AVI, and MOV video uploads with a 100 MB limit
- FFmpeg audio extraction and thumbnail generation
- Database-backed queued video analysis
- External ML processing integration and status callbacks
- Eight-branch KBS assessment flow backed by a Python service
- Assessment report storage and platform analytics
- Firebase Cloud Messaging notifications
- User profiles and avatar uploads
- Throttling on sensitive authentication endpoints

## Technology Stack

- PHP 8.2+
- Laravel 12
- MariaDB/MySQL for local application data
- Eloquent ORM and Laravel migrations
- `php-open-source-saver/jwt-auth` for JWT authentication
- Laravel Sanctum for the default framework user route
- FFmpeg/FFprobe through `pbmedia/laravel-ffmpeg`
- Firebase Admin SDK through `kreait/laravel-firebase`
- Database queues for background processing
- Vite, Tailwind CSS, and Axios
- Pest/PHPUnit for testing

## Architecture

```text
Client
  |
  v
Laravel API routes
  |
  +--> Authentication services --> Users, refresh tokens, OTP cache
  |
  +--> Video service --> FFmpeg --> Video records
  |                         |
  |                         v
  |                    Queue job --> External ML service
  |                                      |
  |                                      v
  |                              Status callback --> Scores + FCM notification
  |
  +--> KBS assessment service --> Python branch service --> Assessment reports
  |
  +--> Admin dashboard service --> Platform analytics
```

Main implementation areas:

- `routes/api.php`: API endpoints and middleware assignments
- `app/Http/Controllers`: request entry points
- `app/Services`: authentication, uploads, assessments, home data, and analytics
- `app/Models`: Eloquent models and relationships
- `app/Jobs`: asynchronous video processing and Firebase notifications
- `database/migrations`: database schema history

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- MariaDB/MySQL, or another configured Laravel database
- FFmpeg and FFprobe on `PATH`, or configured binary paths
- SMTP credentials for real OTP email delivery
- Access to the external ML service
- Access to the Python KBS service
- Firebase credentials if push notifications are enabled

## Installation

```powershell
git clone <repository-url>
cd MentalState
composer run setup
php artisan storage:link
```

The setup script installs Composer dependencies, creates `.env` from `.env.example` when needed, generates `APP_KEY`, runs migrations, installs npm dependencies, and builds frontend assets.

Create the configured database before running migrations. For the current local MariaDB setup, use values similar to:

```dotenv
APP_NAME=Invera
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mentalstate
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
```

Configure `JWT_SECRET`, mail settings, `ML_API_KEY`, Firebase credentials, and FFmpeg paths in `.env` as needed. Do not commit real credentials or production secrets.

## Running Locally

Run the API, queue listener, and Vite development server together:

```powershell
composer run dev
```

The API normally runs at `http://localhost:8000`. To run processes separately:

```powershell
php artisan serve
php artisan queue:work
npm run dev
```

Build frontend assets with:

```powershell
npm run build
```

## Testing

```powershell
composer run test
```

The test environment uses an in-memory SQLite database, array cache/mail/session drivers, synchronous queues, and reduced bcrypt rounds. Current tests are basic smoke tests; broader coverage is needed for authentication, uploads, FFmpeg, ML callbacks, KBS branching, notifications, authorization, admin access, and analytics.

## API Overview

The API is defined in `routes/api.php`. Product routes use JWT authentication unless noted otherwise.

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Register a user and send an OTP. |
| `POST` | `/api/auth/register/verify-otp` | Verify the OTP and issue tokens. |
| `POST` | `/api/auth/login` | Log in a verified user. |
| `POST` | `/api/auth/refresh` | Rotate a refresh token and issue a new JWT. |
| `POST` | `/api/auth/logout` | Revoke a refresh token and access token. |
| `POST` | `/api/auth/update-password` | Change the authenticated user's password. |
| `POST` | `/api/auth/saveFcmToken` | Store a Firebase device token. |

### Video and assessments

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/upload-video` | Upload a video for processing. |
| `GET` | `/api/videos` | List the authenticated user's videos. |
| `POST` | `/api/video/{video}/status` | Receive an ML processing status callback. |
| `POST` | `/api/kbs/start` | Start or resume an assessment for a video. |
| `POST` | `/api/kbs/next` | Submit an answer and request the next decision. |
| `GET` | `/api/kbs/video/{video}/reports` | Retrieve completed reports for a video. |
| `GET` | `/api/home` | Retrieve the latest video and user summary. |
| `GET` | `/api/user-info` | Retrieve profile information. |
| `POST` | `/api/upload-photo` | Upload a user avatar. |

The ML callback uses the `X-Model-API-Key` header. Authenticated client routes use the bearer token returned by the authentication endpoints.

### Admin

Admin routes use the `auth:admin` guard:

- `POST /api/admin/auth/login`
- `POST /api/admin/auth/logout`
- `GET /api/admin/auth/me`
- `POST /api/admin/auth/change-name`
- `POST /api/admin/auth/change-password`
- `GET /api/admin/dashboard/report`

`POST /api/admin/auth/change-email` is registered, but its implementation is currently incomplete.

## Video Processing Flow

1. An authenticated client uploads a video to `/api/upload-video`.
2. The upload service stores the video and uses FFmpeg to extract WAV audio and a PNG thumbnail.
3. A `ProcessVideoJob` is placed on the database queue.
4. The job sends the video and audio to the external ML service.
5. The ML service calls `/api/video/{video}/status` with scores and a processing status.
6. The application stores the scores and can send an FCM notification to the user's devices.
7. The user starts the KBS assessment and submits answers through `/api/kbs/next`.
8. Laravel calls the Python branch service and stores completed reports in the assessment record.

## Important Integrations

The source currently expects two external services:

- ML processing service: the upload URL is defined in `app/Jobs/ProcessVideoJob.php`.
- Python KBS service: branch requests are sent to `http://127.0.0.1:8001/api/branch{number}` by `app/Services/KbsAssessmentService.php`.

These services are not included in this repository. Their availability is required for the complete video-analysis and KBS flows.

## Project Structure

```text
app/
  Http/Controllers/       API controllers
  Http/Requests/          Request validation
  Jobs/                   Queued video and notification jobs
  Mail/                   OTP email
  Models/                 Eloquent models
  Services/               Application business logic
config/                   Laravel and integration configuration
database/
  migrations/             Database schema
  factories/              Test/data factories
  seeders/                Database seeders
resources/                Vite, Tailwind, JavaScript, and Blade assets
routes/                   API, web, and console routes
storage/                  Runtime files, logs, cache, and uploaded media
tests/                    Feature and unit tests
```

## Known Limitations

- The included frontend is only a minimal Vite/Tailwind shell; the mobile client and admin UI are not included.
- The outbound ML URL and API key handling in `ProcessVideoJob` should be moved to configuration.
- The ML callback accepts `analyzed`, while the video status database enum does not currently include that value.
- KBS start should apply the same video ownership check used by report retrieval.
- The test suite needs coverage for the main product workflows.
- Detailed code findings remain in `PROJECT_DOCS.md`.

## Useful Artisan Commands

```powershell
php artisan migrate
php artisan storage:link
php artisan queue:work
php artisan route:list
php artisan make:service ExampleService
php artisan test
```

## License

This repository does not currently define a separate project license. Confirm the intended license before distributing it publicly.
