<?php
//##copyright##

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$iaArticlecat = $iaCore->factoryModule('articlecat', IA_CURRENT_MODULE);

	$entriesNum = $iaDb->one_bind(iaDb::STMT_COUNT_ROWS, '`status` = :status', ['status' => iaCore::STATUS_ACTIVE], iaArticlecat::getTable());
	$dynamicLoadMode = ($entriesNum > 500);

	$parentId = $iaArticlecat->getRootId();

	if ($dynamicLoadMode)
	{
		empty($_GET['id']) || $parentId = (int)$_GET['id'];
		$clause = '`parent_id` = :parent AND `status` = :status ORDER BY `title`';

		$iaDb->bind($clause, ['parent' => $parentId, 'status' => iaCore::STATUS_ACTIVE]);
	}
	else
	{
		$clause = '`parent_id` != 0 AND `status` = :status ORDER BY `title`';

		$iaDb->bind($clause, ['status' => iaCore::STATUS_ACTIVE]);
	}

	$categories = $iaArticlecat->all($clause, ['id', 'parent_id', 'title', 'locked', 'child']);
	$output = [];

	foreach ($categories as $row)
	{
		$entry = ['id' => $row['id'], 'text' => $row['title']];
		empty($row['locked']) || $entry['state'] = ['disabled' => true];

		$dynamicLoadMode
			? $entry['children'] = $row['child'] && $row['child'] != $row['id']
			: $entry['parent'] = ($parentId == $row['parent_id']) ? '#' : $row['parent_id'];

		$output[] = $entry;
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (!$iaCore->get('articles_add_guest', true) && !iaUsers::hasIdentity())
	{
		return iaView::accessDenied(iaLanguage::getf('article_add_no_auth', ['base_url' => IA_URL]));
	}

	$iaField = $iaCore->factory('field');
	$iaUtil = $iaCore->factory('util');
	$iaArticle = $iaCore->factoryModule('article', IA_CURRENT_MODULE);

	$itemData = [];

	$id = 0;
	if (isset($iaCore->requestPath[0]) && is_numeric($iaCore->requestPath[0]))
	{
		$id = (int)$iaCore->requestPath[0];
	}

	$article = [
		'category_id' => isset($_GET['category']) ? (int)$_GET['category'] : 0
	];

	if (iaCore::ACTION_EDIT == $pageAction)
	{
		if (empty($id))
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}
		else
		{
			$article = $iaArticle->getById($id);

			if (empty($article))
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}
			if ($article['member_id'] != iaUsers::getIdentity()->id)
			{
				return iaView::errorPage(iaView::ERROR_FORBIDDEN);
			}
		}
	}

	if (iaCore::ACTION_DELETE == $pageAction)
	{
		$result = $iaArticle->delete($id);
		if ($result)
		{
			$iaCore->factory('log')->write(iaLog::ACTION_DELETE, ['item' => 'article', 'name' => $article['title'], 'id' => $id]);
		}

		iaUtil::redirect(iaLanguage::get('thanks'), iaLanguage::get('art_deleted'), $iaArticle->url('my', []));
	}


	$iaPlan = $iaCore->factory('plan');
	$iaView->assign('plans', $iaPlan->getPlans($iaArticle->getItemName()));

	// Save article
	if (isset($_POST['data-article']) || isset($_POST['draft']))
	{
		$messages = [];
		$error = false;

		list($itemData, $error, $messages) = $iaField->parsePost($iaArticle->getItemName(), $article);

		if (!iaUsers::hasIdentity() && !iaValidate::isCaptchaValid())
		{
			$error = true;
			$messages[] = iaLanguage::get('confirmation_code_incorrect');
		}

		if (empty($itemData['summary']))
		{
			$itemData['summary'] = iaSanitize::snippet($_POST['body'], $iaCore->get('snip_len'));
		}

		$itemData['category_id'] = (int)$_POST['tree_id'];
		if (empty($itemData['category_id']))
		{
			$error = true;
			$messages[] = iaLanguage::getf('field_is_not_selected', ['field' => iaLanguage::get('category')]);
		}
		else
		{
			$row = $iaDb->row('locked', iaDb::convertIds($itemData['category_id']), 'articles_categories');

			if ($row && $row['locked'])
			{
				$error = true;
				$messages[] = iaLanguage::get('articles_category_locked');
			}
		}

		// limitation enabled
		if ($linksLimit = (int)$iaCore->get('article_max_links'))
		{
			$matches = [];
			$count = preg_match_all('/<a[^>]*>(.*?)<\/a>/', $itemData['body'], $matches);

			if ($count > $linksLimit)
			{
				$error = true;
				$messages[] = iaLanguage::getf('error_links_limit_reached', ['allowed' => $linksLimit, 'found' => $count]);
			}
		}

		if (isset($_POST['draft']))
		{
			$itemData['status'] = iaCore::STATUS_DRAFT;
		}
		elseif (isset($_POST['preview']))
		{
			$itemData['status'] = iaArticle::STATUS_HIDDEN;
		}
		elseif ($iaCore->get('article_auto_approval'))
		{
			$itemData['status'] = iaCore::STATUS_ACTIVE;
		}
		else
		{
			$itemData['status'] = iaCore::STATUS_APPROVAL;
		}

		$itemData['meta_description'] = iaSanitize::tags($itemData['summary']);
		$itemData['meta_description'] = str_replace(PHP_EOL, '', $itemData['meta_description']);

		if (empty($itemData['meta_keywords']) && $itemData['body'])
		{
			$itemData['meta_keywords'] = iaUtil::getMetaKeywords($itemData['body']);
		}

		if (!$error)
		{
			if (iaCore::ACTION_ADD == $pageAction)
			{
				$id = $iaArticle->createPostingSession();
			}

			$result = $iaArticle->update($itemData, $id);

			$iaCore->startHook('phpAddItemAfterAll', [
				'type' => iaCore::FRONT,
				'listing' => $id,
				'item' => $iaArticle->getItemName(),
				'data' => $itemData,
				'old' => $article
			]);

			if ($result && iaCore::ACTION_ADD == $pageAction)
			{
				$iaCore->factory('log')->write(iaLog::ACTION_CREATE, ['item' => 'article', 'name' => $itemData['title'], 'id' => $id]);
			}

			$iaArticle->sendMail($id);

			$messages[] = iaLanguage::get($iaCore->get('article_auto_approval') ? 'art_added' : 'art_approval');

			$result = $iaArticle->getById($id);
			$url = $iaArticle->url('view', $result);

			if (isset($_POST['plan_id']) && $_POST['plan_id'])
			{
				$plan = $iaPlan->getById($_POST['plan_id']);
				if ($plan['cost'] > 0)
				{
					$url = $iaPlan->prePayment($iaArticle->getItemName(), $result, $plan['id'], $url);

					$iaArticle->update(['status' => iaArticle::STATUS_PENDING], $id);

					iaUtil::redirect(iaLanguage::get('redirect'), $messages, $url);
				}
			}

			$iaView->setMessages($messages, iaView::SUCCESS);

			iaUtil::go_to($url);
		}
		else
		{
			$article['category_id'] = $itemData['category_id'];
		}

		$iaView->assign('item', $itemData);

		$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
	}
	else
	{
		if (isset($_POST['title']))
		{
			$article = [
				'title' => $_POST['title'],
				'body' => $_POST['body'],
				'category_id' => $_POST['tree_id'],
				'url' => $_POST['url'],
				'url_description' => $_POST['url_description']
			];
		}
		elseif (empty($article))
		{
			$url = iaUsers::getIdentity()->articles_url;
			$url || $url = 'http://';

			$article = [
				'title' => '',
				'body' => '',
				'category_id' => empty($_GET['category']) ? 0 : (int)$_GET['category_id'],
				'url' => $url,
				'url_description' => iaUsers::getIdentity()->articles_url_description
			];
		}
		if (empty($article['image']))
		{
			unset($article['image']);
		}

		$iaView->assign('item', $article);
	}

	if ($iaCore->get('articles_categories_selector') == 'Handy javascript tree')
	{
		$category = $iaCore->factoryModule('articlecat', IA_CURRENT_MODULE)->getCategory(iaDb::convertIds($article['category_id']));

		$iaView->assign('category', $category);
	}
	else
	{
		$categoryOptions = $iaCore->factoryModule('common', IA_CURRENT_MODULE, iaCore::FRONT)
			->getCategoriesTree($article['category_id']);

		$iaView->assign('categories', $categoryOptions);
	}

	$iaView->assign('sections', $iaField->getTabs($iaArticle->getItemName(), $article));

	$iaView->display('manage');
}