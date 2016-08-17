<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$sql  = "SELECT a.`id`, a.`title`, a.`title_alias`, a.`nofollow`, a.`date_added`, a.`views_num`, a.`summary`, a.`image`,";
	$sql .= " cat.`title_alias` `category_alias`, cat.`title` `category_title`, cat.`nofollow`, ";
	$sql .= 'IF(\'\' != acc.`fullname`, acc.`fullname`, acc.`username`) as `account_fullname` ';
	$sql .= "FROM `{$iaDb->prefix}articles` AS a ";
	$sql .= "LEFT JOIN `{$iaDb->prefix}articles_categories` AS cat ON a.`category_id`=cat.`id` ";
	$sql .= "LEFT JOIN `{$iaDb->prefix}members` AS acc ON a.`member_id`=acc.`id` ";
	$sql .= "WHERE a.`status`='active' AND cat.`status`='active' ";
	$sql .= "AND (acc.`status`='active' OR acc.`status` IS NULL) ";

	$defaultLimit = (int)$iaCore->get('art_per_block', 5);

	$iaArticle = $iaCore->factoryPackage('article', 'publishing');

	if ($iaView->blockExists('random_articles'))
	{
		$max = (int)$iaDb->one('MAX(`id`) as `max`', null, 'articles');
		$sql2 = $iaCore->iaDb->orderByRand($max, 'a.`id`') . " ORDER BY RAND() LIMIT 0, " . (int)$iaCore->get('art_per_block_random', 6);
		if ($data = $iaDb->getAll($sql . $sql2))
		{
			$iaArticle->wrapValues($data);
			$iaView->assign('random_articles', $data);
		}
	}
	if ($iaView->blockExists('latest_articles'))
	{
		$sql2 = 'ORDER BY a.`date_added` DESC LIMIT 0, ' . $defaultLimit;
		if ($data = $iaDb->getAll($sql . $sql2))
		{
			$iaArticle->wrapValues($data);
			$iaView->assign('latest_articles', $data);
		}
	}
	if ($iaView->blockExists('popular_articles'))
	{
		$sql2 = 'ORDER BY a.`views_num` DESC LIMIT 0, ' . (int)$iaCore->get('art_per_block_popular', 6);
		if ($data = $iaDb->getAll($sql . $sql2))
		{
			$iaArticle->wrapValues($data);
			$iaView->assign('popular_articles', $data);
		}
	}

	if ($iaView->blockExists('featured_articles'))
	{
		$sql2  = " AND a.featured = 1 AND (a.`featured_end` > '" . date('Y-m-d') . "') ORDER BY a.`date_added` DESC LIMIT 0, " . (int)$iaCore->get('art_per_block_featured', 6);

		if ($data = $iaDb->getAll($sql . $sql2))
		{
			$iaArticle->wrapValues($data);
			$iaView->assign('featured_articles', $data);
		}
	}

	if ($iaView->blockExists('sponsored_articles'))
	{
		$sql2  = " AND a.sponsored = 1 AND (a.`sponsored_end` > '" . date('Y-m-d') . "') ORDER BY a.`date_added` DESC LIMIT 0, " . (int)$iaCore->get('art_per_block_sponsored', 6);

		if ($data = $iaDb->getAll($sql . $sql2))
		{
			$iaArticle->wrapValues($data);
			$iaView->assign('sponsored_articles', $data);
		}
	}

	if ($iaView->blockExists('sticky_articles'))
	{
		$sql2  = ' AND a.sticky = 1 ORDER BY a.`date_added` DESC LIMIT 0, ' . (int)$iaCore->get('art_per_block_sticky', 5);

		if ($data = $iaDb->getAll($sql . $sql2))
		{
			$iaArticle->wrapValues($data);
			$iaView->assign('sticky_articles', $data);
		}
	}

	$defaultLimit = (int)$iaCore->get('art_perpage_block', 10);

	if ($iaView->blockExists('most_viewed_articles'))
	{
		$listingData = $iaView->getValues('item');
		if (isset($listingData['category_id']) && $listingData['category_id'])
		{
			$sql2 = iaDb::printf(' AND a.`id` != :id AND a.`category_id` = :category_id ORDER BY a.`views_num` DESC LIMIT :limit', array(
				'id' => (int)$listingData['id'],
				'category_id' => (int)$listingData['category_id'],
				'limit' => $defaultLimit
			));
			if ($data = $iaDb->getAll($sql . $sql2))
			{
				$iaArticle->wrapValues($data);
				$iaView->assign('most_viewed_articles', $data);
			}
		}
	}
	if ($iaView->blockExists('most_recent_articles'))
	{
		if (!isset($listingData))
		{
			$listingData = $iaView->getValues('item');
		}
		if (isset($listingData['category_id']) && $listingData['category_id'])
		{
			$sql2 = iaDb::printf(' AND a.`id` != :id AND a.`category_id` = :category_id ORDER BY a.`date_added` DESC LIMIT :limit', array(
				'id' => (int)$listingData['id'],
				'category_id' => (int)$listingData['category_id'],
				'limit' => $defaultLimit
			));
			if ($data = $iaDb->getAll($sql . $sql2))
			{
				$iaArticle->wrapValues($data);
				$iaView->assign('most_recent_articles', $data);
			}
		}
	}

	if ($iaView->blockExists('related_articles'))
	{
		$listingData = $iaView->getValues('item');
		if (isset($listingData['category_id']) && $listingData['category_id'])
		{
			$max = (int)$iaDb->one('MAX(`id`) as `max`', null, 'articles');
			$sql2 = iaDb::printf(' AND a.`id` != :id AND a.`category_id` = :category_id ' . $iaCore->iaDb->orderByRand($max, 'a.`id`') . ' ORDER BY RAND() LIMIT :limit', array(
				'id' => (int)$listingData['id'],
				'category_id' => (int)$listingData['category_id'],
				'limit' => $defaultLimit
			));
			if ($data = $iaDb->getAll($sql . $sql2))
			{
				$iaArticle->wrapValues($data);
				$iaView->assign('related_articles', $data);
			}
		}
	}

	iaLanguage::set('no_articles2', array('url' => $iaCore->packagesData['publishing']['url'] . 'add/'));

	if ($iaView->blockExists('new_articles'))
	{
		$data = $iaArticle->get(' ORDER BY t1.`date_added` DESC', 0, $iaCore->get('art_per_block_new', 6));
		$foundRows = $iaArticle->iaDb->foundRows();

		if ($data)
		{
			$iaItem = $iaCore->factory('item');
			$data = $iaItem->updateItemsFavorites($data, $iaArticle->getItemName());
			$iaView->assign('session_id', session_id());

			$iaArticle->wrapValues($data);
		}

		$iaView->assign('new_articles', $data);
	}

	if ($iaView->blockExists('top_categories'))
	{
		$data = $iaCore->factoryPackage('articlecat', 'publishing')
			->get($iaCore->get('art_view_category', true) ? '' : ' AND `num_all_articles` > 0', 0, $iaCore->get('article_top_categories', 12), 1, '`num_all_articles` DESC, `title` ASC');
		if ($data)
		{
			$iaView->assign('top_categories', $data);
		}
	}

	if ($iaView->blockExists('priority_categories'))
	{
		$data = $iaCore->factoryPackage('articlecat', 'publishing')->get(' && `priority` = 1 ' . ($iaCore->get('art_view_category', true) ? '' : ' && `num_all_articles` > 0'), 0, $iaCore->get('article_top_categories', 12), 1, '`title` ASC');
		if ($data)
		{
			foreach ($data as $key => $value)
			{
				$data[$key]['articles'] = $iaArticle->get(" AND t1.`category_id` IN ({$value['child']}) ORDER BY t1.`date_added` DESC", 0, $iaCore->get('art_per_block_featured_categories', 6));
			}

			$iaView->assign('priority_categories', $data);
		}
	}

	if ($iaView->blockExists('articles_archive'))
	{
		$data = array();
		if ($array = $iaDb->all('DISTINCT(MONTH(`date_added`)) `month`, YEAR(`date_added`) `year`', "`status` = 'active' GROUP BY `date_added` ORDER BY `date_added` DESC", 0, 6, $iaArticle::getTable()))
		{
			$url = $iaCore->packagesData['publishing']['url'] . 'date' . IA_URL_DELIMITER;
			foreach ($array as $date)
			{
				$data[] = array(
					'url' => $url . $date['year'] . IA_URL_DELIMITER . $date['month'] . IA_URL_DELIMITER,
					'month' => $date['month'],
					'year' => $date['year']
				);
			}
		}

		$iaView->assign('articles_archive', $data);
	}

	if ($iaView->blockExists('filters') && $iaArticle->getItemName() == $iaView->get('filtersItemName'))
	{
		$iaArticlecat = $iaCore->factoryPackage('articlecat', $iaArticle->getPackageName());

		$categories = $iaDb->all(array('id', 'title'), "`status` = 'active' AND `level` = 1 ORDER BY `title`", null, null, $iaArticlecat::getTable());

		if (!empty($categories))
		{
			$iaView->assign('publishingFiltersCategories', $categories);
		}
	}
}