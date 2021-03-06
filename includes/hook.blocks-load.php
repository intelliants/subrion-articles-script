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

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    $sql  = "SELECT a.`id`, a.`title_{$iaView->language}`, a.`title_alias`, a.`nofollow`, a.`date_added`, a.`views_num`, a.`summary_{$iaView->language}`, a.`image`,";
    $sql .= " cat.`title_alias` `category_alias`, cat.`title_{$iaView->language}` `category_title`, cat.`nofollow`, ";
    $sql .= 'IF(\'\' != acc.`fullname`, acc.`fullname`, acc.`username`) as `account_fullname` ';
    $sql .= "FROM `{$iaDb->prefix}articles` AS a ";
    $sql .= "LEFT JOIN `{$iaDb->prefix}articles_categories` AS cat ON a.`category_id`=cat.`id` ";
    $sql .= "LEFT JOIN `{$iaDb->prefix}members` AS acc ON a.`member_id`=acc.`id` ";
    $sql .= "WHERE a.`status`='active' && cat.`status`='active' ";
    $sql .= "AND (acc.`status`='active' OR acc.`status` IS NULL) ";

    $defaultLimit = (int)$iaCore->get('art_per_block', 5);

    $iaArticle = $iaCore->factoryItem('article');
    $iaArticlecat = $iaCore->factoryItem('articlecat');

    if ($iaView->blockExists('random_articles')) {
        $max = (int)$iaDb->one('MAX(`id`) as `max`', null, 'articles');
        $sql2 = $iaCore->iaDb->orderByRand($max, 'a.`id`') . " ORDER BY RAND() LIMIT 0, " . (int)$iaCore->get('art_per_block_random', 6);
        if ($data = $iaArticle->getByQuery($sql . $sql2)) {
            $iaView->assign('random_articles', $data);
        }
    }

    if ($iaView->blockExists('latest_articles')) {
        $sql2 = 'ORDER BY a.`date_added` DESC LIMIT 0, ' . $defaultLimit;
        if ($data = $iaArticle->getByQuery($sql . $sql2)) {
            $iaView->assign('latest_articles', $data);
        }
    }

    if ($iaView->blockExists('popular_articles')) {
        $sql2 = 'ORDER BY a.`views_num` DESC LIMIT 0, ' . (int)$iaCore->get('art_per_block_popular', 6);
        if ($data = $iaArticle->getByQuery($sql . $sql2)) {
            $iaView->assign('popular_articles', $data);
        }
    }

    if ($iaView->blockExists('featured_articles')) {
        $sql2  = " && a.featured = 1 && (a.`featured_end` > '" . date('Y-m-d') . "') ORDER BY a.`date_added` DESC LIMIT 0, " . (int)$iaCore->get('art_per_block_featured', 6);
        if ($data = $iaArticle->getByQuery($sql . $sql2)) {
            $iaView->assign('featured_articles', $data);
        }
    }

    if ($iaView->blockExists('sponsored_articles')) {
        $sql2  = " && a.sponsored = 1 && (a.`sponsored_end` > '" . date('Y-m-d') . "') ORDER BY a.`date_added` DESC LIMIT 0, " . (int)$iaCore->get('art_per_block_sponsored', 6);
        if ($data = $iaArticle->getByQuery($sql . $sql2)) {
            $iaView->assign('sponsored_articles', $data);
        }
    }

    if ($iaView->blockExists('sticky_articles')) {
        $sql2  = ' && a.sticky = 1 ORDER BY a.`date_added` DESC LIMIT 0, ' . (int)$iaCore->get('art_per_block_sticky', 5);
        if ($data = $iaArticle->getByQuery($sql . $sql2)) {
            $iaView->assign('sticky_articles', $data);
        }
    }

    $defaultLimit = (int)$iaCore->get('art_perpage_block', 10);

    if ($iaView->blockExists('most_viewed_articles')) {
        $listingData = $iaView->getValues('item');
        if (isset($listingData['category_id']) && $listingData['category_id']) {
            $sql2 = iaDb::printf(' && a.`id` != :id && a.`category_id` = :category_id ORDER BY a.`views_num` DESC LIMIT :limit', [
                'id' => (int)$listingData['id'],
                'category_id' => (int)$listingData['category_id'],
                'limit' => $defaultLimit
            ]);
            if ($data = $iaArticle->getByQuery($sql . $sql2)) {
                $iaView->assign('most_viewed_articles', $data);
            }
        }
    }

    if ($iaView->blockExists('most_recent_articles')) {
        isset($listingData) || $listingData = $iaView->getValues('item');
        if (isset($listingData['category_id']) && $listingData['category_id']) {
            $sql2 = iaDb::printf(' && a.`id` != :id && a.`category_id` = :category_id ORDER BY a.`date_added` DESC LIMIT :limit', [
                'id' => (int)$listingData['id'],
                'category_id' => (int)$listingData['category_id'],
                'limit' => $defaultLimit
            ]);
            if ($data = $iaArticle->getByQuery($sql . $sql2)) {
                $iaView->assign('most_recent_articles', $data);
            }
        }
    }

    if ($iaView->blockExists('related_articles')) {
        isset($listingData) || $listingData = $iaView->getValues('item');
        if (isset($listingData['category_id']) && $listingData['category_id']) {
            $max = (int)$iaDb->one('MAX(`id`) as `max`', null, 'articles');
            $sql2 = iaDb::printf(' && a.`id` != :id && a.`category_id` = :category_id ' . $iaCore->iaDb->orderByRand($max, 'a.`id`') . ' ORDER BY RAND() LIMIT :limit', [
                'id' => (int)$listingData['id'],
                'category_id' => (int)$listingData['category_id'],
                'limit' => $defaultLimit
            ]);
            if ($data = $iaArticle->getByQuery($sql . $sql2)) {
                $iaView->assign('related_articles', $data);
            }
        }
    }

    iaLanguage::set('no_articles2', ['url' => $iaCore->modulesData['publishing']['url'] . 'add/']);

    if ($iaView->blockExists('new_articles')) {
        $data = $iaArticle->get(' ORDER BY t1.`date_added` DESC', 0, $iaCore->get('art_per_block_new', 6));
        $foundRows = $iaArticle->iaDb->foundRows();

        empty($data) || $iaView->assign('session_id', session_id());

        $iaView->assign('new_articles', $data);
    }

    if ($iaView->blockExists('top_categories')) {
        $data = $iaCore->factoryItem('articlecat')
            ->get($iaCore->get('art_view_category', true) ? '' : ' && `num_all_articles` > 0', 0, $iaCore->get('article_top_categories', 12), 1, '`num_all_articles` DESC, `title_' . $iaView->language . '` ASC');
        if ($data) {
            $iaView->assign('top_categories', $data);
        }
    }

    if ($iaView->blockExists('priority_categories')) {
        $data = $iaCore->factoryItem('articlecat')->get(' && `priority` = 1 ' . ($iaCore->get('art_view_category', true) ? '' : ' && `num_all_articles` > 0'),
            0, $iaCore->get('article_top_categories', 12), 1, '`title_' . $iaView->language . '` ASC');
        if ($data) {
            foreach ($data as $key => $value) {
                $children = sprintf(" && t1.`category_id` IN (SELECT `child_id` FROM `%s` WHERE `parent_id` = %d) ", $iaArticlecat->getTableFlat(true), $value['id']);
                $data[$key]['articles'] = $iaArticle->get($children . ' ORDER BY t1.`date_added` DESC', 0, $iaCore->get('art_per_block_featured_categories', 6));
            }

            $iaView->assign('priority_categories', $data);
        }
    }

    if ($iaView->blockExists('articles_archive')) {
        $data = [];
        if ($array = $iaDb->all('DISTINCT(MONTH(`date_added`)) `month`, YEAR(`date_added`) `year`', "`status` = 'active' GROUP BY `date_added` ORDER BY `date_added` DESC", 0, 6, $iaArticle::getTable())) {
            $url = $iaCore->modulesData['publishing']['url'] . 'date' . IA_URL_DELIMITER;
            foreach ($array as $date) {
                $data[] = [
                    'url' => $url . $date['year'] . IA_URL_DELIMITER . $date['month'] . IA_URL_DELIMITER,
                    'month' => $date['month'],
                    'year' => $date['year']
                ];
            }
        }

        $iaView->assign('articles_archive', $data);
    }

    if ($iaView->blockExists('filters') && $iaArticle->getItemName() == $iaView->get('filtersItemName')) {
        $categories = $iaDb->all(['id', 'title' => 'title_' . $iaView->language],
            "`status` = 'active' && `level` = 1 ORDER BY `title`", null, null, $iaArticlecat::getTable());

        if (!empty($categories)) {
            $iaView->assign('publishingFiltersCategories', $categories);
        }
    }
}
