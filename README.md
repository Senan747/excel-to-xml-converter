# Excel to XML Upload Service

This Laravel project provides an API to upload Excel files (`.xlsx` or `.xls`) and convert them into XML format. The generated XML files are stored in the `public` storage disk, and a download URL is returned in the API response.

The project uses Docker for containerization, making setup and deployment easier and consistent across environments.

---

## Features

* Upload Excel files via API
* Validate Excel file type and size
* Convert Excel data to XML
* Store XML files in public storage
* Return download URL for the XML file
* Dockerized for easy development and deployment

---

## Requirements

* Docker & Docker Compose installed
* PHP 8.1+ (handled by Docker)
* Composer (handled by Docker)
* Laravel 10+

---

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/Senan747/excel-to-xml-converter.git
cd excel-to-xml-converter
```

### 2. Copy the environment file

```bash
cp .env.example .env
```

Update `.env` if needed (e.g., database configuration, storage settings).

### 3. Build and run Docker containers

```bash
docker-compose up -d --build
```

This will start the following services:

* **app**: Laravel PHP container
* **db**: MySQL database container
* **phpmyadmin** (optional): MySQL admin interface

### 4. Install dependencies inside the container

```bash
docker exec -it app bash
composer install
php artisan key:generate
```

### 5. Set up storage link

```bash
php artisan storage:link
```

This ensures uploaded files are accessible via the public URL.

---

## API Usage

### Endpoint

```
POST /api/upload
```

### Request Parameters

| Parameter | Type | Description                    |
| --------- | ---- | ------------------------------ |
| file      | file | Excel file (`.xlsx` or `.xls`) |

### Response

**Success (200):**

```json
{
  "status": "success",
  "message": "XML successfully uploaded.",
  "data": {
    "file_name": "ipn_2025_11_08_120000.xml",
    "download_url": "http://localhost/storage/ipn_2025_11_08_120000.xml"
  }
}
```

**Validation Error (422):**

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "file": ["The file must be an Excel file."]
  }
}
```

**Server Error (500):**

```json
{
  "status": "error",
  "message": "There is an error while creating xml: ..."
}
```

---

## File Storage

Uploaded XML files are stored in:

```
storage/app/public/
```

They can be accessed via:

```
http://<your-domain>/storage/<file_name>.xml
```

---

## Docker Commands

* **Start containers**:

```bash
docker-compose up -d
```

* **Stop containers**:

```bash
docker-compose down
```

* **Access app container**:

```bash
docker exec -it <app_container_name> bash
```

* **Run artisan commands** inside container:

```bash
php artisan migrate
php artisan storage:link
```