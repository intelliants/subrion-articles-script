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

class iaArticle extends abstractModuleFront implements iaPublishingModule
{
    protected static $_table = 'articles';

    protected $_itemName = 'article';

    protected $_statuses = [iaCore::STATUS_ACTIVE, iaCore::STATUS_APPROVAL, self::STATUS_REJECTED, self::STATUS_HIDDEN, self::STATUS_SUSPENDED, self::STATUS_DRAFT, self::STATUS_PENDING];

    public $coreSearchEnabled = true;
    public $coreSearchOptions = [
        'tableAlias' => 't1',
        'regularSearchFields' => ['title', 'body', 'url', 'url_description'],
        'customColumns' => ['keywords', 'c', 'sc']
    ];

    private $_urlPatterns = [
        'default' => ':base:action/:id/',
        'view' => ':base:category_alias:id-:title_alias.html',
        'my' => 'profile/articles/'
    ];

    protected $_validProtocols = ['http://', 'https://'];

    private $_foundRows = 0;


    public function url($action, array $data)
    {
        $data['base'] = $this->getInfo('url');
        $data['action'] = $action;
        $data['category_alias'] = isset($data['category_alias']) ? $data['category_alias'] : '';
        $data['title_alias'] = isset($data['title_alias']) ? $data['title_alias'] : '';

        unset($data['title']);

        if (!isset($this->_urlPatterns[$action])) {
            $action = 'default';
        }
        if ('view' == $action && !$this->iaCore->get('articles_compact_url')) {
            $data['base'] .= 'article/';
        }

        return iaDb::printf($this->_urlPatterns[$action], $data);
    }

    public function getUrl(array $data)
    {
        return $this->url('view', $data);
    }

    public function accountActions($params)
    {
        $url = '';
        if ($params['item']['member_id'] == iaUsers::getIdentity()->id) {
            $url = $this->url(iaCore::ACTION_EDIT, $params['item']);
        }

        return [$url, ''];
    }

    // called at search pages
    public function coreSearch($stmt, $start, $limit, $order)
    {
        $stmt = $stmt ? ('AND ' . $stmt . $order) : null;
        $rows = $this->get($stmt, $start, $limit);

        return [$this->getFoundRows(), $rows];
    }

    public function coreSearchTranslateColumn($column, $value)
    {
        switch ($column) {
            case 'keywords':
                $columns = ['title_' . $this->iaView->language, 'body_' . $this->iaView->language, 'url', 'url_description'];
                $value = "'%" . iaSanitize::sql($value) . "%'";

                $result = [];
                foreach ($columns as $column) {
                    $result[] = ['col' => ':column', 'cond' => 'LIKE', 'val' => $value, 'field' => $column];
                }

                return $result;

            case 'c':
                $iaArticlecat = $this->iaCore->factoryItem('articlecat');

                $sql = sprintf('SELECT `child_id` FROM `%s` WHERE `parent_id` = %d', $iaArticlecat->getTableFlat(true), $value);

                return ['col' => ':column', 'cond' => 'IN', 'val' => '(' . $sql . ')', 'field' => 'category_id'];

            case 'sc':
                return ['col' => ':column', 'cond' => '=', 'val' => (int)$value, 'field' => 'category_id'];
        }
    }

    /**
     * Returns listings for Favorites page
     *
     * @param $ids
     *
     * @return mixed
     */
    public function getFavorites($ids)
    {
        $listingIds = implode(",", $ids);
        $listings = $this->get("&& `t1`.`id` IN ({$listingIds}) ", 0, 50);

        if ($listings) {
            foreach ($listings as &$listing) {
                $listing['favorite'] = 1;
            }
        }

        return $listings;
    }

    public function get($stmtWhere = null, $start = 0, $limit = 0, $joinTransactions = false)
    {
        $this->iaCore->factoryItem('articlecat');
        if (!$limit) {
            $limit = 1000;
        }
        $fields = [
            't1.*',
            't2.`title_' . $this->iaView->language . '` `category_title`',
            't2.`title_alias` `category_alias`',
            //'t2.`' . iaArticlecat::COL_PARENT_ID . '` `category_parent`',
            //'t2.`' . iaArticlecat::COL_PARENTS . '` `category_parents`',
            't2.`locked` `category_locked`',
            't3.`username` `account_username`',
            'IF(\'\' != t3.`fullname`, t3.`fullname`, t3.`username`) `account_fullname`',
        ];

        if ($joinTransactions) {
            $iaTransaction = $this->iaCore->factory('transaction');
            $fields[] = "SUBSTRING(GROUP_CONCAT(t4.`sec_key` ORDER BY t4.`date_created`) FROM -14) `transaction_id`";

            list($where, $order) = explode('ORDER BY', $stmtWhere);

            $stmtWhere = $where . 'GROUP BY t1.`id` ORDER BY' . $order;
        }

        $sql = 'SELECT ' . iaDb::STMT_CALC_FOUND_ROWS . ' ' . implode(', ', $fields)
            . 'FROM ' . self::getTable(true) . ' t1 '
            . 'LEFT JOIN `' . $this->iaDb->prefix . 'articles_categories` t2 ON (t1.`category_id` = t2.`id`) '
            . 'LEFT JOIN `' . iaUsers::getTable(true) . '` t3 ON (t1.`member_id` = t3.`id`) '
            . ($joinTransactions ? "LEFT JOIN `" . $iaTransaction::getTable(true) . "` t4 ON (t4.`status` = 'pending' AND t4.`member_id` = t1.`member_id` AND t4.`item` = '" . $this->getItemName() . "' AND t4.`item_id` = t1.`id`) " : '')
            . "WHERE t2.`status` = 'active' " . ($joinTransactions ? '' : "AND t1.`status` = 'active' ") . $stmtWhere
            . ' LIMIT ' . $start . ', ' . $limit;

        $articles = $this->iaDb->getAll($sql);
        $this->_foundRows = $this->iaDb->foundRows();

        $this->_processValues($articles);

        return $articles;
    }

    public function getFoundRows()
    {
        return $this->_foundRows;
    }

    public function getArticleBy($where, $order = '', $displayInactive = false, $decorateValues = true)
    {
        $this->iaCore->factoryItem('articlecat');

        $accountId = iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0;

        $fields = [
            'SQL_CALC_FOUND_ROWS art.*',
            'cat.`title_' . $this->iaView->language . '` `category_title`',
            'cat.`title_alias` `category_alias`',
            //'cat.`' . iaArticlecat::COL_PARENT_ID . '` `category_parent`',
            //'cat.`' . iaArticlecat::COL_PARENTS . '` `category_parents`',
            'acc.`username` `account_username`',
            'IF(\'\' != acc.`fullname`, acc.`fullname`, acc.`username`) `account_fullname`',
        ];

        $sql = 'SELECT ' . implode(', ', $fields)
                . 'FROM ' . self::getTable(true) . ' art '
                . 'LEFT JOIN `' . $this->iaDb->prefix . 'articles_categories` cat ON (art.`category_id` = cat.`id`) '
                . 'LEFT JOIN `' . $this->iaDb->prefix . 'members` acc ON (art.`member_id` = acc.`id`) '
                . "WHERE " . $where;

        if (!$displayInactive) {
            $sessionId = session_id();

            $sql .= "AND ( ((art.`status` = 'active' AND cat.`status` = 'active') ";
            $sql .= "AND (acc.`status` = 'active' OR acc.`status` IS NULL OR acc.`id` = '{$accountId}')) ";
            $sql .= $accountId ? "OR art.`member_id` = {$accountId} OR `session` = '{$sessionId}' " : " OR (`session` = '{$sessionId}') ";
            $sql .= ')';
        }

        $sql .= $order ? 'ORDER BY ' . $order : '';
        $sql .= ' LIMIT 1';

        $article = $this->iaDb->getRow($sql);

        $decorateValues && $this->_processValues($article, true);

        return $article;
    }

    public function getById($id, $decorate = true)
    {
        return $this->getArticleBy('art.`id` = ' . (int)$id . ' ', '', false, $decorate);
    }

    public function getPreviousArticle($date, $categoryId)
    {
        return $this->getArticleBy("art.`date_added` < '{$date}' AND art.`category_id` = " . (int)$categoryId . ' ', 'art.`date_added` DESC ');
    }

    public function getNextArticle($date, $categoryId)
    {
        return $this->getArticleBy("art.`date_added` > '{$date}' AND art.`category_id` = " . (int)$categoryId . ' ', 'art.`date_added` ASC ');
    }

    /**
     * Inserts new article, returns article id
     *
     * @param array $itemData article information
     *
     * @return integer
     */
    protected function _addArticle($itemData)
    {
        $itemData['date_added'] = date(iaDb::DATETIME_FORMAT);
        $itemData['date_modified'] = date(iaDb::DATETIME_FORMAT);

        return parent::insert($itemData);
    }

    public function updateCounters($itemId, array $itemData, $action, $previousData = null)
    {
        $this->_checkIfCountersNeedUpdate($action, $itemData, $previousData,
            $this->iaCore->factoryItem('articlecat'));
    }

    public function sendMail($articleId)
    {
        $article = $this->getById($articleId);

        if ($this->iaCore->get('article_notif')) {
            $articleData = $this->getById($articleId, true);
            $iaMailer = $this->iaCore->factory('mailer');

            $iaMailer->loadTemplate('article_notif');
            $iaMailer->setReplacements([
                'title' => $articleData['title'],
                'url' => IA_ADMIN_URL . 'publishing/articles/edit/' . $articleData['id'] . '/',
                'view_url' => $article['link']
            ]);

            return $iaMailer->sendToAdministrators();
        }

        return false;
    }

    /**
    * Check for previous incomplete or saved article, creates new record if does not found
    * or restores old session.
    */
    public function createPostingSession()
    {
        $this->iaCore->factory('util');

        $data = [
            'status' => self::STATUS_HIDDEN,
            'ip' => iaUtil::getIp(),
            'member_id' => iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0,
            'session' => iaUsers::hasIdentity() ? '' : session_id()
        ];

        $result = ($article = $this->_getIncompleteArticle($data['member_id'], $data['session']))
            ? $article['id']
            : $this->_addArticle($data);

        return $result;
    }

    /**
     * Returns incomplete article
     *
     * @param integer $authorId account id
     * @param string $sessionId session id
     *
     * @return array
     */
    protected function _getIncompleteArticle($authorId, $sessionId)
    {
        $sql = 'SELECT * FROM ' . self::getTable(true) . " WHERE `status` = 'hidden' AND ";
        $sql .= ($authorId)
            ? '`member_id` = ' . (int)$authorId . ' '
            : "`member_id` = 0 AND `session` = '" . $sessionId . "' ";

        return $this->iaDb->getRow($sql);
    }

    public function update(array $entryData, $id)
    {
        $this->iaCore->factory('util');

        $langCode = $this->iaCore->language['iso'];

        // If URL field is empty, fill it
        if (empty($article['title_alias']) && empty($entryData['title_alias']) && $entryData['title_' . $langCode]) {
            $entryData['title_alias'] = iaSanitize::alias($entryData['title_' . $langCode]);
        }

        if ($this->iaCore->get('auto_generate_keywords') && empty($entryData['meta_keywords_' . $langCode]) && $entryData['body_' . $langCode]) {
            $entryData['meta_keywords_' . $langCode] = iaUtil::getMetaKeywords($entryData['body_' . $langCode]);
        }

        if (empty($entryData['meta_description_' . $langCode]) && !empty($entryData['summary_' . $langCode])) {
            $entryData['meta_description_' . $langCode] = substr(str_replace(PHP_EOL, '', iaSanitize::tags($entryData['summary_' . $langCode])), 0, 255);
        }

        if (in_array($entryData['url'], $this->_validProtocols)) {
            $entryData['url'] = '';
        } else {
            $found = false;
            foreach ($this->_validProtocols as $protocol) {
                if (stripos($entryData['url'], $protocol) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $entryData['url'] = $this->_validProtocols[0] . $entryData['url'];
            }
        }

        $entryData['date_modified'] = date(iaDb::DATETIME_FORMAT);
        $entryData['ip'] = iaUtil::getIp();

        return parent::update($entryData, $id);
    }

    public function fetchMemberListings($memberId, $start, $limit)
    {
        $stmt = 'AND t1.`member_id` = :member ORDER BY `t1`.`date_added` DESC';
        $this->iaDb->bind($stmt, ['member' => (int)$memberId]);

        return [
            'items' => $this->get($stmt, $start, $limit),
            'total_number' => $this->iaDb->foundRows()
        ];
    }

    public function getByQuery($sql)
    {
        $rows = $this->iaDb->getAll($sql);

        $this->_processValues($rows);

        return $rows;
    }
}
