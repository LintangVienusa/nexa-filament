## Installation Guide For Local Development Environment

- Clone this repostitory into your local machine by running the command below in your terminal:
  ```bash
  Using SSH:
  git clone git@github.com:LintangVienusa/nexa-filament.git
  
  Using HTTPS:
  git clone https://github.com/LintangVienusa/nexa-filament.git
    ```
- Navigate to the project directory:
  ```bash
  cd nexa-filament
  ```
- Install the required dependencies using Composer:
  ```bash
    composer install
    ```
- Copy the example environment file and create a new `.env` file:
  ```bash
    cp .env.example .env
    ```
- Generate an application key by running the command below:
  ```bash
    php artisan key:generate
    ```
- Configure your database settings in the `.env` file. Update the following lines with your database credentials. e.g:
    ```plaintext
    DB_CONNECTION=mysql
    DB_HOST=localhost
    DB_PORT=3306
    DB_DATABASE=<your_database_name>
    DB_USERNAME=root
    DB_PASSWORD=

- Migrate the database to create the necessary tables:
  ```bash
    php artisan migrate
    ```
- Now you can start the development server by running:
  ```bash
    php artisan serve
    ```
