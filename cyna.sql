-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 09 jan. 2026 à 13:27
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `cyna`
--

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prenom` varchar(100) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `est_confirme` tinyint(1) DEFAULT '0',
  `token_confirmation` varchar(255) DEFAULT NULL,
  `date_inscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `token_reinitialisation` varchar(64) DEFAULT NULL,
  `expiration_token` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `prenom`, `nom`, `email`, `mot_de_passe`, `est_confirme`, `token_confirmation`, `date_inscription`, `derniere_connexion`, `token_reinitialisation`, `expiration_token`) VALUES
(1, 'Teo', 'Rebelo', 'teo.rebelo05@gmail.com', '$2y$10$r6pFtUcPSwuq8ngqsvNRdOg2w26dTODwjkgYUnyrk..oyZpjdaPT.', 0, 'f5ea753476b5e7148595369ee7e25ce3d82136f28afcf312e9e67101cac35dcd', '2026-01-05 09:18:19', NULL, 'fd7d2c84e4836203d205d81935e762c701be6d9bd05b13414f9559d3823384d8', '2026-01-09 13:56:54'),
(5, 'Teo', 'Rebelo', 'teo.sniper10@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$YWJsZlJPUFFUay5hUVpJUA$DofYZU4vjxPDl/vDvh2JNIREHx7O7QF79I+8ZIcBeNA', 0, 'a68e96993d14722783b629f33611e324cdd1101f62d066eaab72d53239c43b4e', '2026-01-05 13:28:54', NULL, 'f4f23fd7dbc040aa21e30c24de1f8871fab1b0ae5450fcb5cff3669e91359b38', '2026-01-09 12:13:07'),
(7, 'aksel', 'MEKCHICHE', 'aksel.mekchiche@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$eGtSM0xtOVI3QnRWNjZweQ$o2X01mCpYR1LgO1myDDrbbNGaSigsMCXX8V/FjfKO+0', 0, '106a06e92921a6dd1838a753199f6d79858db2520e5cc5b6a88597e517680f92', '2026-01-05 13:52:15', NULL, NULL, NULL),
(8, 'rebelo', 'teo', 'rebeloteobtsslam@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$OXdxWVd4bjZlR2doOUFTeg$oIHjdrd30JIRI/fYgEOnGegrodWrgLGh5z377xrhq28', 0, '33b379e0c897324fe959cd17f80688e0350e8eb25c68002f4b609309f8cf305b', '2026-01-05 15:23:40', NULL, '5bc49b0de2641dd3d1888cab2ab4804cc4fd09471fd0628d1619ead2a117286a', '2026-01-09 12:14:10'),
(9, 'test', 'test', 'testcyna01@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$NjZsQWQ3TnFRdGRKb2NabA$u+FlVOsPRMKuBm48zCWD1hbbXDSx/vzQJw82S5DJI0M', 0, 'd8ebe35b3c8dd2eedb50b100bad83b3b1ff1c8079fcd1f7d32336a8797f2f102', '2026-01-09 13:06:13', NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
