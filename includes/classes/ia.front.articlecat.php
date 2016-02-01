<?php
//##copyright##

class iaArticlecat extends abstractPublishingPackageFront
{
	protected static $_table = 'articles_categories';

	protected $_itemName = 'articlecats';

	protected $_rootId;

	private $_urlPatterns = array(
		'default' => ':base:alias'
	);


	public function getRootId()
	{
		if (is_null($this->_rootId))
		{
			$this->_rootId = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds(0, 'parent_id'), self::getTable());
		}

		return $this->_rootId;
	}

	public function all($aWhere, $fields = '*')
	{
		return $this->iaDb->all($fields, $aWhere, null, null, self::getTable());
	}

	public function url($action, $params)
	{
		$data = array(
			'base' => $this->getInfo('url'),
			'action' => $action,
			'alias' => isset($params['title_alias']) ? $params['title_alias'] : ''
		);
		$data['alias'] = isset($params['category_alias']) ? $params['category_alias'] : $params['title_alias'];

		if (!isset($this->_urlPatterns[$action]))
		{
			$action = 'default';
		}

		return iaDb::printf($this->_urlPatterns[$action], $data);
	}

	/**
	 * Returns category information
	 *
	 * @param string $aWhere condition to return category information
	 *
	 * @return array
	 */
	public function getCategory($aWhere)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $aWhere, self::getTable());
	}

	/**
	 * Returns article categories
	 *
	 * @param string $aClause additional WHERE clause
	 * @param integer $aStart[optional] starting position
	 * @param integer $aLimit[optional] number of categories to return
	 * @param integer $aIdParent[optional] parent category id
	 *
	 * @return array
	 */
	public function get($conditions = false, $aStart = 0, $aLimit = 0, $parentId = 0, $sorting = false)
	{
		$fields = "SQL_CALC_FOUND_ROWS `id`, `title`, `level`, `title_alias`, `child`, `icon`, `nofollow`, `num_all_articles` 'num'";
		$statement = "`status` = 'active' AND `parent_id` != 0 " . ($parentId > 0 ? "AND `parent_id`='{$parentId}' " : '');
		if ($conditions)
		{
			$statement .= ' ' . $conditions;
		}
		$statement .= ' ORDER BY ';
		if ($sorting)
		{
			$statement .= $sorting;
		}
		else
		{
			$statement .= $this->iaCore->get('articles_categs_sort', 'by title') == 'by title' ? '`title`' : '`order`';
		}

		$result = $this->iaDb->all($fields, $statement, $aStart, $aLimit, self::getTable());

		empty($result) || $this->_wrapValues($result);

		return $result;
	}

	protected function _wrapValues(&$entries)
	{
		foreach ($entries as &$entry)
		{
			empty($entry['icon']) || $entry['icon'] = unserialize($entry['icon']);
		}
	}
}