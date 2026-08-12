USE `school_erp`;

-- 1. Insert Demo Schools
INSERT INTO `schools` (`id`, `school_name`, `school_code`, `email`, `phone`, `address`, `city`, `state`, `pincode`, `board`, `medium`, `established_year`, `school_type`, `principal_name`, `principal_email`, `principal_phone`, `status`) VALUES
(1, 'St. Xavier\'s High School', 'STX001', 'stx@school.com', '555-0100', '100 Education Way', 'Rajkot', 'Gujarat', '360001', 'CBSE', 'English', 1995, 'Co-Ed', 'Dr. Xavier Kumar', 'xavier@school.com', '555-0101', 'approved'),
(2, 'Delhi Public School', 'DPS002', 'dps@school.com', '555-0200', '200 Academy Boulevard', 'Ahmedabad', 'Gujarat', '380001', 'CBSE', 'English', 2002, 'Co-Ed', 'Mrs. Sunita Sharma', 'sunita@school.com', '555-0202', 'pending');

-- 2. Insert School Admins
-- Passwords: admin123
INSERT INTO `school_admins` (`id`, `school_id`, `admin_name`, `admin_email`, `username`, `password_hash`, `status`) VALUES
(1, 1, 'Stx Admin User', 'stxadmin@school.com', 'stx_admin', '$2y$10$3D/wM2kz013WXSDqprKOGey2cClD5Iu8P5qASW88HUc0xU29.MdGO', 'active'),
(2, 2, 'Dps Admin User', 'dpsadmin@school.com', 'dps_admin', '$2y$10$3D/wM2kz013WXSDqprKOGey2cClD5Iu8P5qASW88HUc0xU29.MdGO', 'active');

-- 3. Insert Academic Year
INSERT INTO `academic_years` (`id`, `school_id`, `name`, `start_date`, `end_date`, `status`) VALUES
(1, 1, '2025-26', '2025-06-01', '2026-04-30', 'active');

-- 4. Insert Standards
INSERT INTO `standards` (`id`, `school_id`, `academic_year_id`, `name`, `display_order`, `status`) VALUES
(1, 1, 1, 'Standard 10', 10, 'active'),
(2, 1, 1, 'Standard 12', 12, 'active');

-- 5. Insert Sections
INSERT INTO `sections` (`id`, `school_id`, `standard_id`, `name`, `class_teacher`, `capacity`, `status`) VALUES
(1, 1, 1, 'A', 'Mr. Amit Patel', 40, 'active'),
(2, 1, 2, 'A', 'Mrs. Binita Shah', 40, 'active');

-- 6. Insert Subjects
INSERT INTO `subjects` (`id`, `school_id`, `name`, `stream`, `status`) VALUES
(1, 1, 'Mathematics', 'General', 'active'),
(2, 1, 'Science', 'General', 'active'),
(3, 1, 'English', 'General', 'active');

-- 7. Map Subjects to Standards
INSERT INTO `standard_subjects` (`school_id`, `standard_id`, `subject_id`) VALUES
(1, 1, 1),
(1, 1, 2),
(1, 1, 3),
(1, 2, 1),
(1, 2, 3);

-- 8. Insert Students
-- Passwords: student123
INSERT INTO `students` (`id`, `school_id`, `student_id`, `first_name`, `middle_name`, `last_name`, `gender`, `dob`, `address`, `city`, `state`, `pincode`, `academic_year_id`, `standard_id`, `section_id`, `roll_number`, `admission_number`, `admission_date`, `father_name`, `mother_name`, `parent_phone`, `username`, `password_hash`, `status`) VALUES
(1, 1, 'STX001-2025-0001', 'Prem', 'Ramesh', 'Agravat', 'Male', '2010-08-20', '123 Ring Road', 'Rajkot', 'Gujarat', '360001', 1, 1, 1, 1, 'ADM2025-001', '2025-06-01', 'Ramesh Agravat', 'Geeta Agravat', '9876543210', 'stx0001', '$2y$10$Pzf6YeilceboVwysOSn1ReUgOAzy7nom.Zz7YTL4cWQhIPZPCVG6q', 'active'),
(2, 1, 'STX001-2025-0002', 'Aarav', 'Dinesh', 'Joshi', 'Male', '2010-12-10', '456 University Road', 'Rajkot', 'Gujarat', '360005', 1, 1, 1, 2, 'ADM2025-002', '2025-06-01', 'Dinesh Joshi', 'Kirti Joshi', '9876543211', 'stx0002', '$2y$10$Pzf6YeilceboVwysOSn1ReUgOAzy7nom.Zz7YTL4cWQhIPZPCVG6q', 'active');
