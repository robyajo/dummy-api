# Proyek Dummy API

Ini adalah proyek API berbasis Laravel yang dirancang untuk berjalan di lingkungan Docker, terhubung ke layanan eksternal yang sudah ada (MySQL, Redis) yang berjalan di jaringan host.

## Prasyarat

Sebelum menjalankan proyek ini, pastikan Anda telah menginstal:

-   [Docker](https://docs.docker.com/get-docker/)
-   [Docker Compose](https://docs.docker.com/compose/install/)

### Dependensi Eksternal

Proyek ini mengharapkan container dan jaringan berikut sudah berjalan di mesin host (VPS) Anda:

-   **Jaringan**: `set-docker-server-roby_backend`
-   **Database**: Container MariaDB/MySQL bernama `mysql-db` yang terhubung ke jaringan di atas.
-   **Redis**: Container Redis bernama `redis` yang terhubung ke jaringan di atas.

## Instalasi & Pengaturan

1.  **Clone Repository**

    ```bash
    git clone <repository-url>
    cd dummy-api
    ```

2.  **Konfigurasi Environment**
    Mengelola variabel environment dengan benar sangat penting di Docker. File `.env` memungkinkan Anda mengonfigurasi aplikasi tanpa mengubah kode.

    -   **Salin file contoh**:

        ```bash
        cp .env.example .env
        ```

    -   **Edit file `.env`**:
        Anda bisa mengedit file ini langsung di mesin host menggunakan `nano` atau teks editor lainnya.
        ```bash
        nano .env
        ```
    -   **Konfigurasi Database & Redis**:
        Pastikan ini sesuai dengan kredensial container eksternal Anda:

        ```ini
        DB_CONNECTION=mysql
        DB_HOST=mysql-db
        DB_PORT=3306
        DB_DATABASE=mituni_api
        DB_USERNAME=root
        DB_PASSWORD=Tiram@1993

        # Konfigurasi Redis
        CACHE_STORE=redis
        QUEUE_CONNECTION=redis
        SESSION_DRIVER=redis

        REDIS_HOST=redis
        REDIS_PORT=6379
        # Wajib diisi agar tidak bentrok dengan aplikasi lain di server yang sama
        CACHE_PREFIX=dummy_api_cache_
        ```

    > **Catatan Penting tentang Variabel Environment:**
    > Docker menyuntikkan variabel ini saat container dimulai. Jika Anda mengubah `.env`, perubahannya **TIDAK AKAN** berlaku secara otomatis pada container yang sedang berjalan.
    >
    > Untuk menerapkan perubahan, Anda harus merestart container:
    >
    > ```bash
    > docker compose restart app
    > ```
    >
    > Atau jika Anda menambahkan variabel baru ke `docker-compose.yml`, Anda perlu membuat ulang container:
    >
    > ```bash
    > docker compose up -d
    > ```

3.  **Setup Nginx Host**
    Karena Anda menggunakan Nginx yang terinstal di VPS (host), Anda perlu mengonfigurasinya untuk meneruskan request (proxy) ke container Docker ini.

    Contoh konfigurasi disediakan di file `nginx-host.conf`.

    -   Salin konfigurasi ke direktori sites Nginx Anda:
        ```bash
        sudo cp nginx-host.conf /etc/nginx/sites-available/dummy-api
        ```
    -   Edit file untuk mengatur path `root` dan `server_name` yang benar:
        ```bash
        sudo nano /etc/nginx/sites-available/dummy-api
        ```
        Pastikan `root` mengarah ke `/path/ke/folder/dummy-api/public` yang sebenarnya.
    -   Aktifkan situs:
        ```bash
        sudo ln -s /etc/nginx/sites-available/dummy-api /etc/nginx/sites-enabled/
        sudo nginx -t
        sudo systemctl restart nginx
        ```

4.  **Jalankan Container**
    Bangun dan jalankan container aplikasi menggunakan Docker Compose.

    ```bash
    docker compose up -d --build
    ```

    (Catatan: Jika perintah `docker-compose` tidak ditemukan, gunakan `docker compose`, karena versi Docker baru sudah mengintegrasikannya).

    > **Tips:** Jika Anda melakukan perubahan pada `Dockerfile` atau `docker-compose.yml`, jalankan perintah ini lagi untuk mem-build ulang container.

    Ini akan menjalankan aplikasi PHP di port `9001`.

5.  **Install Dependensi**
    Install dependensi PHP menggunakan Composer di dalam container.

    ```bash
    docker compose run --rm app composer install
    ```

6.  **Setup Aplikasi**
    Generate key aplikasi dan jalankan migrasi database.
    ```bash
    docker compose run --rm app php artisan key:generate
    docker compose run --rm app php artisan migrate
    ```

## Alur Kerja Pengembangan & Masalah Umum

### Kapan Harus Rebuild?

Anda **TIDAK** perlu me-rebuild Docker untuk setiap perubahan kode.

-   **Cukup `git pull`**: Untuk perubahan pada file PHP (Controller, Model, Route), View, atau Config. Perubahan akan terlihat secara instan.
-   **Rebuild (`docker compose up -d --build`)**: HANYA jika Anda mengubah `Dockerfile`, `docker-compose.yml`, atau dependensi sistem.
-   **Restart (`docker compose restart app`)**: Ketika Anda mengedit file `.env`.
-   **Jalankan Composer**: Ketika file `composer.json` atau `composer.lock` berubah (`docker compose run --rm app composer install`).

### Rekomendasi Masa Depan & Potensi Masalah

1.  **Masalah Izin Folder Storage (Sering Terjadi)**

    -   **Masalah**: Terkadang setelah `git pull` atau clear cache, Laravel kehilangan akses tulis ke log atau cache.
    -   **Solusi**: Jalankan kembali perintah permission:
        ```bash
        docker compose run --rm app chmod -R 777 storage bootstrap/cache
        ```

2.  **Konflik Migrasi Database**

    -   **Masalah**: Jika banyak developer membuat migrasi dengan timestamp yang sama atau jika `migrate:rollback` gagal.
    -   **Pencegahan**: Selalu jalankan `php artisan migrate:status` untuk cek status.
    -   **Solusi**: Jika macet, Anda mungkin perlu menghapus entri dari tabel `migrations` di database secara manual.

3.  **Queue Workers**

    -   **Saran**: Jika nanti Anda menggunakan Queue/Job, Anda butuh container atau proses terpisah untuk menjalankan `php artisan queue:work`.
    -   **Peringatan**: Worker queue harus di-restart (`php artisan queue:restart`) setiap kali Anda deploy kode baru, jika tidak dia akan tetap menjalankan kode lama dari memori.

4.  **Penggunaan Ruang Disk**

    -   **Masalah**: Image Docker dan container yang berhenti akan memakan tempat seiring waktu.
    -   **Pemeliharaan**: Secara berkala jalankan `docker system prune` untuk membersihkan data yang tidak terpakai (Peringatan: ini menghapus container yang berhenti dan network yang tidak terpakai).

5.  **Ukuran File Log**
    -   **Masalah**: `laravel.log` atau log Docker bisa membesar tanpa batas.
    -   **Rekomendasi**: Konfigurasikan Log Rotation di `config/logging.php` (gunakan channel `daily`) atau atur driver logging Docker untuk membatasi ukuran file.

## Mengakses Aplikasi

-   **URL Web**: `http://domain-anda.com` (atau sesuai yang dikonfigurasi di Nginx)

## Perintah Berguna

-   **Matikan Container**:

    ```bash
    docker compose down
    ```

-   **Lihat Log**:

    ```bash
    docker compose logs -f
    ```

-   **Jalankan Perintah Artisan**:

    ```bash
    docker compose run --rm app php artisan <perintah>
    ```

    Contoh: `docker compose run --rm app php artisan make:controller TestController`

-   **Masuk ke Shell Container**:
    ```bash
    docker compose exec app bash
    ```

## Troubleshooting

### Masalah Jaringan

Jika aplikasi tidak bisa terhubung ke database, verifikasi bahwa jaringan eksternal ada:

```bash
docker network ls
```

Pastikan `set-docker-server-roby_backend` ada dalam daftar. Jika nama jaringan Anda berbeda, perbarui `docker-compose.yml` yang sesuai.

### Masalah Izin (Permission)

Jika Anda mengalami masalah izin dengan `storage` atau `bootstrap/cache`, jalankan:

```bash
docker compose run --rm app chmod -R 777 storage bootstrap/cache
docker compose run --rm app chown -R www-data:www-data storage bootstrap/cache
```

### Masalah Storage Link (Forbidden/404 pada Gambar)

Jika gambar tidak muncul atau error **403 Forbidden** saat diakses via Nginx Host (VPS), ini karena symlink default Laravel menggunakan absolute path container yang tidak terbaca oleh Host.

**Solusi: Buat Symlink Relatif Manual**

Jalankan perintah ini di terminal VPS (folder project):

```bash
cd public
rm storage
ln -s ../storage/app/public storage
cd ..
```

Ini akan membuat symlink yang valid baik untuk Docker maupun Nginx Host.
