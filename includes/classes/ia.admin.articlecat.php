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


    /**
     * Updates number of active articles for each category
     */
    public function calculateArticles($start = 0, $limit = 10)
    {
        $this->iaDb->setTable(self::getTable());

        $categories = $this->iaDb->all(['id', 'parent_id', 'child'], '1 ORDER BY `level` DESC', $start, $limit);

        foreach ($categories as $cat) {
            if (0 != $cat['parent_id']) {
                $_id = $cat['id'];

                $sql  = 'SELECT COUNT(a.`id`) `num`';
                $sql .= "FROM `{$this->iaDb->prefix}articles` a ";
                $sql .= "LEFT JOIN `{$this->iaDb->prefix}members` acc ON (a.`member_id` = acc.`id`) ";
                $sql .= "WHERE a.`status`= 'active' AND (acc.`status` = 'active' OR acc.`status` IS NULL) ";
                $sql .= "AND a.`category_id` = {$_id}";

                $num_articles = $this->iaDb->getOne($sql);
                $_num_articles = $num_articles ? $num_articles : 0;
                $num_all_articles = 0;

                if (!empty($cat['child']) && $cat['child'] != $cat['id']) {
                    $num_all_articles = $this->iaDb->one('SUM(`num_articles`)', "`id` IN ({$cat['child']})", self::getTable());
                }

                $num_all_articles += $_num_articles;

                $this->iaDb->update(['num_articles' => $_num_articles, 'num_all_articles' => $num_all_articles], iaDb::convertIds($_id));
            }
        }

        $this->iaDb->resetTable();

        return true;
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
