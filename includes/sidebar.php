<?php
// C:\xampp\htdocs\school-erp\includes\sidebar.php

require_once __DIR__ . '/../config/constants.php';

function renderSidebar($activePage) {
    $role = $_SESSION['role'] ?? '';
    
    echo '<div class="sidebar">';
    echo '<div class="brand text-center">';
    echo '<h4 class="fw-bold text-white mb-0"><i class="fa-solid fa-graduation-cap me-2"></i>' . htmlspecialchars(APP_NAME) . '</h4>';
    echo '<small class="text-white-50">' . htmlspecialchars($role) . '</small>';
    echo '</div>';
    
    echo '<ul class="nav flex-column">';
    
    if ($role === ROLE_SUPER_ADMIN) {
        // Super Admin Sidebar
        $links = [
            'dashboard' => ['admin/dashboard.php', 'fa-solid fa-chart-line', 'Dashboard'],
            'requests' => ['admin/school-requests.php', 'fa-solid fa-envelope-open-text', 'Pending Requests'],
            'schools' => ['admin/schools.php', 'fa-solid fa-school', 'All Schools'],
        ];
        
        foreach ($links as $key => $val) {
            $activeClass = ($activePage === $key) ? 'active' : '';
            echo '<li class="nav-item">';
            echo '<a class="nav-link ' . $activeClass . '" href="' . BASE_URL . $val[0] . '">';
            echo '<i class="' . $val[1] . '"></i>' . $val[2];
            echo '</a>';
            echo '</li>';
        }
        
    } elseif ($role === ROLE_SCHOOL_ADMIN) {
        // School Admin Sidebar
        $menuItems = [
            ['dashboard', 'school/dashboard.php', 'fa-solid fa-chart-line', 'Dashboard'],
            // Academic group
            ['header', '', '', 'ACADEMIC'],
            ['academic_years', 'school/academic_years.php', 'fa-solid fa-calendar', 'Academic Years'],
            ['standards', 'school/standards.php', 'fa-solid fa-layer-group', 'Standards (Classes)'],
            ['subjects', 'school/subjects.php', 'fa-solid fa-book', 'Subjects'],
            ['timetable', 'school/timetable.php', 'fa-solid fa-clock', 'Timetable'],
            ['holidays', 'school/holidays.php', 'fa-solid fa-umbrella-beach', 'Holidays'],
            
            // Student group
            ['header', '', '', 'STUDENTS'],
            ['students', 'school/students.php', 'fa-solid fa-user-graduate', 'Manage Students'],
            ['import_students', 'school/students_import.php', 'fa-solid fa-file-import', 'Import Students'],
            
            // Academic records
            ['header', '', '', 'ACADEMIC RECORDS'],
            ['attendance', 'school/attendance.php', 'fa-solid fa-calendar-check', 'Mark Attendance'],
            ['attendance_report', 'school/attendance_report.php', 'fa-solid fa-square-poll-vertical', 'Attendance Reports'],
            ['exams', 'school/exams.php', 'fa-solid fa-file-signature', 'Exams & Marks'],
            ['results', 'school/results.php', 'fa-solid fa-square-poll-horizontal', 'Publish Results'],
            
            // Communication
            ['header', '', '', 'COMMUNICATION'],
            ['notices', 'school/notices.php', 'fa-solid fa-bullhorn', 'Noticeboard'],
            

            

            
            // System Settings
            ['header', '', '', 'SYSTEM'],
            ['settings', 'school/settings.php', 'fa-solid fa-gears', 'School Profile'],
        ];
        
        foreach ($menuItems as $item) {
            if ($item[0] === 'header') {
                echo '<li class="nav-item text-white-50 px-4 pt-3 pb-1" style="font-size: 10px; font-weight: 700; letter-spacing: 0.8px;">' . $item[3] . '</li>';
            } else {
                $activeClass = ($activePage === $item[0]) ? 'active' : '';
                echo '<li class="nav-item">';
                echo '<a class="nav-link ' . $activeClass . '" href="' . BASE_URL . $item[1] . '">';
                echo '<i class="' . $item[2] . '"></i>' . $item[3];
                echo '</a>';
                echo '</li>';
            }
        }
        
    } elseif ($role === ROLE_STUDENT) {
        // Student Sidebar
        $links = [
            'dashboard' => ['student/dashboard.php', 'fa-solid fa-chart-line', 'Dashboard'],
            'profile' => ['student/profile.php', 'fa-solid fa-id-card', 'My Profile'],
            'attendance' => ['student/attendance.php', 'fa-solid fa-calendar-days', 'My Attendance'],
            'results' => ['student/results.php', 'fa-solid fa-file-contract', 'My Results'],
            'exams' => ['student/exams.php', 'fa-solid fa-pen-to-square', 'Exam Schedule'],
            'timetable' => ['student/timetable.php', 'fa-solid fa-clock', 'Class Timetable'],
            'notices' => ['student/notices.php', 'fa-solid fa-bullhorn', 'Notices'],
        ];
        
        foreach ($links as $key => $val) {
            $activeClass = ($activePage === $key) ? 'active' : '';
            echo '<li class="nav-item">';
            echo '<a class="nav-link ' . $activeClass . '" href="' . BASE_URL . $val[0] . '">';
            echo '<i class="' . $val[1] . '"></i>' . $val[2];
            echo '</a>';
            echo '</li>';
        }
    }
    
    // Common Logout
    echo '<li class="nav-item mt-auto border-top border-secondary">';
    echo '<a class="nav-link text-danger" href="' . BASE_URL . 'auth/logout.php">';
    echo '<i class="fa-solid fa-right-from-bracket text-danger"></i>Logout';
    echo '</a>';
    echo '</li>';
    
    echo '</ul>';
    echo '</div>';
}
