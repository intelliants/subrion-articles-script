<?php
/******************************************************************************
 *
 * Subrion Articles Publishing Script
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion Articles Publishing Script
 *
 * This program is a commercial software and any kind of using it must agree
 * to the license, see <https://subrion.pro/license.html>.
 *
 * This copyright notice may not be removed from the software source without
 * the permission of Subrion respective owners.
 *
 *
 * @link https://subrion.pro/product/publishing.html
 *
 ******************************************************************************/

// remove incomplete articles older than 2 days
$iaDb->delete("`url`='' AND (TO_DAYS(NOW()) - TO_DAYS(`date`) > 2)", 'articles');


$iaDb->delete("TO_DAYS(NOW()) - TO_DAYS(`date`) > 30", 'articles_clicks');

// check featured articles
$iaDb->update(['featured' => 0], "`featured` = 1 AND `featured_end` < CURDATE()", 0, 'articles');
