-- DATABASE STRUCTURE `dbpedia_extraction_$language`
--
-- TABLE STRUCTURE `class`
--

--
-- Table structure for table `class`
--

CREATE TABLE IF NOT EXISTS `class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `parent_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_property`
--

CREATE TABLE IF NOT EXISTS `class_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `class_id` int(11) DEFAULT NULL,
  `type` enum('object','datatype') NOT NULL DEFAULT 'object',
  `description` varchar(255) DEFAULT NULL,
  `datatype_range` varchar(100) DEFAULT NULL,
  `uri` varchar(100) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`class_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_property_range`
--

CREATE TABLE IF NOT EXISTS `class_property_range` (
  `property_id` int(11) NOT NULL DEFAULT '0',
  `range_class_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`property_id`,`range_class_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `parser_type_rule`
--

CREATE TABLE IF NOT EXISTS `parser_type_rule` (
  `class_property_id` int(11) NOT NULL,
  `parser_type` enum('date','geocoordinates','unit','currency','url','merge') NOT NULL,
  `unit_type` enum('Length','Area','Volume','Speed','Force','Energy','Temperature','Mass','Pressure','Torque','Fuel efficiency','Power','Currency','Population density','Weight','Flow rate','Time','Density') DEFAULT NULL,
  PRIMARY KEY (`class_property_id`,`parser_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rule_property`
--

CREATE TABLE IF NOT EXISTS `rule_property` (
  `class_id` int(11) NOT NULL DEFAULT '0',
  `template_property_id` int(11) NOT NULL DEFAULT '0',
  `type` enum('value','set') NOT NULL DEFAULT 'value',
  `value` varchar(255) DEFAULT NULL,
  `new_class_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rule_uri`
--

CREATE TABLE IF NOT EXISTS `rule_uri` (
  `template_uri` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `new_class_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `structure_rule`
--

CREATE TABLE IF NOT EXISTS `structure_rule` (
  `template_property_id` int(11) NOT NULL DEFAULT '0',
  `type` enum('value','set') NOT NULL DEFAULT 'value',
  `value` varchar(255) DEFAULT NULL,
  `template_class_id` int(11) NOT NULL DEFAULT '0',
  `class_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `template_class`
--

CREATE TABLE IF NOT EXISTS `template_class` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`template_id`,`class_id`),
  UNIQUE KEY `class_id` (`class_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `template_parser_type_rule`
--

CREATE TABLE IF NOT EXISTS `template_parser_type_rule` (
  `template_property_id` int(11) NOT NULL,
  `unit_exact_type` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`template_property_id`,`unit_exact_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `template_property`
--

CREATE TABLE IF NOT EXISTS `template_property` (
  `name` varchar(255) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`template_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `template_property_class_property`
--

CREATE TABLE IF NOT EXISTS `template_property_class_property` (
  `template_property_id` int(11) NOT NULL,
  `class_property_id` int(11) NOT NULL,
  PRIMARY KEY (`template_property_id`,`class_property_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `template_property_merge_rule`
--

CREATE TABLE IF NOT EXISTS `template_property_merge_rule` (
  `ordered_template_property_ids` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `class_property_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  UNIQUE KEY `ordered_template_property_ids` (`ordered_template_property_ids`,`class_property_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `template_uri`
--

CREATE TABLE IF NOT EXISTS `template_uri` (
  `template_id` int(11) NOT NULL,
  `uri` varchar(255) NOT NULL,
  UNIQUE KEY `uri` (`uri`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
