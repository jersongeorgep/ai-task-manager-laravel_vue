# AI Task Manager Backend

Laravel 10 REST API for an AI-assisted task management system.

## Architecture

The task module follows the required layered structure:

- Controllers: `app/Http/Controllers/Api`
- Validation: `app/Http/Requests`
- API output: `app/Http/Resources`
- Business logic: `app/Services/TaskService.php`
- AI integration: `app/Services/AIService.php`
- Persistence: `app/Repositories/Contracts/TaskRepositoryInterface.php` and `app/Repositories/Eloquent/TaskRepository.php`
- Authorization: `app/Policies/TaskPolicy.php`

Controllers do not query task Eloquent models directly. Task persistence goes through the repository, and task business rules run through `TaskService`.

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

## AI Integration

`AIService` creates a prompt from the task title, description, user priority, and current status. The system prompt instructs the AI to return JSON only:

```text
You summarize task-management items and classify urgency. Return only valid JSON with ai_summary and ai_priority keys. ai_priority must be low, medium, or high.
```

If `OPENAI_API_KEY` is configured, the service calls OpenAI chat completions. If the API call fails or no key is present, it falls back to a deterministic local summary and priority heuristic.

Environment variables:

```env
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
