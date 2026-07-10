# Universal Bank Transaction Import System

A full-stack application built to upload, validate, and store bank transactions from multiple file formats (JSON, CSV, and XML). The system features an automated background architecture using Docker, a validation engine on the backend, and a monitoring dashboard on the frontend.

---

## Quick Start (How to Run This Project)

You do not need to install PHP, Node.js, or MySQL on your computer. Everything runs inside isolated Docker containers.

### Prerequisites
* Ensure you have **Docker Desktop** installed and running.
* Ensure you have **Git** installed.

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone <your-github-repository-url>
   cd bank-transaction-import-system
   ```

2. **Configure the Backend Environment:**
   Go into the backend folder, duplicate the example configuration file, and name it `.env`:
   ```bash
   cp backend/.env.example backend/.env
   ```
   *(Note: The default database credentials inside `.env.example` are pre-configured to match our Docker setup automatically)*

3. **Launch the Docker Containers:**
   Run this command in the root folder of the project to download, build, and start all services in the background:
   ```bash
   docker compose up -d
   ```

4. **Run Database Migrations:**
   Create the required tables inside your MySQL database by running:
   ```bash
   docker compose exec backend php artisan migrate
   ```

5. **Open the App:**
   * **Frontend Dashboard (Vue 3):** Open [http://localhost:3000](http://localhost:3000) in your browser.
   * **Backend API Gateway (Laravel 12):** Accessible at [http://localhost:8000].

---

## Architecture & Core Technologies

The project is split into three main services managed by Docker:

* **Frontend:** Built with **Vue 3 (Composition API)** and powered by **Vite** bundler. Styled via **Tailwind CSS v4**.
* **Backend:** Powered by **Laravel 12** running as a headless REST API.
* **Database:** **MySQL 8.0** engine with a dedicated persistence layer so data isn't lost when containers are turned off.

---

## Key Files Blueprint & Their Roles

Here is the structural map of the most important files:

### Frontend (`frontend/src/`)
* **`components/BankImportManager.vue`**
  * *Role:* The primary interface controller. It lets users select a file, sends it as an asynchronous network request (`multipart/form-data`) using Axios to the server, and renders the dynamic history feed.
* **`components/ErrorLogsPanel.vue`**
  * *Role:* A reusable sub-component that receives selected data row properties from its mother-component and prints individual, row-by-row transaction validation failure errors.
* **`index.css`**
  * *Role:* The style entrypoint loading the modern `@import "tailwindcss";` compiler directive.

### Backend (`backend/app/`)
* **`routes/api.php`**
  * *Role:* The routing gateway. Registers the HTTP entry endpoints (`POST /imports`, `GET /imports`, `GET /imports/{id}`).
* **`Http/Controllers/Api/ImportController.php`**
  * *Role:* The request dispatcher. Receives client HTTP actions, passes the workload to the application logic layer, and replies with structured, safe JSON status codes.
* **`Services/TransactionImportService.php`**
  * *Role:* The core system "brain". It reads uploaded file contents, auto-detects extensions (JSON/CSV/XML), parses raw formats into unified arrays, filters valid rows via strict regex configurations, and saves error summaries.

---

## Database Schema (How Data Looks)

MySQL schema is split into three relational tables:

```text
  [ imports ]  
  (Main record generated per file uploaded)
  ├── id (Primary Key)
  ├── file_name (e.g., transactions.xml)
  ├── total_records
  ├── successful_records
  ├── failed_records
  └── status (success, partial, failed)
         │
         ├───► Has Many ───► [ transactions ]
         │                    (Stores only clean rows)
         │                    ├── id
         │                    ├── import_id (Foreign Key)
         │                    ├── transaction_id (Unique String, e.g., TXN-001)
         │                    ├── account_number (Validated standard length)
         │                    ├── transaction_date
         │                    ├── amount (Decimal format, absolute positive values)
         │                    └── currency
         │
         └───► Has Many ───► [ import_logs ]
                              (Stores rejection records for analysis)
                              ├── id
                              ├── import_id (Foreign Key)
                              ├── transaction_id (Faulty record identifier)
                              └── error_message (e.g., "The amount field must be greater than 0.")
```

---

## Supported File Samples for Testing

You can copy-paste these templates into files on your local drive to test the application directly through the interface:

### `test_data.json` (Mixed - 1 Valid, 1 Invalid)
```json
[
  {
    "transaction_id": "TXN-JSON-01",
    "account_number": "PL12345678901234567890123456",
    "transaction_date": "2026-06-05",
    "amount": 1250.75,
    "currency": "PLN"
  },
  {
    "transaction_id": "TXN-JSON-02",
    "account_number": "PL98765432109876543210987654",
    "transaction_date": "2026-06-05",
    "amount": -50.00, 
    "currency": "USD"
  }
]
```
*(The system will successfully import transaction 01, reject transaction 02 because of the negative amount, mark the file status as "partial", and store the explicit breakdown in your logs panel)*

---

## How to Turn Off the System Safely

When you're finished testing or programming, close the apps by stopping your Docker environment inside your command terminal:

```bash
docker compose down
```
