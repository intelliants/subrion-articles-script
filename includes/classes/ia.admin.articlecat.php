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

class iaArticlecat extends iaAbstractHelperCategoryHybrid
{
    protected static $_table = 'articles_categories';

    protected $_moduleName = 'publishing';

    protected $_itemName = 'articlecats';

    public $dashboardStatistics = ['icon' => 'folder', 'url' => 'publishing/categories/'];


    public function get($columns, $where, $order = '', $start = null, $limit = null)
    {
        $sql = <<<SQL
SELECT :columns, p.`title_:lang` `parent_title`
	FROM `:table` c 
LEFT JOIN `:table` p ON (c.`:col_parent` = p.`id`) 
WHERE :where :order 
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'lang' => $this->iaCore->language['iso'],
            'table' => self::getTable(true),
            'columns' => $columns,
            'where' => $where,
            'order' => $order,
            'start' => $start,
            'limit' => $limit,
            'col_parent' => self::COL_PARENT_ID
        ]);

        return $this->iaDb->getAll($sql);
    }

    public function insert(array $itemData)
    {
        $itemData['date_added'] = date(iaDb::DATE_FORMAT);
        $itemData['date_modified'] = date(iaDb::DATE_FORMAT);

        return parent::insert($itemData);
    }

    public function update(array $itemData, $id)
    {
        $itemData['date_modified'] = date(iaDb::DATE_FORMAT);

        return parent::update($itemData, $id);
    }

    public function getSitemapEntries()
    {
        $result = [];

        $stmt = '`parent_id` != 0 AND `status` = :status ORDER BY `title`';
        $this->iaDb->bind($stmt, ['status' => iaCore::STATUS_ACTIVE]);

        if ($entries = $this->iaDb->onefield('title_alias', $stmt, null, null, self::getTable())) {
            $baseUrl = $this->getInfo('url');

            foreach ($entries as $alias) {
                $result[] = $baseUrl . $alias;
            }
        }

        return $result;
    }
}
