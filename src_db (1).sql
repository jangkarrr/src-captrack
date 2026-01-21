-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 10, 2025 at 03:26 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `src_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_year`
--

CREATE TABLE `academic_year` (
  `ay_id` int(11) NOT NULL,
  `ay_name` varchar(50) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_year`
--

INSERT INTO `academic_year` (`ay_id`, `ay_name`, `date_start`, `date_end`, `is_current`, `is_active`) VALUES
(2526, 'S.Y. 2025-2026', '2025-09-01', '2026-05-31', 1, 1),
(2527, 'S.Y. 2026-2027', '2026-09-01', '2027-05-31', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `admission`
--

CREATE TABLE `admission` (
  `admission_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `year_level_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Late','Absent') NOT NULL,
  `admission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `research_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `authors` text DEFAULT NULL,
  `year` varchar(25) DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `keywords` mediumtext DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `published_by_dean` tinyint(1) NOT NULL DEFAULT 0,
  `dean_published_at` datetime DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `course_strand` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `student_id` varchar(10) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `title_norm` varchar(255) GENERATED ALWAYS AS (lcase(trim(`title`))) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `authors`, `year`, `abstract`, `keywords`, `document`, `user_id`, `status`, `submission_date`, `published_by_dean`, `dean_published_at`, `department`, `course_strand`, `image`, `views`, `student_id`, `adviser_id`) VALUES
(1, 'qwe', 'STUDENT_DATA:qwe|qwe|qweqwe||DISPLAY:qwe qwe qweqwe', '2025', 'wqeqwe', 'qwe', '../assets/uploads/books/1764521657_Research_Project_Letter_pdf', 3, 1, '2025-11-30 16:54:17', 1, '2025-12-09 13:57:22', '', '', '', 0, '', 0),
(2, 'sample', 'AUTHOR_DATA:sample|sample|sample||DISPLAY:sample sample sample', '2025', 'sample', 'sample', '../assets/uploads/capstone/research_692d548580995.pdf', NULL, 1, '2025-12-01 08:40:37', 1, '2025-12-09 13:57:22', '', '', '', 0, '', 0),
(3, 'test', 'AUTHOR_DATA:test|test|test||DISPLAY:test test test', '2025', 'test', 'test', '../assets/uploads/capstone/research_6937d0ae2732c.pdf', NULL, 1, '2025-12-09 07:33:02', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(4, 'qwe', 'AUTHOR_DATA:qwe|qwe|qwe||DISPLAY:qwe qwe qwe', '2025', 'qwe', 'qwe', '../assets/uploads/capstone/research_6937d0cde6257.pdf', NULL, 1, '2025-12-09 07:33:33', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(5, 'test', 'STUDENT_DATA:testf|testm|testf|@@testf2||testl2||DISPLAY:testf testm testf, testf2 testl2', '2025', 'test', 'test', '../assets/uploads/books/1765267637_reviewer_pdf', 7, 1, '2025-12-09 08:07:17', 0, NULL, NULL, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `defense_revisions`
--

CREATE TABLE `defense_revisions` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `defense_type` enum('title','final') NOT NULL,
  `defense_id` int(11) NOT NULL,
  `revision_file` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `status` enum('pending','under_review','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_reviewed` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Dean','Teacher') NOT NULL DEFAULT 'Teacher',
  `roles` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `gender` varchar(10) DEFAULT 'Male',
  `status` varchar(20) DEFAULT 'verified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `firstname`, `lastname`, `middle_name`, `email`, `password`, `role`, `roles`, `profile_pic`, `gender`, `status`) VALUES
(1, 'Admin', 'User', '', 'admin@example.com', '$2y$10$0QFk0pwTqiYHLatajRr1FOQsWzP6lQX8mAySJzbsk2F62Gy3VxIxq', '', 'admin', NULL, 'Male', 'verified'),
(2, 'Joshua', 'Tiongco', '', 'joshua@gmail.com', '$2y$10$ABLksgKSmfz.rXMUylHEkOsP1Yy4eo/c5DtnzOS/B7QM29WSZMawO', '', 'faculty, panelist, plagscanner, grammarian, adviser, dean', NULL, 'Male', 'verified'),
(3, 'Grammarian', 'Grammarwan', '', 'grammarian1@gmail.com', '$2y$10$5Y/XwPgdv3M3l4X/iWYS1.2l0B1TbJ98YzpgdtNxTgdXmBelNvqcG', '', 'grammarian', NULL, 'Male', 'verified'),
(4, 'Grammarian', 'Grammarwanplaswan', '', 'grammarian2@gmail.com', '$2y$10$F5U4AWtVlLuhkBYCJovDTO3UwzqkBy1POcHtRRf1ZYkbzGjRZoK4u', '', 'grammarian', NULL, 'Male', 'verified');

-- --------------------------------------------------------

--
-- Table structure for table `facility`
--

CREATE TABLE `facility` (
  `lab_id` int(11) NOT NULL,
  `lab_name` varchar(100) NOT NULL,
  `location` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_defense`
--

CREATE TABLE `final_defense` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `submitted_by` varchar(10) NOT NULL,
  `final_defense_pdf` varchar(255) NOT NULL,
  `status` enum('pending','rejected','approved') DEFAULT 'pending',
  `remarks` mediumtext DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `final_defense`
--

INSERT INTO `final_defense` (`id`, `project_id`, `submitted_by`, `final_defense_pdf`, `status`, `remarks`, `scheduled_date`, `date_submitted`) VALUES
(1, 2, '3', '../assets/uploads/final_defense/1764518542_final_Research_Project_Letter.pdf', 'approved', '', '2025-12-01 00:08:00', '2025-11-30 16:02:22'),
(2, 3, '7', '../assets/uploads/final_defense/1765267531_final_reviewer.pdf', 'approved', 'test', '2025-12-09 16:06:00', '2025-12-09 08:05:31');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  `year_level` tinyint(4) NOT NULL,
  `section_letter` char(1) NOT NULL,
  `year_section` varchar(10) NOT NULL,
  `cohort_year` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `group_name`, `year_level`, `section_letter`, `year_section`, `cohort_year`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'G1', 4, 'B', '4B', NULL, 1, '2025-11-30 21:28:05', '2025-11-30 21:28:05'),
(2, 'G1', 4, 'B', '4A', NULL, 1, '2025-11-30 21:37:17', '2025-11-30 21:37:17'),
(3, 'G1', 3, 'B', '3B', NULL, 1, '2025-11-30 21:57:52', '2025-11-30 21:57:52'),
(4, 'G1', 3, 'A', '3A', NULL, 1, '2025-12-05 14:21:43', '2025-12-05 14:21:43'),
(5, 'G2', 4, 'B', '4B', NULL, 1, '2025-12-09 15:52:35', '2025-12-09 15:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `manuscript_reviews`
--

CREATE TABLE `manuscript_reviews` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `version` int(11) DEFAULT 1,
  `student_id` varchar(10) NOT NULL,
  `manuscript_file` varchar(500) DEFAULT NULL,
  `grammarian_reviewed_file` varchar(500) DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected') DEFAULT 'pending',
  `grammarian_notes` mediumtext DEFAULT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_reviewed` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manuscript_reviews`
--

INSERT INTO `manuscript_reviews` (`id`, `project_id`, `version`, `student_id`, `manuscript_file`, `grammarian_reviewed_file`, `status`, `grammarian_notes`, `date_submitted`, `date_reviewed`, `reviewed_by`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '2511133218', '../assets/uploads/manuscripts/1764520800_manuscript_JULIAN_JOANNA_CSE_PRO_APP (1) (1).pdf', '../assets/uploads/grammarian_reviews/1764521085_reviewed_Research_Project_Letter.pdf', 'approved', 'qwe', '2025-11-30 16:40:00', '2025-10-30 03:49:30', 2, '2025-11-30 16:40:00', '2025-10-30 03:49:30'),
(2, 3, 1, '2512594336', '../assets/uploads/manuscripts/1765267580_manuscript_reviewer.pdf', '../assets/uploads/grammarian_reviews/1765267608_reviewed_reviewer.pdf', 'approved', 'tset', '2025-12-09 08:06:20', '2025-12-09 08:06:48', 2, '2025-12-09 08:06:20', '2025-12-09 08:06:48');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `related_id`, `related_type`, `is_read`, `created_at`) VALUES
(0, 1, 'Student Added', 'A new student \"John Carl Dizon\" has been added to the system.', 'info', NULL, 'new_student', 0, '2025-11-30 13:28:05'),
(0, 2, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-11-30 13:37:17'),
(0, 2, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-11-30 13:37:24'),
(0, 2, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-11-30 13:37:49'),
(0, 2, 'Group Code Assigned', 'You have been assigned to group 4A-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-11-30 13:42:16'),
(0, 2, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-11-30 13:42:22'),
(0, 1, 'Student Added', 'A new student \"John Carl Dizon\" has been added to the system.', 'info', NULL, 'new_student', 0, '2025-11-30 13:57:52'),
(0, 2, 'New Project Assignment', 'You have been assigned as books adviser for the project \"Sample\".', 'info', 2, 'project', 0, '2025-11-30 15:26:15'),
(0, 2, 'New PlagScan Assignment', 'You have been assigned to run plagiarism check for project \\\"Sample\\\" by John Carl Dizon.', 'info', 2, 'plagscan_review', 0, '2025-11-30 16:26:10'),
(0, 2147483647, 'PlagScan Approved', 'Plagiarism checking result has been updated for your project. Status: Approved.', 'info', 2, 'plagscan_review', 0, '2025-11-30 16:33:13'),
(0, 2, 'New Manuscript Assignment', 'You have been assigned to review the manuscript for project \"Sample\" by John Carl Dizon.', 'info', 2, 'manuscript_review', 0, '2025-11-30 16:42:42'),
(0, 2147483647, 'Manuscript Review Complete', 'Your manuscript has been approved by the grammarian. Notes: qwe', 'success', 2, 'manuscript_review', 0, '2025-11-30 16:44:45'),
(0, 1, 'New Research Submitted', 'A new research paper \"qwe\" has been submitted and requires verification.', 'info', 0, 'new_research', 0, '2025-11-30 16:54:17'),
(0, 1, 'Research Added', 'A new research paper \"sample\" has been added to the system.', 'info', 0, 'new_research', 0, '2025-12-01 08:40:37'),
(0, 3, 'Panel Member Assigned', 'A panel member (Joshua Tiongco) has been assigned to your group (4B-G1).', 'info', NULL, 'panel_assignment', 0, '2025-10-30 03:48:43'),
(0, 2, 'Panel Assignment', 'You have been assigned as a panel member for group 4B-G1.', 'info', NULL, 'panel_assignment', 0, '2025-10-30 03:48:43'),
(0, 2147483647, 'Manuscript Review Complete', 'Your manuscript has been approved by the grammarian. Notes: qwe', 'success', 2, 'manuscript_review', 0, '2025-10-30 03:49:30'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 01:43:34'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 01:54:12'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 02:00:05'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 02:00:16'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 02:02:44'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 3B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 02:03:14'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 3B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 02:06:53'),
(0, 4, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 05:33:57'),
(0, 5, 'Group Code Assigned', 'You have been assigned to group 3B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 05:34:25'),
(0, 5, 'Group Code Assigned', 'You have been assigned to group 3B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 05:36:15'),
(0, 5, 'Group Code Assigned', 'You have been assigned to group 4B-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 05:36:27'),
(0, 6, 'Group Code Assigned', 'You have been assigned to group 3A-G1 by the administrator.', 'info', NULL, 'group_assignment', 0, '2025-12-05 05:36:43'),
(0, 3, 'Research Published', 'Your research \"qwe\" has been published in the CCS Research Repository.', 'success', 0, 'books', 0, '2025-12-09 05:57:22'),
(0, 1, 'Research Added', 'A new research paper \"test\" has been added to the system.', 'info', 0, 'new_research', 0, '2025-12-09 07:33:02'),
(0, 3, 'Research Verified', 'Your research \"qwe\" has been verified and is now available in the repository.', 'success', 0, 'books', 0, '2025-12-09 07:33:06'),
(0, 1, 'Research Added', 'A new research paper \"qwe\" has been added to the system.', 'info', 0, 'new_research', 0, '2025-12-09 07:33:33'),
(0, 3, 'Research Verified', 'Your research \"qwe\" has been verified and is now available in the repository.', 'success', 0, 'books', 0, '2025-12-09 07:35:09'),
(0, 1, 'Student Added', 'A new student \"test test\" has been added to the system.', 'info', NULL, 'new_student', 0, '2025-12-09 07:52:35'),
(0, 7, 'Project Submitted Successfully', 'Your project \"test\" has been submitted and is waiting for dean to assign capstone adviser.', 'success', 3, 'project', 0, '2025-12-09 07:53:01'),
(0, 7, 'Capstone Adviser Assigned', 'A books adviser (Joshua Tiongco) has been assigned to your project \"test\".', 'info', 3, 'project', 0, '2025-12-09 07:53:32'),
(0, 2, 'New Project Assignment', 'You have been assigned as books adviser for the project \"test\".', 'info', 3, 'project', 0, '2025-12-09 07:53:32'),
(0, 7, 'Project Approved by Faculty', 'Your project \"test\" has been approved by the faculty advisor.', 'success', 3, 'project', 0, '2025-12-09 07:53:47'),
(0, 7, 'Project Approved by Adviser', 'Your project \"test\" has been approved by your adviser.', 'success', 3, 'project', 0, '2025-12-09 07:53:57'),
(0, 7, 'Project Fully Approved!', 'Congratulations! Your project \"test\" has been fully approved by the dean. You can now proceed to the next phase.', 'success', 3, 'project', 0, '2025-12-09 07:54:09'),
(0, 7, 'Title Defense Submitted', 'Your title defense for project \"test\" has been submitted and is under review.', 'info', 3, 'title_defense', 0, '2025-12-09 07:54:16'),
(0, 7, 'Title Defense Scheduled', 'Your title defense has been scheduled for December 09, 2025 at 4:06 PM.', 'info', 3, 'title_defense', 0, '2025-12-09 08:05:12'),
(0, 7, 'Title Defense Approved!', 'Congratulations! Your title defense has been approved. You can now proceed to final defense.', 'success', 3, 'title_defense', 0, '2025-12-09 08:05:12'),
(0, 7, 'Final Defense Submitted', 'Your final defense for project \"test\" has been submitted and is under review.', 'info', 3, 'final_defense', 0, '2025-12-09 08:05:31'),
(0, 7, 'Final Defense Scheduled', 'Your final defense has been scheduled for December 09, 2025 at 4:06 PM.', 'info', 3, 'final_defense', 0, '2025-12-09 08:05:40'),
(0, 7, 'Final Defense Approved!', 'Congratulations! You have successfully completed your final defense!', 'success', 3, 'final_defense', 0, '2025-12-09 08:05:40'),
(0, 7, 'PlagScan Submitted', 'Your manuscript for project \"test\" has been submitted for plagiarism checking.', 'info', 3, 'plagscan_review', 0, '2025-12-09 08:05:47'),
(0, 7, 'PlagScanner Assigned', 'A PlagScanner (Joshua Tiongco) has been assigned to check your manuscript for project \\\"test\\\".', 'info', 3, 'plagscan_review', 0, '2025-12-09 08:05:54'),
(0, 2, 'New PlagScan Assignment', 'You have been assigned to run plagiarism check for project \\\"test\\\" by test test.', 'info', 3, 'plagscan_review', 0, '2025-12-09 08:05:54'),
(0, 2147483647, 'PlagScan Approved', 'Plagiarism checking result has been updated for your project. Status: Approved.', 'info', 3, 'plagscan_review', 0, '2025-12-09 08:06:11'),
(0, 7, 'Manuscript Submitted', 'Your manuscript for project \"test\" has been submitted for grammar review.', 'info', 3, 'manuscript_submission', 0, '2025-12-09 08:06:20'),
(0, 7, 'Grammarian Assigned', 'A grammarian (Joshua Tiongco) has been assigned to review your manuscript for project \"test\".', 'info', 3, 'manuscript_review', 0, '2025-12-09 08:06:27'),
(0, 2, 'New Manuscript Assignment', 'You have been assigned to review the manuscript for project \"test\" by test test.', 'info', 3, 'manuscript_review', 0, '2025-12-09 08:06:27'),
(0, 2147483647, 'Manuscript Review Complete', 'Your manuscript has been approved by the grammarian. Notes: tset', 'success', 3, 'manuscript_review', 0, '2025-12-09 08:06:48'),
(0, 7, 'Capstone Project Submitted', 'Your books project \"test\" has been submitted successfully and is under review.', 'success', 0, 'books', 0, '2025-12-09 08:07:17'),
(0, 1, 'New Research Submitted', 'A new research paper \"test\" has been submitted and requires verification.', 'info', 0, 'new_research', 0, '2025-12-09 08:07:17'),
(0, 3, 'Research Verified', 'Your research \"qwe\" has been verified and is now available in the repository.', 'success', 0, 'books', 0, '2025-12-09 08:07:37');

-- --------------------------------------------------------

--
-- Table structure for table `panelist_grades`
--

CREATE TABLE `panelist_grades` (
  `id` int(11) NOT NULL,
  `panelist_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `defense_type` enum('title','final') NOT NULL,
  `defense_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `individual_grade` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panelist_group_grades`
--

CREATE TABLE `panelist_group_grades` (
  `id` int(11) NOT NULL,
  `panelist_id` int(11) NOT NULL,
  `group_code` varchar(20) NOT NULL,
  `defense_type` enum('title','final') NOT NULL,
  `defense_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `group_grade` decimal(5,2) NOT NULL,
  `group_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panel_assignments`
--

CREATE TABLE `panel_assignments` (
  `id` int(11) NOT NULL,
  `panelist_id` int(11) NOT NULL,
  `group_code` varchar(50) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panel_assignments`
--

INSERT INTO `panel_assignments` (`id`, `panelist_id`, `group_code`, `group_id`, `assigned_by`, `assigned_date`, `status`) VALUES
(1, 2, '4B-G1', NULL, 2, '2025-10-30 03:48:43', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_users`
--

CREATE TABLE `pending_users` (
  `id` int(11) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `year_section` varchar(50) NOT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `verification_code` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plagscan_reviews`
--

CREATE TABLE `plagscan_reviews` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `version` int(11) DEFAULT 1,
  `student_id` varchar(10) NOT NULL,
  `manuscript_file` varchar(500) DEFAULT NULL,
  `plagscan_result_file` varchar(500) DEFAULT NULL,
  `ai_result_file` varchar(500) DEFAULT NULL,
  `percent_similarity` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_reviewed` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plagscan_reviews`
--

INSERT INTO `plagscan_reviews` (`id`, `project_id`, `version`, `student_id`, `manuscript_file`, `plagscan_result_file`, `ai_result_file`, `percent_similarity`, `status`, `notes`, `date_submitted`, `date_reviewed`, `reviewed_by`, `created_at`, `updated_at`) VALUES
(2, 2, 1, '2511133218', '../assets/uploads/plagscan/1764519694_plagscan_Research_Project_Letter.pdf', '../assets/uploads/plagscan_results/1764520393_plagreport_Research_Project_Letter.pdf', '../assets/uploads/plagscan_results/1764520393_aires_Research_Project_Letter.pdf', 23.00, 'approved', 'qwe', '2025-11-30 16:21:34', '2025-11-30 16:33:13', 2, '2025-11-30 16:21:34', '2025-11-30 16:33:13'),
(3, 3, 1, '2512594336', '../assets/uploads/plagscan/1765267547_plagscan_reviewer.pdf', NULL, '../assets/uploads/plagscan_results/1765267571_aires_reviewer.pdf', 99.00, 'approved', 'asd', '2025-12-09 08:05:47', '2025-12-09 08:06:11', 2, '2025-12-09 08:05:47', '2025-12-09 08:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `project_approvals`
--

CREATE TABLE `project_approvals` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `faculty_approval` enum('approved','rejected','pending') DEFAULT 'pending',
  `faculty_comments` mediumtext DEFAULT NULL,
  `adviser_approval` enum('approved','rejected','pending') DEFAULT 'pending',
  `adviser_comments` mediumtext DEFAULT NULL,
  `dean_approval` enum('approved','rejected','pending') DEFAULT 'pending',
  `dean_comments` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_approvals`
--

INSERT INTO `project_approvals` (`id`, `project_id`, `faculty_approval`, `faculty_comments`, `adviser_approval`, `adviser_comments`, `dean_approval`, `dean_comments`) VALUES
(2, 2, 'approved', '', 'approved', '', 'approved', ''),
(3, 3, 'approved', 'test', 'approved', 'test', 'approved', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `project_working_titles`
--

CREATE TABLE `project_working_titles` (
  `id` int(11) NOT NULL,
  `proponent_1` varchar(255) DEFAULT NULL,
  `proponent_2` varchar(255) DEFAULT NULL,
  `proponent_3` varchar(255) DEFAULT NULL,
  `proponent_4` varchar(255) DEFAULT NULL,
  `project_title` varchar(255) DEFAULT NULL,
  `beneficiary` varchar(255) DEFAULT NULL,
  `focal_person` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `noted_by` varchar(255) DEFAULT NULL,
  `submitted_by` varchar(255) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `archived` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_working_titles`
--

INSERT INTO `project_working_titles` (`id`, `proponent_1`, `proponent_2`, `proponent_3`, `proponent_4`, `project_title`, `beneficiary`, `focal_person`, `gender`, `position`, `address`, `noted_by`, `submitted_by`, `version`, `archived`, `date_created`) VALUES
(2, 'DIZON, JOHN CARL D.', 'DIZON, JOHN EARL D.', 'DAVID, JAMVEE JOYCE D.', '', 'Sample', 'Santa Rita College', 'Sample Person', 'male', 'Sample', 'San Jose, Santa Rita, Pampanga', '2', 'jcdd@gmail.com', 1, 0, '2025-11-30 23:24:16'),
(3, 'test', '', '', '', 'test', 'test', 'test', 'male', 'test', 'test', '2', 'test@gmail.com', 1, 0, '2025-12-09 15:53:01');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `level` tinyint(4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `section_name`, `level`, `is_active`) VALUES
(3, 'Section A', 3, 1),
(4, 'Section A', 4, 1),
(5, 'Section B', 4, 1),
(6, 'Section C', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `semester`
--

CREATE TABLE `semester` (
  `semester_id` int(11) NOT NULL,
  `ay_id` int(11) NOT NULL,
  `semester_now` enum('1','2') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semester`
--

INSERT INTO `semester` (`semester_id`, `ay_id`, `semester_now`) VALUES
(1, 2526, '1'),
(2, 2526, '2');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(10) NOT NULL,
  `rfid_number` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT '',
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(50) DEFAULT '',
  `gender` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `rfid_number`, `profile_picture`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`) VALUES
(' 24-000342', ' 24-0003425', '', ' RUFFA', 'ROMERO', 'CALILUNG', '', 'Female'),
('19-0000124', '19-0000124', '', ' ALDRIN', 'PEREZ', 'FAVOR', '', 'Male'),
('20-0000651', '20-0000651', '', ' OLIVER', 'LANSANGAN', 'DELFIN', '', 'Male'),
('21-0000840', '21-0000840', '', ' DIETHER JOSHUA', 'SAGUN', 'CALAGUAS', '', 'Male'),
('21-0000897', '21-0000897', '', ' ERIS', 'ESPIRITU', 'PONIO', '', 'Male'),
('21-0000905', '21-0000905', '', ' CRISTOPHER JAMES', 'BARNES', 'ANGELES', '', 'Male'),
('21-0001062', '21-0001062', '', ' JOHN MICHAEL', 'FLORES', 'DIZON', '', 'Male'),
('21-0001280', '21-0001280', '', ' VINCE NICOLAS', 'ENRIQUEZ', 'SANGALANG', '', 'Male'),
('22-0001230', '22-0001230', '', ' PRINCE', 'JAN', 'VITUG', '', 'Male'),
('22-0001234', '22-0001234', '', ' ROSA', 'CAMMILE', 'MANGAYA', '', 'Female'),
('22-0001235', '22-0001235', '', ' JAYANNE', '', 'MONTEMAYOR', '', 'Female'),
('22-0001236', '22-0001236', '', ' DEANA', '', 'NULUD', '', 'Female'),
('22-0001237', '22-0001237', '', ' TENCHI', '', 'SENYO', '', 'Male'),
('22-0001238', '22-0001238', '', ' ALAN', '', 'TOLENTINO', '', 'Male'),
('22-0001239', '22-0001239', '', ' ANGELA', '', 'VALDEZ', '', 'Female'),
('22-0001456', '22-0001456', '', ' LORENZO EMMANUEL', 'MINGUINTO', 'URBANO', '', 'Male'),
('22-0001559', '22-0001559', '', ' NINO ANJELO', '', 'DIZON', '', 'Male'),
('22-0002120', '22-0002120', '', ' PATRICK JOHN', 'LAPIRA', 'ALIPIO', '', 'Male'),
('22-0002123', '22-0002123', '', ' JEROME ANGELO', 'LEJARDE', 'LANSANG', '', 'Male'),
('22-0002127', '22-0002127', '', ' NICOLE', '', 'ENRIQUEZ', '', 'Female'),
('22-0002128', '22-0002128', '', ' ANGELA', 'ENRIQUEZ', 'AVILA', '', 'Female'),
('22-0002129', '22-0002129', '', ' JOHN LESTER', 'GARCIA', 'BACANI', '', 'Male'),
('22-0002131', '22-0002131', '', ' JOHN CARL', 'DELA PENA', 'DIZON', '', 'Male'),
('22-0002141', '22-0002141', '', ' PRINCESS', 'OCAMPO', 'CALMA', '', 'Female'),
('22-0002142', '22-0002142', '', ' KYLE', 'MANALAC', 'FERNANDEZ', '', 'Female'),
('22-0002145', '22-0002145', '', ' AIRA', 'MANALAC', 'FERNANDEZ', '', 'Female'),
('22-0002146', '22-0002146', '', ' RIMARCH', 'ROQUE', 'DIZON', '', 'Male'),
('22-0002147', '22-0002147', '', ' LHOURD ANDREI', 'LEANO', 'GANZON', '', 'Male'),
('22-0002148', '22-0002148', '', ' MARK GLEN', 'PINEDA', 'GUEVARRA', '', 'Male'),
('22-0002149', '22-0002149', '', ' JEROME', 'PAMAGAN', 'GARCIA', '', 'Male'),
('22-0002152', '22-0002152', '', ' MICAELLA', 'PINEDA', 'MILLOS', '', 'Female'),
('22-0002153', '22-0002153', '', ' ELAINE', 'SALALILA', 'MONTEMAYOR', '', 'Female'),
('22-0002154', '22-0002154', '', ' CLARENCE', 'BUAN', 'DULA', '', 'Male'),
('22-0002155', '22-0002155', '', ' ROY', 'DELA CRUZ', 'JUNTILLA', '', 'Male'),
('22-0002156', '22-0002156', '', ' ASHLIE JOHN', 'VALENCIA', 'GATCHALIAN', '', 'Male'),
('22-0002157', '22-0002157', '', ' RAINIER', 'JOVELLAR', 'LAXAMANA', '', 'Male'),
('22-0002158', '22-0002158', '', ' ROMAN', 'SANTOS', 'MERCADO', '', 'Male'),
('22-0002167', '22-0002167', '', ' GENER JR.', 'VALENCIA', 'MANLAPAZ', '', 'Male'),
('22-0002170', '22-0002170', '', ' LAWRENCE ANDREI', '', 'GUIAO', '', 'Male'),
('22-0002171', '22-0002171', '', ' LLANYELL', 'REYES', 'MANALANG', '', 'Male'),
('22-0002191', '22-0002191', '', ' JOHN EMIL', 'MANALAC', 'TUPAS', '', 'Male'),
('22-0002199', '22-0002199', '', ' JANIRO', 'MENDOZA', 'SERRANO', '', 'Male'),
('22-0002200', '22-0002200', '', ' MARK ANTHONY', 'SISON', 'VILLAFUERTE', '', 'Male'),
('22-0002201', '22-0002201', '', ' FRENCER GIL', 'MANANSALA', 'ROMERO', '', 'Male'),
('22-0002202', '22-0002202', '', ' LIMUEL', 'VARQUEZ', 'MIRANDA', '', 'Male'),
('22-0002204', '22-0002204', '', ' JONNARIE', 'MERCADO', 'ROLL', '', 'Female'),
('22-0002209', '22-0002209', '', ' RONIEL MARCO', 'PUNZALAN', 'BAYAUA', '', 'Male'),
('22-0002224', '22-0002224', '', ' JANESSA', 'HICBAN', 'SANTOS', '', 'Female'),
('22-0002225', '22-0002225', '', ' MARK EDRIAN', 'DE DIOS', 'ROQUE', '', 'Male'),
('22-0002226', '22-0002226', '', ' RALPH', 'AGUILAR', 'SIMBUL', '', 'Male'),
('22-0002264', '22-0002264', '', ' MICHELLE', 'DAGOY', 'GUANLAO', '', 'Female'),
('22-0002294', '22-0002294', '', ' JEROME', 'DETERA', 'OCAMPO', '', 'Male'),
('22-0002365', '22-0002365', '', ' JOHN ARLEY', 'MANALANSAN', 'DABU', '', 'Male'),
('22-0002372', '22-0002372', '', ' TRICIA ANN', 'MANABAT', 'NEPOMUCENO', '', 'Female'),
('22-0002376', '22-0002376', '', ' CRISTINE', '', 'MAAMBONG', '', 'Male'),
('22-0002382', '22-0002382', '', ' VINCENT', '', 'TIATCO', '', 'Male'),
('22-0002387', '22-0002387', '', ' GUEN CARLO', '', 'GOMEZ', '', 'Male'),
('22-0002388', '22-0002388', '', ' JOSEPH LORENZ', 'DIMACALI', 'SISON', '', 'Male'),
('22-0002389', '22-0002389', '', ' JESSA', 'VERZOSA', 'GUANLAO', '', 'Female'),
('22-0002390', '22-0002390', '', ' NEIL TRISTAN', 'PAYUMO', 'MANGILIMAN', '', 'Male'),
('22-0002391', '22-0002391', '', ' KHIAN CARL', 'BORJA', 'HERODICO', '', 'Male'),
('22-0002393', '22-0002393', '', ' RAMLEY JON', 'RAMOS', 'MAGPAYO', '', 'Male'),
('22-0002394', '22-0002394', '', ' LEONEL', 'PACHICO', 'POPATCO', '', 'Male'),
('22-0002398', '22-0002398', '', ' KING WESHLEY', 'GALANG', 'MUTUC', '', 'Male'),
('22-0002400', '22-0002400', '', ' STEVEN', 'LOBERO', 'GONZALES', '', 'Male'),
('22-0002401', '22-0002401', '', ' RICHARD', 'BUNQUE', 'GUEVARRA', '', 'Male'),
('22-0002403', '22-0002403', '', ' CHRISTOPHER', 'MADEJA', 'PANOY', '', 'Male'),
('22-0002407', '22-0002407', '', ' JHAY-R', 'LLENAS', 'MERCADO', '', 'Male'),
('22-0002409', '22-0002409', '', ' VAL NERIE', 'ONG', 'ESPELETA', '', 'Male'),
('22-0002413', '22-0002413', '', ' JOHN LOUISE', 'CUNANAN', 'SEMSEM', '', 'Male'),
('22-0002414', '22-0002414', '', ' RAPH JUSTINE', 'BAUTISTA', 'BUTIAL', '', 'Male'),
('22-0002415', '22-0002415', '', ' ANGEL ROSE ANNE', 'FABROA', 'MALLARI', '', 'Female'),
('22-0002416', '22-0002416', '', ' KELSEY KEMP', 'SAZON', 'BONOAN', '', 'Male'),
('22-0002419', '22-0002419', '', ' PRINCESS SHAINE', 'BUCUD', 'SANTIAGO', '', 'Female'),
('22-0002420', '22-0002420', '', ' YVES ANDREI', 'MANALO', 'SANTOS', '', 'Male'),
('22-0002421', '22-0002421', '', ' CHRISTINE ANNE', 'MALLARI', 'FLORENDO', '', 'Female'),
('22-0002425', '22-0002425', '', ' RICHMOND', 'MARTIN', 'SAFICO', '', 'Male'),
('22-0002431', '22-0002431', '', ' JANRIX HARVEY', 'CRUZ', 'RIVERA', '', 'Male'),
('22-0002434', '22-0002434', '', ' AERIAL JERAMY', 'APARICI', 'LAYUG', '', 'Male'),
('22-0002436', '22-0002436', '', ' RUSSEL KENNETH', 'CASTLLO', 'LIM', '', 'Male'),
('22-0002438', '22-0002438', '', ' ANGELITO', '', 'CRUZ', '', 'Male'),
('22-0002439', '22-0002439', '', ' JOANNA', 'DUNGCA', 'JULIAN', '', 'Female'),
('22-0002442', '22-0002442', '', ' PRINCE ALVIER', 'GALANG', 'NUNEZ', '', 'Male'),
('22-0002453', '22-0002453', '', ' DEXTER', 'SALALILA', 'VILLEGAS', '', 'Male'),
('22-0002455', '22-0002455', '', ' JHAYZHELLE', 'DUNGCA', 'ALVARADO', '', 'Male'),
('22-0002458', '22-0002458', '', ' VERONICA', 'ALBISA', 'MERCADO', '', 'Female'),
('22-0002460', '22-0002460', '', ' JOHN MICHAEL', 'JIMENEZ', 'ELILIO', '', 'Male'),
('22-0002467', '22-0002467', '', ' ROSE ANN', 'DELA CRUZ', 'DELA ROSA', '', 'Female'),
('22-0002507', '22-0002507', '', ' ABRAHAM CHRISTIAN', 'SIMBAHAN', 'GAPPI', '', 'Male'),
('22-0002509', '22-0002509', '', ' JHON LOUIE', 'BOGNOT', 'DIZON', '', 'Male'),
('22-0002525', '22-0002525', '', ' JOHN REVELYN', 'DURAN', 'GONZALES', '', 'Male'),
('22-0002534', '22-0002534', '', ' RHAINE JUSTIN', '', 'MANALAC', '', 'Male'),
('22-0002686', '22-0002686', '', ' JOHN BENEDICT', 'DE GUZMAN', 'DEL ROSARIO', '', 'Male'),
('22-0002726', '22-0002726', '', ' QUEEN MEILANIE', 'BILLENA', 'BENRIL', '', 'Female'),
('22-0002822', '22-0002822', '', ' RHEALLE', 'DELA CRUZ', 'ALKUINO', '', 'Female'),
('22-0003082', '22-0003082', '', ' JAYSON', '', 'BACSAN', '', 'Male'),
('23-0002973', '23-0002973', '', ' JOHN MICHAEL', 'GALANG', 'DAVID', '', 'Male'),
('23-0003005', '23-0003005', '', ' MERWIN', 'PASCUAL', 'HIPOLITO', '', 'Male'),
('23-0003011', '23-0003011', '', ' IGIDIAN VINCE', 'GUINTU', 'CASTRO', '', 'Male'),
('23-0003012', '23-0003012', '', ' REYMART', 'LANSANG', 'PINEDA', '', 'Male'),
('23-0003021', '23-0003021', '', ' C-JAY', 'HICBAN', 'SANTOS', '', 'Male'),
('23-0003022', '23-0003022', '', ' RENZ YUAN', 'GUEVARRA', 'CAYANAN', '', 'Male'),
('23-0003023', '23-0003023', '', ' LEAN', 'CRUZ', 'LAXAMANA', '', 'Female'),
('23-0003026', '23-0003026', '', ' JULIUS CEDRICK', 'GUIAO', 'VIRAY', '', 'Male'),
('23-0003028', '23-0003028', '', ' MARK ATHAN', 'GUANZON', 'MANALANG', '', 'Male'),
('23-0003031', '23-0003031', '', ' JHON MICHAEL', 'OCAMPO', 'BATAC', '', 'Male'),
('23-0003034', '23-0003034', '', ' JOSEPH MIGUEL', '', 'URBANO', '', 'Male'),
('23-0003053', '23-0003053', '', ' ROY FRANCIS', 'SALALILA', 'ENRIQUEZ', '', 'Male'),
('23-0003054', '23-0003054', '', ' KATE LYN', 'PINEDA', 'BUAN', '', 'Female'),
('23-0003058', '23-0003058', '', ' KEN HARVEY', 'REQUIRON', 'SORIANO', '', 'Male'),
('23-0003060', '23-0003060', '', ' JOHN KEISLY', 'DY', 'BACANI', 'LabB-PC20', 'Male'),
('23-0003062', '23-0003062', '', ' JOHN CLARENCE', 'MUTUC', 'DAVID', '', 'Male'),
('23-0003063', '23-0003063', '', ' TIMOTHY EARL', 'CORONA', 'BUAN', '', 'Male'),
('23-0003082', '23-0003082', '', ' JAYSON', 'INDIONGCO', 'BACSAN', '', 'Male'),
('23-0003087', '23-0003087', '', ' MHARK CHEDRICK', '', 'FERNANDO', '', 'Male'),
('23-0003098', '23-0003098', '', ' NICK IVAN', 'BUAN', 'MARIANO', '', 'Male'),
('23-0003103', '23-0003103', '', ' JULIANA CLAIR', 'PINEDA', 'IGNACIO', '', 'Female'),
('23-0003108', '23-0003108', '', ' RENELLE ROBIE', 'DULCE', 'LOPEZ', '', 'Male'),
('23-0003167', '23-0003167', '', ' RYAN', 'MULI', 'GUINTO', '', 'Male'),
('24 -000326', '24 -0003269', '', ' GIRLLY', 'MANALAC', 'FERNANDEZ', '', 'Female'),
('24-0003044', '24-0003044', '', ' ELKAN', 'ALONZO', 'SARMIENTO', '', 'Male'),
('24-0003174', '24-0003174', '', ' REX', 'LAPURE', 'GATCHALIAN', '', 'Male'),
('24-0003256', '24-0003256', '', ' JUSTINE', 'LICAME', 'ANGELES', '', 'Male'),
('24-0003261', '24-0003261', '', ' JOHN PAUL', 'DUNGCA', 'ARCILLA', '', 'Male'),
('24-0003262', '24-0003262', '', ' KAREN', 'DAVID', 'MONTES', '', 'Female'),
('24-0003267', '24-0003267', '', ' KIM WESLEY', 'ANTONIO', 'PERALTA', '', 'Male'),
('24-0003280', '24-0003280', '', ' EDRON', 'BATAC', 'GARCIA', '', 'Male'),
('24-0003285', '24-0003285', '', ' JUSTINE', 'PITUC', 'SINGAN', '', 'Male'),
('24-0003290', '24-0003290', '', ' RONNIE JR.', 'BARBIN', 'HALOG', '', 'Male'),
('24-0003292', '24-0003292', '', ' SHIANN KELLY', 'GARCIA', 'PAYUMO', '', 'Male'),
('24-0003303', '24-0003303', '', ' ANTONETTE', 'DELFIN', 'BERNARDO', '', 'Female'),
('24-0003306', '24-0003306', '', ' JOHN BENEDICT', 'GOMEZ', 'PERRERAS', '', 'Male'),
('24-0003307', '24-0003307', '', ' JERALD', 'FORTIN', 'GALANG', '', 'Male'),
('24-0003308', '24-0003308', '', ' WARREN KING', 'DIMAANO', 'CANLAS', '', 'Male'),
('24-0003309', '24-0003309', '', ' IYA NEL', 'SERRANO', 'MANGARING', '', 'Female'),
('24-0003310', '24-0003310', '', ' SHIN', 'GARCIA', 'BARTOCILLO', '', 'Male'),
('24-0003314', '24-0003314', '', ' JHON FRANCIS', 'GUANZON', 'ALAVE', '', 'Male'),
('24-0003315', '24-0003315', '', ' ALEXANDER JEHRIEL', 'ARRIOLA', 'NULUD', '', 'Male'),
('24-0003318', '24-0003318', '', ' NICOLE', 'BUAN', 'SAMBILE', '', 'Female'),
('24-0003321', '24-0003321', '', ' MARVIN JOEY', 'OCAMPO', 'APAREJADO', '', 'Male'),
('24-0003325', '24-0003325', '', ' MARLYN', '', 'MERCADO', '', 'Female'),
('24-0003331', '24-0003331', '', ' SOPIA MAE', 'CARLOS', 'GUINTO', '', 'Female'),
('24-0003339', '24-0003339', '', ' KEVIN', 'MARIANO', 'CASTRO', '', 'Male'),
('24-0003343', '24-0003343', '', ' ARJAY', 'PERENIA', 'DEL CASTILLO', '', 'Male'),
('24-0003349', '24-0003349', '', ' JAZELLE ANNE', 'GARCES', 'BATAS', '', 'Female'),
('24-0003362', '24-0003362', '', ' ERIC', 'SUYOM', 'CADOCOY', '', 'Male'),
('24-0003375', '24-0003375', '', ' KATHEINE JOY', 'CORTEZ', 'FERNANDO', '', 'Female'),
('24-0003393', '24-0003393', '', ' ERICAH MAE', 'INFANTE', 'VALENCIA', '', 'Female'),
('24-0003410', '24-0003410', '', ' JESSICA', 'CABACUNGAN', 'SALALILA', '', 'Female'),
('24-0003414', '24-0003414', '', ' VHON LEAMBEER', 'DELOS REYES', 'GONZALES', '', 'Male'),
('24-0003425', '24-0003425', '', ' RUFFA', '', 'CALILUNG', '', 'Female'),
('24-0003426', '24-0003426', '', ' JOHN PAUL', 'MARMETO', 'SANTOS', '', 'Male'),
('24-0003433', '24-0003433', '', ' LYKA NICOLE', 'TORRES', 'LAYUG', '', 'Female'),
('24-0003434', '24-0003434', '', ' TRISTAN', 'LUSUNG', 'DUQUE', '', 'Male'),
('24-0003435', '24-0003435', '', ' ALEXA KEITH', 'CALAGOS', 'BOSTERO', '', 'Female'),
('25-0003688', '25-0003688', '', ' TRISHA', 'CABILES', 'BARRUGA', '', 'Female'),
('25-0003690', '25-0003690', '', ' KERWIN', 'PADILLA', 'BUAN', '', 'Male'),
('25-0003691', '25-0003691', '', ' JOSHUA', 'RAMIREZ', 'CAMITAN', '', 'Male'),
('25-0003692', '25-0003692', '', ' JOHN CHLOE', 'TUMINTIN', 'CASUPANAN', '', 'Male'),
('25-0003693', '25-0003693', '', ' DAVE GABRIEL', 'BALTAZAR', 'CRUZ', '', 'Male'),
('25-0003694', '25-0003694', '', ' KAYCEE LYN', 'NARVAREZ', 'DIMAL', '', 'Female'),
('25-0003695', '25-0003695', '', ' NORMAN', 'SAMPANG', 'FRESNOZA JR.', '', 'Male'),
('25-0003698', '25-0003698', '', ' MAUI', 'MALLARI', 'MARCELO', '', 'Female'),
('25-0003704', '25-0003704', '', ' ELLAIZA', 'BACANI', 'NEPOMUCENO', '', 'Female'),
('25-0003706', '25-0003706', '', ' JEROME', 'TORANO', 'PINEDA', '', 'Male'),
('25-0003707', '25-0003707', '', ' JOSHUA', 'LUCINO', 'PINEDA', '', 'Male'),
('25-0003708', '2874015315', '', ' JOLAINE', 'JIMENEZ', 'ANDAMON', '', 'Female'),
('25-0003709', '25-0003709', '', ' EMY JANE', 'LUBIANO', 'ROYO', '', 'Female'),
('25-0003711', '25-0003711', '', ' CID', 'MALIGLIG', 'SOTTO', '', 'Male'),
('25-0003736', '25-0003736', '', ' CINDY', 'ENRIQUEZ', 'ROQUE', '', 'Female'),
('25-0003751', '25-0003751', '', ' GERALD', 'DELA CRUZ', 'PANTIG', '', 'Male'),
('25-0003756', '25-0003756', '', ' TRISTAN', 'CENAL', 'BUAN', '', 'Male'),
('25-0003763', '25-0003763', '', ' JOHN RUSTI', 'BUTIAL', 'NIO', '', 'Male'),
('25-0003765', '25-0003765', '', ' IVAN', 'DELA CRUZ', 'MARIANO', '', 'Male'),
('25-0003768', '25-0003768', '', ' KIRK RINGO', 'BEJASA', 'SERIOS', '', 'Male'),
('25-0003771', '25-0003771', '', ' JAN MARK', 'PAMINTUAN', 'TUAZON', '', 'Male'),
('25-0003774', '25-0003774', '', ' KYLE ZEDDRICK', 'MACALINO', 'SUBOC', '', 'Male'),
('25-0003781', '25-0003781', '', ' SHANNEN', 'MONTEALTO', 'MONSALOD', '', 'Female'),
('25-0003782', '25-0003782', '', ' ANGEL', 'LOBERO', 'GONZALES', '', 'Female'),
('2511133218', '', NULL, 'John Carl', '', 'Dizon', '', 'Male'),
('2512594336', '2512594336', NULL, 'test', 'test', 'test', '', 'Male'),
('26-4378547', '26-43785476', '', ' JHOANA MARIE', 'MANLULU', 'SALVADOR', '', 'Female'),
('2643794436', '2643794436', '', ' Mark', 'Glen', 'Guevarra', '', 'Male');

-- --------------------------------------------------------

--
-- Table structure for table `student_details`
--

CREATE TABLE `student_details` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `group_code` varchar(255) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `is_graduated` tinyint(1) NOT NULL DEFAULT 0,
  `graduated_at` datetime DEFAULT NULL,
  `ready_to_graduate` tinyint(1) NOT NULL DEFAULT 0,
  `show_research_guide` tinyint(1) DEFAULT 1,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_details`
--

INSERT INTO `student_details` (`id`, `student_id`, `group_code`, `group_id`, `is_graduated`, `graduated_at`, `ready_to_graduate`, `show_research_guide`, `email`, `password`, `profile_pic`, `created_at`, `updated_at`) VALUES
(3, '2511133218', '4B-G1', 1, 0, NULL, 0, 0, 'jcdd@gmail.com', '$2y$10$VgVIf/dyUmkjoYciZAY43eFuzMQuz/t2zhtmVbmzfeE08yn7gGWWq', NULL, '2025-11-30 21:57:52', '2025-11-30 23:59:08'),
(4, '2643794436', '4B-G1', 1, 0, NULL, 0, 1, 'mark@gmail.com', '$2y$10$gT8iH3iDa6H1vPc8Rng/xeIcpMnL8VfUoVLRAjYx.dnpPuk08sig.', NULL, '2025-12-05 10:28:34', '2025-12-05 14:18:57'),
(5, '26-4378547', '4B-G1', 1, 0, NULL, 0, 1, 'jo@gmail.com', '$2y$10$AZPnwLnfjm5ke55WvCb8cur.wNmVW72fLsjMQHRMqaS.2SljMsYVa', NULL, '2025-12-05 14:19:25', '2025-12-05 14:21:27'),
(6, '25-0003782', '3A-G1', 4, 0, NULL, 0, 1, '2@g.c', '$2y$10$7Ny2c/GQ465UTvZFVu/b6OqA4WNg5taFb1b2ki6AT7M9KLm3EKv96', NULL, '2025-12-05 14:21:43', '2025-12-05 14:21:43'),
(7, '2512594336', '4B-G2', 5, 0, NULL, 0, 1, 'test@gmail.com', '$2y$10$2gFqdby3MPo1RQB0GJtjm.qD/1zx8GdlqqoGjWNkIPCQIakdNJk42', NULL, '2025-12-09 15:52:35', '2025-12-09 15:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `units` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `title_defense`
--

CREATE TABLE `title_defense` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `submitted_by` varchar(10) NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `status` enum('pending','rejected','approved') DEFAULT 'pending',
  `remarks` mediumtext DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `title_defense`
--

INSERT INTO `title_defense` (`id`, `project_id`, `submitted_by`, `pdf_file`, `status`, `remarks`, `scheduled_date`, `date_submitted`) VALUES
(1, 2, '3', '../assets/uploads/title_defense/1764517273_Research_Project_Letter.pdf', 'approved', '', '2025-11-30 23:52:00', '2025-11-30 15:41:13'),
(2, 3, '7', '../assets/uploads/title_defense/1765266856_reviewer.pdf', 'approved', '', '2025-12-09 16:06:00', '2025-12-09 07:54:16');

-- --------------------------------------------------------

--
-- Table structure for table `year_level`
--

CREATE TABLE `year_level` (
  `year_id` int(11) NOT NULL,
  `year_name` varchar(20) NOT NULL,
  `level` tinyint(4) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `year_level`
--

INSERT INTO `year_level` (`year_id`, `year_name`, `level`, `is_active`) VALUES
(3, '3rd Year', 3, 1),
(4, '4th Year', 4, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_year`
--
ALTER TABLE `academic_year`
  ADD PRIMARY KEY (`ay_id`);

--
-- Indexes for table `admission`
--
ALTER TABLE `admission`
  ADD PRIMARY KEY (`admission_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `year_level_id` (`year_level_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `admission_id` (`admission_id`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`user_id`,`research_id`),
  ADD KEY `research_id` (`research_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);
ALTER TABLE `books` ADD FULLTEXT KEY `title` (`title`,`authors`,`abstract`,`keywords`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `defense_revisions`
--
ALTER TABLE `defense_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_project_defense` (`project_id`,`defense_type`,`defense_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`lab_id`);

--
-- Indexes for table `final_defense`
--
ALTER TABLE `final_defense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `submitted_by` (`submitted_by`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_group_unique` (`group_name`,`cohort_year`,`year_section`);

--
-- Indexes for table `manuscript_reviews`
--
ALTER TABLE `manuscript_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `panelist_group_grades`
--
ALTER TABLE `panelist_group_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_panelist_group_defense` (`panelist_id`,`group_code`,`defense_type`,`defense_id`),
  ADD KEY `idx_panelist_group_grades_defense` (`defense_type`,`defense_id`),
  ADD KEY `idx_panelist_group_grades_project` (`project_id`),
  ADD KEY `idx_panelist_group_grades_group` (`group_code`);

--
-- Indexes for table `panel_assignments`
--
ALTER TABLE `panel_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_panelist_group` (`panelist_id`,`group_code`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_panel_assignments_group_id` (`group_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pending_users`
--
ALTER TABLE `pending_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `plagscan_reviews`
--
ALTER TABLE `plagscan_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plagscan_reviews_ibfk_1` (`project_id`),
  ADD KEY `plagscan_reviews_ibfk_2` (`student_id`),
  ADD KEY `plagscan_reviews_ibfk_3` (`reviewed_by`);

--
-- Indexes for table `project_approvals`
--
ALTER TABLE `project_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `project_working_titles`
--
ALTER TABLE `project_working_titles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submitted_by` (`submitted_by`(191));

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `lab_id` (`lab_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`section_id`);

--
-- Indexes for table `semester`
--
ALTER TABLE `semester`
  ADD PRIMARY KEY (`semester_id`),
  ADD KEY `ay_id` (`ay_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `rfid_number` (`rfid_number`);

--
-- Indexes for table `student_details`
--
ALTER TABLE `student_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_student_details_group_id` (`group_id`),
  ADD KEY `idx_student_details_is_graduated` (`is_graduated`),
  ADD KEY `idx_student_details_graduated_at` (`graduated_at`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `title_defense`
--
ALTER TABLE `title_defense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `submitted_by` (`submitted_by`);

--
-- Indexes for table `year_level`
--
ALTER TABLE `year_level`
  ADD PRIMARY KEY (`year_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_year`
--
ALTER TABLE `academic_year`
  MODIFY `ay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2528;

--
-- AUTO_INCREMENT for table `admission`
--
ALTER TABLE `admission`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `defense_revisions`
--
ALTER TABLE `defense_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `lab_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `final_defense`
--
ALTER TABLE `final_defense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `manuscript_reviews`
--
ALTER TABLE `manuscript_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `panelist_group_grades`
--
ALTER TABLE `panelist_group_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `panel_assignments`
--
ALTER TABLE `panel_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pending_users`
--
ALTER TABLE `pending_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plagscan_reviews`
--
ALTER TABLE `plagscan_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_approvals`
--
ALTER TABLE `project_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `project_working_titles`
--
ALTER TABLE `project_working_titles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `semester`
--
ALTER TABLE `semester`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_details`
--
ALTER TABLE `student_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `title_defense`
--
ALTER TABLE `title_defense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `year_level`
--
ALTER TABLE `year_level`
  MODIFY `year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission`
--
ALTER TABLE `admission`
  ADD CONSTRAINT `admission_academic_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_year` (`ay_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_course_fk` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_schedule_fk` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_section_fk` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_semester_fk` FOREIGN KEY (`semester_id`) REFERENCES `semester` (`semester_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_year_level_fk` FOREIGN KEY (`year_level_id`) REFERENCES `year_level` (`year_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_admission_fk` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_schedule_fk` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `defense_revisions`
--
ALTER TABLE `defense_revisions`
  ADD CONSTRAINT `defense_revisions_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project_working_titles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defense_revisions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defense_revisions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `panelist_group_grades`
--
ALTER TABLE `panelist_group_grades`
  ADD CONSTRAINT `panelist_group_grades_ibfk_1` FOREIGN KEY (`panelist_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panelist_group_grades_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project_working_titles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `panel_assignments`
--
ALTER TABLE `panel_assignments`
  ADD CONSTRAINT `fk_panel_assignments_group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `panel_assignments_ibfk_1` FOREIGN KEY (`panelist_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panel_assignments_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `plagscan_reviews`
--
ALTER TABLE `plagscan_reviews`
  ADD CONSTRAINT `plagscan_reviews_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project_working_titles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plagscan_reviews_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plagscan_reviews_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `project_approvals`
--
ALTER TABLE `project_approvals`
  ADD CONSTRAINT `project_approvals_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project_working_titles` (`id`);

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `facility` (`lab_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `semester`
--
ALTER TABLE `semester`
  ADD CONSTRAINT `semester_ibfk_1` FOREIGN KEY (`ay_id`) REFERENCES `academic_year` (`ay_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
