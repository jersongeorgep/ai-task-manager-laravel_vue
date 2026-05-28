# AI Task Manager Backend

Laravel 10 REST API for an AI-assisted task management system.


## Authentication And Roles

Sanctum token authentication is used.

- Admin: full task access, create/update/delete tasks, assign tasks.
- User: can view assigned tasks and update the status of assigned tasks only.

Seeded users:

- `admin@example.com` / `password`
- `user@example.com` / `password`

## API Endpoints

- `POST /api/register`
- `POST /api/login`
- `POST /api/logout`
- `GET /api/user`
- `GET /api/dashboard`
- `GET /api/tasks`
- `POST /api/tasks`
- `GET /api/tasks/{task}`
- `PUT/PATCH /api/tasks/{task}`
- `DELETE /api/tasks/{task}`
- `PATCH /api/tasks/{task}/status`
- `GET /api/tasks/{task}/ai-summary`

Task list and dashboard support filters: `search`, `priority`, `status`, `ai_priority`, `assigned_to`, and `due_before`.



## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
