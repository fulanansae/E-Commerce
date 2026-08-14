-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 14, 2026 at 08:31 AM
-- Server version: 10.4.18-MariaDB
-- PHP Version: 8.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_ecommerce2`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kode_invoice` varchar(30) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `status` enum('Pending','Proses','Selesai','Batal') DEFAULT 'Pending',
  `tanggal_order` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `kode_invoice`, `total_harga`, `status`, `tanggal_order`) VALUES
(1, 3, 'INV-20260814-6814', 419000, 'Pending', '2026-08-14 06:13:08');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `harga` int(11) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `nama_produk`, `kategori`, `harga`, `stok`, `deskripsi`, `gambar`, `created_at`) VALUES
(1, 'Sepatu Sneakers Modern', 'Fashion', 250000, 15, 'Sepatu kasual dengan desain ergonomis dan ringan.', 'prod_1786688776_333.jfif', '2026-08-13 08:36:51'),
(2, 'Kemeja Casual Slimfit', 'Pakaian', 145000, 20, 'Bahan katun berkualitas, cocok untuk acara santai.', 'prod_1786612181_800.jfif', '2026-08-13 08:36:51'),
(3, 'Jam Tangan Digital', 'Aksesori', 199000, 10, 'Tahan air dengan fitur backlight dan stopwatch.', 'prod_1786682135_799.jfif', '2026-08-13 08:36:51'),
(4, 'Tas Ransel Laptop', 'Tas', 220000, 8, 'Kapasitas muat hingga laptop 15.6 inch.', 'prod_1786682070_891.jfif', '2026-08-13 08:36:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@mail.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1e8cT3UaG0F3/B1iL2E5DqXw8q8K7mO', 'admin', '2026-08-13 08:36:51'),
(2, 'user', 'user@mail.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1e8cT3UaG0F3/B1iL2E5DqXw8q8K7mO', 'user', '2026-08-13 08:36:51'),
(3, 'fulan', 'fulan@gmail.com', '$2y$10$W2RvzSC.AQSaWkI4Rdmt/OEowshMtR.NPDcRW17GB9md1y9c7d3Jm', 'user', '2026-08-14 04:06:43'),
(4, 'eful', 'eful@gmail.com', '$2y$10$XIlMvEvXmB2L6wkElSiKC.vtH6QE1HSrx0ATOQIJawdqgNiBkiqQu', 'admin', '2026-08-14 06:21:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
