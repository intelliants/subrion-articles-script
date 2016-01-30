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

	public function rebuildAliases($id)
	{
		$this->_iaDb->setTable(self::getTable());

		$category = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
		$path = $this->_getPathForRebuild($category['title'], $category['parent_id']);
		$this->_iaDb->update(array('title_alias' => $path), iaDb::convertIds($category['id']));

		$this->_iaDb->resetTable();
	}

	/**
	 * Updates number of active articles for each category
	 */
	public function calculateArticles($start = 0, $limit = 10)
	{
		$this->iaDb->setTable(self::getTable());

		$categories = $this->iaDb->all(array('id', 'parent_id', 'child'), '1 ORDER BY `level` DESC', $start, $limit);

		foreach ($categories as $cat)
		{
			if (-1 != $cat['parent_id'])
			{
				$_id = $cat['id'];

				$sql  = 'SELECT COUNT(a.`id`) `num`';
				$sql .= "FROM `{$this->iaDb->prefix}articles` a ";
				$sql .= "LEFT JOIN `{$this->iaDb->prefix}members` acc ON (a.`member_id` = acc.`id`) ";
				$sql .= "WHERE a.`status`= 'active' AND (acc.`status` = 'active' OR acc.`status` IS NULL) ";
				$sql .= "AND a.`category_id` = {$_id}";

				$num_articles = $this->iaDb->getOne($sql);
				$_num_articles = $num_articles ? $num_articles : 0;
				$num_all_articles = 0;

				if (!empty($cat['child']) && $cat['child'] != $cat['id'])
				{
					$num_all_articles = $this->iaDb->one('SUM(`num_articles`)', "`id` IN ({$cat['child']})", self::getTable());
				}

				$num_all_articles += $_num_articles;

				$this->iaDb->update(array('num_articles' => $_num_articles, 'num_all_articles' => $num_all_articles), iaDb::convertIds($_id));
			}
		}

		$this->iaDb->resetTable();

		return true;
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


	public function clearArticlesNum()
	{
		$this->iaDb->update(array('num_articles' => 0, 'num_all_articles' => 0), iaDb::EMPTY_CONDITION, self::getTable());
	}

	public function getCount()
	{
		return $this->iaDb->one(iaDb::STMT_COUNT_ROWS, null, self::getTable());
	}
}