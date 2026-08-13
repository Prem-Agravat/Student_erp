<?php
// C:\xampp\htdocs\school-erp\index.php

require_once 'config/constants.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchoolERP - Complete Multi-School Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-indigo" href="index.php" style="color: #4f46e5;">
                <i class="fa-solid fa-graduation-cap me-2"></i>SchoolERP
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">Why SchoolERP</a></li>
                    <li class="nav-item"><a class="nav-link" href="#process">Process</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-light px-3 py-2 border rounded-pill" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Login Gateway
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item py-2" href="auth/student-login.php"><i class="fa-solid fa-user-graduate text-primary me-2"></i>Student Login</a></li>
                            <li><a class="dropdown-item py-2" href="auth/school-login.php"><i class="fa-solid fa-school text-indigo me-2"></i>School Admin Login</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2" href="auth/admin-login.php"><i class="fa-solid fa-user-shield text-danger me-2"></i>Super Admin Login</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="auth/school-register.php" class="btn btn-indigo rounded-pill px-4">Register School</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section text-center text-lg-start d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center py-5">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <span class="badge bg-indigo mb-3 px-3 py-2 text-uppercase tracking-wider" style="background-color: #6366f1;">SaaS Multi-School ERP</span>
                    <h1 class="display-4 fw-bold lh-sm mb-4">Complete Multi-School ERP Management System</h1>
                    <p class="lead text-white-50 mb-5">Streamline student admission, academic years, classes, daily attendance, examination performance, fees collections, and notice publications in one secure, isolated environment.</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                        <a href="auth/school-register.php" class="btn btn-indigo btn-lg px-4 py-3 rounded-pill"><i class="fa-solid fa-school-flag me-2"></i>Register Your School</a>
                        <a href="auth/student-login.php" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill"><i class="fa-solid fa-user-graduate me-2"></i>Student Login</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=600&q=80" alt="Students studying" class="img-fluid rounded-4 shadow-lg border border-secondary" style="max-height: 400px; object-fit: cover;">
                </div>
            </div>
        </div>
    </header>

    <!-- Statistics Counter Section -->
    <section class="py-5 text-white shadow-sm" style="background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%);">
        <div class="container py-4">
            <div class="row g-4 text-center">
                <div class="col-6 col-md-3">
                    <h2 class="display-5 fw-bold mb-2">15+</h2>
                    <p class="text-white-50 mb-0 font-semibold">Registered Schools</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="display-5 fw-bold mb-2">5,000+</h2>
                    <p class="text-white-50 mb-0 font-semibold">Active Students</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="display-5 fw-bold mb-2">99.9%</h2>
                    <p class="text-white-50 mb-0 font-semibold">Data Isolation Guarantee</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2 class="display-5 fw-bold mb-2">100%</h2>
                    <p class="text-white-50 mb-0 font-semibold">Digitalized Operations</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-white">
        <div class="container py-5">
            <div class="text-center mb-5 max-w-2xl mx-auto">
                <h2 class="fw-bold mb-3">Enterprise-Grade Features</h2>
                <p class="text-secondary">Explore the robust modules designed to manage all educational and administrative operations cleanly and dynamically.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm hover-scale glass-card">
                        <div class="feature-icon"><i class="fa-solid fa-users-gear"></i></div>
                        <h4 class="fw-bold mb-3">School Management</h4>
                        <p class="text-secondary mb-0">Admins can create academic years, configure standards (1-12), setup sections, assign subjects, stream streams, and manage teaching staff schedules.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm hover-scale glass-card">
                        <div class="feature-icon"><i class="fa-solid fa-user-graduate"></i></div>
                        <h4 class="fw-bold mb-3">Student Management</h4>
                        <p class="text-secondary mb-0">Manage profile data, parent contacts, document uploads, and roll assignments. Auto-generate secure login credentials for common login page access.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm hover-scale glass-card">
                        <div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <h4 class="fw-bold mb-3">Attendance System</h4>
                        <p class="text-secondary mb-0">Dynamic daily, weekly, or monthly class attendance trackers with status updates (Present, Absent, Late, Leave) and detailed student reports.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm hover-scale glass-card">
                        <div class="feature-icon"><i class="fa-solid fa-file-invoice"></i></div>
                        <h4 class="fw-bold mb-3">Exams & Results</h4>
                        <p class="text-secondary mb-0">Configure exams, manage marks entries via AJAX, and publish results. Auto-calculate percentages, configurable grades, and print reports card templates.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm hover-scale glass-card">
                        <div class="feature-icon"><i class="fa-solid fa-bullhorn"></i></div>
                        <h4 class="fw-bold mb-3">Notifications & Noticeboard</h4>
                        <p class="text-secondary mb-0">Publish notices with target audiences (All, Specific standard, or section) and category types (Holidays, Exams, Fees alerts, Results announcements).</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm hover-scale glass-card">
                        <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <h4 class="fw-bold mb-3">Multi-School SaaS Isolation</h4>
                        <p class="text-secondary mb-0">Rigorous security filters and unique `school_id` tagging ensures absolute database data isolation between multiple registered institutions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why SchoolERP section -->
    <section id="about" class="py-5 bg-light">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=600&q=80" alt="Classroom learning" class="img-fluid rounded-4 shadow border border-white">
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h2 class="fw-bold mb-4">Why Educational Institutions Prefer SchoolERP</h2>
                    <ul class="list-unstyled d-flex flex-column gap-3">
                        <li class="d-flex align-items-start gap-3">
                            <div class="text-indigo mt-1"><i class="fa-solid fa-circle-check fa-lg"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Modern & Clean Interface</h5>
                                <p class="text-secondary mb-0">A dashboard-oriented, mobile-responsive responsive design easy to navigate for school staff, teachers, and students alike.</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <div class="text-indigo mt-1"><i class="fa-solid fa-circle-check fa-lg"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Robust Security Architecture</h5>
                                <p class="text-secondary mb-0">Protects against SQL Injection, XSS, CSRF attacks, IDOR, and session hijacking. Secure password bcrypt hashing.</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                            <div class="text-indigo mt-1"><i class="fa-solid fa-circle-check fa-lg"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Searchable School Selector</h5>
                                <p class="text-secondary mb-0">Common student login portal integrates a searchable school selector so students can easily choose their school name + code to login.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="process" class="py-5 bg-white">
        <div class="container py-5">
            <div class="text-center mb-5 max-w-2xl mx-auto">
                <h2 class="fw-bold mb-3">Get Started in 3 Simple Steps</h2>
                <p class="text-secondary">SchoolERP is designed to get your institution online as quickly as possible.</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm glass-card">
                        <div class="feature-icon bg-indigo-light text-indigo mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; font-size: 24px; background: rgba(79,70,229,0.1);"><i class="fa-solid fa-school-flag"></i></div>
                        <h5 class="fw-bold mb-2">1. Register Institution</h5>
                        <p class="text-secondary mb-0">Fill in your school details, upload your logo, and generate your admin credentials instantly.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm glass-card">
                        <div class="feature-icon bg-indigo-light text-indigo mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; font-size: 24px; background: rgba(79,70,229,0.1);"><i class="fa-solid fa-file-csv"></i></div>
                        <h5 class="fw-bold mb-2">2. Setup Academics</h5>
                        <p class="text-secondary mb-0">Configure academic years, define classes/standards, assign subjects, and upload student lists in bulk via CSV.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4 border-0 shadow-sm glass-card">
                        <div class="feature-icon bg-indigo-light text-indigo mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; font-size: 24px; background: rgba(79,70,229,0.1);"><i class="fa-solid fa-graduation-cap"></i></div>
                        <h5 class="fw-bold mb-2">3. Run Operations</h5>
                        <p class="text-secondary mb-0">Track daily student attendance, publish school notices, manage term examinations, and generate printable report cards.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5 max-w-2xl mx-auto">
                <h2 class="fw-bold mb-3">Frequently Asked Questions</h2>
                <p class="text-secondary">Have questions about the platform? Check out the answers below.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion shadow-sm rounded-4 overflow-hidden border-0" id="faqAccordion">
                        <div class="accordion-item border-0 border-bottom">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How is my school's database data kept secure and isolated?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    SchoolERP is built on a robust multi-school tenant architecture. Every record in our database is tagged with a unique <code>school_id</code>. Session authorization checks are strictly validated on every endpoint, preventing any cross-tenant data leaks.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 border-bottom">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Can we upload students in bulk?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    Yes! We provide a bulk CSV import template. Simply download the sample template CSV, paste your student records into the columns, and upload it to import hundreds of students instantly.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Can students access their profiles, attendance, and exam scores?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary bg-white">
                                    Absolutely. Each student gets auto-generated login credentials. When they log in, they can view their class timetables, daily attendance reports, school notices, fee ledger logs, and published exam results/report cards.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-5 mb-5 mb-lg-0">
                    <h2 class="fw-bold mb-4">Get In Touch</h2>
                    <p class="text-secondary">Have questions about setting up your school registration? Reach out to our system support team today.</p>
                    <div class="mt-4 d-flex flex-column gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white text-indigo rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;"><i class="fa-solid fa-envelope"></i></div>
                            <span class="text-secondary">support@schoolerp.com</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white text-indigo rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;"><i class="fa-solid fa-phone"></i></div>
                            <span class="text-secondary">+91 98250 12345</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <form class="glass-card p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Your Name</label>
                                <input type="text" class="form-control rounded-3" placeholder="Your Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Email Address</label>
                                <input type="email" class="form-control rounded-3" placeholder="xyz@gmail.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold">Message</label>
                                <textarea class="form-control rounded-3" rows="4" placeholder="Your query..."></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-indigo rounded-pill px-4">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white-50 py-4 border-top border-secondary">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> SchoolERP Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
