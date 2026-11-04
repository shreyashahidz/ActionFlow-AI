# ActionFlow AI - Intelligent Inbox & Task Management

ActionFlow AI is an intelligent triage and task management web application that transforms unstructured incoming business communications (emails and chat messages) into prioritized, actionable tasks using Google Gemini. The application features robust schema validation, idempotent JSON importing, zero-hallucination due-date extraction, context-aware reply generation, and a responsive reactive dashboard.

- **Stack:** Laravel 11/12 (PHP 8.3) + MySQL 8 + Google Gemini API + Blade + Tailwind CSS + Alpine.js
- **Assignment:** Traction Shastra Technical Assignment (FSD / AI Intern)
- **Demo Video:** [Watch Demo Recording (Google Drive)](https://drive.google.com/drive/folders/1FqT3oirYaPEiLOCYZQ1rqVYUZFZ0xrZ6?usp=sharing)

---

## Features (Summary)

- **JSON Dataset Import:** Upload and validate JSON files against strict schemas without third-party dependencies.
- **Duplicate Prevention:** Skips existing message IDs automatically on re-import.
- **AI-Powered Analysis (Gemini):** Extracts neutral summaries, business intent, categorization, priority, and reply requirement.
- **Anti-Hallucination Guardrails:** AI only outputs deadlines if explicitly mentioned; dates are verified server-side against the raw message text.
- **Suggested Replies:** Contextual draft replies with one-click clipboard copying.
- **Full Task Management:** Filter, search, edit details, inline status changes, and Kanban board view.
- **Formula-Safe CSV Export:** Secure export guarding against spreadsheet formula injection (`=`, `+`, `-`, `@`).
- **Duplicate Task Detection:** Flags tasks matching existing active tasks by 85%+ text similarity.
- **Executive Daily Briefing:** Synthesized on-demand briefing of open items using Gemini.

*For comprehensive feature breakdowns, prompt details, and architecture notes, see [FEATURES.md](FEATURES.md).*

---

## Requirements

- **PHP:** 8.2 or newer (with `curl`, `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`)
- **Composer:** 2.x
- **Database:** MySQL 8.0+
- **API Key:** Google Gemini API Key (free tier from [Google AI Studio](https://aistudio.google.com/apikey))
- **Web Browser:** Modern browser with JavaScript enabled

---

## Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone <repo-url> actionflow-ai
   cd actionflow-ai
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Create the MySQL Database:**
   ```sql
   CREATE DATABASE actionflow_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Configure `.env` database and Gemini credentials:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=actionflow_ai
   DB_USERNAME=root
   DB_PASSWORD=your_mysql_password

   GEMINI_API_KEY=your_gemini_api_key_here
   GEMINI_MODEL=gemini-2.5-flash
   ```

6. **Run database migrations:**
   ```bash
   php artisan migrate
   ```
   *(Alternatively, import `database/actionflow_schema.sql` directly into MySQL).*

7. **Start the local development server:**
   ```bash
   php artisan serve
   ```
   Open your browser at [http://127.0.0.1:8000](http://127.0.0.1:8000).

---

## Usage Guide

1. **Import Messages:**
   - Navigate to the **Import & Automation** tab.
   - Choose `sample_messages.json` (located in the project root) and click upload.
   - Inspect the import result banner showing total, imported, and skipped counts.
2. **Run AI Analysis:**
   - Click **"Analyze All Pending"** in the Import or Messages tab.
   - The system processes messages with live progress tracking, spacing calls to adhere to Gemini free-tier rate limits.
3. **Manage Tasks:**
   - Go to the **Tasks Management** tab.
   - Switch between **Table** and **Kanban** view.
   - Update statuses inline, edit task fields in modal, search, filter by priority/status, or click **Export CSV**.
4. **Inspect Messages & Suggested Replies:**
   - Go to the **Messages & Analysis** tab.
   - Click any message to open the drawer: review AI summary, category, intent, generated tasks, and copy the suggested reply.

---

## Environment Variables

| Variable | Required | Description | Default |
|---|---|---|---|
| `APP_NAME` | Yes | Application Name | `ActionFlow AI` |
| `APP_ENV` | Yes | Environment stage | `local` |
| `APP_KEY` | Yes | Laravel encryption key | Generated via `php artisan key:generate` |
| `DB_CONNECTION`| Yes | Database driver | `mysql` |
| `DB_HOST` | Yes | MySQL hostname | `127.0.0.1` |
| `DB_PORT` | Yes | MySQL port | `3306` |
| `DB_DATABASE` | Yes | Database name | `actionflow_ai` |
| `DB_USERNAME` | Yes | Database username | `root` |
| `DB_PASSWORD` | Yes | Database password | `""` |
| `GEMINI_API_KEY`| Yes | Google Gemini API Key | `""` |
| `GEMINI_MODEL` | Yes | Gemini Model identifier | `gemini-2.5-flash` |

---

## Database Architecture

- **`messages`**: Original incoming message records with metadata, attachments, and analysis status.
- **`message_analyses`**: Gemini analysis output per message (summary, category, intent, priority, requires response, suggested reply, raw JSON).
- **`tasks`**: Extracted actionable tasks with priority, verified due dates, status, duplicate pointers, and parent message foreign key.
- **`import_logs`**: Audit trail of every JSON file import batch.
- **Schema Export:** Available at `database/actionflow_schema.sql`.

---

## JSON Dataset Format (`sample_messages.json`)

The required structure adheres strictly to the assignment specification:

```json
{
  "messages": [
    {
      "id": "MSG001",
      "type": "email",
      "sender": {
        "name": "Rahul Sharma",
        "email": "rahul@example.com"
      },
      "recipient": "team@example.com",
      "subject": "Website Launch",
      "date": "2026-11-02T10:30:00+05:30",
      "content": "Please complete the payment gateway integration and share the staging URL before Thursday.",
      "attachments": []
    }
  ]
}
```

- Root object must contain `messages` array.
- `id` must be unique across the dataset.
- `type` must be either `email` or `message`.
- `date` must conform to ISO 8601 with timezone offset (`YYYY-MM-DDTHH:MM:SS+HH:MM`).

---

## Testing

Run the automated PHPUnit feature test suite:
```bash
php artisan test
```

Tests cover:
- Dashboard page rendering
- Stats endpoint structure
- Import schema validation & bad JSON rejection
- Manual message submission
- Task CRUD lifecycle
- Formula-safe CSV streaming

---

## Rate Limits Note

The Google Gemini free-tier imposes a limit of approximately 10–15 RPM (Requests Per Minute). The "Analyze All Pending" routine on the frontend automatically spaces consecutive requests by ~6.5 seconds with exponential backoff on retries (4s, 8s, 16s, 32s) to ensure uninterrupted batch processing without HTTP 429 rate limit exceptions.

---

## Known Limitations

- **Authentication:** Omitted as designated in the project specifications.
- **Gmail OAuth:** Skipped in favor of reliable, independent JSON dataset execution.
- **CDN Styling:** Tailwind CSS and Alpine.js assets are served via CDN, requiring an internet connection for UI presentation.

---

## Author

Developed for Traction Shastra Technical Assignment (ActionFlow AI).
