# SchoolERP - Complete Multi-School ERP Management System

SchoolERP is a production-grade, multi-school (multi-tenant) School ERP Management System built in native PHP 8+, MySQL, HTML5, Vanilla CSS3, Bootstrap 5, and AJAX.

---

## 1. System Requirements

* **Local Server Suite**: XAMPP / WAMP / Apache server bundle
* **PHP Version**: 8.0 or higher
* **Database**: MySQL / MariaDB
* **Web Browser**: Chrome, Firefox, Edge, Safari

---

## 2. Installation & Database Setup

Follow these steps to run the application locally on XAMPP:

### Step A: Clone or copy project files
Move the `/school-erp` folder into your XAMPP web root directory, normally located at:
`C:\xampp\htdocs\school-erp`

### Step B: Start MySQL & Apache
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to the **Apache** module.
3. Click **Start** next to the **MySQL** module.

### Step C: Create and Import Database
1. Open your web browser and navigate to `http://localhost/phpmyadmin/`.
2. Click on **New** in the left sidebar to create a new database.
3. Set the database name to `school_erp` and click **Create**.
4. Select the newly created `school_erp` database, click on the **Import** tab in the top menu.
5. Click **Choose File** and select the SQL schema script located at:
   `C:\xampp\htdocs\school-erp\database\school_erp.sql`
6. Click **Import** at the bottom of the page to execute the SQL queries.

---

## 3. Account Access & Testing Credentials

The database comes pre-seeded with a default Super Admin account:

### A. Super Admin Panel
* **Access Link**: `http://localhost/school-erp/auth/admin-login.php`
* **Username**: `admin`
* **Password**: `admin123`

---

## 4. ERP Workflow Setup Guide

To test the multi-school environment, execute this step-by-step registration flow:

### Step 1: Register a New School
1. Visit the home landing page `http://localhost/school-erp/`.
2. Click **Register Your School** or navigate to `http://localhost/school-erp/auth/school-register.php`.
3. Fill in the school particulars, principal details, and admin account credentials. Click **Submit**.
4. The system logs the request as `pending` status.

### Step 2: Approve the School
1. Log in to the **Super Admin Panel** (see credentials above).
2. Go to **Pending Requests** in the sidebar.
3. Review the school registration and click **Approve** (the green checkmark).

### Step 3: Configure Academic Infrastructure
1. Log in to the **School Admin Panel** using the credentials you defined during registration (`http://localhost/school-erp/auth/school-login.php`).
2. Go to **Academic Years** and click **Add Academic Year** (e.g., `2025-26`). Click **Activate** to make it the active cycle.
3. Go to **Standards** and configure your classroom grades (e.g. `Standard 10`, `Grade 5`).
4. Go to **Sections** and create divisions for your standards (e.g., Section `A`, Section `B`).
5. Go to **Subjects** to create subjects (e.g., `Mathematics`) and map them to your active standards.

### Step 4: Register Students
1. In the School Admin panel, go to **Manage Students** and click **Add Student**.
2. Fill in the student profile and parent details. Click **Enroll**.
3. The system automatically generates a unique Student ID (e.g., `CODE-YEAR-0001`) and a portal Username (e.g., `code0001`). Note these down.

### Step 5: Student Portal Access
1. Go to the student login page: `http://localhost/school-erp/auth/student-login.php`.
2. Select your registered school in the **Searchable Dropdown**.
3. Enter the student's username and the temporary password (default is `student123`).
4. Access the student dashboard to view attendance, exam schedules, and results!

---

## 5. Security Features

* **Data Tenant Isolation**: Queries validate `school_id` from the secure session variables, preventing cross-tenant URL manipulation (IDOR).
* **Cross-Site Request Forgery (CSRF)**: All POST requests enforce CSRF token verification.
* **SQL Injection Protection**: All queries utilize PDO prepared statements.
* **Password Hashing**: Stored securely using `password_hash()` (Bcrypt).
