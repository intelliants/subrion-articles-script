<?php
//##copyright##

// remove incomplete articles older than 2 days
$sbrDb->delete("`url`='' AND (TO_DAYS(NOW()) - TO_DAYS(`date`) > 2)", 'articles');


$sbrDb->delete("TO_DAYS(NOW()) - TO_DAYS(`date`) > 30", 'articles_clicks');

// check featured articles
$sbrDb->update(['featured' => 0], "`featured` = 1 AND `featured_end` < CURDATE()", 0, 'articles');