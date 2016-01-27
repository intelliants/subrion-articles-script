<?php
//##copyright##

class iaBackendController extends iaAbstractControllerPackageBackend
{
	protected $_name = 'articles';

	protected $_helperName = 'article';

	protected $_gridColumns = "a.`id`, a.`title`, a.`date_added`, a.`date_modified`, a.`summary`, a.`status`, a.`category_id`, a.`member_id`, 1 `update`, 1 `delete`, c.`title` `category_title`, c.`title_alias` `category_alias`, c.`level` `category_level`, IF(m.`fullname` != '', m.`fullname`, IFNULL(m.`username`, '')) `member`, m.`email` ";
	protected $_gridFilters = array('status' => self::EQUAL, 'title' => self::LIKE);
	protected $_gridQueryMainTableAlias = 'a';

	protected $_phraseAddSuccess = 'article_added';

	protected $_activityLog = true;

	private $_validUrlProtocols = array('http://', 'https://');


	protected function _modifyGridParams(&$conditions, &$values, array $params)
	{
		if (!empty($params['member']))
		{
			$stmt = iaDb::printf("`fullname` LIKE ':member%' OR  `username` LIKE ':member' ", array('member' => iaSanitize::sql($params['member'])));
			$memberId = $this->_iaDb->one(iaDb::ID_COLUMN_SELECTION, $stmt, iaUsers::getTable());

			$conditions[] = 'a.`member_id` = :member_id';
			$values['member_id'] = $memberId;
		}
	}

	protected function _gridQuery($columns, $where, $order, $start, $limit)
	{
		$iaArticlecat = $this->_iaCore->factoryPackage('articlecat', $this->getPackageName(), iaCore::ADMIN);

		$sql =
			'SELECT :columns ' .
			'FROM `:prefix:table_articles` a ' .
			'LEFT JOIN `:prefix:table_categories` c ON (a.`category_id` = c.`id`) ' .
			'LEFT JOIN `:prefix:table_members` m ON (a.`member_id` = m.`id`) ' .
			'WHERE :where :order ' .
			'LIMIT :start, :limit';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_articles' => $this->getTable(),
			'table_categories' => $iaArticlecat::getTable(),
			'table_members' => iaUsers::getTable(),
			'columns' => $columns,
			'where' => $where,
			'order' => $order,
			'start' => $start,
			'limit' => $limit
		));

		return $this->_iaDb->getAll($sql);
	}

	protected function _entryAdd(array $entryData)
	{
		$entryData['date_added'] = date(iaDb::DATETIME_FORMAT);
		$entryData['date_modified'] = date(iaDb::DATETIME_FORMAT);

		if (isset($entryData['url']))
		{
			$entryData['url'] = $this->_processUrl($entryData['url']);
		}

		return parent::_entryAdd($entryData);
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		$entryData['date_modified'] = date(iaDb::DATETIME_FORMAT);

		if (isset($entryData['url']))
		{
			$entryData['url'] = $this->_processUrl($entryData['url']);
		}

		return  parent::_entryUpdate($entryData, $entryId);
	}

	public function updateCounters($entryId, array $entryData, $action, $previousData = null)
	{
		$this->_iaCore->factoryPackage('articlecat', $this->getPackageName(), iaCore::ADMIN)
			->calculateArticles();

		if (iaCore::ACTION_EDIT == $action)
		{
			// notify owner on status change
			if (isset($entryData['status']) && in_array($entryData['status'], array(iaArticle::STATUS_SUSPENDED, iaArticle::STATUS_REJECTED, iaCore::STATUS_ACTIVE)))
			{
				$entry = $this->getById($entryId);
				$owner = $this->_iaCore->factory('users')->getInfo($entry['member_id']);
				$action = $entryData['status'];

				if (iaCore::STATUS_ACTIVE == $entryData['status'])
				{
					$action = iaCore::STATUS_APPROVAL;
				}

				$this->getHelper()->sendMail('article_' . $action, $owner['email'], $entry);
			}
		}
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry = array(
			'member_id' => iaUsers::getIdentity()->id,
			'category_id' => 0,
			'featured' => false,
			'sponsored' => false,
			'status' => iaCore::STATUS_ACTIVE,
			'sticky' => false,
			'url' => ''
		);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$fields = $this->_iaField->getByItemName($this->getHelper()->getItemName());
		list($entry, , $this->_messages, ) = $this->_iaField->parsePost($fields, $entry);

		$entry['category_id'] = (int)$data['tree_id'];
		$entry['sticky'] = (int)$data['sticky'];
		$entry['title_alias'] = iaSanitize::alias(empty($data['title_alias']) ? $data['title'] : $data['title_alias']);

		if ($this->_iaCore->get('auto_generate_keywords')
			&& empty($data['meta_keywords']) && !empty($entry['body']))
		{
			$data['meta_keywords'] = iaUtil::getMetaKeywords($entry['body']);
		}

		return !$this->getMessages();
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		parent::_assignValues($iaView, $entryData);

		if (isset($entryData['url']) && empty($entryData['url']))
		{
			$entryData['url'] = $this->_validUrlProtocols[0];
		}

		// category
		$iaArticleCat = $this->_iaCore->factoryPackage('articlecat', $this->getPackageName(), iaCore::ADMIN);
		$parent = $iaArticleCat->getById($entryData['category_id']);

		$entryData['parents'] = $parent['parents'];

		$iaView->assign('parent', $parent);
		$iaView->assign('statuses', $this->getHelper()->getStatuses());
	}


	protected function _getJsonAlias($params)
	{
		$title = isset($params['title']) ? $params['title'] : '';
		$id = empty($params['id']) ? $this->_iaDb->getNextId() : (int)$params['id'];

		$category = isset($params['category']) ? (int)$params['category'] : 0;
		$alias = '';
		if ($category)
		{
			$alias = $this->_iaDb->one('title_alias', iaDb::convertIds($category), 'articles_categories');
		}

		if (!$this->_iaCore->get('articles_compact_url'))
		{
			$alias = 'article' . IA_URL_DELIMITER . $alias;
		}

		$alias = IA_PACKAGE_URL . $alias . $id . '-' . iaSanitize::alias($title) . '.html';

		return array('data' => $alias);
	}

	private function _processUrl($url)
	{
		$result = $url;

		if (in_array($result, $this->_validUrlProtocols))
		{
			$result = '';
		}
		else
		{
			$found = false;
			foreach ($this->_validUrlProtocols as $protocol)
			{
				if (stripos($result, $protocol) !== false)
				{
					$found = true;
				}
			}
			if (!$found)
			{
				$result = $this->_validUrlProtocols[0] . $result;
			}
		}

		return $result;
	}
}