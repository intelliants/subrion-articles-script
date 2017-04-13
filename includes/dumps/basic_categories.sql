TRUNCATE TABLE `{prefix}articles_categories`;
TRUNCATE TABLE `{prefix}articles_categories_flat`;

INSERT INTO `{prefix}articles_categories` (`id`,`parent_id`,`title_{lang}`,`title_alias`,`level`,`order`) VALUES
(1,0,'ROOT','',0,0),
(2,1,'Arts & Entertainment','Arts-and-Entertainment/',1,1),
(3,1,'Business','Business/',1,2),
(4,1,'Communications','Communications/',1,3),
(5,1,'Computers','Computers/',1,4),
(6,1,'Environment','Environment/',1,5),
(7,1,'Fashion','Fashion/',1,6),
(8,1,'Finance','Finance/',1,7),
(9,1,'Food & Beverage','Food-and-Beverage/',1,8),
(10,1,'Health & Fitness','Health-and-Fitness/',1,9),
(11,1,'Internet Business','Internet-Business/',1,10),
(12,1,'Politics','Politics/',1,11),
(13,1,'Product Reviews','Product-Reviews/',1,12),
(14,1,'Property','Property/',1,13),
(15,1,'Education','Education/',1,14),
(16,1,'Self Improvement','Self-Improvement/',1,15),
(17,1,'Social Networking','Social-Networking/',1,16),
(18,1,'Sports','Sports/',1,17),
(19,1,'Transportation','Transportation/',1,18),
(20,1,'Travel & Leisure','Travel-and-Leisure/',1,19),
(21,1,'Photography','Photography/',1,20),
(22,1,'Software','Software/',1,21),
(23,1,'Home Improvement','Home-Improvement/',1,22);

UPDATE `{prefix}articles_categories` SET `locked` = 1 WHERE `parent_id` = 0;

INSERT INTO `{prefix}articles_categories_flat` VALUES
(2,2),
(3,3),
(4,4),
(5,5),
(6,6),
(7,7),
(8,8),
(9,9),
(10,10),
(11,11),
(12,12),
(13,13),
(14,14),
(15,15),
(16,16),
(17,17),
(18,18),
(19,19),
(20,20),
(21,21),
(22,22),
(23,23);