# Faculty of Engineering Resource Booking System (FOE-RBS)

## 🚀 Supabase Cloud Database Setup

The backend microservices are now connected to your **Supabase PostgreSQL** cloud database.

### 📦 Running the System Locally with Supabase
```bash
docker compose up -d
```

---

### 🗄 Migrations & Seeders (Supabase Cloud DB)
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

### 🔗 Public Storage Links
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
| **Supabase DB** | `aws-0-ap-southeast-1.pooler.supabase.com:6543` | Cloud PostgreSQL DB |

### 🔑 Default Master Admin Account
* **Email:** `master@example.com`
* **Password:** `Masteradmin@123`
