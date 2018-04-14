<?php
/******************************************************************************
 *
 * Subrion Articles Publishing Script
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

class iaArticlecat extends iaAbstractFrontHelperCategoryFlat implements iaPublishingModule
{
    protected static $_table = 'articles_categories';

    protected $_moduleName = 'publishing';

    protected $_itemName = 'articlecat';

    protected $_recountOptions = [
        'listingsTable' => 'articles',
        'columnCounter' => 'num_articles',
        'columnTotalCounter' => 'num_all_articles'
    ];


    public function getUrl(array $data)
    {
        return $this->getInfo('url')
            . (isset($data['category_alias']) ? $data['category_alias'] : $data['title_alias']);
    }

    /**
     * Returns article categories
     *
     * @param string $where additional WHERE clause
     * @param integer $start [optional] starting position
     * @param integer $limit [optional] number of categories to return
     * @param integer $parentId [optional] parent category id
     * @param bool $sorting
     *
     * @return array
     */
    public function get($where = null, $start = 0, $limit = 0, $parentId = 0, $sorting = false)
    {
        $fields = 'SQL_CALC_FOUND_ROWS `id`, `title_alias`, `icon`, `nofollow`, `num_all_articles` `num`,'
            . '`title_' . $this->iaView->language . '`, `parent_id`';

        $stmt = "`status` = 'active' AND `parent_id` != 0 " . ($parentId > 0 ? "AND `parent_id` = {$parentId} " : '');
        $where && $stmt.= $where;
        $stmt.= ' ORDER BY ';
        $stmt.= $sorting
            ? $sorting
            : ($this->iaCore->get('articles_categs_sort', 'by title') == 'by title' ? '`title_' . $this->iaView->language . '`' : '`order`');

        $rows = $this->iaDb->all($fields, $stmt, $start, $limit, self::getTable());

        $this->iaView->assign('root_cat_id', $this->getRootId());
        $this->_processValues($rows);

        return $rows;
    }
}
