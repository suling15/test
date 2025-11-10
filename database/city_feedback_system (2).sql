-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 12:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `city_feedback_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `create_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `create_at`) VALUES
(1, 'admin', '$2y$10$rZ5GGie56ylY1DLUrb9mM.dS3//yTUpmNQhONrPHfqFACiYTOqGPa', 'admin@gmail.com', '2025-07-14 14:01:01'),
(2, 'admin1', '$2y$10$nUlqGYvywKeldr5hWFqVTOeh3zYwWDHzoQCuEzkC84KMroMzTpIma', 'admin@gmail.con', '2025-08-14 07:02:45');

-- --------------------------------------------------------

--
-- Table structure for table `citizen`
--

CREATE TABLE `citizen` (
  `id` int(11) NOT NULL,
  `username` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL,
  `create_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `citizen`
--

INSERT INTO `citizen` (`id`, `username`, `password`, `create_at`, `status`, `updated_at`) VALUES
(2, 'sulo', '$2y$10$R7dTLHeBsE4xVEsZNezX2.g.U2RyZloy/pwdrjGPvAmV7EP2YbqVq', '2025-07-15 08:53:02', 'approved', '2025-10-21 15:34:01'),
(7, 'laiza', '$2y$10$pTaROY0BGvURIHzcLJBIUObBjRC.tb48OL4guMcO9zW873iiai/PS', '2025-07-15 09:20:11', 'approved', '2025-10-14 22:18:17'),
(24, 'test', '$2y$10$Hq08guifSM86i1T48/lynOE6Mh0UPiJaaz39ECqCIRqaPKu35iVxS', '2025-10-24 08:59:00', 'pending', NULL),
(25, 'sample', '$2y$10$fgZ1CyreZryFiMvNEJKJkuFZRbqQe213XtqtzFd0YFQ0v52LFrVJW', '2025-10-24 09:06:01', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_response`
--

CREATE TABLE `feedback_response` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `response_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_response`
--

INSERT INTO `feedback_response` (`id`, `feedback_id`, `staff_id`, `response_text`, `created_at`, `updated_at`) VALUES
(2, 29, 8, 'test1', '2025-09-22 11:55:01', '2025-09-22 11:55:01');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `citizen_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `user_type` enum('citizen','staff') NOT NULL,
  `session_token` varchar(100) NOT NULL,
  `login_status` enum('active','inactive') DEFAULT 'active',
  `login_time` timestamp NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `citizen_id`, `staff_id`, `user_type`, `session_token`, `login_status`, `login_time`, `logout_time`, `ip_address`, `device_info`) VALUES
(102, 7, NULL, 'citizen', 'f9fbbb1aabda7366faaec122bd3af96fdfe9040f4d082e10edab42fb07950256', 'inactive', '2025-10-18 08:00:58', '2025-10-18 09:02:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(103, NULL, 7, 'staff', '5178ca6fb7548a686ef158547abf4c7c404f538428a2b1095d6ddfcd08d7f6cf', 'inactive', '2025-10-19 09:05:57', '2025-10-19 09:48:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(104, NULL, 7, 'staff', '97f0583a9b59a32b6f6d0fc1a699d881df28dfbe469d1d31ff66aa7a396c79bf', 'inactive', '2025-10-19 09:49:59', '2025-10-19 10:36:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(105, NULL, 8, 'staff', 'cd27ecbeeccb31365f056f82cf630f9e4f5bfc28e04275dbc73c887502310b8b', 'inactive', '2025-10-19 10:36:36', '2025-10-19 10:39:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(106, NULL, 8, 'staff', 'cbbfb233aefcc371882e381aab9170364686d4ce595119b58c1baf8ffd4f2c2c', 'inactive', '2025-10-19 10:39:27', '2025-10-19 11:53:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(107, NULL, 7, 'staff', 'ca722e397a09725ce8f8f4263a4aff25bb0ae12cfd9a36a48aede3ff4f249008', 'inactive', '2025-10-19 10:45:19', '2025-10-19 10:47:38', '192.168.1.38', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36'),
(108, NULL, 7, 'staff', 'bb2b0b6fb096e929eefa195b7a55920d8786da0de2a9d86efa719902784babe3', 'active', '2025-10-19 10:53:00', NULL, '192.168.1.38', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36'),
(109, 7, NULL, 'citizen', 'bdc214a21e849d417587fbdcb6ad6fcd3b48f66d007f8275fd950dda37ac361b', 'inactive', '2025-10-20 02:42:43', '2025-10-20 02:44:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(110, NULL, 7, 'staff', 'c0cbb57635bc2ffc78dc8b37a4c74bf3cfb09bc10e23e2c1de7d0ccd7ee3faff', 'inactive', '2025-10-20 02:44:53', '2025-10-20 02:45:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(111, NULL, 8, 'staff', 'c0a27bccab79be099c980634d172770850fb4dfa9c4145d8a507617a32975107', 'active', '2025-10-20 02:45:07', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(112, NULL, 7, 'staff', '8b4114ef4dc9c55fcafea8bfa8d88b8e9d5b80bfc7367d40430d214f5333ace2', 'inactive', '2025-10-20 02:46:41', '2025-10-20 02:47:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(113, NULL, 7, 'staff', '4e12ff44c65eebb49006e63a8c7f6c2360c54d7807959aeb3f85b3bb70f10399', 'inactive', '2025-10-20 02:48:31', '2025-10-20 02:53:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(114, NULL, 8, 'staff', '27afe63a2af55182d371fccc54b078eb862bccf939bb773cf7ca0c6f876c4c41', 'inactive', '2025-10-20 02:53:47', '2025-10-20 02:54:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(115, NULL, 7, 'staff', 'aa137525b691c790a40b82184d96872b8063462bd5cc5ed1feb51d6a4368f493', 'inactive', '2025-10-20 03:47:19', '2025-10-20 03:56:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(116, 7, NULL, 'citizen', '253d3637525e5846c453f5fc90d019a187091484ae2fb0cf304bdf6802b02e93', 'inactive', '2025-10-20 03:56:37', '2025-10-20 03:59:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(117, NULL, 7, 'staff', '48bf2f1dcaae79224177aecba202745dce886ac6ae139a67735d22b8e4ec32d1', 'inactive', '2025-10-20 04:00:01', '2025-10-20 04:01:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(118, 7, NULL, 'citizen', '95d95292932cf679d75c55114039f4eb68f4d75aeb7492d9296646054b126458', 'inactive', '2025-10-20 04:02:14', '2025-10-20 04:04:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(119, NULL, 7, 'staff', '1029a88bd646c1b19245843cdaeaa70d66a42c70d05f8cedacd22369eca63c8c', 'inactive', '2025-10-20 04:04:34', '2025-10-20 04:05:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(120, NULL, 7, 'staff', 'd1c733f17a5ce7ecf04bd4c7058385459ab1b7995dfc90d771c98b23dbf937e5', 'inactive', '2025-10-21 05:53:55', '2025-10-21 06:16:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(121, 2, NULL, 'citizen', '1dd3ddacba99b9e2ea04ef51b092f5b90fd7a60a2e9fcef9c304d3ed085fff48', 'inactive', '2025-10-21 07:34:15', '2025-10-21 07:34:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(122, NULL, 7, 'staff', 'd1d97156cd3bb30e170872241f8e0ee52f72e976258cb888deedec308928840c', 'inactive', '2025-10-22 05:57:13', '2025-10-22 06:00:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(123, 7, NULL, 'citizen', '5c72edae137efb073343648448a477bc1c0fe03da490a32411741dae418810cd', 'inactive', '2025-10-22 06:00:18', '2025-10-22 06:07:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(124, NULL, NULL, 'citizen', '755cdaa59ae882a4e0db1653bcd1ccd50b5379e57a060def14a4b8c14e35990b', 'inactive', '2025-10-22 08:16:28', '2025-10-22 08:19:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(125, NULL, 7, 'staff', '6430081c3b4c02202f6b6f69a5d576af02c27e3f089f12326a5bcddecf785dc5', 'active', '2025-10-22 08:22:57', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(126, NULL, 7, 'staff', '5bf7150670b3eca309fcca315438a5c678b4442a37abf340134fa2682bd2e03d', 'active', '2025-10-23 02:49:35', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(127, NULL, 7, 'staff', '1976081f3532b07592df574c8513e49512aa053ceaa577611884b0b8649934ff', 'active', '2025-10-23 09:51:47', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(128, NULL, 7, 'staff', 'de13fff8e66acc27a01dc62873e82f02b6a3f3a37dc3b8e3a7b011772edfdd22', 'active', '2025-10-23 09:52:09', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(129, NULL, 7, 'staff', 'c10128f5e67c4414545b01be96c6a4d4dad40498afab7a7a06d2187fbfa8a396', 'active', '2025-10-23 09:52:22', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(130, NULL, 7, 'staff', 'c9131ce3d52a64f268eb41ace52feb509b46b564dbc8128a8bd21cc5abd5d126', 'inactive', '2025-10-23 09:53:02', '2025-10-23 10:18:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(131, NULL, 7, 'staff', 'ba675a2bbf7189e88caacbecc3c4b43ca5518f3296c1233869a9bc8207dca8f1', 'inactive', '2025-10-23 10:28:06', '2025-10-23 10:50:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(132, NULL, 8, 'staff', '5985085c1036993d077d101da94141b5b83f4e3c7a9734f76518566e6db4ba0d', 'inactive', '2025-10-23 10:50:29', '2025-10-23 10:51:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(133, NULL, 7, 'staff', '1ae8e4ae0771dfa65c44d22e065816730cd75fedf77ae009243714ae362d2a2c', 'inactive', '2025-10-23 10:51:11', '2025-10-23 11:39:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(134, NULL, 8, 'staff', '2f3be7fc34a774a11659ff77bac7d44fae241d44d3e043c19ecabd0089304896', 'inactive', '2025-10-23 11:39:44', '2025-10-23 11:42:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(135, NULL, 7, 'staff', 'f4c053c7dd710ab02de18d81622561d576af14382318975c4a826d35de338772', 'inactive', '2025-10-24 05:20:17', '2025-10-24 06:00:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(136, NULL, 7, 'staff', 'db21049e6e08aa79b4006a6881789cad9832a04565eb6535b2fdf06f763a3e70', 'inactive', '2025-10-24 06:42:07', '2025-10-24 06:51:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(137, NULL, 7, 'staff', 'f37e53154385d5cdca13ae640b8e2933dec5c30767dbf82a79fa4c3d0de91c29', 'inactive', '2025-10-24 07:08:19', '2025-10-24 08:04:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(138, NULL, 7, 'staff', 'd07465d2e5c98aa4a221d2a6c95f95ebcc5862835b5e5f44f40f882f393a0757', 'inactive', '2025-10-24 08:11:38', '2025-10-24 08:11:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(139, 7, NULL, 'citizen', 'd5aec3c3f1125b373cdd6d834162830376850617c6fbd7da385142564fef2dfc', 'inactive', '2025-10-24 08:18:59', '2025-10-24 08:38:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(140, 7, NULL, 'citizen', '446c640dd79e9f20b17a5693974c777935c8f88025f5c2cc9381e8fb0bdfa594', 'inactive', '2025-10-24 10:05:29', '2025-10-24 10:33:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(141, 7, NULL, 'citizen', '34e634782856effe109dc10aea2685a4c560131841479e79523a22e87f499a5d', 'inactive', '2025-10-24 10:34:28', '2025-10-24 10:34:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `id` int(11) NOT NULL,
  `citizen_id` int(11) DEFAULT NULL,
  `firstname` varchar(250) DEFAULT NULL,
  `middlename` varchar(250) DEFAULT NULL,
  `lastname` varchar(250) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `birthday` date NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced','Separated') NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(13) DEFAULT NULL,
  `image` varchar(250) DEFAULT NULL,
  `valid_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`id`, `citizen_id`, `firstname`, `middlename`, `lastname`, `gender`, `birthday`, `civil_status`, `address`, `contact_number`, `image`, `valid_id`, `created_at`) VALUES
(1, 2, 'Sulo', '', 'Cañoso', 'Male', '2025-07-08', 'Single', 'Purok malinawon2 brgy, balintawak, escalante city, negros Occidental\r\nBrgy, balintawak, Escalante city', '09773661432', NULL, '0', '2025-07-15 08:53:02'),
(4, 7, 'Laiza', 'R', 'Limbaga', 'Female', '2025-07-15', 'Single', 'Escalante city', '098765454', '68b04e22e8716.jpg', 'valid_id_7_68c5044880b7b.jpeg', '2025-07-15 09:20:11'),
(16, 24, 'Ameva Ameve', 's', 'Cañoso', 'Male', '2025-10-24', 'Married', 'Marlea Cafe N. Bacalso Avenue', '09207961295', NULL, '../uploads/valid_id/68fb3fd464659_1761296340.png', '2025-10-24 08:59:00'),
(17, 25, 'Ameva Ameve', 'Sinangote', 'Cañoso', 'Male', '2025-10-24', 'Single', 'Purok Malinawon II', '9876543', NULL, '../uploads/valid_id/68fb417905d55_1761296761.png', '2025-10-24 09:06:01');

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(250) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`id`, `name`, `description`, `contact_number`, `location`, `image`, `create_at`, `updated_at`) VALUES
(2, 'Cadiz City Disaster Risk Reduction & Manangement Office', 'test', '', '', 'service_68f9fa5935cae.png', '2025-08-13 14:06:12', '2025-10-23 09:50:17'),
(3, 'Cadiz City Bureau of Fire Protection Office', 'test1', NULL, NULL, '../uploads/services_image/service_68b2c18c1d2202.42291670.jpg', '2025-08-14 04:27:43', '2025-09-22 11:34:09'),
(4, 'Cadiz City Tourism Office', 'VISION: “A one-stop Tourist Destination in Northern Negros”.\r\n\r\nMISSION: ”Transformation of Cadiz City through a Responsible Implementation of Integrated Sustainable Development Program”.', NULL, NULL, '../uploads/services_image/service_68b2c09e88d258.78373630.jpeg', '2025-08-19 07:46:06', '2025-09-22 11:34:09'),
(5, 'Cadiz City Social Welfare and Development Office', '(CCSWDO):\r\nOpen: Monday, Tuesday, Wednesday, Thursday, Friday\r\n8:00 AM - 5:00 PM\r\nCLOSED: Saturday, Sunday', '09207961295', 'City Hall 2, Cabahug Street, Barangay Zone 3, Cadiz City, Philippines', '../uploads/services_image/service_68aff5fb8ff973.95156812.jpg', '2025-08-28 06:23:55', '2025-10-07 11:21:43');

-- --------------------------------------------------------

--
-- Table structure for table `service_feedback`
--

CREATE TABLE `service_feedback` (
  `id` int(11) NOT NULL,
  `citizen_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_offer_id` int(11) DEFAULT NULL,
  `CC1` enum('I know what a CC is and I saw this office''s CC','I know what a CC is but I did NOT saw this office''s CC','I learned of the CC only when I saw this office''s CC','I do not know what a CC is and I did not see one in this office') DEFAULT NULL,
  `CC2` enum('Easy to see','Somewhat easy to see','Difficult to see','Not visible at all','N/A') DEFAULT NULL,
  `CC3` enum('Helped very much','Somewhat helped','Did not help','N/A') DEFAULT NULL,
  `SQD0` tinyint(5) DEFAULT NULL,
  `SQD1` tinyint(5) DEFAULT NULL,
  `SQD2` tinyint(5) DEFAULT NULL,
  `SQD3` tinyint(5) DEFAULT NULL,
  `SQD4` tinyint(5) DEFAULT NULL,
  `SQD5` tinyint(5) DEFAULT NULL,
  `SQD6` tinyint(5) DEFAULT NULL,
  `SQD7` tinyint(5) DEFAULT NULL,
  `SQD8` tinyint(5) DEFAULT NULL,
  `feedback_text` text NOT NULL,
  `rating` tinyint(1) DEFAULT 5,
  `sentiment` varchar(250) DEFAULT NULL,
  `sentiment_scores` text DEFAULT NULL,
  `create` timestamp NULL DEFAULT current_timestamp(),
  `is_anonymous` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_feedback`
--

INSERT INTO `service_feedback` (`id`, `citizen_id`, `service_id`, `service_offer_id`, `CC1`, `CC2`, `CC3`, `SQD0`, `SQD1`, `SQD2`, `SQD3`, `SQD4`, `SQD5`, `SQD6`, `SQD7`, `SQD8`, `feedback_text`, `rating`, `sentiment`, `sentiment_scores`, `create`, `is_anonymous`) VALUES
(28, 7, 5, 18, 'I know what a CC is and I saw this office\'s CC', 'Easy to see', 'Helped very much', 4, 4, 4, 4, 4, 4, 4, 4, 4, 'ang ganda ang serberyo ninyo sana magpatuloy ang ganitong serberyo', 4, 'neutral', '{\"negative\":0.10557125508785248,\"neutral\":0.7518535852432251,\"positive\":0.14257515966892242}', '2025-09-13 10:08:57', 0),
(29, 7, 5, 19, 'I know what a CC is and I saw this office\'s CC', 'Easy to see', 'Helped very much', 4, 4, 4, 4, 4, 4, 4, 4, 4, 'The service was outstanding!\" or \"I felt taken care of,\" which praise efficiency, quality, and going \"above and beyond', 4, 'positive', '{\"negative\":0.006723988801240921,\"neutral\":0.01851748675107956,\"positive\":0.974758505821228}', '2025-09-13 10:14:58', 0),
(39, 7, 5, 12, 'I know what a CC is but I did NOT saw this office\'s CC', 'N/A', 'N/A', 3, 3, 2, 5, 4, 3, 4, 3, 4, 'Maayo man pero may ara pa sang pwede mapaayo.', 3, 'negative', '{\"negative\":0.7220000410415239,\"neutral\":0.06252494559199458,\"positive\":0.2154750133664815}', '2025-09-29 04:18:11', 0),
(40, 7, 5, 12, 'I know what a CC is but I did NOT saw this office\'s CC', 'N/A', 'N/A', 3, 3, 2, 5, 4, 3, 4, 3, 4, 'Maayo man pero may ara pa sang pwede mapaayo.', 3, 'negative', '{\"negative\":0.7220000410415239,\"neutral\":0.06252494559199458,\"positive\":0.2154750133664815}', '2025-09-29 04:18:11', 0),
(63, 7, 5, 12, 'I know what a CC is but I did NOT saw this office\'s CC', 'Difficult to see', 'N/A', 4, 4, 3, 3, 3, 3, 3, 3, 3, 'kaulolowaw na serbesyo', 3, 'neutral', '{\"negative\":0.40519957999139244,\"neutral\":0.4969520959435421,\"positive\":0.09784832406506545}', '2025-10-18 08:41:41', 1),
(64, 7, 5, 12, 'I know what a CC is but I did NOT saw this office\'s CC', 'Not visible at all', 'Did not help', 2, 2, 2, 2, 2, 2, 2, 2, 2, 'ka nice sa service', 4, 'negative', '{\"negative\":0.46196036210833363,\"neutral\":0.11022240920860726,\"positive\":0.4278172286830591}', '2025-10-20 02:44:19', 1),
(70, 7, 5, 19, 'I know what a CC is but I did NOT saw this office\'s CC', 'Somewhat easy to see', 'Somewhat helped', 2, 2, 2, 2, 2, 2, 2, 2, 2, 'ka maot sa serbesyo', 2, 'negative', '{\"negative\":0.7643419022015021,\"neutral\":0.1459248832751048,\"positive\":0.08973321452339313}', '2025-10-24 10:31:39', 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_offer`
--

CREATE TABLE `service_offer` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `offer_name` varchar(150) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_offer`
--

INSERT INTO `service_offer` (`id`, `service_id`, `offer_name`, `price`, `created_at`, `updated_at`) VALUES
(5, 5, 'Application for emergency assistant program (EAP) - Pelty Cash.', 0.00, '2025-09-02 08:32:47', '2025-09-22 11:34:10'),
(6, 5, 'Application for emergency assistant program (EAP) -regular processing. ', 0.00, '2025-09-02 08:33:00', '2025-09-22 11:34:10'),
(7, 5, 'Request for social case study summary.', 0.00, '2025-09-02 08:33:10', '2025-09-22 11:34:10'),
(8, 5, 'Application for registration and granting of penalty and recognition of public and private child development centers learning centers offering early c', 0.00, '2025-09-02 08:33:25', '2025-09-22 11:34:10'),
(9, 5, 'Application for solo parents identification (SPIC).', 0.00, '2025-09-02 08:33:37', '2025-09-22 11:34:10'),
(10, 5, 'Request for certification referral.', 0.00, '2025-09-02 08:33:48', '2025-09-22 11:34:10'),
(11, 5, 'Request or referral for counseling services. ', 0.00, '2025-09-02 08:33:56', '2025-09-22 11:34:10'),
(12, 5, 'Disaster relief services', 0.00, '2025-09-02 08:34:14', '2025-09-22 11:34:10'),
(13, 5, 'Applying for self employee assistant program Availment of the Provision Stipulated in R.A 10630.', 0.00, '2025-09-02 08:34:22', '2025-09-22 11:34:10'),
(14, 5, 'Comprehensive juvenile justice and welfare system.', 0.00, '2025-09-02 08:34:37', '2025-09-22 11:34:10'),
(15, 5, 'Renewal of solo parents identification valid for 1 year. ', 0.00, '2025-09-02 08:34:47', '2025-09-22 11:34:10'),
(16, 5, 'Application for FSCAP membership and OSCA identification card.', 0.00, '2025-09-02 08:34:55', '2025-09-22 11:34:10'),
(17, 5, 'For solo parent with disability identification card. ', 0.00, '2025-09-02 08:35:11', '2025-09-22 11:34:10'),
(18, 5, 'Availment of livelihood capital assistance.', 0.00, '2025-09-02 08:35:16', '2025-09-22 11:34:10'),
(19, 5, 'Application for minors traveling abroad. ', 0.00, '2025-09-02 08:35:26', '2025-09-22 11:34:10'),
(20, 5, 'Application for alternative family care.', 0.00, '2025-09-02 08:35:36', '2025-09-22 11:34:10'),
(22, 5, 'test2', 0.00, '2025-09-22 11:20:25', '2025-09-22 11:49:46'),
(23, 3, 'test', 0.00, '2025-09-29 04:32:51', '2025-09-29 04:32:51');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `username` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `create_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `username`, `password`, `email`, `create_at`) VALUES
(1, 'staff', '$2y$10$DWY.piXWn2Uv1Wy.JHmAUOAIsCoLpNnRssD.ktoTh8YrxXoDFO1V.', '', '2025-07-15 08:01:39'),
(7, 'asa', '$2y$10$0mrtGYbgU6tAZYnk7FB4w.jfrMFhMZq0SBwWRWnOLNZjhulGbChZi', '', '2025-08-12 07:35:12'),
(8, 'maru', '$2y$10$9YuiHuZQp3KCafZV5c6dKO7Jyr3rdjUypxSdAKBu8CxN5pzbovldC', NULL, '2025-08-13 09:28:14'),
(9, 'test', '$2y$10$9t1lYcdSaVQM0AZKV/7Uv.dOl4XRKvIJrGS/L0o46XmvqpgpXD8QS', NULL, '2025-09-01 10:22:09');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profile`
--

CREATE TABLE `staff_profile` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `firstname` varchar(250) DEFAULT NULL,
  `middlename` varchar(250) DEFAULT NULL,
  `lastname` varchar(250) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `birthday` date NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(13) DEFAULT NULL,
  `image` varchar(250) DEFAULT NULL,
  `valid_id` varchar(250) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profile`
--

INSERT INTO `staff_profile` (`id`, `staff_id`, `firstname`, `middlename`, `lastname`, `gender`, `birthday`, `address`, `contact_number`, `image`, `valid_id`, `created_at`) VALUES
(1, 1, 'sulo', 's', 'canoso', 'Male', '2002-10-15', 'cadiz city', '096543212', NULL, NULL, '2025-08-12 05:47:27'),
(2, 7, 'Ameva Ameve', 's', 'Cañoso', 'Female', '2025-08-12', 'Marlea Cafe N. Bacalso Avenue', '09207961295', '1754984112_avatar-profile-icon-in-flat-style-female-user-profile-illustration-on-isolated-background-women-profile-sign-business-concept-vector.jpg', '1756381675_7be1e7e0-0fa1-4e76-a072-e031cdfd1b49.jpeg', '2025-08-12 07:35:12'),
(3, 8, 'mark', 'o', 'maru', 'Male', '2005-01-04', 'cadiz city', '09448986765', NULL, '68b0521b36cac.jpg', '2025-08-13 09:28:14');

-- --------------------------------------------------------

--
-- Table structure for table `staff_service`
--

CREATE TABLE `staff_service` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_service`
--

INSERT INTO `staff_service` (`id`, `staff_id`, `service_id`, `assigned_at`) VALUES
(89, 1, 2, '2025-09-18 09:43:48'),
(109, 8, 5, '2025-09-18 10:08:09'),
(139, 9, 3, '2025-09-18 10:29:37'),
(140, 9, 2, '2025-09-18 10:29:37'),
(151, 7, 5, '2025-10-24 08:11:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `citizen`
--
ALTER TABLE `citizen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `feedback_response`
--
ALTER TABLE `feedback_response`
  ADD PRIMARY KEY (`id`),
  ADD KEY `feedback_id` (`feedback_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `citizen_id` (`citizen_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `citizen_id` (`citizen_id`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_feedback`
--
ALTER TABLE `service_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `citizen_id` (`citizen_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `service_offer_id` (`service_offer_id`);

--
-- Indexes for table `service_offer`
--
ALTER TABLE `service_offer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `staff_profile`
--
ALTER TABLE `staff_profile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff_service`
--
ALTER TABLE `staff_service`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`staff_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `citizen`
--
ALTER TABLE `citizen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `feedback_response`
--
ALTER TABLE `feedback_response`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `profile`
--
ALTER TABLE `profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_feedback`
--
ALTER TABLE `service_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `service_offer`
--
ALTER TABLE `service_offer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `staff_profile`
--
ALTER TABLE `staff_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff_service`
--
ALTER TABLE `staff_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback_response`
--
ALTER TABLE `feedback_response`
  ADD CONSTRAINT `feedback_response_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `service_feedback` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_response_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`citizen_id`) REFERENCES `citizen` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`citizen_id`) REFERENCES `citizen` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_feedback`
--
ALTER TABLE `service_feedback`
  ADD CONSTRAINT `fk_feedback_offer` FOREIGN KEY (`service_offer_id`) REFERENCES `service_offer` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_feedback_ibfk_1` FOREIGN KEY (`citizen_id`) REFERENCES `citizen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_feedback_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_offer`
--
ALTER TABLE `service_offer`
  ADD CONSTRAINT `fk_service_offer` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_profile`
--
ALTER TABLE `staff_profile`
  ADD CONSTRAINT `staff_profile_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_service`
--
ALTER TABLE `staff_service`
  ADD CONSTRAINT `staff_service_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_service_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
