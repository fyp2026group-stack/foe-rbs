# Faculty of Engineering Resource Booking System (FOE-RBS)

## 🚀 Quick Start Guide

### Step 1: Build and Start Containers
```bash
docker compose up -d --build
```

---

### Step 2: Run Database Migrations & Seeders
```bash
# 1. Auth Service & Master Admin Seeder
docker compose exec auth_service php artisan migrate --seed

# 2. Resource Service (Departments, Resources & Equipment)
docker compose exec resource_service php artisan migrate

# 3. Booking Service (Bookings & Approvals)
docker compose exec booking_service php artisan migrate

# 4. System Settings Service (Settings & Initial Branding)
docker compose exec system_settings_service php artisan migrate --seed
```

---

### Step 3: Link Public Storage (For Image & Logo Uploads)
```bash
docker compose exec resource_service php artisan storage:link
docker compose exec system_settings_service php artisan storage:link
```

---

## 🌐 Application URLs

| Service | URL | Notes |
| :--- | :--- | :--- |
| **Frontend UI** | [http://localhost:5173](http://localhost:5173) | Vue 3 + TypeScript Single Page App |
| **API Gateway** | [http://localhost:8000](http://localhost:8000) | Central Laravel API Gateway |

### 🔑 Default Master Admin Account
* **Email:** `master@example.com`
* **Password:** `Masteradmin@123`

---

## 🛠 Useful Commands

* **Check running containers:**
  ```bash
  docker compose ps
  ```
* **View real-time logs:**
  ```bash
  docker compose logs -f
  ```
* **Stop all containers:**
  ```bash
  docker compose down
  ```
