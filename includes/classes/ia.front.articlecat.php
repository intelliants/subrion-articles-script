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

class iaArticlecat extends abstractPublishingModuleFront
{
    protected static $_table = 'articles_categories';

    protected $_itemName = 'articlecats';

    protected $_rootId;

    private $_urlPatterns = [
        'default' => ':base:alias'
    ];


    public function getRootId()
    {
        if (is_null($this->_rootId)) {
            $this->_rootId = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds(0, 'parent_id'), self::getTable());
        }

        return $this->_rootId;
    }

    public function all($aWhere, $fields = '*')
    {
        return $this->iaDb->all($fields, $aWhere, null, null, self::getTable());
    }

    public function url($action, array $data)
    {
        $params = [
            'base' => $this->getInfo('url'),
            'action' => $action,
            'alias' => isset($data['title_alias']) ? $data['title_alias'] : ''
        ];
        $params['alias'] = isset($data['category_alias']) ? $data['category_alias'] : $data['title_alias'];

        isset($this->_urlPatterns[$action]) || $action = 'default';

        return iaDb::printf($this->_urlPatterns[$action], $params);
    }

    /**
     * Returns category information
     *
     * @param string $where condition to return category information
     *
     * @return array
     */
    public function getCategory($where)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $where, self::getTable());

        $this->_processValues($row, true);

        return $row;
    }

    /**
     * Returns article categories
     *
     * @param string $where additional WHERE clause
     * @param integer $start[optional] starting position
     * @param integer $limit[optional] number of categories to return
     * @param integer $parentId[optional] parent category id
     *
     * @return array
     */
    public function get($where = null, $start = 0, $limit = 0, $parentId = 0, $sorting = false)
    {
        $fields = "SQL_CALC_FOUND_ROWS `id`, `title_{$this->iaView->language}`, `level`, `title_alias`, `child`, `icon`, `nofollow`, `num_all_articles` `num`";

        $stmt = "`status` = 'active' AND `parent_id` != 0 " . ($parentId > 0 ? "AND `parent_id`='{$parentId}' " : '');
        $where && $stmt.= ' ' . $where;
        $stmt.= ' ORDER BY ';
        $stmt.= $sorting
            ? $sorting
            : ($this->iaCore->get('articles_categs_sort', 'by title') == 'by title' ? '`title_' . $this->iaView->language . '`' : '`order`');

        $result = $this->iaDb->all($fields, $stmt, $start, $limit, self::getTable());

        $this->_processValues($result);

        return $result;
    }
}
