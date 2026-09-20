-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2026 at 07:36 PM
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
-- Database: `hida_makeup`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `image`, `description`, `display_order`, `created_at`) VALUES
(1, 'Lèvres & Rouges à Lèvres', 'levres', 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=800&q=80', 'Des rouges mats aux huiles brillantes hydratantes pour des lèvres sublimées au quotidien.', 1, '2026-09-20 15:26:30'),
(2, 'Teint et Fond de Teint', 'teint', 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=800&q=80', 'Fonds de teint couvrance modulable, correcteurs éclat et poudres pour un teint unifié et lumineux.', 2, '2026-09-20 15:26:30'),
(3, 'Soins du Visage', 'soins', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80', 'Sérums nourrissants, crèmes hydratantes et exfoliants doux pour révéler votre éclat naturel.', 3, '2026-09-20 15:26:30'),
(4, 'Maquillage des Yeux', 'yeux', 'https://images.unsplash.com/photo-1583241801142-113b9f5bbde5?auto=format&fit=crop&w=800&q=80', 'Mascaras volume intense, eyeliners précis et fards à paupières pigmentés pour intensifier le regard.', 4, '2026-09-20 15:26:30'),
(5, 'Parfums et Brumes', 'parfums', 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80', 'Fragrances envoûtantes et brumes délicates aux notes orientales et florales raffinées.', 5, '2026-09-20 15:26:30'),
(6, 'Pinceaux et Accessoires', 'accessoires', 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80', 'Pinceaux professionnels, éponges blender et trousses pour une application experte et sans défaut.', 6, '2026-09-20 15:26:30');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_city` varchar(100) NOT NULL,
  `customer_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `subtotal_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cod',
  `status` enum('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_name`, `customer_phone`, `customer_city`, `customer_address`, `notes`, `subtotal_amount`, `shipping_fee`, `total_amount`, `payment_method`, `status`, `created_at`) VALUES
(1, 'HIDA-2026-0001', 'Salma Benjelloun', '+212 661 12 34 56', 'Casablanca', 'Boulevard d\'Anfa, Résidence Les Fleurs Apt 12', 'Appeler avant livraison', 520.00, 0.00, 520.00, 'cod', 'confirmed', '2026-09-20 10:15:00'),
(2, 'HIDA-20260920-1C35', 'ws', '0620283725', 'Casablanca', 'ww', '', 1180.00, 0.00, 1180.00, 'cod', 'pending', '2026-09-20 16:36:25');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total_price`) VALUES
(1, 1, 1, 'Fond de Teint HD Skin Haute Couvrance', 340.00, 1, 340.00),
(2, 1, 2, 'Anticernes Éclat Correcteur Glow', 180.00, 1, 180.00),
(3, 2, 11, 'Palette Ombres à Paupières Nude Eyes 12 Teintes', 280.00, 1, 280.00),
(4, 2, 12, 'Eau de Parfum Fleur d\'Orient 50ml', 450.00, 2, 900.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `short_description` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 5.0,
  `reviews_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `badge` varchar(50) DEFAULT NULL,
  `in_stock` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` int(11) NOT NULL DEFAULT 50,
  `main_image` varchar(255) NOT NULL,
  `gallery_images` text DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `best_seller` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `short_description`, `description`, `price`, `original_price`, `rating`, `reviews_count`, `badge`, `in_stock`, `stock_quantity`, `main_image`, `gallery_images`, `featured`, `best_seller`, `created_at`) VALUES
(1, 2, 'Fond de Teint HD Skin Haute Couvrance', 'fond-de-teint-hd-skin-haute-couvrance', 'Fond de teint fluide fini naturel et semi-mat, tenue 24h sans transfert.', 'Le fond de teint HD Skin offre une couvrance modulable de moyenne à totale avec un fini seconde peau imperceptible. Formulé avec des pigments micro-fins, il camoufle les imperfections et unifie le teint tout en maintenant une hydratation optimale toute la journée. Idéal pour le climat marocain.', 340.00, 390.00, 4.9, 128, 'Nouveau', 1, 45, 'https://placehold.co/800x1000/f1e7e3/7d3638?text=HD+Skin+1', '[\"https://placehold.co/800x1000/f1e7e3/7d3638?text=HD+Skin+1\", \"https://placehold.co/800x1000/ece1dd/7d3638?text=HD+Skin+2\", \"https://placehold.co/800x1000/e6ddd8/7d3638?text=HD+Skin+3\", \"https://placehold.co/800x1000/e8d5d0/7d3638?text=Texture\"]', 1, 1, '2026-09-20 15:26:30'),
(2, 2, 'Anticernes Éclat Correcteur Glow', 'anticernes-eclat-correcteur-glow', 'Correcteur illuminateur anti-cernes, formule enrichie en caféine et vitamine C.', 'Réveillez votre regard en un seul geste ! Cet anticernes crémeux illumine les zones d\'ombre, estompe cernes et ridules avec un fini lumineux qui ne file pas dans les plis.', 180.00, 220.00, 4.8, 94, 'Best-seller', 1, 60, 'https://placehold.co/700x820/ece1dd/7d3638?text=Concealer', '[\"https://placehold.co/700x820/ece1dd/7d3638?text=Concealer\"]', 1, 1, '2026-09-20 15:26:30'),
(3, 2, 'Poudre Libre Fixatrice Translucide', 'poudre-libre-fixatrice-translucide', 'Poudre ultra-fine floutante matifiante pour un fini velouté longue durée.', 'Formule légère micro-broyée qui matifie les zones de brillance sans effet plâtreux. Fixe le maquillage jusqu\'à 16 heures et apporte un effet flouté soft-focus sur les photos.', 210.00, NULL, 4.7, 56, NULL, 1, 35, 'https://placehold.co/700x820/e6ddd8/7d3638?text=Setting+Powder', '[\"https://placehold.co/700x820/e6ddd8/7d3638?text=Setting+Powder\"]', 0, 0, '2026-09-20 15:26:30'),
(4, 6, 'Éponge Beauté Blender Pro Soft', 'eponge-beaute-blender-pro-soft', 'Éponge ergonomique sans latex pour une application uniforme et sans trace.', 'Texture ultra-douce et rebondissante. S\'utilise humide pour un rendu aérien ou sèche pour une couvrance maximale. Nettoyage facile et durable.', 65.00, NULL, 4.9, 82, NULL, 1, 100, 'https://placehold.co/700x820/e8d5d0/7d3638?text=Sponge', '[\"https://placehold.co/700x820/e8d5d0/7d3638?text=Sponge\"]', 0, 0, '2026-09-20 15:26:30'),
(5, 2, 'Spray Fixateur Rafraîchissant 16H', 'spray-fixateur-rafraichissant-16h', 'Brumisation fine fixante et hydratante enrichie en aloé vera et thé vert.', 'Verrouille votre maquillage toute la journée même en cas de chaleur ou d\'humidité. Laisse la peau fraîche, apaisée et parfaitement hydratée.', 195.00, NULL, 4.8, 43, NULL, 1, 40, 'https://placehold.co/700x820/f1e7e3/7d3638?text=Setting+Spray', '[\"https://placehold.co/700x820/f1e7e3/7d3638?text=Setting+Spray\"]', 0, 0, '2026-09-20 15:26:30'),
(6, 2, 'Blush Liquide Soft Pinch Dewy', 'blush-liquide-soft-pinch-dewy', 'Blush liquide ultra-pigmenté effet bonne mine instantané et naturel.', 'Une seule goutte suffit pour illuminer vos pommettes d\'un éclat rosé sain et modulable. Formule longue tenue qui se fond parfaitement au teint.', 230.00, NULL, 4.9, 110, 'Tendance', 1, 28, 'https://placehold.co/700x820/ece1dd/7d3638?text=Blush', '[\"https://placehold.co/700x820/ece1dd/7d3638?text=Blush\"]', 1, 1, '2026-09-20 15:26:30'),
(7, 4, 'Mascara Sky High Longueur & Volume Extrême', 'mascara-sky-high-longueur-volume-extreme', 'Brosse flexible innovante qui attrape chaque cil de la racine à la pointe.', 'Déployez vos cils à l\'infini ! Formule enrichie en extrait de bambou et fibres pour des cils aériens sans paquet, résistant à l\'eau et sans bavure.', 145.00, 170.00, 4.9, 215, 'Top Vente', 1, 80, 'https://placehold.co/700x820/e6ddd8/7d3638?text=Sky+High', '[\"https://placehold.co/700x820/e6ddd8/7d3638?text=Sky+High\"]', 1, 1, '2026-09-20 15:26:30'),
(8, 1, 'Diamond Sleek Shine-Coat Smoothing Spray', 'rouge-a-levres-mat-velvet-teddy', 'Teinte nude iconique au fini mat velours confortable et non asséchant.', 'La teinte universelle préférée des amoureuses du nude chic. Offre une couleur intense et crémeuse qui tient confortablement toute la journée.', 220.00, NULL, 4.8, 77, NULL, 1, 50, '', NULL, 1, 0, '2026-09-20 15:26:30'),
(9, 1, 'Huile pour Lèvres Repulpante Gloss & Care', 'huile-pour-levres-repulpante-gloss-care', 'Huile nourrissante teintée aux huiles de jojoba et de cerise sauvage.', 'Sublimez vos lèvres d\'une brillance miroir irrésistible sans effet collant. Hydrate en profondeur et repulpe délicatement les lèvres.', 160.00, 190.00, 4.9, 142, 'Coup de cœur', 1, 65, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=600&q=80', '[\"https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=600&q=80\"]', 1, 1, '2026-09-20 15:26:30'),
(10, 3, 'Sérum Niacinamide 10% + Zinc 1%', 'serum-niacinamide-10-zinc-1', 'Sérum régulateur de sébum et réducteur de pores et imperfections.', 'Sérum hautement concentré qui affine le grain de peau, estompe les rougeurs et régule l\'excès de sébum pour un teint net et purifié.', 120.00, 140.00, 4.8, 98, 'Best-seller', 1, 55, 'https://placehold.co/700x820/e6ddd8/7d3638?text=The+Ordinary', '[\"https://placehold.co/700x820/e6ddd8/7d3638?text=The+Ordinary\"]', 1, 1, '2026-09-20 15:26:30'),
(11, 4, 'Palette Ombres à Paupières Nude Eyes 12 Teintes', 'palette-ombres-a-paupieres-nude-eyes', 'Harmonie de 12 fards mats, satinés et pailletés pour looks de jour et soirée.', 'Des teintes chaudes et universelles hautement pigmentées qui se dégradent avec facilité sans chutes pour un regard sublime et intense.', 280.00, 320.00, 4.7, 63, NULL, 1, 29, 'https://images.unsplash.com/photo-1583241801142-113b9f5bbde5?auto=format&fit=crop&w=600&q=80', '[\"https://images.unsplash.com/photo-1583241801142-113b9f5bbde5?auto=format&fit=crop&w=600&q=80\"]', 1, 0, '2026-09-20 15:26:30'),
(12, 5, 'Eau de Parfum Fleur d\'Orient 50ml', 'eau-de-parfum-fleur-d-orient-50ml', 'Notes florales délicates de jasmin marocain, fleur d\'oranger et ambre vanillé.', 'Une signature olfactive envoûtante et féminine, célébrant la richesse et le raffinement des senteurs du Maroc avec un sillage mémorable.', 450.00, 520.00, 4.9, 87, 'Exclusif', 1, 18, 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80', '[\"https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80\"]', 1, 0, '2026-09-20 15:26:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_fav` (`session_id`,`product_id`),
  ADD KEY `fk_favorites_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
