# Faculty of Engineering Resource Booking System (FOE-RBS)

FOE-RBS is a single-backend Laravel application with a Vue frontend for resource booking and approval workflows.

## Architecture

- Backend: Laravel app in [backend](./backend)
- Frontend: Vue 3 app in [frontend](./frontend)
- Database: Supabase PostgreSQL (cloud)
- Deployment: simple free-tier stack using Render + Vercel + Supabase

## Local development

### 1) Start the app
```bash
docker compose up -d
```

### 2) Run migrations and seeders
```bash
docker compose exec backend php artisan migrate --seed
```

### 3) Link public storage (if needed)
```bash
docker compose exec backend php artisan storage:link
```

### 4) Access the app
- Frontend: http://localhost:5173
- Backend: http://localhost:8000

## Default admin account

- Email: master@example.com
- Password: Masteradmin@123

## Free deployment guide

The easiest free setup is:

- Backend: Render (free web service)
- Frontend: Vercel (free hosting)
- Database: Supabase free tier

### Option A: Deploy backend on Render (recommended)

1. Push your project to GitHub.
2. Go to https://render.com and sign in.
3. Click New + > Web Service.
4. Connect your GitHub repo.
5. Choose the root project folder as the service root.
6. Use these settings:
   - Name: foe-rbs-api
   - Runtime: Docker
   - Dockerfile Path: ./backend/Dockerfile
   - Docker Context: ./backend
   - Plan: Free
7. Set environment variables in Render:
   - APP_ENV=production
   - APP_DEBUG=false
   - APP_KEY=generate a new Laravel key
   - APP_URL=https://your-render-backend-url.onrender.com
   - DB_CONNECTION=pgsql
   - DB_HOST=your-supabase-host
   - DB_PORT=5432 or 6543 depending on your Supabase connection mode
   - DB_DATABASE=postgres
   - DB_USERNAME=your-supabase-user
   - DB_PASSWORD=your-supabase-password
   - CACHE_DRIVER=file
   - SESSION_DRIVER=file
   - FILESYSTEM_DISK=public
8. Deploy the service.
9. After deployment, run the database migration once:
   ```bash
   php artisan migrate --seed
   ```
   or run through the Render shell if needed.

### Option B: Deploy frontend on Vercel

1. Go to https://vercel.com.
2. Import the frontend folder from GitHub.
3. Framework: Vite
4. Build command: npm run build
5. Output directory: dist
6. Add environment variable:
   - VITE_API_BASE_URL=https://your-render-backend-url.onrender.com/api
7. Deploy.

### Option C: Use Supabase for the database

1. Create a new project in Supabase.
2. Copy your PostgreSQL connection details.
3. Update the backend .env values with the Supabase host, database name, username, and password.
4. Make sure the backend can reach the database and the app is configured for production.

### Recommended production config

- Frontend uses Vercel and points to the Render backend URL.
- Backend uses Supabase PostgreSQL.
- Store all secrets in Render and Vercel environment variables instead of committing them to Git.
- Do not commit .env files with secrets.

## Useful commands

### Local build/test
```bash
docker compose up -d
```

```bash
docker compose exec backend php artisan migrate --seed
```

```bash
docker compose exec backend php artisan storage:link
```

### Generate Laravel app key
```bash
docker compose exec backend php artisan key:generate
```

## Notes

- The repository already contains a Render config in [render.yaml](./render.yaml), but you should still set the real environment variables in the Render dashboard.
- If your frontend is served from a different domain, update CORS and the app URL values accordingly.
- For a completely free setup, the typical stack is: Vercel + Render + Supabase.

## Default admin account

- Email: master@example.com
- Password: Masteradmin@123
