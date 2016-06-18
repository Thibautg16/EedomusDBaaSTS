--
-- Base de données :  `eedomus`
--

-- --------------------------------------------------------

--
-- Structure de la table `eedomus_periph`
--

CREATE TABLE IF NOT EXISTS `eedomus_periph` (
`id` int(11) NOT NULL,
  `periph_id` int(11) NOT NULL,
  `parent_periph_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value_unite` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `room_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `usage_id` int(11) NOT NULL,
  `usage_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `creation_date` datetime NOT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `eedomus_periph`
--
ALTER TABLE `eedomus_periph`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `eedomus_periph`
--
ALTER TABLE `eedomus_periph`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;


/srv/www/EedomusDBaaSTS/cron_eedomus_to_ovhdbaasts.php