-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 18, 2025 at 07:31 AM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u107408893_firstHealth`
--

-- --------------------------------------------------------

--
-- Table structure for table `action_logs`
--

CREATE TABLE `action_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `reg_id` int(100) DEFAULT NULL,
  `trip_id` int(100) DEFAULT NULL,
  `is_scheduled` int(100) NOT NULL DEFAULT 0,
  `activity` varchar(255) DEFAULT NULL,
  `activity_date` varchar(255) DEFAULT NULL,
  `activity_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_masters`
--

CREATE TABLE `activity_masters` (
  `id` int(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_masters`
--

INSERT INTO `activity_masters` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Ambulance Requested', '2024-10-09 09:00:22', '2024-10-09 09:00:22'),
(2, 'Ambulance Scheduled', '2024-10-09 09:00:22', '2024-10-09 09:00:22'),
(3, 'Scheduled Ambulance Cancelled', '2024-10-09 09:01:25', '2024-10-09 09:01:25'),
(4, 'Ambulance Cancelled', '2024-10-09 09:01:25', '2024-10-09 09:01:25'),
(5, 'Ambulance Arrived', '2024-10-09 09:01:46', '2024-10-09 09:01:46');

-- --------------------------------------------------------

--
-- Table structure for table `ambulances`
--

CREATE TABLE `ambulances` (
  `id` int(250) NOT NULL,
  `user_id` int(250) NOT NULL,
  `patient_name` varchar(250) DEFAULT NULL,
  `age` int(200) DEFAULT NULL,
  `gender` varchar(100) DEFAULT NULL,
  `reg_id` int(250) DEFAULT NULL,
  `manual_username` varchar(250) DEFAULT NULL,
  `driver_id` varchar(250) DEFAULT NULL,
  `driver` varchar(250) DEFAULT NULL,
  `phone_number` varchar(250) DEFAULT NULL,
  `pickup_date` varchar(250) DEFAULT NULL,
  `hospital_id` varchar(250) DEFAULT NULL,
  `hospital` varchar(250) DEFAULT NULL,
  `diagnosis` longtext DEFAULT NULL,
  `careoff` varchar(250) DEFAULT NULL,
  `location` varchar(250) DEFAULT NULL,
  `location_name` varchar(250) DEFAULT NULL,
  `trip` varchar(250) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `clinical_info` longtext DEFAULT NULL,
  `registered_address` longtext DEFAULT NULL,
  `reg_lat` decimal(10,7) DEFAULT NULL,
  `reg_long` decimal(10,7) DEFAULT NULL,
  `status` varchar(250) DEFAULT NULL,
  `trip_status` varchar(250) DEFAULT NULL,
  `assigned_trip_status` varchar(100) DEFAULT NULL,
  `decline_count` int(100) NOT NULL DEFAULT 0,
  `pcr_file` varchar(250) DEFAULT NULL,
  `zoho_record_id` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `ambulances`
--
DELIMITER $$
CREATE TRIGGER `freeze_status_at_4` BEFORE UPDATE ON `ambulances` FOR EACH ROW BEGIN
    -- If the existing status is 4, keep it frozen and prevent any change
    IF OLD.status = 4 THEN
        SET NEW.status = OLD.status;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `prevent_update_when_trip_complete` BEFORE UPDATE ON `ambulances` FOR EACH ROW BEGIN
    IF OLD.`trip_status` = 'Complete' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot update record: trip already completed';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `benefit_masters`
--

CREATE TABLE `benefit_masters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `benefit_description` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `benefit_masters`
--

INSERT INTO `benefit_masters` (`id`, `benefit_description`, `created_at`, `updated_at`) VALUES
(1, '15-minute response time anywhere', '2024-08-13 12:15:53', '2024-12-23 08:29:18'),
(2, 'Coverage for 365 days', '2024-08-13 12:15:53', '2024-08-13 12:15:53'),
(3, 'Priority scheduling for Non-emergency medical transportation and follow-up medical coordination', '2024-08-13 12:15:53', '2024-08-13 12:15:53');

-- --------------------------------------------------------

--
-- Table structure for table `dependants`
--

CREATE TABLE `dependants` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `first_name` varchar(250) NOT NULL,
  `last_name` varchar(250) NOT NULL,
  `ic_number` int(100) NOT NULL,
  `phone_number` varchar(100) NOT NULL,
  `race` varchar(250) NOT NULL,
  `gender` tinyint(1) NOT NULL,
  `nationality` varchar(250) NOT NULL,
  `dob` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `heart_problems` tinyint(1) DEFAULT NULL,
  `diabetes` tinyint(1) DEFAULT NULL,
  `allergic` tinyint(1) DEFAULT NULL,
  `allergic_medication_list` varchar(200) DEFAULT NULL,
  `status` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dependants`
--

INSERT INTO `dependants` (`id`, `user_id`, `type`, `first_name`, `last_name`, `ic_number`, `phone_number`, `race`, `gender`, `nationality`, `dob`, `email`, `heart_problems`, `diabetes`, `allergic`, `allergic_medication_list`, `status`, `created_at`, `updated_at`) VALUES
(6, 1, 'Adult', 'Eucto', 'eswaran', 1234567890, '0123456789', 'test', 0, 'indian', '1994-03-18', 'test121.eswaran@eucto.com', 0, 1, 0, 'testing', 1, '2024-09-01 01:57:24', '2024-09-01 04:32:43'),
(7, 1, 'Child', 'Eucto', 'eswaran', 1234567890, '0123456789', 'test', 0, 'indian', '2014-03-18', 'test131.eswaran@eucto.com', 0, 1, 0, 'testing', 1, '2024-09-01 04:32:16', '2024-09-01 04:34:44'),
(8, 2, 'Adult', 'Eucto', 'eswaran', 1234567890, '0123456789', 'test', 0, 'indian', '1994-03-18', 'test1211.eswaran@eucto.com', 0, 1, 0, 'testing', 1, '2024-09-01 01:57:24', '2024-09-01 04:32:43'),
(9, 2, 'Child', 'Eucto', 'eswaran', 1234567890, '0123456789', 'test', 0, 'indian', '2014-03-18', 'test1314.eswaran@eucto.com', 0, 1, 0, 'testing', 1, '2024-09-01 04:32:16', '2024-09-01 04:34:44'),
(10, 2, 'Senior Citizen', 'Eucto', 'eswaran', 1234567890, '0123456789', 'test', 0, 'indian', '2014-03-18', 'test13144.eswaran@eucto.com', 0, 1, 0, 'testing', 1, '2024-09-01 04:32:16', '2024-09-01 04:34:44');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(250) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `passport_number` varchar(255) DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `current_lat` varchar(250) DEFAULT NULL,
  `current_long` varchar(250) DEFAULT NULL,
  `address` varchar(250) DEFAULT NULL,
  `hospital_name` varchar(250) DEFAULT NULL,
  `hospital_id` int(250) DEFAULT NULL,
  `guarantor_name` varchar(255) DEFAULT NULL,
  `guarantor_phone_number` varchar(255) DEFAULT NULL,
  `license_issue_date` date DEFAULT NULL,
  `license_valid_from` date DEFAULT NULL,
  `license_valid_upto` date DEFAULT NULL,
  `driver_country_code` varchar(255) DEFAULT NULL,
  `guarantor_country_code` varchar(255) DEFAULT NULL,
  `rfid_tracking_id` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `vehicle_number` varchar(255) DEFAULT NULL,
  `shift` varchar(255) DEFAULT NULL,
  `zoho_record_id` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver_declined_reason`
--

CREATE TABLE `driver_declined_reason` (
  `id` int(250) NOT NULL,
  `driver_id` varchar(250) NOT NULL,
  `driver_name` varchar(250) DEFAULT NULL,
  `phone_number` varchar(200) DEFAULT NULL,
  `declined_reason` longtext DEFAULT NULL,
  `trip_id` varchar(250) NOT NULL,
  `zoho_record_id` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(250) DEFAULT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `type`, `question`, `answer`, `created_at`, `updated_at`) VALUES
(1, 'User', 'What is First Health Assist?', 'First Health Assist is an affordable ambulance subscription service that provides reliable emergency and non-emergency medical transportation. With 24/7 coverage, members have access to prompt ambulance services at a fraction of the cost of traditional providers.', '2025-04-15 15:50:59', '2025-04-15 15:50:59'),
(2, 'User', 'How do I sign up for First Health Assist?', 'You can sign up directly on our website or via the First Health Assist app. Simply select your subscription plan, add any dependants if necessary, and complete the registration process.', '2025-04-15 15:52:12', '2025-04-15 15:52:12'),
(3, 'User', 'Who is eligible to subscribe to First Health Assist services?', 'Anyone aged 18 and above with a valid IC or passport can subscribe to First Health Assist. Children below 18 are also eligible but must be registered under a parent or legal guardian’s account. First Health Assist is designed for individuals and families seeking affordable and reliable access to medical transportation services.', '2025-04-15 15:52:28', '2025-04-15 15:52:28'),
(4, 'User', 'Can non-Malaysian residents subscribe to First Health Assist?', 'Yes, non-Malaysian residents can subscribe to First Health Assist. Foreigners can use their passport number to sign up for the service.', '2025-04-15 15:52:47', '2025-04-15 15:52:47'),
(5, 'User', 'Who is considered a Primary User?', 'A Primary User is the main account holder who initiates the First Health Assist subscription. This person is responsible for managing the membership, including adding or updating dependants, and renewing the plan.', '2025-04-15 15:53:06', '2025-04-15 15:53:06'),
(6, 'Driver', 'What is First Health Assist?', 'First Health Assist is an affordable ambulance subscription service that offers reliable\nemergency and non-emergency medical transportation.\n\nWith 24/7 coverage, members can access prompt ambulance services at a fraction of the cost of traditional providers.', '2025-05-26 07:04:56', '2025-06-12 13:52:57'),
(7, 'Driver', 'Who can use the First Health Assist Driver App?', 'Only verified drivers approved by First Health can use the app. Unverified individuals will not be able to log in or access any features.', '2025-06-12 13:48:16', '2025-06-12 13:48:16'),
(8, 'Driver', 'How do I log in?', 'Use the credentials provided by the admin team.\n\nIf you face login issues, contact your supervisor or the call centre for help.', '2025-06-12 13:54:39', '2025-06-12 14:50:34'),
(9, 'Driver', 'Can I use my driver login/email for the First Health Member app?', 'No. Your driver login and email cannot be used for the First Health Member app.\nIf you wish to subscribe as a member, you must use a different email address to register a separate account.', '2025-06-12 13:57:06', '2025-06-12 13:57:06'),
(10, 'Driver', 'What is the First Health Assist Driver App used for?', 'The app is used to:\n\n      1. Receive and manage job assignments\n\n     2. Navigate to pickup and drop-off points\n\n     3. Update status at every job milestone\n\n     4. Share live location and progress with the call centre\n\n        ⚠ Do not close or kill the app while online — it must remain active for real-time tracking and updates.', '2025-06-12 13:59:51', '2025-06-12 17:00:59'),
(11, 'Driver', 'How do I manage my availability?', 'Use the Availability toggle:\n\n      1. Switch to Online when ready to receive jobs\n\n     2. Switch to Offline when unavailable or on a break', '2025-06-12 14:01:01', '2025-06-12 16:20:56'),
(12, 'Driver', 'Do I need the Internet to use the app?', 'Yes. A stable internet connection is required to:\n\n      1. Receive jobs\n\n     2. Update status\n\n     3. Share location\n\n     4. Communicate with the call centre', '2025-06-12 14:02:27', '2025-06-12 16:20:04'),
(13, 'Driver', 'What happens when I receive a new job?', 'You have 20 seconds to accept or decline the job.\n\n      1. Accept: You’ll proceed to the pickup point.\n\n     2. Decline: You must provide a valid reason (e.g. vehicle issue).\n\n     3. No response: The job is auto-declined.\n\n         After declining or not responding, you will not receive new jobs until the call centre reassigns to you from backend.\n\nIf you are facing an emergency (e.g. vehicle breakdown), go to My Profile > Help & Support to contact the call centre directly.', '2025-06-12 14:04:53', '2025-06-12 16:12:36'),
(14, 'Driver', 'Can I cancel a job after accepting it?', 'No. Once accepted, only the call centre can cancel or reassign the task. Use Help & Support to notify them if needed.', '2025-06-12 14:05:18', '2025-06-12 14:05:18'),
(15, 'Driver', 'What happens if I get reassigned to a new case?', 'If you’re reassigned to a new urgent case, your current task will be marked as void, and another driver will take over the previous case.\n\nYou should proceed with the new task immediately as guided by the call centre.', '2025-06-12 14:06:12', '2025-06-12 14:06:12'),
(16, 'Driver', 'How do I update the status of a job?', 'Tap the corresponding status button at each job milestone. Status updates are mandatory to help the call centre track job progress and support you in real time.', '2025-06-12 14:06:29', '2025-06-12 14:06:29'),
(17, 'Driver', 'Who do I contact for help?', 'Go to My Profile > Help & Support in the app.\n\nYou’ll be connected directly to the call centre, who will assist with technical issues, job concerns, or emergencies.', '2025-06-12 14:07:36', '2025-06-12 14:07:36');

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `latitude` decimal(11,8) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `zoho_record_id` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hospitals`
--

INSERT INTO `hospitals` (`id`, `name`, `address`, `phone_number`, `latitude`, `longitude`, `zoho_record_id`, `created_at`, `updated_at`) VALUES
(1, 'Alty Orthopaedic Centre', '', NULL, 3.16095824, 101.725136, NULL, '2025-04-10 12:16:35', '2025-04-10 12:16:35'),
(2, 'An-Nur Specialist Hospital', '', NULL, 2.93307744, 101.764570, NULL, '2025-04-10 12:25:57', '2025-04-10 12:25:57'),
(3, 'Andorra Women & Children Hospital', '', NULL, 2.97468461, 101.678120, NULL, '2025-04-10 12:26:37', '2025-04-10 12:26:37'),
(4, 'Anson Bay Medical Centre', '', NULL, 3.99370285, 100.996170, NULL, '2025-04-10 12:27:14', '2025-04-10 12:27:14'),
(5, 'Apollo Medical Centre', '', NULL, 4.84964577, 100.736257, NULL, '2025-04-10 12:27:48', '2025-04-10 12:27:48'),
(6, 'Ara Damansara Medical Centre', '', NULL, 3.11490661, 101.565107, NULL, '2025-04-10 12:28:35', '2025-04-10 12:28:35'),
(7, 'Assunta Hospital', '', NULL, 3.09361169, 101.645915, NULL, '2025-04-10 12:29:57', '2025-04-10 12:29:57'),
(8, 'Aurelius Hospital Alor Setar', '', NULL, 6.12606055, 100.332579, NULL, '2025-04-10 12:30:23', '2025-04-10 12:30:23'),
(9, 'Aurelius Hospital Nilai', '', NULL, 2.81205165, 101.773142, NULL, '2025-04-10 12:30:49', '2025-04-10 12:30:49'),
(10, 'Aurelius Hospital Pahang', '', NULL, 3.80836182, 103.324456, NULL, '2025-04-10 12:31:16', '2025-04-10 12:31:16'),
(11, 'Avisena Specialist Hospital', '', NULL, 3.07202932, 101.524099, NULL, '2025-04-10 12:32:02', '2025-04-10 12:32:02'),
(12, 'Avisena Women’s & Children’s Specialist Hospital', '', NULL, 3.07191731, 101.521248, NULL, '2025-04-10 12:32:38', '2025-04-10 12:32:38'),
(13, 'Bagan Specialist Centre', '', NULL, 5.41060892, 100.385853, NULL, '2025-04-10 12:33:11', '2025-04-10 12:33:11'),
(14, 'Beacon Hospital', '', NULL, 3.09182640, 101.637982, NULL, '2025-04-10 12:33:41', '2025-04-10 12:33:41'),
(15, 'Beverly Wilshire Medical Centre', '', NULL, 3.14614036, 101.721759, NULL, '2025-04-10 12:34:09', '2025-04-10 12:34:09'),
(16, 'Borneo Medical Centre', '', NULL, 1.52942525, 110.357645, NULL, '2025-04-10 12:34:30', '2025-04-10 12:34:30'),
(17, 'Borneo Medical Centre (Miri)', '', NULL, 4.38082985, 113.984856, NULL, '2025-04-10 12:35:00', '2025-04-10 12:35:00'),
(18, 'Bukit Tinggi Medical Centre', '', NULL, 3.01028692, 101.431908, NULL, '2025-04-10 12:35:16', '2025-04-10 12:35:16'),
(19, 'CMH Specialist Hospital', '', NULL, 2.73795950, 101.931404, NULL, '2025-04-10 12:36:08', '2025-04-10 12:36:08'),
(20, 'Cardiac Vascular Sentral Kuala Lumpur', '', NULL, 3.13376930, 101.684815, NULL, '2025-04-10 12:36:27', '2025-04-10 12:36:27'),
(21, 'Cengild G.I. Medical Centre', '', NULL, 3.10991568, 101.665381, NULL, '2025-04-10 12:36:56', '2025-04-10 12:36:56'),
(22, 'Columbia Asia Extended Care Hospital – Shah Alam', '', NULL, 3.04755940, 101.505104, NULL, '2025-04-10 12:37:18', '2025-04-10 12:37:18'),
(23, 'Columbia Asia Hospital – Bukit Rimau', '', NULL, 2.99734611, 101.528788, NULL, '2025-04-10 12:38:07', '2025-04-10 12:38:07'),
(24, 'Columbia Asia Hospital – Miri', '', NULL, 4.41165202, 114.001899, NULL, '2025-04-10 12:38:49', '2025-04-10 12:38:49'),
(25, 'Columbia Asia Hospital – Puchong', '', NULL, 3.02416700, 101.622191, NULL, '2025-04-10 12:39:10', '2025-04-10 12:39:10'),
(26, 'Columbia Asia Hospital – Seremban', '', NULL, 2.71066995, 101.917484, NULL, '2025-04-10 12:39:32', '2025-04-10 12:39:32'),
(27, 'Columbia Asia Hospital – Taiping', '', NULL, 4.86587396, 100.734330, NULL, '2025-04-10 12:39:55', '2025-04-10 12:39:55'),
(28, 'Columbia Asia Hospital Bukit Jalil', '', NULL, 3.05853361, 101.687296, NULL, '2025-04-10 12:40:14', '2025-04-10 12:40:14'),
(29, 'Columbia Asia Hospital Tebrau', '', NULL, 1.50024250, 103.765767, NULL, '2025-04-10 12:40:32', '2025-04-10 12:40:32'),
(30, 'Columbia Asia Hospital – Bintulu', '', NULL, 3.23332394, 113.086074, NULL, '2025-04-10 12:40:56', '2025-04-10 12:40:56'),
(31, 'Columbia Asia Hospital – Cheras', '', NULL, 3.03173927, 101.762934, NULL, '2025-04-10 12:41:17', '2025-04-10 12:41:17'),
(32, 'Columbia Asia Hospital – Iskandar Puteri', '', NULL, 1.47883418, 103.637265, NULL, '2025-04-10 12:41:36', '2025-04-10 12:41:36'),
(33, 'Columbia Asia Hospital – Klang', '', NULL, 3.08615909, 101.446221, NULL, '2025-04-10 12:41:52', '2025-04-10 12:41:52'),
(34, 'Columbia Asia Hospital – Petaling Jaya', '', NULL, 3.11861361, 101.637629, NULL, '2025-04-10 12:42:12', '2025-04-10 12:42:12'),
(35, 'Columbia Asia Hospital – Setapak', '', NULL, 3.20154869, 101.718184, NULL, '2025-04-10 12:42:34', '2025-04-10 12:42:34'),
(36, 'Daehan Rehabilitation Hospital Putrajaya', '', NULL, 2.97077554, 101.705437, NULL, '2025-04-10 12:42:53', '2025-04-10 12:42:53'),
(37, 'Damai Service Hospital (HQ)', '', NULL, 3.17035738, 101.695157, NULL, '2025-04-10 12:43:09', '2025-04-10 12:43:09'),
(38, 'Darul Makmur Medical Centre', '', NULL, 3.74791753, 103.311631, NULL, '2025-04-10 12:43:29', '2025-04-10 12:43:29'),
(39, 'Georgetown Specialist Hospital', '', NULL, 5.40275064, 100.303584, NULL, '2025-04-10 12:43:51', '2025-04-10 12:43:51'),
(40, 'Gleneagles Hospital Kota Kinabalu', '', NULL, 5.96826489, 116.064447, NULL, '2025-04-10 12:44:40', '2025-04-10 12:44:40'),
(41, 'Gleneagles Hospital Kuala Lumpur', '', NULL, 3.16141335, 101.739162, NULL, '2025-04-10 12:45:09', '2025-04-10 12:45:09'),
(42, 'Gleneagles Hospital Medini Johor', '', NULL, 1.42683266, 103.635591, NULL, '2025-04-10 12:45:26', '2025-04-10 12:45:26'),
(43, 'Gleneagles Hospital Penang', '', NULL, 5.42704423, 100.319981, NULL, '2025-04-10 12:45:42', '2025-04-10 12:45:42'),
(44, 'Hope Children Hospital', '', NULL, 5.43174630, 100.300200, NULL, '2025-04-10 12:45:59', '2025-04-10 12:45:59'),
(45, 'Hospital Ar-Ridzuan', '', NULL, 4.60132906, 101.095741, NULL, '2025-04-10 12:46:21', '2025-04-10 12:46:21'),
(46, 'Hospital Fatimah', '', NULL, 4.61336937, 101.112593, NULL, '2025-04-10 12:46:39', '2025-04-10 12:46:39'),
(47, 'Hospital Islam Az-Zahrah', '', NULL, 2.96009043, 101.754200, NULL, '2025-04-10 12:46:57', '2025-04-10 12:46:57'),
(48, 'Hospital Lam Wah Ee', '', NULL, 5.39185028, 100.304359, NULL, '2025-04-10 12:47:17', '2025-04-10 12:47:17'),
(49, 'Hospital PICASO', '', NULL, 3.11331488, 101.633547, NULL, '2025-04-10 12:47:34', '2025-04-10 12:47:34'),
(50, 'Hospital Pusrawi', '', NULL, 3.17127447, 101.710072, NULL, '2025-04-10 12:47:53', '2025-04-10 12:47:53'),
(51, 'Hospital Seri Botani', '', NULL, 4.52476919, 101.105313, NULL, '2025-04-10 12:48:11', '2025-04-10 12:48:11'),
(52, 'Hospital UMRA', '', NULL, 3.08313520, 101.539982, NULL, '2025-04-10 12:48:35', '2025-04-10 12:48:35'),
(53, 'IIUM Medical Specialist Centre', '', NULL, 3.84292639, 103.302469, NULL, '2025-04-10 12:48:56', '2025-04-10 12:48:56'),
(54, 'Institut Jantung Negara (IJN)', '', NULL, 3.17028817, 101.708943, NULL, '2025-04-10 12:49:18', '2025-04-10 12:49:18'),
(55, 'Island Hospital', '', NULL, 5.42231263, 100.313777, NULL, '2025-04-10 12:49:38', '2025-04-10 12:49:38'),
(56, 'JMC Specialist Medical Centre', '', NULL, 3.03456191, 101.438581, NULL, '2025-04-10 12:49:58', '2025-04-10 12:49:58'),
(57, 'Jesselton Medical Centre', '', NULL, 5.97584945, 116.112278, NULL, '2025-04-10 12:50:17', '2025-04-10 12:50:17'),
(58, 'KMC Medical Centre', '', NULL, 4.58826377, 101.088342, NULL, '2025-04-10 12:50:31', '2025-04-10 12:50:31'),
(59, 'KPJ Ampang Puteri Specialist Hospital', '', NULL, 3.16037630, 101.751663, NULL, '2025-04-10 12:50:52', '2025-04-10 12:50:52'),
(60, 'KPJ Bandar Dato’ Onn Specialist Hospital', '', NULL, 1.54426577, 103.740168, NULL, '2025-04-10 12:51:13', '2025-04-10 12:51:13'),
(61, 'KPJ Bandar Maharani Specialist Hospital', '', NULL, 2.03615933, 102.567260, NULL, '2025-04-10 12:51:29', '2025-04-10 12:51:29'),
(62, 'KPJ Batu Pahat Specialist Hospital', '', NULL, 1.86855423, 102.991356, NULL, '2025-04-10 12:51:44', '2025-04-10 12:51:44'),
(63, 'KPJ Damansara Specialist Hospital', '', NULL, 3.13781310, 101.627535, NULL, '2025-04-10 12:52:24', '2025-04-10 12:52:24'),
(64, 'KPJ Damansara Specialist Hospital 2', '', NULL, 3.17134349, 101.618447, NULL, '2025-04-10 12:52:39', '2025-04-10 12:52:39'),
(65, 'KPJ Ipoh Specialist Hospital', '', NULL, 4.59447137, 101.095880, NULL, '2025-04-10 12:52:56', '2025-04-10 12:52:56'),
(66, 'KPJ Johor Specialist Hospital', '', NULL, 1.47608418, 103.740252, NULL, '2025-04-10 12:53:19', '2025-04-10 12:53:19'),
(67, 'KPJ Kajang Specialist Hospital', '', NULL, 3.00014501, 101.785718, NULL, '2025-04-10 12:53:35', '2025-04-10 12:53:35'),
(68, 'KPJ Klang Specialist Hospital', '', NULL, 3.06248579, 101.463230, NULL, '2025-04-10 12:53:51', '2025-04-10 12:53:51'),
(69, 'KPJ Kluang Utama Specialist Hospital', '', NULL, 2.01334433, 103.274831, NULL, '2025-04-10 12:54:07', '2025-04-10 12:54:07'),
(70, 'KPJ Kuching Specialist Hospital', '', NULL, 1.50601349, 110.365923, NULL, '2025-04-10 12:54:26', '2025-04-10 12:54:26'),
(71, 'KPJ Miri Specialist Hospital', '', NULL, 4.44823312, 114.033949, NULL, '2025-04-10 12:54:58', '2025-04-10 12:54:58'),
(72, 'KPJ Pahang Specialist Hospital', '', NULL, 3.80004972, 103.336884, NULL, '2025-04-10 12:56:22', '2025-04-10 12:56:22'),
(73, 'KPJ Pasir Gudang Specialist Hospital', '', NULL, 1.47726265, 103.895795, NULL, '2025-04-10 12:56:39', '2025-04-10 12:56:39'),
(74, 'KPJ Penang Specialist Hospital', '', NULL, 5.36938636, 100.434293, NULL, '2025-04-10 12:56:58', '2025-04-10 12:56:58'),
(75, 'KPJ Perdana Specialist Hospital', '', NULL, 6.12193468, 102.243609, NULL, '2025-04-10 12:57:14', '2025-04-10 12:57:14'),
(76, 'KPJ Perlis Specialist Hospital', '', NULL, 6.43344810, 100.186455, NULL, '2025-04-10 12:57:30', '2025-04-10 12:57:30'),
(77, 'KPJ Puteri Specialist Hospital', '', NULL, 1.49088470, 103.742082, NULL, '2025-04-10 12:57:45', '2025-04-10 12:57:45'),
(78, 'KPJ Rawang Specialist Hospital', '', NULL, 3.32106243, 101.579251, NULL, '2025-04-10 12:58:00', '2025-04-10 12:58:00'),
(79, 'KPJ Sabah Specialist Hospital', '', NULL, 5.96757142, 116.093212, NULL, '2025-04-10 12:58:24', '2025-04-10 12:58:24'),
(80, 'KPJ Selangor Specialist Hospital', '', NULL, 3.05743060, 101.541591, NULL, '2025-04-10 12:58:56', '2025-04-10 12:58:56'),
(81, 'KPJ Sentosa KL Specialist Hospital', '', NULL, 3.17111489, 101.697203, NULL, '2025-04-10 12:59:13', '2025-04-10 12:59:13'),
(82, 'KPJ Seremban Specialist Hospital', '', NULL, 2.71878220, 101.921826, NULL, '2025-04-10 12:59:41', '2025-04-10 12:59:41'),
(83, 'KPJ Sibu Specialist Medical Centre', '', NULL, 2.30102758, 111.832055, NULL, '2025-04-10 12:59:57', '2025-04-10 12:59:57'),
(84, 'KPJ Sri Manjung Specialist Centre', '', NULL, 4.20801089, 100.675137, NULL, '2025-04-10 13:00:19', '2025-04-10 13:00:19'),
(85, 'KPJ Tawakkal KL Specialist Hospital', '', NULL, 3.17718707, 101.698610, NULL, '2025-04-10 13:00:35', '2025-04-10 13:00:35'),
(86, 'KPMC Puchong Specialist Centre', '', NULL, 3.02491213, 101.615756, NULL, '2025-04-10 13:00:50', '2025-04-10 13:00:50'),
(87, 'Kajang Plaza Medical Centre', '', NULL, 2.99789711, 101.788248, NULL, '2025-04-10 13:01:18', '2025-04-10 13:01:18'),
(88, 'Kedah Medical Centre', '', NULL, 6.14928980, 100.369585, NULL, '2025-04-10 13:01:38', '2025-04-10 13:01:38'),
(89, 'Kek Lok Si Charitable Hospital', '', NULL, 5.40195268, 100.275634, NULL, '2025-04-10 13:01:56', '2025-04-10 13:01:56'),
(90, 'Kelana Jaya Medical Centre', '', NULL, 3.10868588, 101.595380, NULL, '2025-04-10 13:02:16', '2025-04-10 13:02:16'),
(91, 'Kempas Medical Centre', '', NULL, 1.52205372, 103.724568, NULL, '2025-04-10 13:02:34', '2025-04-10 13:02:34'),
(92, 'Kensington Green Specialist Centre', '', NULL, 1.47833388, 103.631262, NULL, '2025-04-10 13:02:53', '2025-04-10 13:02:53'),
(93, 'Kota Bharu Medical Centre', '', NULL, 6.10534146, 102.259692, NULL, '2025-04-10 13:03:09', '2025-04-10 13:03:09'),
(94, 'Kuala Terengganu Specialist Hospital', '', NULL, 5.31397561, 103.155969, NULL, '2025-04-10 13:03:31', '2025-04-10 13:03:31'),
(95, 'Kuantan Medical Centre', '', NULL, 3.82957280, 103.299157, NULL, '2025-04-10 13:03:50', '2025-04-10 13:03:50'),
(96, 'Loh Guan Lye Specialists Centre', '', NULL, 5.42003587, 100.318049, NULL, '2025-04-10 13:04:16', '2025-04-10 13:04:16'),
(97, 'Lourdes Medical Centre', '', NULL, 3.17320558, 101.691164, NULL, '2025-04-10 13:04:59', '2025-04-10 13:04:59'),
(98, 'MAHSA Specialist Hospital', '', NULL, 2.95334099, 101.576059, NULL, '2025-04-10 13:05:34', '2025-04-10 13:05:34'),
(99, 'MSU Medical Centre', '', NULL, 3.07698549, 101.553067, NULL, '2025-04-10 13:05:59', '2025-04-10 13:05:59'),
(100, 'Mahkota Medical Centre', '', NULL, 2.18758618, 102.251572, NULL, '2025-04-10 13:06:19', '2025-04-10 13:06:19'),
(101, 'Mawar Medical Centre', '', NULL, 2.70642490, 101.941355, NULL, '2025-04-10 13:06:36', '2025-04-10 13:06:36'),
(102, 'Medical Specialist Centre[Maria Medical Centre]', '', NULL, 1.46621671, 103.759556, NULL, '2025-04-10 13:06:51', '2025-04-10 13:06:51'),
(103, 'Metro Specialist Hospital', '', NULL, 5.62930894, 100.509986, NULL, '2025-04-10 13:07:14', '2025-04-10 13:07:14'),
(104, 'Miri City Medical Centre', '', NULL, 4.39467614, 113.990372, NULL, '2025-04-10 13:07:33', '2025-04-10 13:07:33'),
(105, 'Mount Miriam Cancer Hospital', '', NULL, 5.45881170, 100.301306, NULL, '2025-04-10 13:07:51', '2025-04-10 13:07:51'),
(106, 'Normah Medical Specialist Centre', '', NULL, 1.57811714, 110.329273, NULL, '2025-04-10 13:08:10', '2025-04-10 13:08:10'),
(107, 'Northern Heart Hospital Penang', '', NULL, 5.42460763, 100.316369, NULL, '2025-04-10 13:08:25', '2025-04-10 13:08:25'),
(108, 'OSC Orthopaedic Specialist Centre', '', NULL, 3.05901781, 101.592455, NULL, '2025-04-10 13:08:48', '2025-04-10 13:08:48'),
(109, 'Optimax Eye Specialist Hospital (Penang)', '', NULL, 5.39930310, 100.304230, NULL, '2025-04-10 13:09:16', '2025-04-10 13:09:16'),
(110, 'Oriental Melaka Straits Medical Centre', '', NULL, 2.20819858, 102.213932, NULL, '2025-04-10 13:09:37', '2025-04-10 13:09:37'),
(111, 'Pahang Medical Centre[Aurelius Hospital Pahang]', '', NULL, 3.80835647, 103.324434, NULL, '2025-04-10 13:09:54', '2025-04-10 13:09:54'),
(112, 'Pantai Hospital Ampang', '', NULL, 3.12797808, 101.752076, NULL, '2025-04-10 13:10:11', '2025-04-10 13:10:11'),
(113, 'Pantai Hospital Ayer Keroh', '', NULL, 2.23761379, 102.287298, NULL, '2025-04-10 13:10:59', '2025-04-10 13:10:59'),
(114, 'Pantai Hospital Batu Pahat', '', NULL, 1.86149591, 102.950979, NULL, '2025-04-10 13:11:23', '2025-04-10 13:11:23'),
(115, 'Pantai Hospital Cheras', '', NULL, 3.10284681, 101.740699, NULL, '2025-04-10 13:11:41', '2025-04-10 13:11:41'),
(116, 'Pantai Hospital Ipoh', '', NULL, 4.60367187, 101.119641, NULL, '2025-04-10 13:11:58', '2025-04-10 13:11:58'),
(117, 'Pantai Hospital Klang', '', NULL, 3.02665630, 101.426265, NULL, '2025-04-10 13:12:19', '2025-04-10 13:12:19'),
(118, 'Pantai Hospital Kuala Lumpur', '', NULL, 3.11992946, 101.666930, NULL, '2025-04-10 13:12:34', '2025-04-10 13:12:34'),
(119, 'Pantai Hospital Laguna Merbok', '', NULL, 5.68326177, 100.494589, NULL, '2025-04-10 13:13:15', '2025-04-10 13:13:15'),
(120, 'Pantai Hospital Manjung', '', NULL, 4.21610779, 100.670395, NULL, '2025-04-10 13:13:33', '2025-04-10 13:13:33'),
(121, 'Pantai Hospital Penang', '', NULL, 5.32148871, 100.282212, NULL, '2025-04-10 13:13:54', '2025-04-10 13:13:54'),
(122, 'Pantai Hospital Sungai Petani', '', NULL, 5.67306368, 100.513347, NULL, '2025-04-10 13:14:10', '2025-04-10 13:14:10'),
(123, 'ParkCity Medical Centre', '', NULL, 3.18956428, 101.638507, NULL, '2025-04-10 13:14:30', '2025-04-10 13:14:30'),
(124, 'Peace Medical Centre', '', NULL, 5.41753916, 100.325311, NULL, '2025-04-10 13:14:50', '2025-04-10 13:14:50'),
(125, 'Pelangi Medical Centre', '', NULL, 1.48421075, 103.775037, NULL, '2025-04-10 13:15:12', '2025-04-10 13:15:12'),
(126, 'Penang Adventist Hospital', '', NULL, 5.43243867, 100.305230, NULL, '2025-04-10 13:15:29', '2025-04-10 13:15:29'),
(127, 'Perak Community Specialist Hospital', '', NULL, 4.58182567, 101.100325, NULL, '2025-04-10 13:15:46', '2025-04-10 13:15:46'),
(128, 'Prince Court Medical Centre', '', NULL, 3.14920607, 101.721733, NULL, '2025-04-10 13:16:01', '2025-04-10 13:16:01'),
(129, 'Pusat Perubatan An-Nisa', '', NULL, 6.12128060, 102.240281, NULL, '2025-04-10 13:16:20', '2025-04-10 13:16:20'),
(130, 'Putra Medical Centre Alor Setar', '', NULL, 6.12394958, 100.365540, NULL, '2025-04-10 13:16:36', '2025-04-10 13:16:36'),
(131, 'Putra Medical Centre Bukit Rahman Putra', '', NULL, 3.20970490, 101.562134, NULL, '2025-04-10 13:16:54', '2025-04-10 13:16:54'),
(132, 'Putra Specialist Hospital (Melaka)', '', NULL, 2.20234740, 102.252457, NULL, '2025-04-10 13:17:16', '2025-04-10 13:17:16'),
(133, 'Putra Specialist Hospital Batu Pahat', '', NULL, 1.85818791, 102.922379, NULL, '2025-04-10 13:17:31', '2025-04-10 13:17:31'),
(134, 'Putra Specialist Hospital Kajang[Sungai Long Specialist Hospital]', '', NULL, 3.03965073, 101.795079, NULL, '2025-04-10 13:17:47', '2025-04-10 13:17:47'),
(135, 'QHC Medical Centre', '', NULL, 3.04779150, 101.587448, NULL, '2025-04-10 13:18:04', '2025-04-10 13:18:04'),
(136, 'Quill Orthopaedic Specialist Centre', '', NULL, 3.15279979, 101.620310, NULL, '2025-04-10 13:18:22', '2025-04-10 13:18:22'),
(137, 'Rafflesia Medical Centre', '', NULL, 5.91939227, 116.057707, NULL, '2025-04-10 13:19:33', '2025-04-10 13:19:33'),
(138, 'ReGen Rehab Hospital', '', NULL, 3.11565710, 101.640729, NULL, '2025-04-10 13:19:48', '2025-04-10 13:19:48'),
(139, 'Regency Specialist Hospital', '', NULL, 1.49827970, 103.872609, NULL, '2025-04-10 13:20:05', '2025-04-10 13:20:05'),
(140, 'Rejang Medical Centre', '', NULL, 2.29281935, 111.837002, NULL, '2025-04-10 13:20:21', '2025-04-10 13:20:21'),
(141, 'Roopi Medical Centre', '', NULL, 3.17042477, 101.695721, NULL, '2025-04-10 13:20:39', '2025-04-10 13:20:39'),
(142, 'SALAM Senawang Specialist Hospital', '', NULL, 2.71270558, 102.000740, NULL, '2025-04-10 13:20:56', '2025-04-10 13:20:56'),
(143, 'SALAM Shah Alam Specialist Hospital', '', NULL, 3.04939971, 101.535204, NULL, '2025-04-10 13:21:12', '2025-04-10 13:21:12'),
(144, 'SALAM Specialist Hospital Kuala Terengganu', '', NULL, 5.32624148, 103.139308, NULL, '2025-04-10 13:21:28', '2025-04-10 13:21:28'),
(145, 'Sentosa Specialist Hospital', '', NULL, 3.00590871, 101.482919, NULL, '2025-04-10 13:21:47', '2025-04-10 13:21:47'),
(146, 'Sentul Medical Centre', '', NULL, 3.17432131, 101.692702, NULL, '2025-04-10 13:22:03', '2025-04-10 13:22:03'),
(147, 'Sheela Specialist Centre for Women', '', NULL, 3.05695715, 101.473120, NULL, '2025-04-10 13:22:20', '2025-04-10 13:22:20'),
(148, 'Sri Kota Specialist Medical Centre', '', NULL, 3.04046869, 101.445625, NULL, '2025-04-10 13:22:36', '2025-04-10 13:22:36'),
(149, 'Stella Kasih Medical Centre', '', NULL, 2.93962415, 101.682642, NULL, '2025-04-10 13:23:01', '2025-04-10 13:23:01'),
(150, 'Subang Jaya Medical Centre', '', NULL, 3.07989670, 101.594049, NULL, '2025-04-10 13:23:17', '2025-04-10 13:23:17'),
(151, 'Sunway Medical Centre', '', NULL, 3.06611024, 101.609484, NULL, '2025-04-10 13:23:59', '2025-04-10 13:23:59'),
(152, 'Sunway Medical Centre Penang', '', NULL, 5.39587938, 100.396725, NULL, '2025-04-10 13:24:17', '2025-04-10 13:24:17'),
(153, 'Sunway Medical Centre Velocity', '', NULL, 3.12827529, 101.722066, NULL, '2025-04-10 13:24:42', '2025-04-10 13:24:42'),
(154, 'Taiping Medical Centre', '', NULL, 4.84751605, 100.732457, NULL, '2025-04-10 13:24:52', '2025-04-10 13:24:52'),
(155, 'Taman Desa Medical Centre', '', NULL, 3.09633094, 101.677960, NULL, '2025-04-10 13:25:10', '2025-04-10 13:25:10'),
(156, 'Tey Maternity Specialist & Gynae Centre', '', NULL, 2.04460144, 102.565799, NULL, '2025-04-10 13:25:27', '2025-04-10 13:25:27'),
(157, 'Thomson Hospital Kota Damansara', '', NULL, 3.14916127, 101.578877, NULL, '2025-04-10 13:25:43', '2025-04-10 13:25:43'),
(158, 'Timberland Medical Centre', '', NULL, 1.52163477, 110.338112, NULL, '2025-04-10 13:25:59', '2025-04-10 13:25:59'),
(159, 'Tun Hussein Onn National Eye Hospital (THONEH)', '', NULL, 3.10636578, 101.640112, NULL, '2025-04-10 13:26:17', '2025-04-10 13:26:17'),
(160, 'Tung Shin Hospital', '', NULL, 3.14673280, 101.704001, NULL, '2025-04-10 13:26:37', '2025-04-10 13:26:37'),
(161, 'UCSI Hospital', '', NULL, 2.60595572, 101.852780, NULL, '2025-04-10 13:26:49', '2025-04-10 13:26:49'),
(162, 'UKM Specialist Centre', '', NULL, 3.09846601, 101.726143, NULL, '2025-04-10 13:27:05', '2025-04-10 13:27:05'),
(163, 'UM Specialist Centre', '', NULL, 3.11624118, 101.650760, NULL, '2025-04-10 13:27:23', '2025-04-10 13:27:23'),
(164, 'UTAR Education Foundation Hospital Universiti Tunku Abdul Rahman', '', NULL, 4.33572455, 101.134095, NULL, '2025-04-10 13:27:40', '2025-04-10 13:27:40'),
(165, 'UiTM Private Specialist Centre', '', NULL, 3.22088441, 101.593338, NULL, '2025-04-10 13:28:03', '2025-04-10 13:28:03'),
(166, 'Alty Orthopaedic Centre', 'Kuala Lumpur', NULL, 3.16095824, 101.725136, NULL, '2025-04-10 13:30:23', '2025-04-10 13:30:23'),
(167, 'Beacon Hospital', 'Kuala Lumpur', NULL, 3.09182640, 101.637982, NULL, '2025-04-10 13:30:58', '2025-04-10 13:30:58'),
(168, 'Beverly Wilshire Medical Centre', '', NULL, 3.14614036, 101.721759, NULL, '2025-04-10 14:24:42', '2025-04-10 14:24:42'),
(169, 'Cardiac Vascular Sentral Kuala Lumpur (CVSKL)', 'Kuala Lumpur', NULL, 3.13376930, 101.684815, NULL, '2025-04-10 14:25:43', '2025-04-10 14:25:43'),
(170, 'Cengild G.I. Medical Centre', 'Kuala Lumpur', NULL, 3.10991568, 101.665381, NULL, '2025-04-10 14:27:14', '2025-04-10 14:27:14'),
(171, 'Gleneagles Hospital Kuala Lumpur', 'Kuala Lumpur', NULL, 3.16141335, 101.739162, NULL, '2025-04-10 14:27:46', '2025-04-10 14:27:46'),
(172, 'Hospital Pusrawi', 'Kuala Lumpur', NULL, 3.17127447, 101.710072, NULL, '2025-04-10 14:28:25', '2025-04-10 14:28:25'),
(173, 'Institut Jantung Negara (IJN)', 'Kuala Lumpur', NULL, 3.17028817, 101.708943, NULL, '2025-04-10 14:30:23', '2025-04-10 14:30:23'),
(174, 'KPJ Sentosa KL Specialist Hospital', 'Kuala Lumpur', NULL, 3.17111489, 101.697203, NULL, '2025-04-10 14:30:52', '2025-04-10 14:30:52'),
(175, 'KPJ Tawakkal KL Specialist Hospital', 'Kuala Lumpur', NULL, 2.71878220, 101.921826, NULL, '2025-04-10 14:31:44', '2025-04-10 14:31:44'),
(176, 'Pantai Hospital Kuala Lumpur', 'Kuala Lumpur', NULL, 3.11992946, 101.666930, NULL, '2025-04-10 14:32:07', '2025-04-10 14:32:07'),
(177, 'Prince Court Medical Centre', 'Kuala Lumpur', NULL, 3.14920607, 101.721733, NULL, '2025-04-10 14:32:34', '2025-04-10 14:32:34'),
(178, 'ReGen Rehab Hospital', 'Kuala Lumpur', NULL, 3.11565710, 101.640729, NULL, '2025-04-10 14:33:04', '2025-04-10 14:33:04'),
(179, 'Sentul Medical Centre', 'Kuala Lumpur', NULL, 3.17432131, 101.692702, NULL, '2025-04-10 14:33:36', '2025-04-10 14:33:36'),
(180, 'Sri Kota Specialist Medical Centre', 'Kuala Lumpur', NULL, 3.04046869, 101.445625, NULL, '2025-04-10 14:34:22', '2025-04-10 14:34:22'),
(181, 'Subang Jaya Medical Centre', 'Kuala Lumpur', NULL, 3.07989670, 101.594049, NULL, '2025-04-10 14:34:59', '2025-04-10 14:34:59'),
(182, 'Sunway Medical Centre Velocity', 'Kuala Lumpur', NULL, 3.12827529, 101.722066, NULL, '2025-04-10 14:35:29', '2025-04-10 14:35:29'),
(183, 'Taman Desa Medical Centre', 'Kuala Lumpur', NULL, 3.09633094, 101.677960, NULL, '2025-04-10 14:36:07', '2025-04-10 14:36:07'),
(184, 'Thomson Hospital Kota Damansara', 'Kuala Lumpur', NULL, 3.14916127, 101.578877, NULL, '2025-04-10 14:36:25', '2025-04-10 14:36:25'),
(185, 'Tung Shin Hospital', 'Kuala Lumpur', NULL, 3.14673280, 101.704001, NULL, '2025-04-10 14:36:56', '2025-04-10 14:36:56'),
(186, 'UKM Specialist Centre', 'Kuala Lumpur', NULL, 3.09846601, 101.726143, NULL, '2025-04-10 14:41:43', '2025-04-10 14:41:43'),
(187, 'UM Specialist Centre', 'Kuala Lumpur', NULL, 3.11624118, 101.650760, NULL, '2025-04-10 14:42:20', '2025-04-10 14:42:20'),
(188, 'Ara Damansara Medical Centre', 'Petaling Jaya', NULL, 3.11490661, 101.565107, NULL, '2025-04-10 14:43:15', '2025-04-10 14:43:15'),
(189, 'Assunta Hospital', 'Petaling Jaya', NULL, 3.09361169, 101.645915, NULL, '2025-04-10 14:46:08', '2025-04-10 14:46:08'),
(190, 'Columbia Asia Hospital – Petaling Jaya', 'Petaling Jaya', NULL, 3.11861361, 101.637629, NULL, '2025-04-10 14:47:08', '2025-04-10 14:47:08'),
(191, 'Columbia Asia Hospital – Bukit Jalil', 'Bukit Jalil', NULL, 3.05853361, 101.687296, NULL, '2025-04-10 14:58:46', '2025-04-10 14:58:46'),
(192, 'Columbia Asia Hospital – Bukit Rimau', 'Shah Alam', NULL, 2.99734611, 101.528788, NULL, '2025-04-10 15:00:43', '2025-04-10 15:00:43'),
(193, 'Columbia Asia Hospital – Cheras', 'Cheras', NULL, 3.03173927, 101.762934, NULL, '2025-04-10 15:01:37', '2025-04-10 15:01:37'),
(194, 'Columbia Asia Hospital – Klang', 'Klang', NULL, 3.08615909, 101.446221, NULL, '2025-04-10 15:02:18', '2025-04-10 15:02:18'),
(195, 'Columbia Asia Hospital – Puchong', 'Puchong', NULL, 3.02416700, 101.622191, NULL, '2025-04-10 15:03:02', '2025-04-10 15:03:02'),
(196, 'Columbia Asia Hospital – Setapak', 'Setapak', NULL, 3.20154869, 101.718184, NULL, '2025-04-10 15:03:43', '2025-04-10 15:03:43'),
(197, 'Daehan Rehabilitation Hospital Putrajaya', 'Putrajaya', NULL, 2.97077554, 101.705437, NULL, '2025-04-10 15:10:27', '2025-04-10 15:10:27'),
(198, 'Hospital Islam Az-Zahrah', 'Bandar Baru Bangi', NULL, 2.96009043, 101.754200, NULL, '2025-04-10 15:10:53', '2025-04-10 15:10:53'),
(199, 'Hospital UMRA', 'Shah Alam', NULL, 3.08313520, 101.539982, NULL, '2025-04-10 15:11:28', '2025-04-10 15:11:28'),
(200, 'KPJ Ampang Puteri Specialist Hospital', 'Ampang', NULL, 3.16037630, 101.751663, NULL, '2025-04-10 15:11:57', '2025-04-10 15:11:57'),
(201, 'KPJ Damansara Specialist Hospital', 'Damansara', NULL, 3.13781310, 101.627535, NULL, '2025-04-10 15:12:24', '2025-04-10 15:12:24'),
(202, 'KPJ Damansara Specialist Hospital 2', 'Damansara', NULL, 3.17134349, 101.618447, NULL, '2025-04-10 15:12:55', '2025-04-10 15:12:55'),
(203, 'KPJ Kajang Specialist Hospital', 'Kajang', NULL, 3.00014501, 101.785718, NULL, '2025-04-10 15:13:21', '2025-04-10 15:13:21'),
(204, 'KPJ Klang Specialist Hospital', 'Klang', NULL, 3.06248579, 101.463230, NULL, '2025-04-10 15:13:44', '2025-04-10 15:13:44'),
(205, 'KPJ Rawang Specialist Hospital', 'Rawang', NULL, 3.32106243, 101.579251, NULL, '2025-04-10 15:14:22', '2025-04-10 15:14:22'),
(206, 'KPJ Selangor Specialist Hospital', 'Shah Alam', NULL, 3.05743060, 101.541591, NULL, '2025-04-10 15:14:55', '2025-04-10 15:14:55'),
(207, 'KPMC Puchong Specialist Centre', 'Puchong', NULL, 3.02491213, 101.615756, NULL, '2025-04-10 15:17:09', '2025-04-10 15:17:09'),
(208, 'Kajang Plaza Medical Centre', 'Kajang', NULL, 2.99789711, 101.788248, NULL, '2025-04-10 15:17:43', '2025-04-10 15:17:43'),
(209, 'Kelana Jaya Medical Centre', 'Kelana Jaya', NULL, 3.10868588, 101.595380, NULL, '2025-04-10 15:18:35', '2025-04-10 15:18:35'),
(210, 'MSU Medical Centre', 'Shah Alam', NULL, 3.07698549, 101.553067, NULL, '2025-04-10 15:19:04', '2025-04-10 15:19:04'),
(211, 'MAHSA Specialist Hospital', 'Bandar Saujana Putra', NULL, 2.95334099, 101.576059, NULL, '2025-04-10 15:19:48', '2025-04-10 15:19:48'),
(212, 'Pantai Hospital Ampang', 'Ampang', NULL, 3.12797808, 101.752076, NULL, '2025-04-10 15:20:13', '2025-04-10 15:20:13'),
(213, 'Pantai Hospital Cheras', 'Cheras', NULL, 3.10284681, 101.740699, NULL, '2025-04-10 15:20:45', '2025-04-10 15:20:45'),
(214, 'Pantai Hospital Klang', 'Klang', NULL, 3.02665630, 101.426265, NULL, '2025-04-10 15:21:12', '2025-04-10 15:21:12'),
(215, 'ParkCity Medical Centre', 'Desa ParkCity', NULL, 3.18956428, 101.638507, NULL, '2025-04-10 15:22:00', '2025-04-10 15:22:00'),
(216, 'SALAM Shah Alam Specialist Hospital', 'Shah Alam', NULL, 3.04939971, 101.535204, NULL, '2025-04-10 15:22:22', '2025-04-10 15:22:22'),
(217, 'Sentosa Specialist Hospital', 'Klang', NULL, 3.00590871, 101.482919, NULL, '2025-04-10 15:22:49', '2025-04-10 15:22:49'),
(218, 'Sunway Medical Centre', 'Bandar Sunway', NULL, 3.06611024, 101.609484, NULL, '2025-04-10 15:23:15', '2025-04-10 15:23:15'),
(219, 'UiTM Private Specialist Centre', 'Shah Alam', NULL, 3.22088441, 101.593338, NULL, '2025-04-10 15:23:43', '2025-04-10 15:23:43'),
(220, 'Hospital Ampang', '', NULL, 3.12842495, 101.764009, NULL, '2025-04-10 15:24:44', '2025-04-10 15:24:49'),
(221, 'Hospital Kuala Lumpur (HKL)', '', NULL, 3.17303544, 101.700198, NULL, '2025-04-10 15:24:44', '2025-04-10 15:24:44'),
(222, 'Hospital Rehabilitasi Cheras', '', NULL, 3.10680988, 101.728015, NULL, '2025-04-10 15:25:04', '2025-04-10 15:25:04'),
(223, 'Hospital Selayang', '', NULL, 3.24253104, 101.646276, NULL, '2025-04-10 15:25:22', '2025-04-10 15:25:22'),
(224, 'Hospital Serdang', '', NULL, 2.97670863, 101.719988, NULL, '2025-04-10 15:25:41', '2025-04-10 15:25:41'),
(225, 'Hospital Shah Alam', '', NULL, 3.07160091, 101.490749, NULL, '2025-04-10 15:26:01', '2025-04-10 15:26:01'),
(226, 'Hospital Sungai Buloh', '', NULL, 3.21980866, 101.583092, NULL, '2025-04-10 15:26:16', '2025-04-10 15:26:16'),
(227, 'Hospital Tengku Ampuan Rahimah (Klang)', '', NULL, 3.02042169, 101.441474, NULL, '2025-04-10 15:26:32', '2025-04-10 15:26:32'),
(228, 'Hospital Tunku Azizah (Wanita dan Kanak-Kanak), Kuala Lumpur', '', NULL, 3.17018428, 101.702796, NULL, '2025-04-10 15:26:49', '2025-04-10 15:26:49'),
(229, 'Institut Kanser Negara (Putrajaya)', '', NULL, 2.92745497, 101.673910, NULL, '2025-04-10 15:27:09', '2025-04-10 15:27:09'),
(230, 'Institut Perubatan Respiratori, Kuala Lumpur', '', NULL, 3.17574799, 101.699275, NULL, '2025-04-10 15:27:24', '2025-04-10 15:27:24'),
(231, 'Hospital Putrajaya', '', NULL, 2.92929934, 101.674185, NULL, '2025-04-10 15:27:42', '2025-04-10 15:27:42'),
(232, 'Damai Service Hospital (HQ)', 'Kuala Lumpur', NULL, 3.17035738, 101.695157, NULL, '2025-04-11 04:49:46', '2025-04-11 04:49:46');

-- --------------------------------------------------------

--
-- Table structure for table `invite_users`
--

CREATE TABLE `invite_users` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `to_mail` varchar(100) DEFAULT NULL,
  `type_dependant` varchar(100) DEFAULT NULL,
  `is_accepted` int(10) NOT NULL DEFAULT 0,
  `is_removed` int(100) NOT NULL DEFAULT 0,
  `status` tinyint(2) NOT NULL DEFAULT 1 COMMENT '0 - not sent,1 - sent',
  `type_mail` int(10) DEFAULT 1 COMMENT '1-sent,2-resend',
  `is_revoke` int(11) NOT NULL DEFAULT 0,
  `is_release_slot` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(250) NOT NULL,
  `name` varchar(250) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(9,6) NOT NULL,
  `is_covered` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `latitude`, `longitude`, `is_covered`, `created_at`, `updated_at`) VALUES
(2, 'Port Klang', 3.00180000, 101.397900, 1, NULL, '2024-10-03 06:40:47'),
(3, 'Klang', 3.03350000, 101.445000, 1, NULL, '2024-11-07 07:12:11'),
(4, 'Sungai Buloh', 3.21320000, 101.576300, 1, NULL, '2024-11-07 07:12:11'),
(5, 'Bandar Baru Selayang', 3.26270000, 101.655100, 1, NULL, '2024-11-07 07:13:06'),
(6, 'Batu Caves', 3.26590000, 101.684200, 1, NULL, '2024-11-07 07:13:06'),
(7, 'Gombak', 3.23560000, 101.737200, 1, NULL, '2024-11-07 07:13:53'),
(8, 'Taman Melawati', 3.21750000, 101.748900, 1, NULL, '2024-11-07 07:13:53'),
(9, 'Ampang', 3.15880000, 101.757400, 1, NULL, '2024-11-07 07:14:41'),
(10, 'Kajang Town Area', 2.99360000, 101.787100, 1, NULL, '2024-11-07 07:14:41'),
(11, 'Bangi', 2.91450000, 101.785400, 1, NULL, '2024-11-07 07:15:25'),
(12, 'Putrajaya', 2.92640000, 101.696400, 1, NULL, '2024-11-07 07:15:25'),
(13, 'Cyberjaya', 2.92260000, 101.650600, 1, NULL, '2024-11-07 07:16:13'),
(14, 'Port Klang', 3.00180000, 101.397900, 1, NULL, '2024-11-07 07:16:13'),
(34, 'America', 38.79460000, 106.534800, 1, '2024-12-20 10:38:52', '2024-12-20 10:38:52'),
(35, 'Central Park', 10.99623140, 76.967932, 1, '2025-01-22 10:34:09', '2025-01-22 10:34:09'),
(36, 'Central Park', 10.99623140, 76.967932, 1, '2025-05-16 08:10:18', '2025-05-16 08:10:18'),
(37, 'Central Park', 10.99623140, 76.967932, 1, '2025-05-16 08:57:16', '2025-05-16 08:57:16');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(100) NOT NULL,
  `type` int(100) NOT NULL DEFAULT 1,
  `title` varchar(250) DEFAULT NULL,
  `range_limit` varchar(200) DEFAULT NULL,
  `price` int(100) DEFAULT NULL,
  `discount` int(250) DEFAULT NULL,
  `count` int(100) DEFAULT NULL,
  `is_active` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `type`, `title`, `range_limit`, `price`, `discount`, `count`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Adult', '18-59 years old', 325, 0, 0, 1, '2024-12-17 05:16:23', '2025-02-10 12:19:13'),
(2, 1, 'Child', 'Younger than 18 years old', 325, 10, 0, 1, '2024-12-17 05:27:04', '2025-04-24 04:43:26'),
(3, 1, 'Senior citizen', 'Older than 59 years old', 375, 0, 0, 1, '2024-12-17 05:32:01', '2025-04-11 19:25:28');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_users`
--

CREATE TABLE `notification_users` (
  `id` int(11) NOT NULL,
  `form_user_id` int(11) NOT NULL,
  `to_user_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `to_email` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `updated_by` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_auth_codes`
--

CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_personal_access_clients`
--

CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paramedics`
--

CREATE TABLE `paramedics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `hospital_id` int(250) DEFAULT NULL,
  `hospital_name` varchar(250) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `random_no` int(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_slots`
--

CREATE TABLE `purchase_slots` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `adult_count` int(100) DEFAULT 0,
  `senior_count` int(100) DEFAULT 0,
  `child_count` int(100) DEFAULT 0,
  `status` int(100) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `first_name` varchar(250) NOT NULL,
  `last_name` varchar(250) DEFAULT NULL,
  `ic_number` varchar(255) DEFAULT NULL,
  `phone_number` varchar(100) NOT NULL,
  `race` varchar(250) DEFAULT NULL,
  `are_u_foreigner` tinyint(1) NOT NULL DEFAULT 0,
  `passport_no` varchar(250) DEFAULT NULL,
  `gender` tinyint(1) DEFAULT NULL,
  `nationality` varchar(250) DEFAULT NULL,
  `dob` varchar(250) DEFAULT NULL,
  `email` varchar(250) NOT NULL,
  `otp` int(100) DEFAULT NULL,
  `address` longtext DEFAULT NULL,
  `address2` varchar(250) DEFAULT NULL,
  `postcode` int(100) DEFAULT NULL,
  `city` varchar(250) DEFAULT NULL,
  `state` varchar(250) DEFAULT NULL,
  `country` varchar(250) DEFAULT NULL,
  `is_covered` tinyint(1) NOT NULL DEFAULT 1,
  `longitude` decimal(9,6) DEFAULT NULL,
  `latitude` decimal(11,8) DEFAULT NULL,
  `remindme` tinyint(1) DEFAULT 0,
  `password` varchar(250) DEFAULT NULL,
  `medical_info` varchar(255) DEFAULT NULL,
  `heart_problems` tinyint(1) DEFAULT NULL,
  `diabetes` tinyint(1) DEFAULT NULL,
  `allergic` tinyint(1) DEFAULT NULL,
  `allergic_medication_list` varchar(200) DEFAULT NULL,
  `referral_number` varchar(255) DEFAULT NULL,
  `zoho_record_id` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renewal_records`
--

CREATE TABLE `renewal_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `renewal_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roaster_mapping`
--

CREATE TABLE `roaster_mapping` (
  `id` int(200) NOT NULL,
  `hospital` varchar(255) DEFAULT NULL,
  `hospital_id` int(200) DEFAULT NULL,
  `paramedic_id` varchar(200) DEFAULT NULL,
  `driver_id` int(200) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `vehicle` varchar(255) DEFAULT NULL,
  `vehicle_id` int(100) DEFAULT NULL,
  `driver_status` enum('Online','Offline','Busy') DEFAULT NULL,
  `ride_status` enum('Complete','Dropped Off','Picked Up','Arrived','On the way') DEFAULT NULL,
  `shift` varchar(250) DEFAULT NULL,
  `zoho_record_id` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_benefits`
--

CREATE TABLE `subscription_benefits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED NOT NULL,
  `benefit_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_benefits`
--

INSERT INTO `subscription_benefits` (`id`, `subscription_id`, `benefit_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2024-08-13 12:16:07', '2024-08-13 12:16:07'),
(2, 1, 2, '2024-08-13 12:16:07', '2024-08-13 12:16:07'),
(3, 2, 3, '2024-08-13 12:16:07', '2024-08-13 12:16:07'),
(4, 2, 1, '2024-08-13 12:16:07', '2024-08-13 12:16:07'),
(5, 2, 2, '2024-08-13 12:16:07', '2024-08-13 12:16:07'),
(6, 3, 1, NULL, NULL),
(7, 3, 2, NULL, NULL),
(12, 5, 1, NULL, NULL),
(13, 5, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_masters`
--

CREATE TABLE `subscription_masters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `plan` varchar(255) NOT NULL,
  `price` int(100) NOT NULL,
  `eligible` tinyint(1) NOT NULL,
  `free_plan` tinyint(1) NOT NULL,
  `usual_price` int(100) NOT NULL,
  `child` int(100) DEFAULT NULL,
  `senior` int(100) DEFAULT NULL,
  `adult` int(100) DEFAULT NULL,
  `key_benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`key_benefits`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_masters`
--

INSERT INTO `subscription_masters` (`id`, `plan`, `price`, `eligible`, `free_plan`, `usual_price`, `child`, `senior`, `adult`, `key_benefits`, `created_at`, `updated_at`) VALUES
(1, 'Adult Membership', 325, 1, 0, 800, 2, 1, 1, '{\"emergency_calls\": 2, \"clinic_calls\": 2}', '2024-08-13 12:15:38', '2024-08-13 12:15:38'),
(2, 'Senior Citizen Membership', 375, 0, 0, 800, 2, 1, 1, '{\"emergency_calls\": 2, \"clinic_calls\": 2}', '2024-08-13 12:15:38', '2024-08-13 12:15:38'),
(3, 'Free Membership', 0, 1, 1, 0, NULL, NULL, NULL, '{\"emergency_calls\":3,\"clinic_calls\":3}', '2024-08-28 06:25:49', '2024-08-28 06:25:49'),
(5, 'Child Membership', 0, 1, 0, 0, NULL, NULL, NULL, '{\"emergency_calls\":3,\"clinic_calls\":3}', '2024-09-22 07:33:50', '2024-09-22 07:33:50');

-- --------------------------------------------------------

--
-- Table structure for table `telescope_entries`
--

CREATE TABLE `telescope_entries` (
  `sequence` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `batch_id` char(36) NOT NULL,
  `family_hash` varchar(255) DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telescope_entries_tags`
--

CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) NOT NULL,
  `tag` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telescope_monitoring`
--

CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `trip_id` varchar(255) NOT NULL,
  `trip_details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_status_logs`
--

CREATE TABLE `trip_status_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trip_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('On the way','Arrived','Picked Up','Dropped Off','Complete') NOT NULL,
  `status_updated_at` timestamp NOT NULL,
  `time_taken` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_c_s`
--

CREATE TABLE `t_c_s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `terms` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `t_c_s`
--

INSERT INTO `t_c_s` (`id`, `terms`, `created_at`, `updated_at`) VALUES
(1, '<br><br><br><b>TERMS &amp; CONDITIONS</b><br><br><b>1. Preamble</b><br>This Subscription Agreement (“Agreement”;) for this Application and Services (defined interchangeably in reference to the Application or the Services provided) as by and between First Health Assistance (“FHA”) which is the service provider and you (“Member”) is either effective upon Member`s electronic acceptance of this Agreement or the execution of the Subscription Form wherein this Agreement incorporates the detailed terms and conditions to regulate the Parties and Services provided. FHA and Member may together be also referred to as the Parties” or individually as the “Party.” FHA intends to grant the Member the right to use the Application through a subscription service and Member intends to subscribe to such Application. In addition to the software and the related services required for the provisioning of the software, the Parties may agree upon specific “Professional Services” to be rendered by FHA according to the terms and conditions of this Agreement.<br><b>2. Acknowledgement</b><br>These are the terms and conditions governing the use of this Service and the Agreement that operates between you (Member) and FHA. These Terms and Conditions set out the rights and obligations of all users regarding the use of the Service provided in this Application. Your access to use the Application or Service is contingent upon your acceptance of and in compliance with these Terms and Conditions.<br><br>These Terms and Conditions apply to all visitors, users and others who access or use the Application or Service. By accessing or subscribing to this Application or Service, you agree to be bound by these Terms and Conditions. If you disagree with any part of these Terms and Conditions then you may not access this Application or Service.<br><b>3. General Interpretations </b><br>3.1. Words denoting the singular number include the plural number and vice versa.<br><br>3.2. Headings of clauses are for ease of reference only and shall be ignored in interpreting the provisions hereof.<br><br>3.3. References to any statute or statutory provision shall be construed as a reference to the same as it may have been, or may from time to time be, amended, modified or re-enacted.<br><br>3.4. Any liberty or power which may be exercised or any determination which may be made hereunder by any of the Parties hereto may be exercised or made in the Party’s discretion and the Party shall not be under any obligation to give any reason there for to the other Party.<br><br>3.5. No rule of construction applies to the disadvantage of one party because that party was responsible for the preparation of this Agreement.<br><br>Now, therefore, the Parties agree by considering the stipulations set forth as follows:<br><b>4. General</b><br>The signatory and/ or accepting party to this Terms and Conditions of Service shall be the Member. Where a Member is medically certified to be mentally incompetent such as by reason of a state of unsound mind / comatose state / state of unconsciousness and/or physically incapacitated such as by reason of a physical handicap which causes an inability in the member to sign or stamp his thumbprint or who is legally incompetent in that he/she is a minor who has not attained the age of 18 years (hereinafter referred to as “Incompetent Member”;) and where in the case of mental incompetence or physical incapacity, such mental incompetence or physical incapacity is certified by a medical doctor practicing (then such authority to sign this Terms and Conditions of Service on behalf of the Incompetent Member devolves on the spouse, parent or next of kin of an adult Incompetent Member and in the case of a minor Incompetent Member, on the parent or guardian).<br><b>5. Service Provider General Obligations.</b><br>FHA shall make the software and the services required for provisioning the Membership ambulance services (hereinafter together and individually may also be referred to as “Service” or “Services”) available to Member as described in the Services Description to be referenced in this Agreement. FHA may amend the Services from time to time, provided that such amendments shall not materially diminish the overall Service. <br><b>6. Membership Ambulance Service Description.</b><br>The key features upon which Services would be provided for the Members are including but not limited to the following (“Service Description”):<br><br>6.1. Priority Dispatch: Members receive top priority in emergencies, ensuring the rapid deployment of our medical team.<br><br>6.2. 24/7 Availability: Our services guarantee round-the-clock assistance wherever and whenever required.<br><br>6.3. Dedicated Hotline: An exclusive emergency hotline will be provided for our e-wallet clients, ensuring quick access to our services.<br><br>6.4. Professional Medical Team: Skilled medical professionals and paramedics ensure the highest level of care during transportation to medical facilities.<br><br>6.5. Transparent Pricing: Members enjoy discounted rates with transparent and competitive pricing structures.<br><br>6.6. Coverage Area: Unless otherwise notified, our ambulance Services will cover the Klang Valley region and only within a distance of 15 kilometer radius from the designated pick-up point (hereinafter referred to as “Coverage Area”), subject to an expansion in area and coverage which may be progressively updated to each state and ultimately nationwide. In the event the coverage area is beyond the aforementioned, Members will be bound by the extra charges incurred. <br><b>7. Membership Benefits</b><br>The key benefits for the Services aforementioned would include (not exhaustive) to the following:<br><br>7.1. Peace of Mind: Swift and reliable emergency medical services.<br><br>7.2. Savings: With a membership, they often don’t have to bear the full cost of an ambulance call, which can be substantial with or without insurance coverage.<br><br>7.3. Transparent Communication: Dedicated hotline and priority dispatch mean help is just a call away. This transparency also helps clients understand the terms of their coverage, the process of requesting services.<br><br>7.4. Enhanced Member Loyalty: Strengthening the brand’s relationship with clients, enhancing their loyalty.<br><br>7.5. No Out-of-Pocket Expenses: Members will not have to deal with out-of-pocket expenses related to ambulance services once covered by the membership. This can be especially valuable during times of unexpected medical emergencies.<br><br>7.6. Priority Scheduling: Certain ambulance membership programs may offer priority scheduling for Non-emergency medical transportation. This can be beneficial for clients who require regular medical appointments, ensuring timely and convenient transport.<br><br>7.7. Reduced Administrative Burden: Clients with ambulance memberships may experience a reduced administrative burden during emergencies. Since their membership details are already on record, the process of requesting and receiving Emergency services can be more streamlined, allowing for quicker response times.<br><b>8. Membership Additional Benefits for Ambulance Services Provided Solely by FHA</b><br>Upon subscribing, Members would not have to bear the transport costs for the ambulances provided by FHA which includes Emergency or Non-emergency transports within the parameters of the approved areas in which FHA operates “Operational Boundaries”.<br><b>9. Beyond Coverage Area</b><br>The rates set by FHA for ambulance transport services beyond the Coverage Area as specified in this Agreement for Emergency transport transfers will be charged at RM25.00 per kilometer whereas for Non-emergency transport transfers a charge of RM10.00 per kilometer will be incurred. <br><b>10.Membership Tiers Categories</b><br>10.1. Unless otherwise specified in this Agreement, “Emergency” would mean a medical emergency which exists or is believed to exist; an accident, incident and/or episode which has occurred where medical care and/or transport is believed to be urgently required; or in the event the ambulance service is urgently required as a result of an unforeseen illness and/or injury. “Non- emergency” means a pre-arranged booking for ambulance services for a transfer to or from a hospital, nursing home, residence, or any other relevant places in order to receive medical treatment and is deemed to be Clinically Necessary (defined in the clause herein below) as authorised by an appropriate medical professional.<br><br>10.2. Single Member: Covers essential ambulance services for emergencies and non-emergencies; limited to two emergency call per year, Limited to two Clinically Necessary non-emergency calls per year (which is defined in this Agreement as the transportation of a patient via ambulance that is deemed essential by medical professionals to address the patient\'s medical condition and/or needs. This type of transfer is typically authorized based on the patient’s clinical status that are supported by medical evidence and/or are deemed appropriate for medical intervention or transportation to a medical facility) non-emergency calls per year including priority scheduling for non-emergency medical transportation and follow-up medical coordination; and with an annual fee.<br><br>10.3. Family Membership: Covers essential ambulance services for Emergencies and Non-emergencies in which all benefits are extended to the spouse and eligible Member’s Dependants; limited to two emergency call per year per household member, Limited to two Clinically Necessary non-emergency calls per year per household member, including priority scheduling for non-emergency medical transportation and follow-up medical coordination. The coverage for this category is for the Member and eligible “Member Dependants” which includes the family, spouse and/or children minimum of 2 under the age of 17 and living under the same roof or full-time students under 25; (The annual fee includes the primary Applicant/Member, with the option to add on additional eligible Member dependant at an additional cost.<br><br>10.4. Senior Citizen Membership: Limited to two Emergency call per year; limited to two Clinically Necessary Non-emergency calls per year, including priority scheduling for Non-emergency medical transportation and follow-up medical coordination; Member and eligible Member Dependants; with an annual fee (The annual fee of includes the primary Applicant/Member, with the option to add on additional eligible Senior Citizen Member dependant at an additional cost).<br><b>11. Membership Commencement Period</b><br>Without prejudice to the Qualifying Period, the commencement period in which the Membership commences for New and Reinstated Members “vis-à-vis” this Agreement would begin forty-eight (48) hours on the day after the Membership Fee is duly received by FHA. For Renewing Members, Membership benefits are continuous subject to the Membership Fee being received in full by FHA no later than the due date of the Membership Renewal. Once a Membership has commenced it cannot be suspended.<br><b>12. Qualifying Period</b><br>There will be an exact fourteen (14) day Qualifying Period from the Membership commencement date for all New and Reinstated Members. During the aforementioned Qualifying Period, Members are not entitled to receive, benefit and/ or utilise Member Benefits for Non-emergency patient transport Ambulance Services and / or Emergency Ambulance Services where the service is required because of a pre-existing ailment.<br><b>13. Waiver of Qualifying Period</b><br>The Qualifying Period may be waived under the following circumstances; wherein in respect of children who qualify as a Dependant when added to an existing Family Membership which has already served the Qualifying Period and where a New or Reinstated Member was formerly an eligible FHA Member and joins the Membership scheme within thirty (30) days.<br><b>14. Members’ General Eligibility</b><br>Members must be Malaysian citizens or have been granted permanent or temporary residential status in Malaysia and have their permanent place of residence located within the approved areas in which FHA operates which has been defined as Operational Boundaries.<br><b>15. Members’ Prerequisites</b><br>15.1. In order to be entitled as a Member under this Agreement, one must be a Malaysian citizen; a Permanent/ Temporary Resident of Malaysia; or a legal work permit or employment pass holder who is legally residing in Malaysia. In addition to the aforesaid requirements, one must also fulfil the following Age Requirements and the ‘Eligible Member Dependant’ which is particularized as follows:-<br><br>1.   Members must be at least 18 years old or classified as an;eligible Member Dependent.<br>2.  Members must be aged between 15 days and 60 years old, with renewals allowed up to 70 years old.<br>3.  Members up to the age of 25, or those registered as full-time students at a recognized educational institution, qualify as eligible Member Dependents.<br>4.  Orang Kurang Upaya (OKU) /Persons with Disabilities (PWD); is eligible for the cover. <br><br>15.2. Notwithstanding the above, non-Malaysian residents may subscribe to this Service at the discretion of FHA.<br><b>16. Change in Membership Category</b><br>A Family Membership can be changed into two Single Memberships if a couple separates and have no further Dependants. Both Single Memberships will retain the original joining/commencement date however the expiry date of the new Single Memberships will be adjusted on a pro-rata basis based on the current Membership Fee at the time the change is made. If there are Dependants, the main Member may continue with the Family Membership covering themselves and their Dependants, while the other member maybe removed from the Family Membership and shall pay for a Single Membership with the Qualifying Period waived if the Single Membership is established and paid in full within thirty (30) days of being removed from the Family Membership.<br><br>In the instance that two existing Single Members combine their Memberships, both Single Memberships will cease on the date requested and a new Family Membership can be established with the remaining Membership Fees allocated on a pro-rata basis based on the current Membership Fee at the time the change is made. Verbal or written permission is required from both Members before the consolidation can occur and be processed.<br><b>17. Relationship of Parties</b><br>The Parties in this Agreement constitute to be independent contractors. This Agreement does not create nor is it intended to create a partnership, franchise, joint venture, agency, fiduciary or employment relationship between the Parties involved. <br><b>18. Members’ Obligations in Providing the Correct Information</b><br>It is incumbent upon the Member to advise FHA of any changes to their Membership, including but not limited to changes to their Membership type, medical history, Dependants, residential address, contact and payment details. Members are also required to notify FHA of any change of address. If a Member moves address resulting in no longer having their permanent place of residence located within FHA Operational Boundaries, Member Benefits will cease the day after the Member moves. (this can be viewed on the FHA website under Membership FAQs.)<br><b>19. Loss of Dependant Status</b><br>If a Dependant no longer meets the criteria for being a Dependant on an existing Family Membership that has already served the Qualifying Period then a new Single or Family Membership (as applicable) must be taken out on the Dependant. The Qualifying Period will be waived if the Membership is taken out and paid in full within thirty (30) days of the loss of Dependant status.<br><b>20. Caveat in Terms</b><br>This Agreement constitutes the entire agreement between the Parties with respect to the subject matter hereof. There are no other agreements, representations, warranties, promises, covenants, commitments, or undertakings binding unless otherwise expressly set forth therein. This Agreement supersedes all prior agreements, proposals or representations, written or oral, concerning its subject matter. In the event of a conflict between this Agreement and any one or more of the documents referenced in regards to the terms of this Agreement, the documents shall be construed consistently &amp; given priority based on the latest publication date, insofar as reasonably practicable.<br><b>21. Exclusions</b><br>21.1. Membership benefits does not cover Ambulance Services that are not deemed Clinically Necessary; FHA Services where a patient requests to be moved between medical facilities for reasons that are not Clinically Necessary especially a social or for convenience purposes; the patient chooses to move to another hospital to be closer to their home and/or family members; the patient chooses to move to another hospital to be treated by a preferred physician or in a preferred hospital; transport from one private home to another; relocation from one accommodation facility to another or from hospital to home and return to hospital for weekend or holiday relief; Non-emergency cases requiring FHA Services during the Qualifying Period; Emergency cases during the Qualifying Period where the service is required because of a pre-existing ailment; transport that is not to the nearest medical place that can treat you (please reach out to <a style=\"display:inline;\" target=\"_blank\" href=\"http://hello@firsthealthassist.com/\"><u>hello@firsthealthassist.com</u></a>, if you have any questions).<br><br>21.2. The Services subscribed to further does not cover the following:<br><br>1. cost of ambulance services to a location other than the Members place of intended or previous medical treatment, unless the transport has been approved in writing by FHA and an eligible medical professional.<br>2. cost of ambulance services not provided by FHA, unless co-ordinated or requested by FHA, or where FHA has given the Member its consent to use another provider, or transport provided by an interstate ambulance service.<br>3. cost of ambulance services interstate or overseas. Members will not be covered for any services provided by an ambulance service of another jurisdiction within Malaysia or overseas.<br>4. cost of ambulance services which the Members have been insured from a third party that would cover the cost if the Members do not have membership with FHA, or where a third party would be responsible for the cost if Members did not have FHA.<br>5. Transfers of patients between two hospitals, where one hospital is obligated to bear the financial responsibility.<br>6. Public hospitals or alternative medical facilities for outpatient care where payment is covered by the hospital.<br>7. Not a permanent Malaysian resident. If such a subscriber has voluntarily and knowingly renewed the subscription and a transport occurs, the said subscriber will be liable for the cost.<br>8. Relocation requests made by patients for non-Clinically Necessary reasons (such as social, familial, or personal reasons). The only scenarios that are viable to be covered are those in which repatriation is authorized as Clinically Necessary and there is a clear clinical need for ambulance services.<br>9. Organizations or agencies under the government of Malaysia. <br><b>22. Independent Healthcare Providers as Independent Contractors</b><br>The Member agrees and accepts that all medical professionals or independent healthcare providers who have practicing rights serviced in the Ambulance are independent contractors and are not employees of FHA. The Member further irrevocably agrees that the cost for Services rendered by the medical professionals or independent healthcare providers, if any shall be collected by FHA and that FHA shall have all legal rights to recover the cost for Services in FHA’s own volition.<br><b>23. Disclosure Of Member’s Own Medication</b><br>On application or renewal, the Member agrees to disclose and inform the attending doctor, medical consultant, or nurse of all medicines he/she is on, including but not limited to own medications, prescription medicines, over-the-counter medicines (OTC), vitamins, herbs, natural remedies, and traditional medicines voluntarily.<br><b>24. Loss Or Damage to Valuables</b><br>Members are encouraged to store and secure their valuables if any or to either use these or to ensure that valuables are not brought into the Ambulance. FHA shall not be liable to make good to any Member or guest or whosoever for the loss of or damage to property including but not limited to cash (monies), good(s) and/or valuables) brought into the Ambulance during transport. <br><b>25. Consent To Disclose Medical Information and Records to Healthcare Professional</b><br>The Member authorizes and consents to FHA releasing medical information and records “Medical Information” which shall include but not be limited to documentation related to the Services provided to the Member’s employer/insurance company or to the company issuing a guarantee for payment of Cost for Services rendered / to be rendered to the Member. The Member also authorizes the disclosure of the Medical Information whenever and to whosoever the law or a court order may require. The Member agrees to discharge and release FHA of any liability whatsoever and reimburse and indemnify FHA against any action, claim, suit or proceeding which may be brought by any person against FHA for releasing the Medical Information to the recipient in accordance with the authorization herein. The Member also agrees to sign an authorization in the Consent to release Medical Information Form if required by FHA. The Member agrees and understands that FHA will respond to requests for Medical Information in accordance with its internal policies and practices.<br><b>26. Requirement of Medical Professional’s Written Authorization.</b><br>Where an on-going Non-emergency ambulance service is required, the relevant Medical Professional’s written authorisation of the Member must be provided to FHA which is subject to a maximum validity period of one (1) month, after which it has to be renewed in order to utilize the service. <br><b>27. Membership Fees</b><br>Membership Fees are set by FHA and may be subject to change from time to time. Any changes in Membership Fees are only effective upon the next Membership Renewal date following a Membership Fee change unless otherwise notified. Members who renew their Membership within forty-eight (48) hours of the Renewal date may be offered continuity of benefits at the discretion of FHA. <br><b>28. Funds: Defaulted Fees</b><br>Members must ensure sufficient funds are available to cover drawing down/ transfer/transacted amount of the Membership Fee by FHA on the scheduled date(s). It is the sole responsibility of the Member to ensure that the payment is made for the Membership Fees. This includes ensuring FHA as the current valid payment details. If the payment is dishonored for any reason whatsoever, FHA may attempt to notify the Member using the contact details provided.<br><b>29. Refunds</b><br>If a Member has made a duplicate payment in error a refund for the full amount of the current Membership Period may be provided to the Member on request and upon confirmation by FHA. FHA may waive the administration fee in these circumstances. If a refund is not requested by the Member, FHA will extend the Membership Period which commensurate and reflects with the amount paid. If a Member is deceased, the unused portion of the Membership may be refunded to the estate of the deceased Member or to an authorized representative of the deceased Member upon a written request with the necessary legal documents to substantiate the same. FHA may waive the administration fee in these circumstances. <br><b>30. Limitation of Liability</b><br>In no event will FHA or its affiliates be liable for any indirect, consequential, incidental, special,punitive, or exemplary damages, or any loss of revenue, profits (excluding fees under this agreement), sales, data, data use, goodwill, or reputation. <br><b>31. Indemnification</b><br>31.1. Each Party shall at its own expense indemnify and keep the other including its directors, officers, employees, agents, successors and assigns indemnified from and against any actual or third-party claims, actions, suits, liabilities, losses, damages, costs, and expenses arising out of or in connection with the Agreement that includes any violation of any Intellectual Property Rights; any violation of proprietary right of any person or entity; any violation of any state, and/or federal laws or regulations; any defamatory matter.<br><br>31.2. FHA has the right to recover the cost of Ambulance services from any Member if the Member receives compensation, damages or any other payment from a third party covering the cost of the transport.<br><b>32. Assignment</b><br>Unless pursuant to the provisions of this Agreement, the Parties to this Agreement shall not be entitled to assign, transfer or otherwise dispose of any of their rights benefits or obligations under this Agreement to any other person, without the prior written consent of the other parties.<br><b>33. Currency</b><br>All payments under this Policy shall be made in the legal currency of Malaysia which is Malaysian Ringgit (MYR). Should you request any payments to be made in any other currency, then such amount shall be payable in the demand currency as may be purchased in Malaysia at the prevailing currency market rates on the date of the claim settlement.<br><b>34. Exclusion on Confidentiality</b><br>Confidential information shall not include any information that: (i) is or becomes generally known to the public without breach of any obligation owed to the other Party; (ii) was known to a party prior to its disclosure by the other Party without breach of any obligation owed to the other Party; (iii) was independently developed by a Party without breach of any obligation owed to the other Party; or (iv) is received from a third party without breach of any obligation owed to the other Party, (provided, that Member Data containing personal data shall be handled in accordance with the standards required by this Agreement even if the same information may be generally known, publicly available or otherwise accessible to the Service Provider from other sources.<br><b>35. Tax</b><br>FHA reserves the right to levy such taxes and/ or charges allowable under the Laws of Malaysia. If FHA has an obligation to pay or collect taxes for which Member is responsible under this section, the appropriate amount shall be invoiced to and paid by Member.<br><b>36. Termination</b><br>36.1. Termination for Convenience. Each Party may terminate this entire Agreement for convenience on not less than sixty (60) days prior written notice to the end of a calendar month, unless otherwise set forth in any Subscription Forms.<br><br>36.2. Termination for Cause. In addition, each Party may terminate this Agreement in the event of a material breach, which is not remedied within [thirty (30)] days of written notice from the non-defaulting party of the breach, subject to the applicable statutory requirements.<br><b>37. Force Majeure</b><br>No Party shall be liable for any loss or damage incurred by any other Party arising from any failure to perform its obligations hereunder where such failure arises from strikes, riots, lock-downs, civil commotions, wars, orders or regulation embargoes, fire, earthquake, hostility, governmental interference, pandemics, storms, sabotages, explosions, acts of god or other cause which is beyond the reasonable control of the Parties herein and which could not have been avoided by exercise of due care.<br><b>38. Personal Data Protection Laws</b><br>FHA shall comply with applicable personal data protection laws of Malaysia in receiving and processing the Member / Signatory personal data. The Member / Signatory understands that personal data is required by FHA to provide the Services and comply with laws, regulations, and FHA policies. Notwithstanding anything to the contrary, for purposes of verification, FHA reserves the right to request that the Member / Signatory produces the original of his/her Malaysian Identity Card or Passport or any other documents deemed appropriate by FHA at any time. FHA shall allow the Member/ Signatory reasonable access to such information as is necessary or to the extent required by laws of Malaysia. The Member / Signatory also understands that he/she has the right to have his/her personal data corrected, updated and/or modified from the FHA records which may be subjected to a fee at FHA’s sole discretion. FHA reserves the right to request the Member / Signatory to provide in writing the details of the data that he/she is desirous of correcting, updating, or modifying and the reason(s) for such request. FHA also reserves the right to ask for proof of the identity of the Member/ Requester or any other information or documents prior to making the change. <br><br>Confidential Information means (a) the Software`s source code; (b) Member Data; and (c) each Party’s business or technical information, including but not limited to any information relating to software plans, designs, costs, prices and names, finances, marketing plans, business opportunities, personnel, research, development, or know-how. A Party shall not disclose or use any Confidential Information of the other Party for any purpose outside the scope of this Agreement, except with the other Party’s prior written permission or as required by Law. Each Party agrees to protect the Confidential Information of the other Party in the same manner that it protects its own Confidential Information of like kind (but in no event using less than a reasonable degree of care and reasonable technology industry standards). <br><b>39. Notices, Legal Process and Consent to Disclosure Of Information For Recovery Of Cost For Services</b><br>The Member / Signatory irrevocably agrees that any notice required to be given to the Member /Signatory including legal notices/summons or other processes arising from or in connection with the recovery of the Cost for Services instituted against the Member / Signatory shall be deemed to have been validly served if delivered to the Member / Signatory personally or sent to him/her by prepaid registered post or left at the address of the Member / Signatory as stated herein or at the Member /Signatory last known business or residential address. Any such notice or correspondence sent by post shall be deemed conclusively to have been received by the Member / Signatory within seventy-two (72) hours after the time of posting despite any evidence to the contrary. The Member / Signatory irrevocably agrees that as long as any part of the Cost for Services remains unpaid, the Member / Signatory hereby consents to and authorizes FHA to make use of, disclose, to any parties including credit reporting agencies /credit recovery companies, the auditors, solicitors and other professional advisors of FHA, any third party if required by law, regulation or by-law, court order or other legal process without further reference to the Member / Signatory.<br><b>40. Governing Law &amp; Jurisdiction</b><br>This Subscription Agreement for Service shall be governed in accordance with the laws of Malaysia and the Member/Signatory hereby submits to the exclusive jurisdiction of the courts of Malaysia.<br><b>41. Usage Restrictions</b><br>The Services usage and functional limitations (“Usage Restrictions”) are determined in the Services Description and must be complied with to the fullest by Member and considered when using the Services. Member waives any and all warranty and liability claims and remedies resulting due to Member`s usage of the Services not being in compliance with the Usage Restrictions.<br><b>42. Severability</b><br>If any provision of this Agreement is invalid, illegal or unenforceable in any jurisdiction, such invalidity, illegality or unenforceability shall not affect any other term or provision of this Agreement or invalidate or render unenforceable such term or provision in any other jurisdiction. Upon such determination that any term or other provision is invalid, illegal or unenforceable, the Parties shall negotiate in good faith to modify this Agreement to affect the original intent of the Parties as closely as possible in a mutually acceptable manner in order that the transactions contemplated hereby may be consummated as originally contemplated to the greatest extent possible. As far as this agreement contains gaps, the applicable legal provisions shall be deemed agreed to fill these gaps which the contracting parties would have agreed to in accordance with the economic objectives of the Agreement and its purpose if they had been aware of the gap.<br><b>43. Counterparts</b><br>This Agreement may be executed in any number of counterparts each of which shall for all purposes be deemed an original instrument and all such counterparts together shall constitute the same instrument.<br><b>44. Intellectual Property Rights</b><br>All rights, title and interest in and to this Application and Services (including without limitation all Intellectual Property Rights (as defined herein) therein and all modifications, extensions, customizations, scripts or other derivative works of the Application and Services provided or developed by FHA and anything developed or delivered by or on behalf of FHA under this Agreement are owned exclusively by FHA or its licensors. Except as provided in this Agreement, the rights granted to Member do not convey any rights in the Application, express or implied, or ownership in the Application or any Intellectual Property Rights thereto.<br><b>45. Privacy</b><br>FHA undertakes to maintain and preserve confidentiality of all information and documents which is provided to us in the course of our services to our Members and also to protect the privacy and security of information provided by you. By taking out FHA Membership, you agree to be bound by the terms of the Privacy Policy. The Privacy Policy is available by calling on (1300 88 3422) and asking to speak to Legal Counsel or on the website at <a style=\"display:inline;\" target=\"_blank\" href=\"http://www.firsthealthassist.com/\"><u>www.firsthealthassist.com</u></a>.<br><b>46. Communication</b><br>Except for verbal arrangements after the conclusion of the agreement, no modification, amendment, or waiver of any provision of this Agreement shall be effective, unless being agreed upon in text form (e.g.email, notifications, etc.) or in writing by the Party against whom the modification, amendment or waiver is to be asserted. Transmission by fax, e-mail or any other equivalent form of electronic exchange or execution shall be deemed to comply with such form requirement. The Parties furthermore acknowledge and agree that this Agreement may be executed, exchanged, stored and processed by applying any form simple- or advanced eSignatures (e.g. DocuSign, etc.) and that such eSignatures shall comply with the written form requirement. The Parties agree that they will not challenge the authenticity or correctness for the sole reason of the Agreement being executed in electronic form only. <br><b>47. Contact</b><br>For questions or queries relating to your Membership please email us at <a style=\"display:inline;\" target=\"_blank\" href=\"http://hello@firsthealthassist.com/\"><u>hello@firsthealthassist.com</u></a>.<br><b>48. Dispute Resolution</b><br>Any dispute under or arising out of this Agreement shall first be resolved amicably by way of negotiations in good faith, failing which it shall be determined by the courts of Malaysia.<br><b>49. Complaints</b><br>FHA Membership aims to meet our Members’ expectations with every interaction. We appreciate your time and take all feedback seriously.', '2025-06-02 14:35:10', '2025-06-06 13:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reg_id` int(100) DEFAULT NULL,
  `driver_id` int(250) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `device_token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `is_active` int(100) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `first_login` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int(250) NOT NULL,
  `user_id` int(100) DEFAULT NULL,
  `subscription_id` int(100) DEFAULT NULL,
  `referral_no` varchar(250) DEFAULT NULL,
  `referral_id` int(100) DEFAULT NULL,
  `is_accepted` tinyint(1) NOT NULL DEFAULT 1,
  `is_qualifying_period` int(100) NOT NULL DEFAULT 1,
  `count` int(100) DEFAULT 3,
  `adult_count` int(100) NOT NULL DEFAULT 0,
  `senior_count` int(100) NOT NULL DEFAULT 0,
  `child_count` int(100) NOT NULL DEFAULT 0,
  `slot_count` int(100) NOT NULL DEFAULT 0,
  `free_plan` tinyint(1) NOT NULL DEFAULT 0,
  `is_dependent` tinyint(1) NOT NULL DEFAULT 0,
  `is_read` int(100) NOT NULL DEFAULT 0,
  `is_manual` int(100) NOT NULL DEFAULT 0,
  `is_plan_expired` tinyint(1) NOT NULL DEFAULT 0,
  `plan_times` int(100) NOT NULL DEFAULT 1,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `is_paid` int(100) NOT NULL DEFAULT 0,
  `is_renewed` tinyint(4) NOT NULL DEFAULT 0,
  `amount` int(250) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `type_dependant` varchar(100) DEFAULT NULL,
  `reg_id` int(11) DEFAULT NULL,
  `t_emergency_calls` int(100) NOT NULL DEFAULT 2,
  `r_emergency_calls` int(100) NOT NULL DEFAULT 2,
  `t_clinic_calls` int(100) NOT NULL DEFAULT 2,
  `r_clinic_calls` int(100) NOT NULL DEFAULT 2,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `zoho_record_id` varchar(250) DEFAULT NULL,
  `is_active` int(10) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_name` varchar(250) NOT NULL,
  `vehicle_number` varchar(255) NOT NULL,
  `hospital_id` int(100) DEFAULT NULL,
  `hospital_name` varchar(250) DEFAULT NULL,
  `ambulance_life_support` varchar(250) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `action_logs`
--
ALTER TABLE `action_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_masters`
--
ALTER TABLE `activity_masters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ambulances`
--
ALTER TABLE `ambulances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `zoho_record_id` (`zoho_record_id`);

--
-- Indexes for table `benefit_masters`
--
ALTER TABLE `benefit_masters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dependants`
--
ALTER TABLE `dependants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `driver_declined_reason`
--
ALTER TABLE `driver_declined_reason`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invite_users`
--
ALTER TABLE `invite_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_users`
--
ALTER TABLE `notification_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_auth_codes_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`);

--
-- Indexes for table `paramedics`
--
ALTER TABLE `paramedics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_user_id_foreign` (`user_id`),
  ADD KEY `payments_subscription_id_foreign` (`subscription_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `purchase_slots`
--
ALTER TABLE `purchase_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `renewal_records`
--
ALTER TABLE `renewal_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `renewal_records_user_id_foreign` (`user_id`);

--
-- Indexes for table `roaster_mapping`
--
ALTER TABLE `roaster_mapping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_benefits`
--
ALTER TABLE `subscription_benefits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_benefits_subscription_id_foreign` (`subscription_id`),
  ADD KEY `subscription_benefits_benefit_id_foreign` (`benefit_id`);

--
-- Indexes for table `subscription_masters`
--
ALTER TABLE `subscription_masters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `telescope_entries`
--
ALTER TABLE `telescope_entries`
  ADD PRIMARY KEY (`sequence`),
  ADD UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  ADD KEY `telescope_entries_batch_id_index` (`batch_id`),
  ADD KEY `telescope_entries_family_hash_index` (`family_hash`),
  ADD KEY `telescope_entries_created_at_index` (`created_at`),
  ADD KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`);

--
-- Indexes for table `telescope_entries_tags`
--
ALTER TABLE `telescope_entries_tags`
  ADD PRIMARY KEY (`entry_uuid`,`tag`),
  ADD KEY `telescope_entries_tags_tag_index` (`tag`);

--
-- Indexes for table `telescope_monitoring`
--
ALTER TABLE `telescope_monitoring`
  ADD PRIMARY KEY (`tag`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trips_trip_id_unique` (`trip_id`),
  ADD KEY `trips_user_id_foreign` (`user_id`);

--
-- Indexes for table `trip_status_logs`
--
ALTER TABLE `trip_status_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `t_c_s`
--
ALTER TABLE `t_c_s`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicles_vehicle_number_unique` (`vehicle_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `action_logs`
--
ALTER TABLE `action_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_masters`
--
ALTER TABLE `activity_masters`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ambulances`
--
ALTER TABLE `ambulances`
  MODIFY `id` int(250) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `benefit_masters`
--
ALTER TABLE `benefit_masters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `dependants`
--
ALTER TABLE `dependants`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_declined_reason`
--
ALTER TABLE `driver_declined_reason`
  MODIFY `id` int(250) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT for table `invite_users`
--
ALTER TABLE `invite_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(250) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_users`
--
ALTER TABLE `notification_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paramedics`
--
ALTER TABLE `paramedics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_slots`
--
ALTER TABLE `purchase_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `renewal_records`
--
ALTER TABLE `renewal_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roaster_mapping`
--
ALTER TABLE `roaster_mapping`
  MODIFY `id` int(200) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_benefits`
--
ALTER TABLE `subscription_benefits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=500;

--
-- AUTO_INCREMENT for table `subscription_masters`
--
ALTER TABLE `subscription_masters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT for table `telescope_entries`
--
ALTER TABLE `telescope_entries`
  MODIFY `sequence` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_status_logs`
--
ALTER TABLE `trip_status_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_c_s`
--
ALTER TABLE `t_c_s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int(250) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription_masters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_benefits`
--
ALTER TABLE `subscription_benefits`
  ADD CONSTRAINT `subscription_benefits_benefit_id_foreign` FOREIGN KEY (`benefit_id`) REFERENCES `benefit_masters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_benefits_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscription_masters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `telescope_entries_tags`
--
ALTER TABLE `telescope_entries_tags`
  ADD CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE;

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
