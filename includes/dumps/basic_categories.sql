TRUNCATE TABLE `{prefix}articles_categories`;
INSERT INTO `{prefix}articles_categories` (`id`,`parent_id`,`title_{lang}`,`title_alias`,`level`,`parents`,`child`,`order`) VALUES
(1,0,'ROOT','',0,'1','2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,1',0),
(2,1,'Arts & Entertainment','Arts-and-Entertainment/',1,'2,1','2',1),
(3,1,'Business','Business/',1,'3,1','3',2),
(4,1,'Communications','Communications/',1,'4,1','4',3),
(5,1,'Computers','Computers/',1,'5,1','5',4),
(6,1,'Environment','Environment/',1,'6,1','6',5),
(7,1,'Fashion','Fashion/',1,'7,1','7',6),
(8,1,'Finance','Finance/',1,'8,1','8',7),
(9,1,'Food & Beverage','Food-and-Beverage/',1,'9,1','9',8),
(10,1,'Health & Fitness','Health-and-Fitness/',1,'10,1','10',9),
(11,1,'Internet Business','Internet-Business/',1,'11,1','11',10),
(12,1,'Politics','Politics/',1,'12,1','12',11),
(13,1,'Product Reviews','Product-Reviews/',1,'13,1','13',12),
(14,1,'Property','Property/',1,'14,1','14',13),
(15,1,'Education','Education/',1,'15,1','15',14),
(16,1,'Self Improvement','Self-Improvement/',1,'16,1','16',15),
(17,1,'Social Networking','Social-Networking/',1,'17,1','17',16),
(18,1,'Sports','Sports/',1,'18,1','18',17),
(19,1,'Transportation','Transportation/',1,'19,1','19',18),
(20,1,'Travel & Leisure','Travel-and-Leisure/',1,'20,1','20',19),
(21,1,'Photography','Photography/',1,'21,1','21',20),
(22,1,'Software','Software/',1,'22,1','22',21),
(23,1,'Home Improvement','Home-Improvement/',1,'23,1','23',22);
UPDATE `{prefix}articles_categories` SET `locked` = 1 WHERE `parent_id` = 0;
UPDATE `{prefix}articles_categories` SET `status` = 'active',`date_added` = NOW(),`date_modified` = NOW();