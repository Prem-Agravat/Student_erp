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

### Step C: Create and Import Database Schema
1. Open your web browser and navigate to `http://localhost/phpmyadmin/`.
2. Click on **New** in the left sidebar to create a new database.
3. Set the database name to `school_erp` and click **Create**.
4. Select the newly created `school_erp` database, click on the **Import** tab in the top menu.
5. Click **Choose File** and select the SQL base schema script located at:
   `C:\xampp\htdocs\school-erp\database\school_erp.sql`
6. Click **Import** at the bottom of the page to execute the SQL queries.

### Step D: Seed the Database (Optional but Recommended)
To populate the database with realistic sample data for immediate testing, you can choose one of the following seeding options:

* **Option 1: Complete Realistic Seed (Radio Station Primary School)**
  * Contains a fully configured school layout: 10 academic standards, sections, subjects, 30 students, 5 days of attendance logs, exams, and exam marks.
  * **Via CLI**: Run `php database/seed_radio_station_school.php` in your terminal. This generates the SQL script and automatically seeds the database.
  * **Via phpMyAdmin**: Select `school_erp` database, go to **Import**, and import `C:\xampp\htdocs\school-erp\database\seed_radio_station_school.sql`.

* **Option 2: Minimal Demo Seed (St. Xavier's High School)**
  * Contains 2 schools (one approved, one pending), sections, and a couple of students for a quick run.
  * **Via phpMyAdmin**: Select `school_erp` database, go to **Import**, and import `C:\xampp\htdocs\school-erp\database\seed_demo.sql`.

---

## 3. Account Access & Testing Credentials

The system includes pre-configured credentials depending on the seeder imported:

### A. Super Admin Panel (Manage all schools)
* **Access Link**: `http://localhost/school-erp/auth/admin-login.php`
* **Username**: `admin`
* **Password**: `admin123`

### B. Radio Station Primary School (Detailed Seed - Option 1)
* **School Admin Panel**:
  * **Access Link**: `http://localhost/school-erp/auth/school-login.php`
  * **Username**: `rsps_admin`
  * **Password**: `admin123`
* **Student Portal**:
  * **Access Link**: `http://localhost/school-erp/auth/student-login.php`
  * **School**: Select `Radio Station Primary School`
  * **Usernames**: `rsps0001` to `rsps0030` (e.g. `rsps0001`, `rsps0002`)
  * **Password**: `student123`

### C. St. Xavier's High School (Minimal Seed - Option 2)
* **School Admin Panel**:
  * **Access Link**: `http://localhost/school-erp/auth/school-login.php`
  * **Username**: `stx_admin`
  * **Password**: `admin123`
* **Student Portal**:
  * **Access Link**: `http://localhost/school-erp/auth/student-login.php`
  * **School**: Select `St. Xavier's High School`
  * **Usernames**: `stx0001`, `stx0002`
  * **Password**: `student123`

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

## 5. Key Features & Quality of Life Improvements

* **Smart Daily Attendance**: Daily attendance default values auto-populate from each student's last marked status, reducing workload for teachers. The UI displays a clock badge detailing their history.
* **Scrollable Navigation Sidebar**: Enhanced scrollbars and sidebar viewport heights optimized for fluid desktop navigation.
* **Data Tenant Isolation**: Queries validate `school_id` from secure session variables to prevent cross-tenant URL tampering (IDOR).
* **Data Security & Hashing**: CSRF tokens protect post requests, PDO statements prevent SQL Injection, and passwords are hashed using Bcrypt (`password_hash()`).

---

## 6. Security Features

* **Data Tenant Isolation**: Queries validate `school_id` from the secure session variables, preventing cross-tenant URL manipulation (IDOR).
* **Cross-Site Request Forgery (CSRF)**: All POST requests enforce CSRF token verification.
* **SQL Injection Protection**: All queries utilize PDO prepared statements.
* **Password Hashing**: Stored securely using `password_hash()` (Bcrypt).
