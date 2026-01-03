# Dummy API Project

This is a Laravel-based API project designed to run in a Docker environment, connecting to existing external services (MySQL, Redis) running on the host network.

## Prerequisites

Before running this project, ensure you have the following installed:

-   [Docker](https://docs.docker.com/get-docker/)
-   [Docker Compose](https://docs.docker.com/compose/install/)

### External Dependencies

This project expects the following containers and network to be already running on your host machine:

-   **Network**: `set-docker-server-roby_backend`
-   **Database**: A MariaDB/MySQL container named `mysql-db` connected to the above network.
-   **Redis**: A Redis container named `redis` connected to the above network.

## Installation & Setup

1.  **Clone the Repository**

    ```bash
    git clone <repository-url>
    cd dummy-api
    ```

2.  **Environment Configuration**

    Managing environment variables correctly is crucial in Docker. The `.env` file allows you to configure your application without changing the code.

    -   **Copy the example file**:

        ```bash
        cp .env.example .env
        ```

    -   **Edit the `.env` file**:
        You can edit this file directly on your host machine using `nano` or any text editor.
        ```bash
        nano .env
        ```
    -   **Configure Database & Redis**:
        Ensure these match your external container credentials:

        ```ini
        DB_CONNECTION=mysql
        DB_HOST=mysql-db
        DB_PORT=3306
        DB_DATABASE=mituni_api
        DB_USERNAME=root
        DB_PASSWORD=Tiram@1993

        REDIS_HOST=redis
        ```

    > **Important Note on Environment Variables:**
    > Docker injects these variables when the container starts. If you modify `.env`, the changes **will not apply immediately** to the running container.
    >
    > To apply changes, you must restart the container:
    >
    > ```bash
    > docker compose restart app
    > ```
    >
    > Or if you added new variables to `docker-compose.yml`, you need to recreate it:
    >
    > ```bash
    > docker compose up -d
    > ```

3.  **Setup Nginx Host**
    Since you are using the Nginx installed on your VPS, you need to configure it to proxy requests to this Docker container.

    A sample configuration is provided in `nginx-host.conf`.

    -   Copy the configuration to your Nginx sites directory:
        ```bash
        sudo cp nginx-host.conf /etc/nginx/sites-available/dummy-api
        ```
    -   Edit the file to set the correct `root` path and `server_name`:
        ```bash
        sudo nano /etc/nginx/sites-available/dummy-api
        ```
        Make sure `root` points to `/path/to/your/dummy-api/public`.
    -   Enable the site:
        ```bash
        sudo ln -s /etc/nginx/sites-available/dummy-api /etc/nginx/sites-enabled/
        sudo nginx -t
        sudo systemctl restart nginx
        ```

4.  **Start Containers**
    Build and start the application container using Docker Compose.

    ```bash
    docker compose up -d --build
    ```

    Masuk ke container aplikasi.

    ```bash
    docker compose exec app bash
    ```

    (Note: If `docker-compose` command is not found, use `docker compose` instead, as newer Docker versions integrate compose directly).

    > **Tip:** If you make changes to `Dockerfile` or `docker-compose.yml`, run this command again to rebuild the containers.

    This will start the PHP app on port `9001`.

5.  **Install Dependencies**
    Install PHP dependencies using Composer inside the container.

    ```bash
    docker compose run --rm app composer install
    ```

6.  **Application Setup**
    Generate the application key and run database migrations.
    ```bash
    docker compose run --rm app php artisan key:generate
    docker compose run --rm app php artisan migrate
    ```

## Accessing the Application

-   **Web URL**: `http://your-domain.com` (or whatever you configured in Nginx)

## Useful Commands

-   **Stop Containers**:

    ```bash
    docker compose down
    ```

-   **View Logs**:

    ```bash
    docker compose logs -f
    ```

-   **Run Artisan Commands**:

    ```bash
    docker compose run --rm app php artisan <command>
    ```

    Example: `docker compose run --rm app php artisan make:controller TestController`

-   **Access Container Shell**:
    ```bash
    docker compose exec app bash
    ```

## Troubleshooting

### Network Issues

If the application cannot connect to the database, verify that the external network exists:

```bash
docker network ls
```

Ensure `set-docker-server-roby_backend` is listed. If your network name is different, update `docker-compose.yml` accordingly.

### Permission Issues

If you encounter permission issues with `storage` or `bootstrap/cache`, run:

```bash
docker compose run --rm app chmod -R 775 storage bootstrap/cache
docker compose run --rm app chown -R www-data:www-data storage bootstrap/cache
```
