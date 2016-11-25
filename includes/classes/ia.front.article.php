<?php
//##copyright##

class iaArticle extends abstractPublishingPackageFront
{
	protected static $_table = 'articles';

	protected $_itemName = 'articles';

	protected $_statuses = array(iaCore::STATUS_ACTIVE, iaCore::STATUS_APPROVAL, self::STATUS_REJECTED, self::STATUS_HIDDEN, self::STATUS_SUSPENDED, self::STATUS_DRAFT, self::STATUS_PENDING);

	public $coreSearchEnabled = true;
	public $coreSearchOptions = array(
		'tableAlias' => 't1',
		'regularSearchStatements' => array("t1.`title` LIKE '%:query%' OR t1.`body` LIKE '%:query%'"),
		'customColumns' => array('keywords', 'c', 'sc')
	);

	private $_urlPatterns = array(
		'default' => ':base:action/:id/',
		'view' => ':base:category_alias:id-:title_alias.html',
		'my' => 'profile/articles/'
	);

	protected $_validProtocols = array('http://', 'https://');


	public function url($action, $data)
	{
		$data['base'] = $this->getInfo('url');
		$data['action'] = $action;
		$data['category_alias'] = isset($data['category_alias']) ? $data['category_alias'] : '';
		$data['title_alias'] = isset($data['title_alias']) ? $data['title_alias'] : '';

		unset($data['title']);

		if (!isset($this->_urlPatterns[$action]))
		{
			$action = 'default';
		}
		if ('view' == $action && !$this->iaCore->get('articles_compact_url'))
		{
			$data['base'] .= 'article/';
		}

		return iaDb::printf($this->_urlPatterns[$action], $data);
	}

	public function accountActions($params)
	{
		$url = '';
		if ($params['item']['member_id'] == iaUsers::getIdentity()->id)
		{
			$url = $this->url(iaCore::ACTION_EDIT, $params['item']);
		}

		return array($url, '');
	}

	// called at search pages
	public function coreSearch($stmt, $start, $limit, $order)
	{
		$stmt = $stmt ? ('AND ' . $stmt . $order) : null;
		$rows = $this->get($stmt, $start, $limit);

		return array($this->iaDb->foundRows(), $rows);
	}

	public function coreSearchTranslateColumn($column, $value)
	{
		switch ($column)
		{
			case 'keywords':
				$fields = array('title', 'description');
				$value = "'%" . iaSanitize::sql($value) . "%'";

				$result = array();
				foreach ($fields as $fieldName)
				{
					$result[] = array('col' => ':column', 'cond' => 'LIKE', 'val' => $value, 'field' => $fieldName);
				}

				return $result;

			case 'c':
				$iaArticlecat = $this->iaCore->factoryPackage('articlecat', $this->getPackageName());

				$sql = sprintf('SELECT `id` FROM `%s` WHERE `parent_id` = %d', $iaArticlecat::getTable(true), $value);

				return array('col' => ':column', 'cond' => 'IN', 'val' => '(' . $sql . ')', 'field' => 'category_id');

			case 'sc':
				return array('col' => ':column', 'cond' => '=', 'val' => (int)$value, 'field' => 'category_id');
		}
	}

	/**
	 * Returns listings for Favorites page
	 *
	 * @param $ids
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function getFavorites($ids, $fields)
	{
		$listingIds = implode(",", $ids);
		$listings = $this->get("&& `t1`.`id` IN ({$listingIds}) ", 0, 50);

		if ($listings)
		{
			foreach ($listings as &$listing)
			{
				$listing['favorite'] = 1;
			}
		}

		return $listings;
	}

	public function get($stmtWhere = null, $start = 0, $limit = 0, $joinTransactions = false)
	{
		if (!$limit)
		{
			$limit = 1000;
		}
		$fields = array(
			't1.*',
			't2.`title` `category_title`',
			't2.`title_alias` `category_alias`',
			't2.`parent_id` `category_parent`',
			't2.`parents` `category_parents`',
			't2.`locked` `category_locked`',
			't3.`username` `account_username`',
			'IF(\'\' != t3.`fullname`, t3.`fullname`, t3.`username`) `account_fullname`',
		);

		if ($joinTransactions)
		{
			$iaTransaction = $this->iaCore->factory('transaction');
			$fields[] = "SUBSTRING(GROUP_CONCAT(t4.`sec_key` ORDER BY t4.`date_created`) FROM -14) `transaction_id`";

			list($where, $order) = explode('ORDER BY', $stmtWhere);

			$stmtWhere = $where . 'GROUP BY t1.`id` ORDER BY' . $order;
		}

		$sql = 'SELECT ' . iaDb::STMT_CALC_FOUND_ROWS . ' ' . implode(', ', $fields)
			. 'FROM ' . self::getTable(true) . ' t1 '
			. 'LEFT JOIN `' . $this->iaDb->prefix . 'articles_categories` t2 ON (t1.`category_id` = t2.`id`) '
			. 'LEFT JOIN `' . iaUsers::getTable(true) . '` t3 ON (t1.`member_id` = t3.`id`) '
			. ($joinTransactions ? "LEFT JOIN `" . $iaTransaction::getTable(true) . "` t4 ON (t4.`status` = 'pending' AND t4.`member_id` = t1.`member_id` AND t4.`item` = '" . $this->getItemName() . "' AND t4.`item_id` = t1.`id`) " : '')
			. "WHERE t2.`status` = 'active' " . ($joinTransactions ? '' : "AND t1.`status` = 'active' ") . $stmtWhere
			. ' LIMIT ' . $start . ', ' . $limit;

		$articles = $this->iaDb->getAll($sql);
		$this->wrapValues($articles);

		return $articles;
	}

	public function getArticleBy($where, $order = '', $displayInactive = false, $decorateValues = true)
	{
		$accountId = iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : false;

		$fields = array(
			'SQL_CALC_FOUND_ROWS art.*',
			'cat.`title` `category_title`',
			'cat.`title_alias` `category_alias`',
			'cat.`parent_id` `category_parent`',
			'cat.`parents` `category_parents`',
			'acc.`username` `account_username`',
			'IF(\'\' != acc.`fullname`, acc.`fullname`, acc.`username`) `account_fullname`',
		);

		$sql = 'SELECT ' . implode(', ', $fields)
				. 'FROM ' . self::getTable(true) . ' art '
				. 'LEFT JOIN `' . $this->iaDb->prefix . 'articles_categories` cat ON (art.`category_id` = cat.`id`) '
				. 'LEFT JOIN `' . $this->iaDb->prefix . 'members` acc ON (art.`member_id` = acc.`id`) '
				. "WHERE " . $where;

		if (!$displayInactive)
		{
			$sessionId = session_id();

			$sql .= "AND ( ((art.`status` = 'active' AND cat.`status` = 'active') ";
			$sql .= "AND (acc.`status` = 'active' OR acc.`status` IS NULL OR acc.`id` = '{$accountId}')) ";
			$sql .= $accountId ? "OR art.`member_id` = {$accountId} OR `session` = '{$sessionId}' " : " OR (`session` = '{$sessionId}') ";
			$sql .= ')';
		}

		$sql .= $order ? 'ORDER BY ' . $order : '';
		$sql .= ' LIMIT 1';

		if ($article = $this->iaDb->getRow($sql))
		{
			$result = array($article);
			if ($result && $decorateValues)
			{
				$this->wrapValues($result);
			}
			$article = array_shift($result);
		}

		return $article;
	}

	/**
	 * Returns article by given id
	 *
	 * @param int id article id
	 * @param boolean $displayInactive[optional] article filter, true - display inactive articles
	 *
	 * @return array
	 */
	public final function getById($id, $displayInactive = false, $decorateValues = true)
	{
		return $this->getArticleBy("art.`id` = '{$id}'", '', $displayInactive, $decorateValues);
	}

	public function getPreviousArticle($date, $categoryId)
	{
		return $this->getArticleBy("art.`date_added` < '{$date}' AND art.`category_id` = " . (int)$categoryId . ' ', 'art.`date_added` DESC ');
	}

	public function getNextArticle($date, $categoryId)
	{
		return $this->getArticleBy("art.`date_added` > '{$date}' AND art.`category_id` = " . (int)$categoryId . ' ', 'art.`date_added` ASC ');
	}

	/**
	 * Inserts new article, returns article id
	 *
	 * @param array $itemData article information
	 *
	 * @return integer
	 */
	protected function _addArticle($itemData)
	{
		$itemData['date_added'] = date(iaDb::DATETIME_FORMAT);
		$itemData['date_modified'] = date(iaDb::DATETIME_FORMAT);

		return parent::insert($itemData);
	}

	public function updateCounters($itemId, array $itemData, $action)
	{
		if (iaCore::STATUS_ACTIVE == $itemData['status'] && in_array($action, array(iaCore::ACTION_DELETE, iaCore::ACTION_ADD)))
		{
			$factor = ($action == iaCore::ACTION_DELETE) ? -1 : 1;
			$this->_updateCategoryCounter($itemData['category_id'], $factor);
		}
	}

	public function sendMail($articleId)
	{
		if ($this->iaCore->get('article_notif'))
		{
			$articleData = $this->getById($articleId, true);
			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate('article_notif');
			$iaMailer->setReplacements(array(
				'title' => $articleData['title'],
				'url' => IA_ADMIN_URL . 'publishing/articles/edit/' . $articleData['id'] . '/'
			));

			return $iaMailer->sendToAdministrators();
		}

		return false;
	}

	/**
	* Check for previous incomplete or saved article, creates new record if does not found
	* or restores old session.
	*/
	public function createPostingSession()
	{
		$this->iaCore->factory('util');

		$data = array(
			'status' => self::STATUS_HIDDEN,
			'ip' => iaUtil::getIp(),
			'member_id' => iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0,
			'session' => iaUsers::hasIdentity() ? '' : session_id()
		);

		$result = ($article = $this->_getIncompleteArticle($data['member_id'], $data['session']))
			? $article['id']
			: $this->_addArticle($data);

		return $result;
	}

	/**
	 * Returns incomplete article
	 *
	 * @param integer $aAuthorId account id
	 * @param string $sessionId session id
	 *
	 * @return array
	 */
	protected function _getIncompleteArticle($authorId, $sessionId)
	{
		$sql = 'SELECT * FROM ' . self::getTable(true) . " WHERE `status` = 'hidden' AND ";
		$sql .= ($authorId)
			? '`member_id` = ' . (int)$authorId . ' '
			: "`member_id` = 0 AND `session` = '" . $sessionId . "' ";

		return $this->iaDb->getRow($sql);
	}

	public function update(array $entryData, $id)
	{
		// get the previous data
		$article = $this->getById($id, true);

		// If URL field is empty, fill it
		if (empty($article['title_alias']) && empty($entryData['title_alias']) && $entryData['title'])
		{
			$entryData['title_alias'] = $entryData['title'];
			$entryData['title_alias'] = iaSanitize::alias($entryData['title_alias']);
		}

		if (in_array($entryData['url'], $this->_validProtocols))
		{
			$entryData['url'] = '';
		}
		else
		{
			$found = false;
			foreach ($this->_validProtocols as $protocol)
			{
				if (stripos($entryData['url'], $protocol) !== false)
				{
					$found = true;
					break;
				}
			}
			if (!$found)
			{
				$entryData['url'] = $this->_validProtocols[0] . $entryData['url'];
			}
		}

		$entryData['date_modified'] = date(iaDb::DATETIME_FORMAT);
		$entryData['ip'] = $this->iaCore->factory('util')->getIp();

		$result = parent::update($entryData, $id);

		if ($result)
		{
			$status = isset($entryData['status']) ? $entryData['status'] : null;
			$categoryId = isset($entryData['category_id']) ? $entryData['category_id'] : $article['category_id'];

			// If category changed
			if ($categoryId != $article['category_id'])
			{
				if (iaCore::STATUS_ACTIVE == $article['status'] && iaCore::STATUS_ACTIVE == $status)
				{
					$this->_updateCategoryCounter($article['category_id'], -1);
					$this->_updateCategoryCounter($categoryId, 1);
				}
				elseif (iaCore::STATUS_ACTIVE != $article['status'] && iaCore::STATUS_ACTIVE == $status)
				{
					$this->_updateCategoryCounter($categoryId, 1);
				}
				elseif (iaCore::STATUS_ACTIVE == $article['status'] && iaCore::STATUS_ACTIVE != $status)
				{
					$this->_updateCategoryCounter($article['category_id'], -1);
				}
			}
			else
			{
				// status changed
				if (iaCore::STATUS_ACTIVE == $article['status'] && $status != iaCore::STATUS_ACTIVE)
				{
					$this->_updateCategoryCounter($categoryId, -1);
				}
				elseif (iaCore::STATUS_ACTIVE != $article['status'] && iaCore::STATUS_ACTIVE == $status)
				{
					$this->_updateCategoryCounter($categoryId, 1);
				}
			}
		}

		return $result;
	}

	/**
	 * Updates counter for article categories recursively
	 *
	 * @param integer $parentCategoryId category id to update counter
	 * @param integer $factor difference between values
	 *
	 * @return void
	 */
	public function _updateCategoryCounter($parentCategoryId, $factor)
	{
		$sql  = "UPDATE `{$this->iaDb->prefix}articles_categories` ";
		$sql .= "SET `num_articles`=IF(`id`=$parentCategoryId, `num_articles`+{$factor}, `num_articles`) ";
		$sql .= ", `num_all_articles`=`num_all_articles`+{$factor} ";
		$sql .= "WHERE FIND_IN_SET({$parentCategoryId}, `child`) ";

		return $this->iaDb->query($sql);
	}

	public function fetchMemberListings($memberId, $start, $limit)
	{
		$stmt = 'AND t1.`member_id` = :member ORDER BY `t1`.`date_added` DESC';
		$this->iaDb->bind($stmt, array('member' => (int)$memberId));

		return array(
			'items' => $this->get($stmt, $start, $limit),
			'total_number' => $this->iaDb->foundRows()
		);
	}

	public function wrapValues(array &$rows)
	{
		if (is_array($rows) && $rows)
		{
			foreach ($rows as &$row)
			{
				empty($row['image']) || $this->_unwrapImages($row['image']);
			}
		}
	}

	private function _unwrapImages(&$value)
	{
		if (isset($value[1]) && ':' == $value[1])
		{
			$value = unserialize($value);
		}
	}
}