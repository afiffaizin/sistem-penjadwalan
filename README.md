# Sistem Penjadwalan Perkuliahan

Sistem penjadwalan otomatis untuk perkuliahan menggunakan **Laravel 12** + **FastAPI** dengan solver **Google OR-Tools CP-SAT**.

## Quick Start

Pastikan **Git** dan **Docker** sudah terinstall, lalu jalankan:

```bash
git clone https://github.com/afiffaizin/sistem-penjadwalan.git
cd sistem-penjadwalan
bash install.sh
```

Atau satu baris (langsung dari internet):

```bash
curl -fsSL https://raw.githubusercontent.com/afiffaizin/sistem-penjadwalan/main/install.sh | bash
```

Tunggu sampai muncul pesan **"Successfully Running!"** — selesai.

## Akses Aplikasi

| Service         | URL                   |
| --------------- | --------------------- |
| **Laravel App** | http://localhost:8000 |
| **MySQL**       | http://localhost:8081 |

### Default Login

| Username       | Password    | Role         |
| -------------- | ----------- | ------------ |
| `sekjur`       | `sekjur123` | Sekretaris   |
| `kajur`        | `kajur123`  | Kajur        |
| `kaprodi_ti`   | `ti123`     | Kaprodi TI   |
| `kaprodi_rks`  | `rks123`    | Kaprodi RKS  |
| `kaprodi_trm`  | `trm123`    | Kaprodi TRM  |
| `kaprodi_trpl` | `trpl123`   | Kaprodi TRPL |

## Perintah Berguna

```bash
# Stop semua container (data tetap aman)
docker compose down

# Start kembali tanpa rebuild
docker compose up -d

# Lihat logs semua service
docker compose logs -f

# Lihat logs service tertentu
docker compose logs -f laravel-app
docker compose logs -f python-app

# Rebuild setelah ubah kode
docker compose up -d --build

# Hapus semua container + DATABASE
docker compose down -v
```

## Tech Stack

| Layer     | Technology                                     |
| --------- | ---------------------------------------------- |
| Frontend  | Blade, TailwindCSS 3, Alpine.js                |
| Backend   | Laravel 12 (PHP 8.2)                           |
| Solver    | FastAPI + Google OR-Tools CP-SAT (Python 3.12) |
| Database  | MySQL 8.0                                      |
| Container | Docker + Docker Compose                        |
| Export    | DomPDF, Maatwebsite Excel                      |
