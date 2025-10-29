# AppointmentsLite

Lightweight appointment scheduling app - create, manage and view appointments.



## Features
- Create, view, edit, delete appointments
- Search and filter appointments

## Prerequisites
1. [PHP](https://www.php.net/downloads.php)
2. [Composer](https://getcomposer.org/download/)
3. [Laravel](https://laravel.com/docs/12.x)
4. [Node.js](https://nodejs.org/en)
5. [PostgreSQL](https://www.postgresql.org/download/)
# Setup
1. **Clone the repository**
```
git clone github.com/dianaforrai/AppointmentsLite
```
2. **Navigate to the backend application**
```
cd AppointmentsLite/backend
```
3. **Create a `.env` file at the root of the `backend` folder (based on the `.env.example`) and fill your database information**
```ini
DB_CONNECTION=pgsql (or other if so)
DB_HOST=your_db_host
DB_PORT=your_db_port
DB_DATABASE=your_db_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```
4. **Install dependencies for the backend and an app key**
```
composer install
php artisan key:generate
```
5. **Run migrations and seed**
```
php artisan migrate --seed
```
or 
```
php artisan migrate
php artisan db:seed
```
6. **Start the backend**
```
composer run dev
```
7. **Navigate to the frontend application**
```
cd ..
cd frontend
```
8. **Create a `.env` at the root of the `frontend` folder (based on the `.env.example`) and fill your Laravel API URL**
```INI
VITE_API_URL=your_api_url
```
9. **Install frontned dependencies**
```
npm install
```
10. **Run the frontend application**
```
npm run dev
```

## Architecture notes
1. **API**

The API is a stateless RESTful service built with Laravel. It exposes the **CRUD** operations under `base_url/api/appointments`. The index endpoint supports server-side pagination, full export via `all=true`, search across `patient_name` and `doctor`, `status` filtering and ordering based on the `datetime` (which is also normalized to `YYYY-MM-DD HH:MM:SS`). Inputs are also validated for required columns (`patient_name`, `doctor`, `datetime`, `status`).

Practical examples:  
>Base fetch: 
```
curl "base_url/api/appointments"
```  
>Fetch all: 
```
curl "base_url/api/appointments?all=true"
```
>Update: 
```
curl -X PUT "base_url/api/appointments/123" \
  -H "Content-Type: application/json" \
  -d '{
    "patient_name": "Diana Forrai",
    "doctor": "Dr. Matei",
    "datetime": "2025-11-20T10:30",
    "status": "Done"
  }'
```

2. **Frontend**

The frontend is a Vue 3 **Single-Page-Application** using a single component `Appointments.vue` to encapsulate UI, states and data fetching. The component reads the `API_URL` from the **Vite** env, and calls the **Laravel API** via **axios** and relies on the server side pagination and query filtering. The web application is styled accordingly and with **UX** in mind.

Lifecycle:
* `created()` triggers the initial fetch of appointments via `fetchAppointments({ append: boolean })` and the data bound table is filled. If there is no data from the database, an empty view is presented. At each fetch, a loading indicator will be displayed to signal the operation, and error messages are present in the main component table if the case.
* `statusFilter()`, `sortOrder()` and `searchQuery()` (debounced -> calls `debouncedQuery()`) watchers are used to refetch based on user input.
* `loadMore()` appends paginated results if prompted.
* A modal is dinamically opened and the state is altered to edit or insert a new appointment via `createAppointment()` or `updateAppointment()`, with validation of the form and error messages if the case.
* A deletion modal is opened to confirm a deletion and an success/error message is displayed after the operation as a toast message in the bottom right.

## Improvements and trade-offs
* User management (doctors) and authentication/authorization system.

> Better control over the filtering as you could filter schedule on configurable doctors and see a centralized doctor schedule. Trade-off: extra steps, more time, token management, more UI flows and business logic.

* User management (patients) and authentication/authorization system.

> Configuration posiblities, possibility of delivering targeted notifications or alarms for appointments.

* Notification system

> As suggested above, a patient could be contacted via `email` with `SMTP` or `phone` (e.g. SMS, WhatsApp, Telegram).
* DB Indexes

> Indexes on `patient_name`, `doctor`, `status` and `datetime` for improved DB performance.
 * API Documentation

 > OpenAPI/Swagger for better development and maintenance of the Laravel API.

 * Resource scheduling

 > Extending the backend to include resources necessary for appointments and availability of them (e.g. rooms, equiment)  
 `Model: Resource - id, appointment_id, name, minimum_schedule, maximum_schedule`.

 * External CRM or calendar sync

 > Sync the appointments with other services like Google Calendar or Outlook.

 * Historic data

 > Implementing a soft delete with a `deleted` column on the `Appointments` model, so historic reports could be queried. Not really that necessary if not extended also with other critical information like documents and critical information discussed in the appointment.
