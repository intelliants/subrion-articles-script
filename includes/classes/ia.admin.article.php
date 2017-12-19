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

class iaArticle extends abstractModuleAdmin implements iaPublishingModule
{
    protected static $_table = 'articles';

    protected $_itemName = 'articles';

    protected $_statuses = [
        iaCore::STATUS_ACTIVE,
        iaCore::STATUS_APPROVAL,
        iaCore::STATUS_DRAFT,
        self::STATUS_REJECTED,
        self::STATUS_HIDDEN,
        self::STATUS_SUSPENDED,
        self::STATUS_PENDING
    ];

    public $dashboardStatistics = ['icon' => 'news'];

    private $_iaArticlecat;


    public function init()
    {
        parent::init();

        $this->_iaArticlecat = $this->iaCore->factoryModule('articlecat', $this->getModuleName(), iaCore::ADMIN);
    }

    public function insert(array $itemData)
    {
        $langCode = $this->iaCore->language['iso'];

        $itemData['date_added'] = date(iaDb::DATETIME_FORMAT);
        $itemData['date_modified'] = date(iaDb::DATETIME_FORMAT);

        if (empty($itemData['meta_keywords_' . $langCode]) && $itemData['body_' . $langCode]) {
            $itemData['meta_keywords_' . $langCode] = iaUtil::getMetaKeywords($itemData['body_' . $langCode]);
        }

        if (empty($itemData['meta_description_' . $langCode]) && !empty($itemData['summary_' . $langCode])) {
            $itemData['meta_description_' . $langCode] = substr(str_replace(PHP_EOL, '', iaSanitize::tags($itemData['summary_' . $langCode])), 0, 255);
        }

        return parent::insert($itemData);
    }

    public function update(array $itemData, $id)
    {
        $langCode = $this->iaCore->language['iso'];

        $itemData['date_modified'] = date(iaDb::DATETIME_FORMAT);

        if (empty($itemData['meta_keywords_' . $langCode]) && $itemData['body_' . $langCode]) {
            $itemData['meta_keywords_' . $langCode] = iaUtil::getMetaKeywords($itemData['body_' . $langCode]);
        }

        if (empty($itemData['meta_description_' . $langCode]) && !empty($itemData['summary_' . $langCode])) {
            $itemData['meta_description_' . $langCode] = substr(str_replace(PHP_EOL, '', iaSanitize::tags($itemData['summary_' . $langCode])), 0, 255);
        }

        return parent::update($itemData, $id);
    }

    public function getSitemapEntries()
    {
        $result = [];

        $sql = <<<SQL
SELECT a.`id`, a.`title_alias`, c.`title_alias` `category_alias` 
    FROM `:table_articles` a 
LEFT JOIN `:table_categories` c ON (c.`id` = a.`category_id`) 
WHERE a.`status` = ':status'
SQL;
        $sql = iaDb::printf($sql, [
            'table_articles' => self::getTable(true),
            'table_categories' => iaArticlecat::getTable(true),
            'status' => iaCore::STATUS_ACTIVE
        ]);

        if ($entries = $this->iaDb->getAll($sql)) {
            $baseUrl = $this->getInfo('url');

            foreach ($entries as $entry) {
                $result[] = $baseUrl . iaDb::printf(':category_alias:id-:title_alias.html', $entry);
            }
        }

        return $result;
    }

    public function get($columns, $where, $order = '', $start = null, $limit = null)
    {
        $sql = <<<SQL
SELECT :columns, c.`title_:lang` `category_title`, c.`title_alias` `category_alias`, m.`fullname` `member` 
    FROM `:prefix:table_articles` a 
LEFT JOIN `:prefix:table_categories` c ON (a.`category_id` = c.`id`) 
LEFT JOIN `:prefix:table_members` m ON (a.`member_id` = m.`id`) 
WHERE :where :order
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'lang' => $this->iaCore->language['iso'],
            'prefix' => $this->iaDb->prefix,
            'table_articles' => $this->getTable(),
            'table_categories' => iaArticlecat::getTable(),
            'table_members' => iaUsers::getTable(),
            'columns' => $columns,
            'where' => $where,
            'order' => $order,
            'start' => $start,
            'limit' => $limit
        ]);

        return $this->iaDb->getAll($sql);
    }

    public function rebuildArticleAliases($id)
    {
        $this->iaDb->setTable(self::getTable());

        $article = $this->iaDb->row('id, title', iaDb::convertIds($id));
        $alias = iaSanitize::alias($article['title']);
        $this->iaDb->update(['title_alias' => $alias], iaDb::convertIds($article['id']));

        $this->iaDb->resetTable();
    }

    public function updateCounters($itemId, array $itemData, $action, $previousData = null)
    {
        $this->_checkIfCountersNeedUpdate($action, $itemData, $previousData, $this->_iaArticlecat);

        if (iaCore::ACTION_EDIT == $action) {
            // notify owner on status change
            if (isset($itemData['status']) && in_array($itemData['status'], [self::STATUS_SUSPENDED, self::STATUS_REJECTED, iaCore::STATUS_ACTIVE])) {
                $entry = $this->getById($itemId);
                $owner = $this->iaCore->factory('users')->getInfo($entry['member_id']);
                $action = $itemData['status'];

                if (iaCore::STATUS_ACTIVE == $itemData['status']) {
                    $action = iaCore::STATUS_APPROVAL;
                }



                $this->_sendMail('article_' . $action, $owner['email'], $entry);
            }
        }
    }

    public function getTreeVars(array $entryData)
    {
        $category = empty($entryData['category_id'])
            ? $this->_iaArticlecat->getRoot()
            : $this->_iaArticlecat->getById($entryData['category_id']);

        $nodes = $this->_iaArticlecat->getParents($category['id'], true);

        return [
            'url' => IA_ADMIN_URL . 'publishing/categories/tree.json?noroot',
            'nodes' => implode(',', $nodes),
            'id' => $category['id'],
            'title' => $category['title']
        ];
    }

    protected function _sendMail($action, $email, $data)
    {
        $category = $this->_iaArticlecat->getById($data['category_id']);

        if ($this->iaCore->get($action) && $email) {
            $iaMailer = $this->iaCore->factory('mailer');

            $iaMailer->loadTemplate($action);
            $iaMailer->addAddress($email);
            $iaMailer->setReplacements([
                'title' => $data['title'],
                'reason' => isset($data['reason']) ? $data['reason'] : '',
                'view_url' => IA_URL . 'article/' . $category['title_alias'] . $data['id'] . '-' . $data['title_alias'] . '.html',
                'edit_url' => IA_MODULE_URL . 'edit/' . $data['id'] . '/'
            ]);

            return $iaMailer->send();
        }

        return false;
    }
}
