<?php
/******************************************************************************
 *
 * Subrion Articles Publishing Script
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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

$iaArticle = $iaCore->factoryModule('article', IA_CURRENT_MODULE);

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : $iaDb->one('id', '`parent_id` = 0', 'articles_categories');

	$where = "`parent_id` = $categoryId && `status` = 'active'";
	$where .= " ORDER BY `title`";

	$data = [];
	$rows = $iaDb->all(['id', 'title', 'title_alias', 'locked', 'child'], $where, null, null, 'articles_categories');
	foreach ($rows as &$row)
	{
		$data[] = [
			'id' => (int)$row['id'],
			'text' => $row['title'],
			'children' => $row['child'] && $row['child'] != $row['id']
		];
	}

	$iaView->assign($data);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$pagination = [
		'total' => 0,
		'url' => IA_SELF . '?page={page}',
		'limit' => $iaCore->get('art_perpage', 10)
	];
	$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
	$start = ($page - 1) * $pagination['limit'];
	$where = '';
	$order = '';

	$categories = [];
	$category = null;
	$rssFeed = null;
	$articles = [];

	$orders = ['date_added-asc', 'date_added-desc', 'title-asc', 'title-desc', 'views_num-asc', 'views_num-desc'];

	if (!isset($_SESSION['p_order']))
	{
		$_SESSION['p_order'] = $orders[0];
	}
	list($p_sort, $p_type) = explode('-', $_SESSION['p_order']);

	if (isset($_GET['sort_by']))
	{
		$p_sort = $_GET['sort_by'];
		$_POST['sort_by'] = $p_sort . '-' . $p_type;
	}
	if (isset($_GET['order_type']))
	{
		$p_type = $_GET['order_type'];
		$_POST['sort_by'] = $p_sort . '-' . $p_type;
	}

	// sort by, and save in session
	if (isset($_POST['sort_by']) && in_array($_POST['sort_by'], $orders))
	{
		$_SESSION['p_order'] = $_POST['sort_by'];
	}

	if (isset($_SESSION['p_order']) && in_array($_SESSION['p_order'], $orders))
	{
		list($sort, $type) = explode('-', $_SESSION['p_order']);
		$iaView->assign('sort_name', $sort);
		$iaView->assign('sort_type', $type);
		$order = ' `' . $sort . '` ' . $type;
	}

	switch ($iaView->name())
	{
		case 'popular_articles':
			$articles = $iaArticle->get($where . " ORDER BY t1.`views_num` DESC", $start, $pagination['limit']);

			$rssFeed = 'popular';
			$pagination['total'] = $iaDb->foundRows();
			$iaView->assign('articles_sorting', false);

			break;

		case 'latest_articles':
			$articles = $iaArticle->get($where . " ORDER BY t1.date_added DESC", $start, $pagination['limit']);

			$rssFeed = 'latest';
			$pagination['total'] = $iaDb->foundRows();
			$iaView->assign('articles_sorting', false);

			break;

		case 'publishing_home':
			$iaArticlecat = $iaCore->factoryModule('articlecat', IA_CURRENT_MODULE);

			// get current category
			$category = $iaArticlecat->getCategory("`title_alias` = '" . (count($iaCore->requestPath) > 0 ? iaSanitize::sql(implode('/', $iaCore->requestPath)) . '/' : '') . "'");
			if (empty($category))
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}
			$iaView->set('subpage', $category['id']);

			if ($category['level'] > 0)
			{
				$iaView->title($category['title']);
			}

			if (0 == $category['parent_id'])
			{
				$rssFeed = 'latest';
			}

			if (0 != $category['parent_id'] && $category['parents'])
			{
				// build breadcrumb
				$parents = $iaDb->all(['title' => 'title_' . $iaView->language, 'title_alias'],
					"`id` IN({$category['parents']}) AND `parent_id` != 0 ORDER BY `level`",
					0, 0, 'articles_categories');
				foreach ($parents as $p)
				{
					iaBreadcrumb::toEnd($p['title'], $iaArticlecat->url('view', $p));
				}
			}

			$categories = $iaArticlecat->get(($iaCore->get('art_view_category', true) ? '' : " AND `num_all_articles` > 0"), 0, 0, $category['id']);

			$order = " ORDER BY t1." . $order;

			$where .= $iaCore->get('articles_show_children') ? " AND t1.`category_id` IN ({$category['child']}) " : " AND t1.`category_id` = ({$category['id']}) " ;

			$articles = $iaArticle->get($where . $order, $start, $pagination['limit']);

			$pagination['total'] = $iaDb->foundRows();
			$pagination['template'] = IA_MODULE_URL . $category['title_alias'] . '?page={page}';

			$iaView->set('description', $category['meta_description']);
			$iaView->set('keywords', $category['meta_keywords']);

			break;

		case 'my_articles':
			$rssFeed = 'author/' . iaUsers::getIdentity()->username;

			if (!iaUsers::hasIdentity())
			{
				return iaView::accessDenied();
			}

			$articles = $iaArticle->get('AND t1.`member_id` = ' . iaUsers::getIdentity()->id . ' ORDER BY t1.' . $order, $start, $pagination['limit'], true);

			$pagination['total'] = $iaDb->foundRows();

			break;

		case 'date_articles':
			$baseUrl = 'date/';

			if (!isset($iaCore->requestPath[1]))
			{
				if ($dates = $iaArticle->get('ORDER BY t1.`date_added`'))
				{
					$years = [];
					$months = [];

					$months['01']['name'] = 'month1';
					$months['02']['name'] = 'month2';
					$months['03']['name'] = 'month3';
					$months['04']['name'] = 'month4';
					$months['05']['name'] = 'month5';
					$months['06']['name'] = 'month6';
					$months['07']['name'] = 'month7';
					$months['08']['name'] = 'month8';
					$months['09']['name'] = 'month9';
					$months['10']['name'] = 'month10';
					$months['11']['name'] = 'month11';
					$months['12']['name'] = 'month12';

					foreach ($dates as $key => $date)
					{
						$fullDate = substr($date['date_added'], 0, strpos($date['date_added'], ' '));
						$fullDate = explode('-', $fullDate);
						$years[$fullDate[0]] = [];
					}

					foreach ($years as $y => $year)
					{
						$years[$y]['months'] = $months;

						foreach ($months as $j => $t)
						{
							foreach ($dates as $key => $date)
							{
								$fullDate = substr($date['date_added'], 0, strpos($date['date_added'], ' '));
								$fullDate = explode('-', $fullDate);

								if ($fullDate[1] == $j && $fullDate[0] == $y)
								{
									if (isset($iaCore->requestPath[0]) && $iaCore->requestPath[0] == $y)
									{
										$months[$j]['articles'] = true;
										$show['months'] = true;
									}
									elseif (!isset($iaCore->requestPath[0]) && !isset($iaCore->requestPath[1]))
									{
										$years[$y]['months'][$j]['articles'] = true;
										$show['years'] = true;
									}

									break 1;
								}
							}
						}
					}

					$iaView->assign('show', $show);
					$iaView->assign('years', $years);
					$iaView->assign('months', $months);

					if (isset($iaCore->requestPath[0]) && !isset($iaCore->requestPath[1]))
					{
						iaBreadcrumb::replaceEnd($iaCore->requestPath[0], IA_SELF);
					}
				}
				else
				{
					$iaView->setMessages(iaLanguage::get('no_articles'), iaView::ALERT);
				}
			}

			if (isset($iaCore->requestPath[0]) && isset($iaCore->requestPath[1]))
			{
				$year = (int)$iaCore->requestPath[0];
				$month = (int)$iaCore->requestPath[1];
				$day = null;

				$stmt = sprintf('AND MONTH(t1.`date_added`) = %d AND YEAR(t1.`date_added`) = %d', $month, $year);

				if ($year > 1980 && $year < 2200 && $month >= 1 && $month <= 12)
				{
					iaBreadcrumb::add($year, IA_MODULE_URL . $baseUrl . $year . IA_URL_DELIMITER);

					if (isset($iaCore->requestPath[2]) && is_numeric($iaCore->requestPath[1]))
					{
						$day = (int)$iaCore->requestPath[2];
						if ($day > 0 && $day <= 31)
						{
							$stmt .= ' AND DAY(t1.`date_added`) = ' . $day;

							iaBreadcrumb::add(iaLanguage::get('month' . $month), IA_MODULE_URL . $baseUrl . $year . IA_URL_DELIMITER . $month . IA_URL_DELIMITER);
							iaBreadcrumb::replaceEnd($day, IA_SELF);
						}
					}
					else
					{
						iaBreadcrumb::replaceEnd(iaLanguage::get('month' . $month), IA_SELF);
					}

					$stmt .= ' ORDER BY t1.' . $order;
				}

				$articles = $iaArticle->get($stmt, $start, $pagination['limit']);

				$pagination['total'] = $iaDb->foundRows();

				$caption = iaLanguage::getf('articles_by_date', ['year' => $year, 'month' => iaLanguage::get('month' . $month), 'day' => is_numeric($day) ? $day : '']);
				$iaView->caption($caption);

				$iaView->assign('curr_year', $year);
				$iaView->assign('curr_month', $month);
			}
	}

	if ($articles)
	{
		$iaItem = $iaCore->factory('item');
		$articles = $iaItem->updateItemsFavorites($articles, $iaArticle->getItemName());

		$iaView->assign('articles', $articles);
		$iaView->assign('pagination', $pagination);
	}
	elseif ('publishing_home' == $iaView->name())
	{
		if (isset($category['parent_id']) && $category['parent_id'] != 0 && isset($category['level']) && $category['level'] > 0)
		{
			$iaView->setMessages(iaLanguage::getf('no_articles2', ['url' => IA_MODULE_URL . 'add/?category=' . $category['id']]), iaView::ALERT);
		}
	}
	elseif (!('date_articles' == $iaView->name() && !isset($iaCore->requestPath[1])))
	{
		$iaView->setMessages(iaLanguage::get('no_articles'), iaView::ALERT);
	}

	$rssFeed = ($rssFeed ? $rssFeed : substr($category['title_alias'], 0, -1)) . '.' . iaCore::EXTENSION_XML;

	if ($iaAcl->isAccessible('add_article', iaCore::ACTION_ADD))
	{
		$pageActions[] = [
			'icon' => 'plus-square',
			'title' => iaLanguage::get('add_article'),
			'url' => IA_MODULE_URL . 'add/' . (is_array($category) ? '?category=' . $category['id'] : '')
		];
	}

	$pageActions[] = [
		'icon' => 'rss',
		'title' => null,
		'url' => IA_MODULE_URL . 'rss/' . $rssFeed,
		'classes' => 'btn-warning'
	];

	$iaView->set('actions', $pageActions);
	$iaView->set('filtersItemName', $iaArticle->getItemName());

	$iaView->assign('fields', $iaCore->factory('field')->filter($iaArticle->getItemName(), $articles));
	$iaView->assign('category', $category);
	$iaView->assign('categories', $categories);

	$iaView->display();
}

if (iaView::REQUEST_XML == $iaView->getRequestType())
{
	$stmt = ' ORDER BY t1.`date_added` DESC';
	$limit = (int)$iaCore->get('art_perpage', 10);

	if (isset($iaCore->requestPath[0]) && $iaCore->requestPath[0] == 'author' && isset($iaCore->requestPath[1]))
	{
		if ($memberInfo = $iaDb->row_bind(['fullname', 'id'], '`username` = :user AND `status` = :status', ['user' => $iaCore->requestPath[1], 'status' => iaCore::STATUS_ACTIVE], iaUsers::getTable()))
		{
			$stmt = 'AND t1.`member_id` = ' . $memberInfo['id'] . $stmt;

			$category = iaLanguage::get('author') . ': ' . $memberInfo['fullname'];
			$articles = $iaArticle->get($stmt, 0, $limit);
		}
	}
	elseif (isset($iaCore->requestPath[0]) && $iaCore->requestPath[0] == 'popular')
	{
		$articles = $iaArticle->get('AND 1 ORDER BY t1.`views_num` DESC', 0, $limit);
	}
	elseif (isset($iaCore->requestPath[0]) && $iaCore->requestPath[0] == 'latest')
	{
		$articles = $iaArticle->get('AND 1 ORDER BY t1.`date_added` DESC', 0, $limit);
	}
	else
	{
		$stmt = "AND t2.`title_alias` = '" . (implode(IA_URL_DELIMITER, $iaCore->requestPath) . IA_URL_DELIMITER) . "'" . $stmt;
		$articles = $iaArticle->get($stmt, 0, $limit);
	}

	$output = [
		'title' => $iaCore->get('site'),
		'description' => '',
		'url' => IA_URL,
		'item' => []
	];

	foreach ($articles as $article)
	{
		$output['item'][] = [
			'title' => $article['title'],
			'guid' => $iaArticle->url('view', $article),
			'link' => $iaArticle->url('view', $article),
			'pubDate' => date('D, d M Y H:i:s T', strtotime($article['date_added'])),
			'description' => iaSanitize::tags($article['summary']),
			'category' => isset($category) ? $category : $article['category_title']
		];
	}

	$iaView->assign('channel', $output);
}
