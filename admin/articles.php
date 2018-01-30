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

class iaBackendController extends iaAbstractControllerModuleBackend
{
    protected $_name = 'articles';

    protected $_helperName = 'article';

    protected $_gridColumns = ['title', 'title_alias', 'body', 'date_added', 'date_modified', 'sticky', 'status'];
    protected $_gridFilters = ['status' => self::EQUAL, 'title' => self::LIKE];
    protected $_gridSorting = ['category_title' => ['title_alias', 'c'], 'member' => ['fullname', 'm']];
    protected $_gridQueryMainTableAlias = 'a';

    protected $_phraseAddSuccess = 'article_added';

    protected $_activityLog = true;

    private $_validUrlProtocols = ['http://', 'https://'];

    private $_iaArticlecat;


    public function init()
    {
        $this->_iaArticlecat = $this->_iaCore->factoryItem('articlecat');
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['member'])) {
            $stmt = iaDb::printf("`fullname` LIKE ':member%' OR  `username` LIKE ':member' ", ['member' => iaSanitize::sql($params['member'])]);
            $memberId = $this->_iaDb->one(iaDb::ID_COLUMN_SELECTION, $stmt, iaUsers::getTable());

            $conditions[] = 'a.`member_id` = :member_id';
            $values['member_id'] = $memberId;
        }
    }

    public function _gridQuery($columns, $where, $order, $start, $limit)
    {
        return $this->getHelper()->get($columns, $where, $order, $start, $limit);
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'member_id' => iaUsers::getIdentity()->id,
            'category_id' => 0,
            'featured' => false,
            'sponsored' => false,
            'status' => iaCore::STATUS_ACTIVE,
            'sticky' => false,
            'url' => ''
        ];
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        parent::_preSaveEntry($entry, $data, $action);

        $langCode = iaLanguage::getMasterLanguage()->iso;

        $entry['category_id'] = (int)$data['tree_id'];
        $entry['sticky'] = (int)$data['sticky'];
        $entry['title_alias'] = iaSanitize::alias(empty($data['title_alias']) ? $data['title'][$langCode] : $data['title_alias']);

        if ($this->_iaCore->get('auto_generate_keywords')
            && empty($entry['meta_keywords_' . $langCode]) && !empty($data['body'][$langCode])) {
            $entry['meta_keywords_' . $langCode] = iaUtil::getMetaKeywords($data['body'][$langCode]);
        }

        if (empty($data['summary'][$langCode])) {
            $entry['summary_' . $langCode] = iaSanitize::snippet($data['body'][$langCode], $this->_iaCore->get('snip_len'));
        }

        if (empty($entry['meta_description_' . $langCode]) && !empty($entry['summary_' . $langCode])) {
            $entry['meta_description_' . $langCode] = substr(str_replace(PHP_EOL, '', iaSanitize::tags($entry['summary_' . $langCode])), 0, 255);
        }

        if (isset($entry['url'])) {
            $entry['url'] = $this->_processUrl($entry['url']);
        }

        return !$this->getMessages();
    }

    protected function _entryAdd(array $entryData)
    {
        return $this->getHelper()->insert($entryData);
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        return $this->getHelper()->update($entryData, $entryId);
    }

    protected function _entryDelete($entryId)
    {
        return $this->getHelper()->delete($entryId);
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        parent::_assignValues($iaView, $entryData);

        if (isset($entryData['url']) && empty($entryData['url'])) {
            $entryData['url'] = $this->_validUrlProtocols[0];
        }

        $iaView->assign('statuses', $this->getHelper()->getStatuses());
        $iaView->assign('tree', $this->getHelper()->getTreeVars($entryData));
    }

    protected function _getJsonSlug($params)
    {
        $title = isset($params['title']) ? $params['title'] : '';
        $id = empty($params['id']) ? $this->_iaDb->getNextId() : (int)$params['id'];

        $category = isset($params['category']) ? (int)$params['category'] : 0;
        $alias = '';
        if ($category) {
            $alias = $this->_iaDb->one('title_alias', iaDb::convertIds($category), 'articles_categories');
        }

        if (!$this->_iaCore->get('articles_compact_url')) {
            $alias = 'article' . IA_URL_DELIMITER . $alias;
        }

        $alias = IA_MODULE_URL . $alias . $id . '-' . iaSanitize::alias($title) . '.html';

        return ['data' => $alias];
    }

    private function _processUrl($url)
    {
        $result = $url;

        if (in_array($result, $this->_validUrlProtocols)) {
            $result = '';
        } else {
            $found = false;
            foreach ($this->_validUrlProtocols as $protocol) {
                if (stripos($result, $protocol) !== false) {
                    $found = true;
                }
            }
            if (!$found) {
                $result = $this->_validUrlProtocols[0] . $result;
            }
        }

        return $result;
    }
}
