<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	// get article id
	$articleId = (int)end($iaCore->requestPath);
	if (empty($articleId))
	{
		$articleId = (int)end($iaView->url);
		if (empty($articleId))
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}
	}

	$iaField = $iaCore->factory('field');

	$iaArticle = $iaCore->factoryPackage('article', IA_CURRENT_PACKAGE);
	$article = $iaArticle->getById($articleId, true);
	if (empty($article) || ($article['status'] == iaCore::STATUS_APPROVAL && $article['member_id'] != iaUsers::getIdentity()->id))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	if ($article['status'] == $iaArticle::STATUS_DRAFT && (!iaUsers::hasIdentity() || iaUsers::getIdentity()->id != $article['member_id']))
	{
		return iaView::errorPage(iaView::ERROR_FORBIDDEN);
	}

	$article['item'] = $iaArticle->getItemName();
	$article['pictures'] = empty($article['pictures']) ? array() : explode(',', $article['pictures']);

	$iaArticle->incrementViewsCounter($article['id']);

	$iaCore->startHook('phpViewListingBeforeStart', array(
		'listing' => $articleId,
		'item' => $article['item'],
		'title' => $article['title'],
		'url' => $iaArticle->url('view', $article),
		'desc' => $article['summary']
	));

	// get account information
	if ($article['member_id'])
	{
		if ($author = $iaCore->factory('users')->getInfo($article['member_id']))
		{
			$iaItem = $iaCore->factory('item');

			if (iaUsers::hasIdentity() && $article['member_id'] == iaUsers::getIdentity()->id)
			{
				$iaItem->setItemTools(array(
					'id' => 'action-edit',
					'title' => iaLanguage::get('edit_article'),
					'attributes' => array(
						'href' => $iaArticle->url(iaCore::ACTION_EDIT, $article)
					)
				));
				$iaItem->setItemTools(array(
					'id' => 'action-delete',
					'title' => iaLanguage::get('delete_article'),
					'attributes' => array(
						'href' => $iaArticle->url(iaCore::ACTION_DELETE, $article),
						'class' => 'js-delete-article'
					)
				));
			}

			if ($iaView->blockExists('author_info'))
			{
				$author['rss'] = IA_URL . $iaDb->one('`alias`', "`name` = 'rss_articles'", 'pages') . 'author' . IA_URL_DELIMITER . $author['username'] . '.' . iaCore::EXTENSION_XML;
				$author['articles_num'] = $iaDb->one_bind(iaDb::STMT_COUNT_ROWS, '`member_id` = :user AND `status` = :status', array('status' => iaCore::STATUS_ACTIVE, 'user' => (int)$article['member_id']), iaArticle::getTable());
			}

			if (isset($author['adsense_id']))
			{
				if (empty($author['adsense_id']) || !preg_match('#^ca\-pub\-[0-9]{16}$#', $author['adsense_id']))
				{
					unset($author['adsense_id']);
				}
			}
			$iaView->assign('author', $author);

			// process default article url
			if (!$article['url'])
			{
				$article['url'] = $author['articles_url'];
				$article['url_description'] = $author['articles_url_description'];
			}
		}
	}

	// set subpage to display blocks
	$iaView->set('subpage', $article['category_id']);

	// breadcrumb
	if ($article['category_id'])
	{
		if (0 != $article['category_parent'] && $article['category_parents'])
		{
			$iaArticleCat = $iaCore->factoryPackage('articlecat', IA_CURRENT_PACKAGE);
			// build breadcrumb
			$parents = $iaDb->all(array('title', 'title_alias'),
				"`id` IN ({$article['category_parents']}) AND `parent_id` != 0 ORDER BY `level`",
				null, null, iaArticlecat::getTable());
			foreach ($parents as $p)
			{
				iaBreadcrumb::add($p['title'], $iaArticleCat->url('view', $p), -1);
			}
		}
	}

	iaBreadcrumb::replaceEnd($article['title'], IA_SELF);
	//

	// get fieldgroups
	list($tabs, $fieldgroups) = $iaField->generateTabs($iaField->filterByGroup($article, $iaArticle->getItemName()));

	// compose tabs
	$sections = array_merge(array('common' => $fieldgroups), $tabs);
	$iaView->assign('sections', $sections);

	if (iaCore::STATUS_ACTIVE != $article['status'])
	{
		$iaView->setMessages(iaLanguage::get('article_' . $article['status']), iaView::ALERT);
	}

	$iaItem = $iaCore->factory('item');
	$article = array_shift($iaItem->updateItemsFavorites(array($article), $iaArticle->getItemName()));

	// get next & previous articles
	$article['prev_article'] = $iaArticle->getPreviousArticle($article['date_added'], $article['category_id']);
	$article['next_article'] = $iaArticle->getNextArticle($article['date_added'], $article['category_id']);

	// get more author articles
	if ($iaView->blockExists('author_articles') && $article['member_id'])
	{
		$authorArticles = array();
		$where = 'AND `t1`.`member_id` = ' . $article['member_id'] . ' AND t1.`id` != ' . $articleId;

		$authorArticles = $iaArticle->get($where, 0, $iaCore->get('art_perpage_block', 5));
		$iaView->assign('author_articles', $authorArticles);
	}

	if ($iaCore->get('articles_source_link'))
	{
		$article['body'] .= iaLanguage::getf('article_source_url', array('url' => $iaArticle->url('view', $article)));
	}
	$iaView->assign('item', $article);
	$iaView->assign('session_id', session_id());

	// define page params
	$iaView->set('keywords', $article['meta_keywords']);
	$iaView->set('description', $article['meta_description']);

	$iaView->title(isset($article['title']) ? $article['title'] : iaLanguage::get('page_title_' . $iaView->name()));

	$iaView->display('view');
}