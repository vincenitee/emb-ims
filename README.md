# EMB-IMS

An Inventory Management System (IMS) built with a CodeIgniter 4 (PHP) backend API and a Vue 3 frontend.

> **Status:** early scaffolding — backend and frontend project structures are in place; features are still being built out.

## Tech Stack

**Backend**
- [CodeIgniter 4](https://codeigniter.com/) (PHP)
- MySQLi (default DB driver)

**Frontend**
- [Vue 3](https://vuejs.org/) + [Vite](https://vitejs.dev/)
- [Vue Router](https://router.vuejs.org/)
- [Pinia](https://pinia.vuejs.org/) (state management)
- [Tailwind CSS](https://tailwindcss.com/)

## Project Structure

```
emb-ims/
├── backend/     # CodeIgniter 4 API
└── frontend/    # Vue 3 + Vite SPA
```

## Prerequisites

- PHP ^7.2 or ^8.0, with [Composer](https://getcomposer.org/)
- MySQL (or another supported database)
- Node.js `^22.18.0 || >=24.12.0` with npm

## Getting Started

### Backend (CodeIgniter 4)

```bash
cd backend
composer install
cp env .env
```

Edit `.env` and configure at least:

```
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = emb_ims
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

Run database migrations, then start the dev server:

```bash
php spark migrate
php spark serve
```

The API will be available at `http://localhost:8080/`.

### Frontend (Vue 3 + Vite)

```bash
cd frontend
npm install
npm run dev
```

The app will be available at the URL printed in the terminal (default `http://localhost:5173/`).

## Frontend Scripts

| Command | Description |
| --- | --- |
| `npm run dev` | Start the Vite dev server |
| `npm run build` | Build for production |
| `npm run preview` | Preview the production build |
| `npm run lint` | Lint with oxlint and ESLint |
| `npm run format` | Format source files with Prettier |

## Backend Commands

| Command | Description |
| --- | --- |
| `php spark serve` | Start the local dev server |
| `php spark migrate` | Run database migrations |
| `composer test` | Run the PHPUnit test suite |

## License

No license has been specified for this project yet.
