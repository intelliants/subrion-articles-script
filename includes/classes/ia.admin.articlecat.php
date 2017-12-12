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

class iaArticlecat extends iaAbstractHelperCategoryFlat implements iaPublishingModule
{
    protected static $_table = 'articles_categories';

    protected $_moduleName = 'publishing';

    protected $_itemName = 'articlecats';

    public $dashboardStatistics = ['icon' => 'folder', 'url' => 'publishing/categories/'];

    protected $_recountOptions = [
        'listingsTable' => 'articles',
        'columnCounter' => 'num_articles',
        'columnTotalCounter' => 'num_all_articles'
    ];

    public function insert(array $itemData)
    {
        $itemData['date_added'] = date(iaDb::DATETIME_FORMAT);

        return parent::insert($itemData);
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
