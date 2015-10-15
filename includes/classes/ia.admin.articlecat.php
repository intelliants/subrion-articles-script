<?php
//##copyright##

class iaArticlecat extends abstractPublishingPackageAdmin
{
	protected static $_table = 'articles_categories';

	protected $_itemName = 'articlecats';


	public function getSitemapEntries()
	{
		$result = array();

		$stmt = '`parent_id` != 0 AND `status` = :status ORDER BY `title`';
		$this->iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE));
		if ($entries = $this->iaDb->onefield('title_alias', $stmt, null, null, self::getTable()))
		{
			$baseUrl = $this->getInfo('url');

			foreach ($entries as $alias)
			{
				$result[] = $baseUrl . $alias;
			}
		}

		return $result;
	}

	public function getRoot()
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds(0, 'parent_id'), self::getTable());
	}

	public function rebuildRelations($id)
	{
		$this->_iaDb->setTable(self::getTable());

		$category = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

		// update parents
		$parents = array();
		$parents = $this->_getParents($category['id'], $parents);
		$parents[] = $category['id'];
		$level = count($parents) - 1;

		$children = array();
		$children[] = $category['id'];
		$children = $this->_getChildren($category['id'], $children);

		$entry = array(
			'parents' => implode(',', $parents),
			'level' => $level,
			'child' => implode(',', $children)
		);

		$this->_iaDb->update($entry, iaDb::convertIds($category['id']));

		$this->_iaDb->resetTable();
	}

	protected function _getPathForRebuild($title, $pid, $path = '')
	{
		static $cache;

		$str = preg_replace('#[^a-z0-9_-]+#i', '-', $title);
		$str = trim($str, '-');
		$str = str_replace("'", '', $str);

		$path = $path ? $str . '/' . $path : $str . '/';

		if ($pid != 1)
		{
			if (isset($cache[$pid]))
			{
				$parent = $cache[$pid];
			}
			else
			{
				$parent = $this->iaDb->row(array('id', 'parent_id', 'title'), "`id` = '{$pid}'");

				$cache[$pid] = $parent;
			}

			$path = $this->_getPathForRebuild($parent['title'], $parent['parent_id'], $path);
		}

		return $path;
	}

	public function rebuildAliases()
	{
		$this->_iaDb->setTable(self::getTable());

		// clean all title_aliases for categories
		$this->_iaDb->update(array('title_alias' => ''), iaDb::EMPTY_CONDITION);

		$categories = $this->iaDb->all(array('id', 'parent_id', 'title'), '`parent_id` != 0 ORDER BY `level` ASC');

		$sql_post = '';
		$sql_pre = "UPDATE `" . self::getTable(true) . "` SET `title_alias` = CASE ";

		foreach ($categories as $key => $category)
		{
			$path = $this->_getPathForRebuild($category['title'], $category['parent_id']);

			$sql_post .= "WHEN `id` = '{$category['id']}' THEN '{$path}' ";

			$ids[] = $category['id'];

			if (('0' != $key) && ($key % 50 != 0))
			{
				$sql_post .= "END WHERE `id` IN ('" . implode("','", $ids) . "')";

				$sql = $sql_pre . $sql_post;

				$sql_post = '';
				$ids = array();

				$this->iaDb->query($sql);
			}
		}

		$this->_iaDb->resetTable();
	}

	/**
	 * Updates number of active articles for each category
	 */
	public function calculateArticles()
	{
		$sql  =
			'SELECT a.`category_id`, COUNT(a.`id`) ' .
			"FROM `{$this->iaDb->prefix}articles` a " .
			"LEFT JOIN `{$this->iaDb->prefix}members` m ON (a.`member_id` = m.`id`) " .
			"WHERE a.`status` = 'active' AND (m.`status` = 'active' OR m.`status` IS NULL) " .
			'GROUP BY a.`category_id` ';
		$count = $this->iaDb->getKeyValue($sql);

		$this->iaDb->setTable(self::getTable());

		$this->iaDb->update(array('num_articles' => 0, 'num_all_articles' => 0));

		$maxLevel = $this->iaDb->one('MAX(`level`)');

		for ($lvl = $maxLevel; $lvl > 0; $lvl--)
		{
			$rows = $this->iaDb->all(array('id', 'parents'), "`level` = {$lvl}");

			foreach ($rows as $category)
			{
				if (isset($count[$category['id']]))
				{
					$this->iaDb->update(null, "`id` IN ({$category['parents']})", array('num_articles' => "IF(`id`={$category['id']}, `num_articles`+{$count[$category['id']]}, `num_articles`)", 'num_all_articles' => "`num_all_articles` + {$count[$category['id']]}"));
				}
			}
		}

		$this->iaDb->resetTable();
	}

	protected function _getParents($cId, $parents = array(), $update = true)
	{
		$parentId = $this->iaDb->one('parent_id', iaDb::convertIds($cId));

		if ($parentId != 0)
		{
			$parents[] = $parentId;

			if ($update)
			{
				$childrenIds = $this->iaDb->one('child', iaDb::convertIds($parentId));
				$childrenIds = $childrenIds ? explode(',', $childrenIds) : array();

				if (!in_array($cId, $childrenIds))
				{
					$childrenIds[] = $cId;
				}

				foreach ($parents as $pid)
				{
					if (!in_array($pid, $childrenIds))
					{
						$childrenIds[] = $pid;
					}
				}

				$this->iaDb->update(array('child' => implode(',', $childrenIds)), '`id` = ' . $parentId);
			}

			$parents = $this->_getParents($parentId, $parents, $update);
		}

		return $parents;
	}

	protected function _getChildren($cId, $children = array(), $update = false)
	{
		if ($childrenIds = $this->iaDb->onefield(iaDb::ID_COLUMN_SELECTION, '`parent_id` = ' . $cId))
		{
			foreach ($childrenIds as $childId)
			{
				$children[] = $childId;

				if ($update)
				{
					$parentIds = $this->iaDb->one('parents', '`id` = ' . $cId, self::getTable());
					$parentIds = $parentIds ? explode(',', $parentIds) : array();

					$parentIds[] = $childId;

					$this->iaDb->update(array('parents' => implode(',', $parentIds)), '`id` = ' . $childId);
				}

				$children = $this->_getChildren($childId, $children, $update);
			}
		}

		return $children;
	}

	public function dropRelations()
	{
		$this->iaDb->update(array('child' => '', 'parents' => ''), iaDb::EMPTY_CONDITION, self::getTable());
	}

	public function getCount()
	{
		return $this->iaDb->one(iaDb::STMT_COUNT_ROWS, null, self::getTable());
	}
}