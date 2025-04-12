# Simple URL Shortener

A web application built with CodeIgniter 4 that allows users to shorten long URLs, generate QR codes, and view their history if logged in.

## Features

* Shorten any valid URL into a shorter code.
* Redirect from short URL to the original URL.
* Track the number of visits for each short URL.
* User registration and login.
* Logged-in users can view a history of the URLs they have shortened, including visit stats.
* Logged-in users can delete URLs from their history.
* Generate QR codes for shortened URLs (displayed in a modal).
* Anonymous URL shortening supported (history not tracked).
* Duplicate URL checking for logged-in users (returns existing short URL).

## Tech Stack

* **Framework:** CodeIgniter 4 (v4.6.0)
* **Language:** PHP (v8.2 recommended, developed with 8.4.5)
* **Database:** PostgreSQL
* **Frontend Styling:** Tailwind CSS (compiled via npm)
* **QR Code Generation:** `endroid/qr-code` library
* **Development Server:** `php spark serve` / PHP Built-in Server
* **Deployment Target:** Render (using Docker)

## Local Installation & Setup Guide

Follow these steps to set up the project for local development.

**Prerequisites:**

* PHP (v8.1 or higher recommended, includes intl, mbstring, pgsql, gd, exif extensions)
* Composer ([https://getcomposer.org/](https://getcomposer.org/))
* Node.js & npm ([https://nodejs.org/](https://nodejs.org/)) - For Tailwind CSS compilation
* PostgreSQL Server

**Steps:**

1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/Yanatg/shorten-url-project
    cd shorten-url-project
    ```

2.  **Install PHP Dependencies:**
    ```bash
    composer install
    ```

3.  **Install Node.js Dependencies:**
    ```bash
    npm install
    ```

4.  **Configure Environment:**
    * Copy the example environment file:
        ```bash
        cp env .env.example
        ```
    * Open the `.env` file in a text editor.
    * Set the `CI_ENVIRONMENT` to `development`:
        ```dotenv
        CI_ENVIRONMENT = development
        ```
    * Set the `app.baseURL` to your local development URL (important: include trailing slash!):
        ```dotenv
        app.baseURL = 'http://localhost:8080/'
        ```
    * Configure your **PostgreSQL database connection details**:
        ```dotenv
        database.default.hostname = localhost # Or your DB host
        database.default.database = ci4_shorturl # Your DB name
        database.default.username = postgres   # Your DB username
        database.default.password = your_db_password # Your DB password
        database.default.DBDriver = Postgre
        database.default.port = 5432
        ```

5.  **Create Database:** Manually create the PostgreSQL database specified in your `.env` file (e.g., `ci4_shorturl`) using a tool like `psql` or pgAdmin.

6.  **Run Database Migrations:** This will create the `users` and `urls` tables.
    ```bash
    php spark migrate
    ```

7.  **Compile CSS Assets:** Run your Tailwind build command. This might be:
    ```bash
    npm run build
    ```
    *(Or `npm run dev`, or `npx tailwindcss -i ... -o ...` - check your `package.json` scripts. Ensure the output is `public/css/style.css` or update the `<link>` tag in your views).*

8.  **Run the Development Server:**
    ```bash
    php spark serve
    ```
    You should now be able to access the application at `http://localhost:8080` (or the port specified by `spark serve`).

## Deployment (Render Notes)

This application was deployed to Render using the **Docker** runtime.

1.  Refer to the `Dockerfile` and `docker/apache-vhost.conf` for the container setup (PHP 8.2, Apache, Node, Composer, required extensions).
2.  Environment variables (Database credentials, `APP_BASE_URL`, `CI_ENVIRONMENT=production`, `APP_KEY`, `ENCRYPTION_KEY`) must be set in the Render service environment (linking the Database Environment Group is recommended).
3.  The Tailwind CSS build (`npm run build`) is included in the Docker build process.
4.  Database migrations (`php spark migrate`) **must be run manually** after deployment using a **Render Job**, as they failed during the Docker build step due to environment variable timing. Configure the Job with the same repository, linked database environment group, and the command `php spark migrate --all`.
