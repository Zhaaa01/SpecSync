-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 04 Jun 2026 pada 11.04
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `specsync`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','editor') DEFAULT 'editor',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `last_login`, `created_at`, `is_active`, `created_by`) VALUES
(4, 'Super Admin', 'admin@specsync.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', NULL, '2026-06-04 07:46:53', 1, NULL),
(5, 'Super Admin', 'admin@gmail.com', '$2y$10$6ycxE5JyyoVLrXQV0jBkWeynvgKmTAb2FS0VbWltqxrB0X9DJ1r7u', 'superadmin', '2026-06-04 08:01:12', '2026-06-04 08:01:01', 1, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `release_year` int(11) DEFAULT NULL,
  `category` enum('flagship','midrange','budget','gaming') DEFAULT 'midrange',
  `screen_size` decimal(4,2) DEFAULT NULL,
  `resolution` varchar(50) DEFAULT NULL,
  `refresh_rate` int(11) DEFAULT NULL,
  `display_type` varchar(50) DEFAULT NULL,
  `chipset` varchar(100) DEFAULT NULL,
  `cpu_cores` int(11) DEFAULT NULL,
  `gpu` varchar(100) DEFAULT NULL,
  `ram` int(11) DEFAULT NULL,
  `storage` int(11) DEFAULT NULL,
  `main_camera` int(11) DEFAULT NULL,
  `front_camera` int(11) DEFAULT NULL,
  `camera_features` text DEFAULT NULL,
  `battery` int(11) DEFAULT NULL,
  `charging_speed` int(11) DEFAULT NULL,
  `has_wireless_charging` tinyint(1) DEFAULT 0,
  `weight` int(11) DEFAULT NULL,
  `thickness` decimal(4,2) DEFAULT NULL,
  `width` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `color_options` text DEFAULT NULL,
  `network_5g` tinyint(1) DEFAULT 0,
  `wifi_version` varchar(20) DEFAULT NULL,
  `bluetooth_version` varchar(10) DEFAULT NULL,
  `nfc` tinyint(1) DEFAULT 0,
  `os` varchar(50) DEFAULT NULL,
  `os_version` varchar(20) DEFAULT NULL,
  `score_camera` decimal(4,1) DEFAULT NULL,
  `score_performance` decimal(4,1) DEFAULT NULL,
  `score_battery` decimal(4,1) DEFAULT NULL,
  `score_design` decimal(4,1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `devices`
--

INSERT INTO `devices` (`id`, `name`, `brand`, `slug`, `price`, `image`, `release_year`, `category`, `screen_size`, `resolution`, `refresh_rate`, `display_type`, `chipset`, `cpu_cores`, `gpu`, `ram`, `storage`, `main_camera`, `front_camera`, `camera_features`, `battery`, `charging_speed`, `has_wireless_charging`, `weight`, `thickness`, `width`, `height`, `color_options`, `network_5g`, `wifi_version`, `bluetooth_version`, `nfc`, `os`, `os_version`, `score_camera`, `score_performance`, `score_battery`, `score_design`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'Samsung Galaxy S25 Ultra', 'Samsung', 'samsung-galaxy-s25-ultra', 19999000, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s25-ultra-sm-s938.jpg', 2025, 'flagship', 6.90, '3088x1440', 120, 'Dynamic AMOLED 2X', 'Snapdragon 8 Elite', 8, 'Adreno 830', 12, 256, 200, 12, '', 5000, 45, 1, 218, 8.20, 77.20, 162.80, '', 1, 'Wi-Fi 7', '5.4', 1, 'Android', '15', 9.7, 9.8, 8.5, 9.2, '2026-06-03 21:40:39', '2026-06-04 08:15:05', 1),
(2, 'Apple iPhone 16 Pro Max', 'Apple', 'apple-iphone-16-pro-max', 22999000, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-16-pro-max.jpg', 2024, 'flagship', 6.90, '2868x1320', 120, 'Super Retina XDR OLED', 'Apple A18 Pro', 6, 'Apple GPU 6-core', 8, 256, 48, 12, NULL, 4685, 30, 1, 227, 8.25, 77.60, 163.00, NULL, 1, 'Wi-Fi 7', '5.3', 1, 'iOS', '18', 9.9, 9.9, 8.2, 9.5, '2026-06-03 21:40:39', '2026-06-03 21:40:39', 1),
(3, 'Xiaomi 14 Ultra', 'Xiaomi', 'xiaomi-14-ultra', 16999000, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-14-ultra-new.jpg', 2024, 'flagship', 6.73, '3200x1440', 120, 'LTPO AMOLED', 'Snapdragon 8 Gen 3', 8, 'Adreno 750', 16, 512, 50, 32, '', 5000, 90, 1, 229, 9.35, 75.30, 161.40, '', 1, 'Wi-Fi 7', '5.4', 1, 'Android', '14', 9.8, 9.6, 9.0, 8.8, '2026-06-03 21:40:39', '2026-06-04 08:14:11', 1),
(4, 'Google Pixel 9 Pro', 'Google', 'google-pixel-9-pro', 15499000, 'https://fdn2.gsmarena.com/vv/bigpic/google-pixel-9-pro-.jpg', 2024, 'flagship', 6.30, '2992x1344', 120, 'LTPO OLED', 'Google Tensor G4', 9, 'Imagination DXT-48-1536', 16, 256, 50, 42, '', 4700, 37, 1, 199, 8.50, 72.00, 152.90, '', 1, 'Wi-Fi 7', '5.3', 1, 'Android', '15', 9.6, 8.8, 8.3, 9.0, '2026-06-03 21:40:39', '2026-06-04 08:13:18', 1),
(5, 'OnePlus 13', 'OnePlus', 'oneplus-13', 11999000, 'https://fdn2.gsmarena.com/vv/bigpic/oneplus-13.jpg', 2025, 'flagship', 6.82, '3168x1440', 120, 'LTPO AMOLED', 'Snapdragon 8 Elite', 8, 'Adreno 830', 16, 512, 50, 32, NULL, 6000, 100, 1, 210, 8.90, 76.00, 162.90, NULL, 1, 'Wi-Fi 7', '5.4', 1, 'Android', '15', 8.9, 9.7, 9.8, 8.7, '2026-06-03 21:40:39', '2026-06-03 21:40:39', 1),
(6, 'Samsung Galaxy A56', 'Samsung', 'samsung-galaxy-a56', 6499000, 'https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a56-.jpg', 2025, 'midrange', 6.70, '2340x1080', 120, 'Super AMOLED', 'Exynos 1580', 8, 'Xclipse 540', 8, 256, 50, 12, '', 5000, 45, 0, 198, 7.40, 77.00, 162.00, '', 1, 'Wi-Fi 6', '5.3', 1, 'Android', '15', 8.2, 7.5, 8.5, 8.0, '2026-06-03 21:40:39', '2026-06-04 08:12:01', 1),
(7, 'Realme GT 7 Pro', 'Realme', 'realme-gt-7-pro', 8999000, 'https://fdn2.gsmarena.com/vv/bigpic/realme-gt7-pro.jpg', 2024, 'midrange', 6.78, '2780x1264', 120, 'LTPO AMOLED', 'Snapdragon 8 Elite', 8, 'Adreno 830', 16, 256, 50, 16, '', 6500, 120, 0, 223, 8.55, 76.00, 161.50, '', 1, 'Wi-Fi 7', '5.4', 1, 'Android', '15', 8.4, 9.7, 9.9, 8.3, '2026-06-03 21:40:39', '2026-06-04 08:08:54', 1),
(8, 'POCO X7 Pro', 'POCO', 'poco-x7-pro', 5299000, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-poco-x7-pro.jpg', 2025, 'midrange', 6.67, '2712x1220', 120, 'AMOLED', 'MediaTek Dimensity 8400 Ultra', 8, 'Mali-G720 MC7', 12, 256, 50, 20, '', 6550, 90, 0, 197, 8.26, 74.70, 160.30, '', 1, 'Wi-Fi 6E', '5.4', 1, 'Android', '15', 7.8, 8.6, 9.8, 8.0, '2026-06-03 21:40:39', '2026-06-04 08:07:20', 1),
(9, 'Redmi Note 14 Pro+', 'Xiaomi', 'redmi-note-14-pro-plus', 4499000, 'https://fdn2.gsmarena.com/vv/bigpic/xiaomi-redmi-note-14-pro-plus-5g.jpg', 2024, 'budget', 6.67, '2712x1220', 120, 'AMOLED', 'Snapdragon 7s Gen 3', 8, 'Adreno 710', 12, 256, 200, 20, NULL, 6200, 90, 0, 210, 8.70, 74.70, 161.20, NULL, 1, 'Wi-Fi 6', '5.3', 1, 'Android', '14', 8.5, 7.6, 9.5, 7.8, '2026-06-03 21:40:39', '2026-06-03 21:40:39', 1),
(10, 'Apple iPhone 17e', 'Apple', '-pple-i-hone-17e', 13500000, 'https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-17e.jpg', 2025, 'flagship', 6.10, '1170 x 2532', 60, 'Super Retina XDR OLED', 'Hexa-core (2x4.26 GHz + 4x2.60 GHz)', 6, 'Apple GPU (4-core graphics)', 4, 256, 48, 12, 'Dual-LED dual-tone flash, HDR, panorama, 3D (spatial) audio', 4005, 15, 1, 169, 7.80, 71.50, 146.70, '', 1, 'Wi-Fi 6', '5.3', 1, 'iOS', '26.5', 9.0, 8.5, 8.5, 8.5, '2026-06-04 08:53:42', '2026-06-04 08:53:42', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `amount` decimal(12,0) NOT NULL,
  `status` enum('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_token` varchar(200) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `promo_id` int(11) DEFAULT NULL,
  `original_amount` decimal(12,0) DEFAULT NULL,
  `discount_amount` decimal(12,0) DEFAULT 0,
  `payment_channel` varchar(50) DEFAULT NULL COMMENT 'va_bca, va_bni, va_mandiri, qris, cc',
  `payment_code` varchar(100) DEFAULT NULL COMMENT 'VA number atau QRIS string',
  `payment_expired_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `shipping_name` varchar(100) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `price_alerts`
--

CREATE TABLE `price_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `target_price` decimal(12,0) NOT NULL,
  `is_triggered` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `promos`
--

CREATE TABLE `promos` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(12,0) DEFAULT 0,
  `max_discount` decimal(12,0) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL COMMENT 'NULL = berlaku semua device',
  `category` enum('flagship','midrange','budget','gaming','all') DEFAULT 'all',
  `banner_image` varchar(500) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `promos`
--

INSERT INTO `promos` (`id`, `title`, `description`, `promo_code`, `discount_type`, `discount_value`, `min_purchase`, `max_discount`, `device_id`, `category`, `banner_image`, `start_date`, `end_date`, `usage_limit`, `used_count`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'Flash Sale Flagship 10%', 'Diskon 10% untuk semua HP Flagship minggu ini!', 'FLAGSHIP10', 'percent', 10.00, 15000000, 2000000, NULL, 'flagship', NULL, '2026-06-04', '2026-06-11', 100, 0, 1, 1, '2026-06-04 06:29:03'),
(2, 'Hemat Rp 500ribu', 'Potongan langsung Rp 500.000 untuk pembelian di atas Rp 5 juta', 'HEMAT500K', 'fixed', 500000.00, 5000000, NULL, NULL, 'all', NULL, '2026-06-04', '2026-06-18', 50, 0, 1, 1, '2026-06-04 06:29:03'),
(3, 'Budget Special 15%', 'Spesial untuk HP budget, diskon 15%!', 'BUDGET15', 'percent', 15.00, 0, 750000, NULL, 'budget', NULL, '2026-06-04', '2026-07-04', 200, 0, 1, 1, '2026-06-04 06:29:03'),
(4, 'Promo Pelajar 5%', 'Diskon spesial 5% untuk HP midrange. Cocok buat budget pelajar!', 'PELAJAR5', 'percent', 5.00, 3000000, 500000, NULL, 'midrange', NULL, '2026-06-04', '2026-08-03', 200, 0, 1, 1, '2026-06-04 07:36:16'),
(5, 'Hemat Rp500rb', 'Potongan langsung Rp500.000 untuk pembelian di atas Rp5 juta', 'HEMAT500', 'fixed', 500000.00, 5000000, NULL, NULL, 'all', NULL, '2026-06-04', '2026-06-18', 50, 0, 1, 1, '2026-06-04 07:36:16'),
(10, 'FlashSale', 'asddas', 'GRTS11', 'percent', 10.00, 100000, NULL, NULL, 'gaming', '', '2026-06-04', '2026-06-05', NULL, 0, 1, 5, '2026-06-04 08:16:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(200) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `is_verified_buyer` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `saved_comparisons`
--

CREATE TABLE `saved_comparisons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device1_id` int(11) NOT NULL,
  `device2_id` int(11) NOT NULL,
  `share_token` varchar(64) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `avatar`, `is_verified`, `created_at`) VALUES
(1, 'zhaaa', 'faizaiza77@gmail.com', '$2y$10$jQ5E5m2EMLnRIoix8BqHmeWf9qG3fQLgLS.rHzkWqZlDCSEe78UnS', NULL, 0, '2026-06-03 21:41:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `fk_order_promo` (`promo_id`);

--
-- Indeks untuk tabel `price_alerts`
--
ALTER TABLE `price_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indeks untuk tabel `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promo_code` (`promo_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indeks untuk tabel `saved_comparisons`
--
ALTER TABLE `saved_comparisons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `share_token` (`share_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`device_id`),
  ADD KEY `device_id` (`device_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `price_alerts`
--
ALTER TABLE `price_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `saved_comparisons`
--
ALTER TABLE `saved_comparisons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_promo` FOREIGN KEY (`promo_id`) REFERENCES `promos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `price_alerts`
--
ALTER TABLE `price_alerts`
  ADD CONSTRAINT `price_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `price_alerts_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `promos`
--
ALTER TABLE `promos`
  ADD CONSTRAINT `promos_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `saved_comparisons`
--
ALTER TABLE `saved_comparisons`
  ADD CONSTRAINT `saved_comparisons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
