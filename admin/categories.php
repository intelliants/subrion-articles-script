<?php
//##copyright##

class iaBackendController extends iaAbstractControllerPackageBackend
{
	protected $_name = 'categories';

	protected $_helperName = 'articlecat';

	protected $_gridColumns = 'c.`id`, c.`title`, c.`title_alias` `alias`, c.`num_articles`, c.`num_all_articles`, c.`order`, c.`status`, c.`description`, c.`parent_id`, c.`level`, p.`title` `parent_title`, p.`id` `parent_id`, 1 `update`, IF(c.`parent_id` != 0, 1, 0) `delete`';
	protected $_gridFilters = array('status' => self::EQUAL, 'title' => self::LIKE);
	protected $_gridQueryMainTableAlias = 'c';

	protected $_phraseAddSuccess = 'article_category_added';

	protected $_activityLog = array('item' => 'category');

	private $_rootCategory;


	public function init()
	{
		$this->_rootCategory = $this->getHelper()->getRoot();
	}

	protected function _gridRead($params)
	{
		$iaArticle = $this->_iaCore->factoryPackage('article', $this->getPackageName(), iaCore::ADMIN);

		if (isset($_POST['action']))
		{
			switch ($_POST['action'])
			{
				case 'pre_repair_articlecats':
					$this->getHelper()->dropRelations();
					$total = $this->getHelper()->getCount();
					$this->_iaCore->iaView->assign('total', $total);

					break;

				case 'pre_repair_articlecats_paths':
					$total = $this->getHelper()->getCount();
					$this->_iaCore->iaView->assign('total', $total);

					break;

				case 'pre_repair_articlecats_num':
					$this->getHelper()->clearArticlesNum();
					$total = $this->getHelper()->getCount();
					$this->_iaCore->iaView->assign('total', $total);

					break;

				case 'pre_rebuild_article_paths':
					$total = $iaArticle->getCount();
					$this->_iaCore->iaView->assign('total', $total);

					break;

				case 'repair_articlecats':
					$rows = $this->_iaDb->all(array(iaDb::ID_COLUMN_SELECTION), '', (int)$_POST['start'], (int)$_POST['limit']);

					foreach ($rows as $row)
					{
						$this->getHelper()->rebuildRelations($row['id']);
					}

					break;

				case 'rebuild_articlecats_paths':
					$rows = $this->_iaDb->all(array(iaDb::ID_COLUMN_SELECTION), iaDb::convertIds(0, 'parent_id', false), (int)$_POST['start'], (int)$_POST['limit']);
					foreach ($rows as $row)
					{
						$this->getHelper()->rebuildAliases($row['id']);
					}

					break;

				case 'repair_articlecats_num':
					$output = $this->getHelper()->calculateArticles((int)$_POST['start'], (int)$_POST['limit']);

					break;

				// Rebuld Article Paths
				case 'rebuild_article_paths':
					$rows = $this->_iaDb->all(array(iaDb::ID_COLUMN_SELECTION), '', (int)$_POST['start'], (int)$_POST['limit'], iaArticle::getTable());

					foreach ($rows as $row)
					{
						$iaArticle->rebuildArticleAliases($row['id']);
					}
			}

			return;
		}

		return parent::_gridRead($params);
	}

	protected function _gridQuery($columns, $where, $order, $start, $limit)
	{
		$sql =
			'SELECT :fields ' .
			'FROM `:prefix:table` c ' .
			'LEFT JOIN `:prefix:table` p ON (c.`parent_id` = p.`id`) ' .
			'WHERE :where :order ' .
			'LIMIT :start, :limit';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table' => $this->getTable(),
			'fields' => $columns,
			'where' => $where,
			'order' => $order,
			'start' => $start,
			'limit' => $limit
		));

		return $this->_iaDb->getAll($sql);
	}

	protected function _entryAdd(array $entryData)
	{
		$entryData['order'] = $this->_iaDb->getMaxOrder() + 1;

		return parent::_entryAdd($entryData);
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		if (0 == $entryData['parent_id']) // makes impossible to change the alias for the root
		{
			unset($entryData['title_alias']);
		}

		return parent::_entryUpdate($entryData, $entryId);
	}

	protected function _entryDelete($entryId)
	{
		$row = $this->getById($entryId);
		$result = parent::_entryDelete($entryId);

		if ($result && $row)
		{
			// remove subcategories as well
			$this->_iaDb->delete(iaDb::convertIds(explode(',', $row['child']), 'parent_id'));
		}

		return $result;
	}

	public function updateCounters($entryId, array $entryData, $action, $previousData = null)
	{
		if (iaCore::ACTION_DELETE != $action)
		{
			$this->getHelper()->rebuildRelations($entryId);
		}

		if (iaCore::ACTION_EDIT == $action)
		{
			if (isset($entryData['title_alias']) && $entryData['title_alias'] != $previousData['title_alias'])
			{
				$this->_correctAlias($entryData['title_alias'], $previousData['title_alias']);
			}
		}
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry = array(
			'parent_id' => $this->_rootCategory['id'],
			'title_alias' => '',
			'locked' => false,
			'nofollow' => false,
			'priority' => false,
			'status' => iaCore::STATUS_ACTIVE
		);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		parent::_preSaveEntry($entry, $data, $action);

		$entry['locked'] = (int)$data['locked'];
		$entry['nofollow'] = (int)$data['nofollow'];
		$entry['priority'] = (int)$data['priority'];
		$entry['parent_id'] = (int)$data['tree_id'];

		if ($entry['parent_id'] != $this->_rootCategory['parent_id'])
		{
			$entry['title_alias'] = empty($data['title_alias']) ? $data['title'] : $data['title_alias'];
			$entry['title_alias'] = iaSanitize::alias($entry['title_alias']);

			if ($this->_iaDb->exists('`title` = :title AND `parent_id` = :parent_id AND `id` != :id', array('title' => $entry['title'], 'parent_id' => $entry['parent_id'], 'id' => $this->getEntryId())))
			{
				$this->addMessage('specified_category_title_exists');
			}

			$parentCategory = $this->_iaDb->row('title_alias', iaDb::convertIds($entry['parent_id']));
			$entry['title_alias'] = ($parentCategory ? $parentCategory['title_alias'] : '') . $entry['title_alias'] . IA_URL_DELIMITER;
		}

		return !$this->getMessages();
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		parent::_assignValues($iaView, $entryData);

		$parent = $this->_iaDb->row(array('id', 'title', 'title_alias', 'parents', 'child'), iaDb::convertIds($entryData['parent_id']));

		$array = explode(IA_URL_DELIMITER, trim($entryData['title_alias'], IA_URL_DELIMITER));
		$entryData['title_alias'] = end($array);

		$iaView->assign('parent', $parent);
		$iaView->assign('statuses', $this->getHelper()->getStatuses());
	}

	protected function _writeLog($action, array $entryData, $entryId)
	{
		if (iaCore::ACTION_ADD != $action)
		{
			return;
		}

		parent::_writeLog($action, $entryData, $entryId);
	}

	protected function _setPageTitle(&$iaView, array $entryData, $action)
	{
		$iaView->title(iaLanguage::get($action . '_category', $iaView->title()));
	}


	protected function _correctAlias($newAlias, $previousAlias)
	{
		$stmtWhere = '`title_alias` LIKE :alias';
		$this->_iaDb->bind($stmtWhere, array('alias' => $previousAlias . '%'));

		$stmtReplace = sprintf("REPLACE(`title_alias`, '%s', '%s')", $previousAlias, $newAlias);

		$this->_iaDb->update(null, $stmtWhere, array('title_alias' => $stmtReplace), self::getTable());
	}

	protected function _getJsonAlias(array $data)
	{
		$categoryId = isset($data['category']) ? (int)$data['category'] : $this->_rootCategory['id'];

		$alias = IA_PACKAGE_URL;
		$alias.= $this->_iaDb->one('title_alias', iaDb::convertIds($categoryId));
		$alias.= iaSanitize::alias($data['title']) . IA_URL_DELIMITER;

		return array('data' => $alias);
	}
}