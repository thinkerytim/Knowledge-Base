/**
 * @version 3.4.1 2015-05-26
 * @package Joomla
 * @subpackage KnowledgeBase
 * @copyright (C) 2015 the Thinkery LLC. All rights reserved.
 * @license GNU/GPL see LICENSE
 */

--
-- Table structure for table `#__knowledgebase_categories`
--

CREATE TABLE IF NOT EXISTS `#__knowledgebase_categories` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`title` varchar(255) NOT NULL,
`alias` varchar(255) NOT NULL,
`description` text NOT NULL,
`parent` tinyint(3) unsigned NOT NULL DEFAULT '0',
`ordering` int(10) unsigned NOT NULL DEFAULT '0',
`access` int(10) unsigned NOT NULL DEFAULT '0',
`publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`state` tinyint(3) unsigned NOT NULL DEFAULT '1',
`checked_out` int(10) NOT NULL DEFAULT '0',
`checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`language` char(7) NOT NULL,
`icon` varchar(255) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Table structure for table `#__knowledgebase_items`
--

CREATE TABLE IF NOT EXISTS `#__knowledgebase_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(125) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `featured` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ordering` int(10) unsigned NOT NULL DEFAULT '0',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `checked_out` int(10) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `language` char(7) NOT NULL,
  `params` text NOT NULL DEFAULT '',
  `metadata` text NOT NULL,
  PRIMARY KEY (`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Table structure for table `#__knowledgebase_itemcat`
--

CREATE TABLE IF NOT EXISTS `#__knowledgebase_itemcat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `cat_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_cat` (`item_id`,`cat_id`),
  KEY `item_id` (`item_id`),
  KEY `cat_id` (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
