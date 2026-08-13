<?php
// C:\xampp\htdocs\school-erp\database\seed_radio_station_school.php

// Set time limit and memory limit for data generation
set_time_limit(300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Open SQL export file
    $sqlFilePath = __DIR__ . '/seed_radio_station_school.sql';
    $sqlFile = fopen($sqlFilePath, 'w');
    if (!$sqlFile) {
        throw new Exception("Unable to open SQL file for writing: " . $sqlFilePath);
    }
    
    // Write header to SQL file
    fwrite($sqlFile, "-- Radio Station Primary School Seed Data\n");
    fwrite($sqlFile, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
    fwrite($sqlFile, "USE `school_erp`;\n\n");
    fwrite($sqlFile, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

    echo "Starting seeding process...\n";
    
    // Start Transaction
    $pdo->beginTransaction();
    
    // 1. Insert School
    $school_name = "Radio Station Primary School";
    $school_code = "RSPS001";
    $school_email = "info@radiostationschool.org";
    $school_phone = "0281-2443355";
    $school_address = "Radio Station Road, opposite All India Radio";
    $school_city = "Rajkot";
    $school_state = "Gujarat";
    $school_pincode = "360003";
    $school_board = "State Board";
    $school_medium = "Gujarati";
    $school_est = 2010;
    $school_type = "Co-Ed";
    $principal_name = "Rajeshbhai Patel";
    $principal_email = "principal@radiostationschool.org";
    $principal_phone = "9825012345";
    $school_status = "approved";
    
    $insert_school = "INSERT INTO `schools` (`school_name`, `school_code`, `email`, `phone`, `address`, `city`, `state`, `pincode`, `board`, `medium`, `established_year`, `school_type`, `principal_name`, `principal_email`, `principal_phone`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($insert_school);
    $stmt->execute([$school_name, $school_code, $school_email, $school_phone, $school_address, $school_city, $school_state, $school_pincode, $school_board, $school_medium, $school_est, $school_type, $principal_name, $principal_email, $principal_phone, $school_status]);
    $school_id = $pdo->lastInsertId();
    
    $sql_line = sprintf(
        "INSERT INTO `schools` (`school_name`, `school_code`, `email`, `phone`, `address`, `city`, `state`, `pincode`, `board`, `medium`, `established_year`, `school_type`, `principal_name`, `principal_email`, `principal_phone`, `status`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s');\n\n",
        addslashes($school_name), addslashes($school_code), addslashes($school_email), addslashes($school_phone), addslashes($school_address), addslashes($school_city), addslashes($school_state), addslashes($school_pincode), addslashes($school_board), addslashes($school_medium), $school_est, addslashes($school_type), addslashes($principal_name), addslashes($principal_email), addslashes($principal_phone), $school_status
    );
    fwrite($sqlFile, "-- 1. School\n" . $sql_line);
    echo "Inserted School: $school_name (ID: $school_id)\n";

    // 2. Insert School Admin
    $admin_name = "Radio Station Admin";
    $admin_email = "admin@radiostationschool.org";
    $admin_username = "rsps_admin";
    $admin_hash = '$2y$10$3D/wM2kz013WXSDqprKOGey2cClD5Iu8P5qASW88HUc0xU29.MdGO'; // password: admin123
    $admin_status = "active";
    
    $insert_admin = "INSERT INTO `school_admins` (`school_id`, `admin_name`, `admin_email`, `username`, `password_hash`, `status`) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insert_admin);
    $stmt->execute([$school_id, $admin_name, $admin_email, $admin_username, $admin_hash, $admin_status]);
    $admin_id = $pdo->lastInsertId();
    
    $sql_line = sprintf(
        "INSERT INTO `school_admins` (`school_id`, `admin_name`, `admin_email`, `username`, `password_hash`, `status`) VALUES (%d, '%s', '%s', '%s', '%s', '%s');\n\n",
        $school_id, addslashes($admin_name), addslashes($admin_email), addslashes($admin_username), addslashes($admin_hash), $admin_status
    );
    fwrite($sqlFile, "-- 2. School Admin\n" . $sql_line);
    echo "Inserted School Admin: $admin_username (ID: $admin_id)\n";

    // 3. Insert Academic Year
    $ay_name = "2025-26";
    $ay_start = "2025-06-01";
    $ay_end = "2026-04-30";
    $ay_status = "active";
    
    $insert_ay = "INSERT INTO `academic_years` (`school_id`, `name`, `start_date`, `end_date`, `status`) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insert_ay);
    $stmt->execute([$school_id, $ay_name, $ay_start, $ay_end, $ay_status]);
    $ay_id = $pdo->lastInsertId();
    
    $sql_line = sprintf(
        "INSERT INTO `academic_years` (`school_id`, `name`, `start_date`, `end_date`, `status`) VALUES (%d, '%s', '%s', '%s', '%s');\n\n",
        $school_id, addslashes($ay_name), $ay_start, $ay_end, $ay_status
    );
    fwrite($sqlFile, "-- 3. Academic Year\n" . $sql_line);
    echo "Inserted Academic Year: $ay_name (ID: $ay_id)\n";

    // 4 & 5. Insert Standards (1 to 10) and Sections
    $teacher_names = [
        1 => 'Mrs. Heenaben Patel',
        2 => 'Mrs. Pritiben Shah',
        3 => 'Mr. Jagdishbhai Joshi',
        4 => 'Mrs. Ranjanben Trivedi',
        5 => 'Mr. Chandreshbhai Dave',
        6 => 'Mrs. Naynaben Mehta',
        7 => 'Mr. Bharatbhai Parmar',
        8 => 'Mrs. Hansaben Vyas',
        9 => 'Mr. Kiritbhai Solanki',
        10 => 'Mr. Mansukhbhai Pandya'
    ];
    
    $standard_ids = [];
    $section_ids = [];
    
    fwrite($sqlFile, "-- 4. Standards & 5. Sections\n");
    for ($i = 1; $i <= 10; $i++) {
        $std_name = "Standard " . $i;
        $insert_std = "INSERT INTO `standards` (`school_id`, `academic_year_id`, `name`, `display_order`, `status`) VALUES (?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($insert_std);
        $stmt->execute([$school_id, $ay_id, $std_name, $i]);
        $std_id = $pdo->lastInsertId();
        $standard_ids[$i] = $std_id;
        
        $sql_line = sprintf(
            "INSERT INTO `standards` (`id`, `school_id`, `academic_year_id`, `name`, `display_order`, `status`) VALUES (%d, %d, %d, '%s', %d, 'active');\n",
            $std_id, $school_id, $ay_id, addslashes($std_name), $i
        );
        fwrite($sqlFile, $sql_line);
        
        // Section A for each standard
        $teacher = $teacher_names[$i];
        $insert_sec = "INSERT INTO `sections` (`school_id`, `standard_id`, `name`, `class_teacher`, `capacity`, `status`) VALUES (?, ?, 'A', ?, 40, 'active')";
        $stmt = $pdo->prepare($insert_sec);
        $stmt->execute([$school_id, $std_id, $teacher]);
        $sec_id = $pdo->lastInsertId();
        $section_ids[$i] = $sec_id;
        
        $sql_line = sprintf(
            "INSERT INTO `sections` (`id`, `school_id`, `standard_id`, `name`, `class_teacher`, `capacity`, `status`) VALUES (%d, %d, %d, 'A', '%s', 40, 'active');\n",
            $sec_id, $school_id, $std_id, addslashes($teacher)
        );
        fwrite($sqlFile, $sql_line);
    }
    fwrite($sqlFile, "\n");
    echo "Inserted 10 Standards and Sections.\n";

    // 6. Insert Subjects
    $subjects = ['Gujarati', 'Mathematics', 'Science', 'Social Science', 'English', 'Hindi', 'Computer Science'];
    $subject_ids = [];
    
    fwrite($sqlFile, "-- 6. Subjects\n");
    foreach ($subjects as $sub_name) {
        $insert_sub = "INSERT INTO `subjects` (`school_id`, `name`, `stream`, `status`) VALUES (?, ?, 'General', 'active')";
        $stmt = $pdo->prepare($insert_sub);
        $stmt->execute([$school_id, $sub_name]);
        $sub_id = $pdo->lastInsertId();
        $subject_ids[$sub_name] = $sub_id;
        
        $sql_line = sprintf(
            "INSERT INTO `subjects` (`id`, `school_id`, `name`, `stream`, `status`) VALUES (%d, %d, '%s', 'General', 'active');\n",
            $sub_id, $school_id, addslashes($sub_name)
        );
        fwrite($sqlFile, $sql_line);
    }
    fwrite($sqlFile, "\n");
    echo "Inserted 7 Subjects.\n";

    // 7. Map Subjects to Standards (standard_subjects)
    fwrite($sqlFile, "-- 7. Standard Subjects mapping\n");
    for ($i = 1; $i <= 10; $i++) {
        $std_id = $standard_ids[$i];
        foreach ($subject_ids as $sub_name => $sub_id) {
            $insert_map = "INSERT INTO `standard_subjects` (`school_id`, `standard_id`, `subject_id`) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($insert_map);
            $stmt->execute([$school_id, $std_id, $sub_id]);
            
            $sql_line = sprintf(
                "INSERT INTO `standard_subjects` (`school_id`, `standard_id`, `subject_id`) VALUES (%d, %d, %d);\n",
                $school_id, $std_id, $sub_id
            );
            fwrite($sqlFile, $sql_line);
        }
    }
    fwrite($sqlFile, "\n");
    echo "Mapped subjects to all standards.\n";

    // 8. Insert Students with Gujarati names
    $boys_first = ['Aarav', 'Kabir', 'Dev', 'Parth', 'Harsh', 'Dhruv', 'Meet', 'Jay', 'Raj', 'Yash', 'Anand', 'Ishan', 'Krrish', 'Dhyey', 'Smit', 'Vedant', 'Moksh', 'Priyansh', 'Shaurya', 'Tirth', 'Veer', 'Manan', 'Darsh'];
    $girls_first = ['Aanya', 'Diya', 'Ishita', 'Kiara', 'Myra', 'Ananya', 'Riya', 'Kavya', 'Isha', 'Sneha', 'Shruti', 'Pooja', 'Hetal', 'Bhumi', 'Kinjal', 'Mansi', 'Payal', 'Janki', 'Nidhi', 'Dhruvi', 'Krishna', 'Dhriti'];
    $fathers = ['Ramesh', 'Dinesh', 'Amit', 'Rajesh', 'Hitesh', 'Sanjay', 'Vijay', 'Suresh', 'Pankaj', 'Manoj', 'Jignesh', 'Harish', 'Kirit', 'Mahesh', 'Bharat', 'Arvind', 'Bhavesh', 'Tushar', 'Harshad'];
    $last_names = ['Patel', 'Shah', 'Mehta', 'Agravat', 'Joshi', 'Trivedi', 'Vora', 'Parmar', 'Solanki', 'Chavda', 'Jadeja', 'Gohil', 'Makwana', 'Dave', 'Bhatt', 'Pandya', 'Vyas', 'Rathod', 'Jhala'];

    // Generate random combinations ensuring we have enough variety
    $students_list = [];
    $student_index = 1;
    
    // Hash for 'student123'
    $student_hash = '$2y$10$Pzf6YeilceboVwysOSn1ReUgOAzy7nom.Zz7YTL4cWQhIPZPCVG6q';
    
    fwrite($sqlFile, "-- 8. Students\n");
    for ($i = 1; $i <= 10; $i++) {
        $std_id = $standard_ids[$i];
        $sec_id = $section_ids[$i];
        
        for ($roll = 1; $roll <= 3; $roll++) {
            $is_boy = ($roll % 2 !== 0);
            $first_name = $is_boy ? $boys_first[($student_index - 1) % count($boys_first)] : $girls_first[($student_index - 1) % count($girls_first)];
            $middle_name = $fathers[($student_index + $roll) % count($fathers)];
            $last_name = $last_names[($student_index + $roll * 2) % count($last_names)];
            
            $gender = $is_boy ? 'Male' : 'Female';
            
            // Age setup: Std 1 student is ~6 years old, Std 10 is ~15 years old.
            $age = $i + 5;
            $birth_year = 2025 - $age;
            $dob = "$birth_year-" . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . "-" . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            
            $student_id_str = "RSPS001-2025-" . str_pad($student_index, 4, '0', STR_PAD_LEFT);
            $admission_num = "ADM2025-RSPS-" . str_pad($student_index, 3, '0', STR_PAD_LEFT);
            $username = "rsps" . str_pad($student_index, 4, '0', STR_PAD_LEFT);
            
            $father_full = $middle_name . " " . $last_name;
            // Mother full name
            $mother_first = $girls_first[($student_index + 3) % count($girls_first)];
            $mother_full = $mother_first . " " . $last_name;
            
            $address = "Street No. " . rand(1, 12) . ", Radio Station Area";
            $city = "Rajkot";
            $state = "Gujarat";
            $pincode = "360003";
            $phone = "98765" . str_pad($student_index, 5, '0', STR_PAD_LEFT);
            
            $insert_student = "INSERT INTO `students` (`school_id`, `student_id`, `first_name`, `middle_name`, `last_name`, `gender`, `dob`, `address`, `city`, `state`, `pincode`, `academic_year_id`, `standard_id`, `section_id`, `roll_number`, `admission_number`, `admission_date`, `father_name`, `mother_name`, `parent_phone`, `username`, `password_hash`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '2025-06-01', ?, ?, ?, ?, ?, 'active')";
            
            $stmt = $pdo->prepare($insert_student);
            $stmt->execute([
                $school_id, $student_id_str, $first_name, $middle_name, $last_name, $gender, $dob, $address, $city, $state, $pincode, $ay_id, $std_id, $sec_id, $roll, $admission_num, $father_full, $mother_full, $phone, $username, $student_hash
            ]);
            
            $student_db_id = $pdo->lastInsertId();
            
            $students_list[] = [
                'db_id' => $student_db_id,
                'standard_val' => $i,
                'standard_id' => $std_id,
                'section_id' => $sec_id
            ];
            
            $sql_line = sprintf(
                "INSERT INTO `students` (`id`, `school_id`, `student_id`, `first_name`, `middle_name`, `last_name`, `gender`, `dob`, `address`, `city`, `state`, `pincode`, `academic_year_id`, `standard_id`, `section_id`, `roll_number`, `admission_number`, `admission_date`, `father_name`, `mother_name`, `parent_phone`, `username`, `password_hash`, `status`) VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, '%s', '2025-06-01', '%s', '%s', '%s', '%s', '%s', 'active');\n",
                $student_db_id, $school_id, $student_id_str, addslashes($first_name), addslashes($middle_name), addslashes($last_name), $gender, $dob, addslashes($address), addslashes($city), addslashes($state), addslashes($pincode), $ay_id, $std_id, $sec_id, $roll, $admission_num, addslashes($father_full), addslashes($mother_full), $phone, addslashes($username), addslashes($student_hash)
            );
            fwrite($sqlFile, $sql_line);
            
            $student_index++;
        }
    }
    fwrite($sqlFile, "\n");
    echo "Inserted " . count($students_list) . " Students (3 per standard, standards 1 to 10).\n";

    // 9. Insert Attendance for each student for 5 days
    $attendance_dates = ['2025-06-02', '2025-06-03', '2025-06-04', '2025-06-05', '2025-06-06'];
    $statuses = ['Present', 'Present', 'Present', 'Present', 'Present', 'Present', 'Present', 'Present', 'Absent', 'Late', 'Leave']; // Weighted
    
    fwrite($sqlFile, "-- 9. Attendance\n");
    foreach ($students_list as $student) {
        foreach ($attendance_dates as $date) {
            $status = $statuses[array_rand($statuses)];
            $remarks = ($status === 'Absent' ? 'Sick' : ($status === 'Late' ? 'Late bus' : NULL));
            
            $insert_att = "INSERT INTO `attendance` (`school_id`, `student_id`, `academic_year_id`, `date`, `status`, `remarks`, `marked_by`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insert_att);
            $stmt->execute([$school_id, $student['db_id'], $ay_id, $date, $status, $remarks, $admin_id]);
            
            $att_id = $pdo->lastInsertId();
            
            $sql_line = sprintf(
                "INSERT INTO `attendance` (`id`, `school_id`, `student_id`, `academic_year_id`, `date`, `status`, `remarks`, `marked_by`) VALUES (%d, %d, %d, %d, '%s', '%s', %s, %d);\n",
                $att_id, $school_id, $student['db_id'], $ay_id, $date, $status, ($remarks ? "'".addslashes($remarks)."'" : "NULL"), $admin_id
            );
            fwrite($sqlFile, $sql_line);
        }
    }
    fwrite($sqlFile, "\n");
    echo "Inserted attendance logs (5 days per student).\n";

    // 10. Exams, Exam Subjects, and Marks
    $exam_name = "First Term Examination";
    $exam_start = "2025-09-15";
    $exam_end = "2025-09-20";
    
    $exam_ids = [];
    
    fwrite($sqlFile, "-- 10. Exams & Exam Subjects\n");
    // Create an exam for each standard
    for ($i = 1; $i <= 10; $i++) {
        $std_id = $standard_ids[$i];
        
        $insert_exam = "INSERT INTO `exams` (`school_id`, `academic_year_id`, `standard_id`, `exam_name`, `start_date`, `end_date`, `status`) VALUES (?, ?, ?, ?, ?, ?, 'published')";
        $stmt = $pdo->prepare($insert_exam);
        $stmt->execute([$school_id, $ay_id, $std_id, $exam_name, $exam_start, $exam_end]);
        $exam_id = $pdo->lastInsertId();
        $exam_ids[$i] = $exam_id;
        
        $sql_line = sprintf(
            "INSERT INTO `exams` (`id`, `school_id`, `academic_year_id`, `standard_id`, `exam_name`, `start_date`, `end_date`, `status`) VALUES (%d, %d, %d, %d, '%s', '%s', '%s', 'published');\n",
            $exam_id, $school_id, $ay_id, $std_id, addslashes($exam_name), $exam_start, $exam_end
        );
        fwrite($sqlFile, $sql_line);
        
        // For each subject, insert into exam_subjects
        $day_offset = 0;
        foreach ($subject_ids as $sub_name => $sub_id) {
            $exam_date = date('Y-m-d', strtotime("$exam_start + $day_offset days"));
            $max_marks = 100;
            $pass_marks = 35;
            
            $insert_es = "INSERT INTO `exam_subjects` (`school_id`, `exam_id`, `subject_id`, `max_marks`, `passing_marks`, `exam_date`) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insert_es);
            $stmt->execute([$school_id, $exam_id, $sub_id, $max_marks, $pass_marks, $exam_date]);
            $es_id = $pdo->lastInsertId();
            
            $sql_line = sprintf(
                "INSERT INTO `exam_subjects` (`id`, `school_id`, `exam_id`, `subject_id`, `max_marks`, `passing_marks`, `exam_date`) VALUES (%d, %d, %d, %d, %d, %d, '%s');\n",
                $es_id, $school_id, $exam_id, $sub_id, $max_marks, $pass_marks, $exam_date
            );
            fwrite($sqlFile, $sql_line);
            
            $day_offset++;
        }
    }
    fwrite($sqlFile, "\n");
    echo "Inserted Exam settings and Exam Subjects for 10 standards.\n";

    // Insert Marks for each student for each subject in their standard's exam
    fwrite($sqlFile, "-- 11. Marks\n");
    foreach ($students_list as $student) {
        $std_val = $student['standard_val'];
        $exam_id = $exam_ids[$std_val];
        
        foreach ($subject_ids as $sub_name => $sub_id) {
            // Generate marks: mostly passing, a few high scores, a few failures
            $roll = rand(1, 10);
            if ($roll === 1) {
                // Fail
                $marks_obtained = rand(20, 34);
            } elseif ($roll <= 4) {
                // Average
                $marks_obtained = rand(35, 60);
            } elseif ($roll <= 8) {
                // Good
                $marks_obtained = rand(61, 85);
            } else {
                // Excellent
                $marks_obtained = rand(86, 100);
            }
            
            // Grade calculation
            if ($marks_obtained >= 90) $grade = 'A+';
            elseif ($marks_obtained >= 80) $grade = 'A';
            elseif ($marks_obtained >= 70) $grade = 'B';
            elseif ($marks_obtained >= 60) $grade = 'C';
            elseif ($marks_obtained >= 50) $grade = 'D';
            elseif ($marks_obtained >= 35) $grade = 'E';
            else $grade = 'F';
            
            $remarks = ($grade === 'F' ? 'Needs Improvement' : 'Good');
            
            $insert_marks = "INSERT INTO `marks` (`school_id`, `student_id`, `exam_id`, `subject_id`, `marks_obtained`, `max_marks`, `grade`, `remarks`, `created_by`) VALUES (?, ?, ?, ?, ?, 100.00, ?, ?, ?)";
            $stmt = $pdo->prepare($insert_marks);
            $stmt->execute([$school_id, $student['db_id'], $exam_id, $sub_id, $marks_obtained, $grade, $remarks, $admin_id]);
            $marks_id = $pdo->lastInsertId();
            
            $sql_line = sprintf(
                "INSERT INTO `marks` (`id`, `school_id`, `student_id`, `exam_id`, `subject_id`, `marks_obtained`, `max_marks`, `grade`, `remarks`, `created_by`) VALUES (%d, %d, %d, %d, %d, %s, 100.00, '%s', '%s', %d);\n",
                $marks_id, $school_id, $student['db_id'], $exam_id, $sub_id, number_format($marks_obtained, 2, '.', ''), $grade, addslashes($remarks), $admin_id
            );
            fwrite($sqlFile, $sql_line);
        }
    }
    fwrite($sqlFile, "\n");
    echo "Inserted Exam Marks for all 30 students in all 7 subjects.\n";

    // Turn back FK checks
    fwrite($sqlFile, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($sqlFile);
    
    // Commit transaction
    $pdo->commit();
    echo "Seeding completed successfully!\n";
    echo "SQL script written to: $sqlFilePath\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Transaction rolled back due to error: " . $e->getMessage() . "\n";
    if (isset($sqlFile) && is_resource($sqlFile)) {
        fclose($sqlFile);
    }
}
