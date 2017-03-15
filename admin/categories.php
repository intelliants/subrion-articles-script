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

class iaBackendController extends iaAbstractControllerModuleBackend
{
	protected $_name = 'categories';
	protected $_itemName = 'articlecats';

	protected $_helperName = 'articlecat';

	protected $_gridColumns = ['parent_id', 'title', 'title_alias', 'num_articles', 'num_all_articles', 'locked', 'level', 'order', 'date_added', 'date_modified', 'status'];
	protected $_gridFilters = ['status' => self::EQUAL, 'title' => self::LIKE];
	protected $_gridQueryMainTableAlias = 'c';

	protected $_phraseAddSuccess = 'article_category_added';

	protected $_activityLog = ['item' => 'category'];

	private $_root;


	public function init()
	{
		$this->_root = $this->getHelper()->getRoot();
	}

	protected function _gridRead($params)
	{
		$iaArticle = $this->_iaCore->factoryModule('article', $this->getModuleName(), iaCore::ADMIN);

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
					$rows = $this->_iaDb->all([iaDb::ID_COLUMN_SELECTION], '', (int)$_POST['start'], (int)$_POST['limit']);

					foreach ($rows as $row)
					{
						$this->getHelper()->rebuildRelations($row['id']);
					}

					break;

				case 'rebuild_articlecats_paths':
					$rows = $this->_iaDb->all([iaDb::ID_COLUMN_SELECTION], iaDb::convertIds(0, 'parent_id', false), (int)$_POST['start'], (int)$_POST['limit']);
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
					$rows = $this->_iaDb->all([iaDb::ID_COLUMN_SELECTION], '', (int)$_POST['start'], (int)$_POST['limit'], iaArticle::getTable());

					foreach ($rows as $row)
					{
						$iaArticle->rebuildArticleAliases($row['id']);
					}
			}

			return;
		}

		return parent::_gridRead($params);
	}

	public function _gridQuery($columns, $where, $order, $start, $limit)
	{
		return $this->getHelper()->get($columns, $where, $order, $start, $limit);
	}

	protected function _entryAdd(array $entryData)
	{
		$entryData['date_added'] = date(iaDb::DATE_FORMAT);
		$entryData['date_modified'] = date(iaDb::DATE_FORMAT);

		return parent::_entryAdd($entryData);
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		$entryData['date_modified'] = date(iaDb::DATE_FORMAT);

		if ($this->_root['parent_id'] == $entryData['parent_id']) // makes impossible to change the alias for the root
		{
			unset($entryData['title_alias']);
		}

		return parent::_entryUpdate($entryData, $entryId);
	}

	protected function _entryDelete($entryId)
	{
		return ($this->_root['id'] == $entryId) ? false : (bool)$this->getHelper()->delete($entryId);
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
		$entry = [
			'parent_id' => $this->_root['id'],
			'title_alias' => '',
			'locked' => false,
			'nofollow' => false,
			'priority' => false,
			'status' => iaCore::STATUS_ACTIVE
		];
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		parent::_preSaveEntry($entry, $data, $action);

		$entry['locked'] = (int)$data['locked'];
		$entry['nofollow'] = (int)$data['nofollow'];
		$entry['priority'] = (int)$data['priority'];
		$entry['parent_id'] = (int)$data['tree_id'];

		if ($entry['parent_id'] != $this->_root['parent_id'])
		{
			$entry['title_alias'] = empty($data['title_alias']) ? $data['title'][$this->_iaCore->language['iso']] : $data['title_alias'];
			$entry['title_alias'] = iaSanitize::alias($entry['title_alias']);

			if ($this->_iaDb->exists('`title` = :title AND `parent_id` = :parent_id AND `id` != :id', ['title' => $entry['title'], 'parent_id' => $entry['parent_id'], 'id' => $this->getEntryId()]))
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

		$parent = $this->getHelper()->getById($entryData['parent_id']);

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
		$this->_iaDb->bind($stmtWhere, ['alias' => $previousAlias . '%']);

		$stmtReplace = sprintf("REPLACE(`title_alias`, '%s', '%s')", $previousAlias, $newAlias);

		$this->_iaDb->update(null, $stmtWhere, ['title_alias' => $stmtReplace], self::getTable());
	}

	protected function _getJsonAlias(array $data)
	{
		$categoryId = isset($data['category']) ? (int)$data['category'] : $this->_root['id'];

		$alias = IA_MODULE_URL;
		$alias.= $this->_iaDb->one('title_alias', iaDb::convertIds($categoryId));
		$alias.= iaSanitize::alias($data['title']) . IA_URL_DELIMITER;

		return ['data' => $alias];
	}

	protected function _getJsonTree(array $data)
	{
		$output = [];

		$dynamicLoadMode = ((int)$this->_iaDb->one(iaDb::STMT_COUNT_ROWS) > 150);
		$noRootMode = isset($data['noroot']) && '' == $data['noroot'];

		$rootId = 1;
		$parentId = isset($data['id']) && is_numeric($data['id'])
			? (int)$data['id']
			: ($noRootMode ? $rootId : 0);

		$where = $dynamicLoadMode
			? '`parent_id` = ' . $parentId
			: ($noRootMode ? '`id` != ' . $rootId : iaDb::EMPTY_CONDITION);

		// TODO: better solution should be found here. this code will break jstree composition in case if
		// category to be excluded from the list has children of 2 and more levels deeper
		empty($data['cid']) || $where.= ' AND `id` != ' . (int)$data['cid'] . ' AND `parent_id` != ' . (int)$data['cid'];

		$where.= ' ORDER BY `' . ($dynamicLoadMode ? 'title' : 'level') . '`';

		$rows = $this->_iaDb->all(['id', 'title' => 'title_' . $this->_iaCore->language['iso'], 'parent_id', 'child'], $where);

		foreach ($rows as $row)
		{
			$entry = ['id' => $row['id'], 'text' => $row['title']];

			if ($dynamicLoadMode)
			{
				$entry['children'] = ($row['child'] && $row['child'] != $row['id']) || 0 === (int)$row['id'];
			}
			else
			{
				$entry['state'] = [];
				$entry['parent'] = $noRootMode
					? ($rootId == $row['parent_id'] ? '#' : $row['parent_id'])
					: ($rootId == $row['id'] ? '#' : $row['parent_id']);
			}

			$output[] = $entry;
		}

		return $output;
	}
}