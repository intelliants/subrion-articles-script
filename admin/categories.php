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
    protected $_name = 'categories';
    protected $_itemName = 'articlecats';

    protected $_helperName = 'articlecat';

    protected $_gridColumns = ['parent_id', 'title', 'title_alias', 'num_articles', 'num_all_articles', 'locked', 'level', 'order', 'status'];
    protected $_gridFilters = ['status' => self::EQUAL, 'title' => self::LIKE];
    protected $_gridQueryMainTableAlias = 'c';

    protected $_phraseAddSuccess = 'article_category_added';

    protected $_activityLog = ['item' => 'category'];


    public function init()
    {
        $this->_gridSorting['parent_title'] = ['title_' . $this->_iaCore->language['iso'], 'p'];
    }

    public function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql = <<<SQL
SELECT :columns, p.`title_:lang` `parent_title`
	FROM `:table` c 
LEFT JOIN `:table` p ON (c.`:col_parent` = p.`id`) 
WHERE :where :order 
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'lang' => $this->_iaCore->language['iso'],
            'table' => $this->_iaDb->prefix . $this->getTable(),
            'columns' => $columns,
            'where' => $where,
            'order' => $order,
            'start' => $start,
            'limit' => $limit,
            'col_parent' => iaArticlecat::COL_PARENT_ID
        ]);

        return $this->_iaDb->getAll($sql);
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

    public function updateCounters($entryId, array $entryData, $action, $previousData = null)
    {
        if (iaCore::ACTION_EDIT == $action) {
            if (isset($entryData['title_alias']) && $entryData['title_alias'] != $previousData['title_alias']) {
                $this->_correctAlias($entryData['title_alias'], $previousData['title_alias']);
            }
        }
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry = [
            'title_alias' => '',
            'locked' => false,
            'nofollow' => false,
            'priority' => false,
            'status' => iaCore::STATUS_ACTIVE,
            'parent_id' => $this->getHelper()->getRootId()
        ];
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        parent::_preSaveEntry($entry, $data, $action);

        $entry['locked'] = (int)$data['locked'];
        $entry['nofollow'] = (int)$data['nofollow'];
        $entry['priority'] = (int)$data['priority'];
        $entry['parent_id'] = (int)$data['tree_id'];

        $langCode = iaLanguage::getMasterLanguage()->iso;

        if (0 == $entry['parent_id']) {
            $entry['title_alias'] = '';
        } else {
            $entry['title_alias'] = empty($data['title_alias']) ? $data['title'][$langCode] : $data['title_alias'];
            $entry['title_alias'] = iaSanitize::alias($entry['title_alias']) . IA_URL_DELIMITER;
        }

        if ($this->_iaDb->exists('`title_' . $langCode . '` = :title AND `parent_id` = :parent_id AND `id` != :id',
            ['title' => $entry['title_' . $langCode], 'parent_id' => $entry['parent_id'], 'id' => $this->getEntryId()])) {
            $this->addMessage('specified_category_title_exists');
        } elseif ($entry['parent_id'] != $this->getHelper()->getRootId()) {
            $parentCategory = $this->_iaDb->row('title_alias', iaDb::convertIds($entry['parent_id']));
            $entry['title_alias'] = ($parentCategory ? $parentCategory['title_alias'] : '') . $entry['title_alias'];
        }

        return !$this->getMessages();
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        parent::_assignValues($iaView, $entryData);

        $array = explode(IA_URL_DELIMITER, trim($entryData['title_alias'], IA_URL_DELIMITER));
        $entryData['title_alias'] = end($array);

        $iaView->assign('statuses', $this->getHelper()->getStatuses());
        $iaView->assign('tree', $this->getHelper()->getTreeVars($this->getEntryId(), $entryData, $this->getPath()));
    }

    protected function _writeLog($action, array $entryData, $entryId)
    {
        if (iaCore::ACTION_ADD != $action) {
            return;
        }

        parent::_writeLog($action, $entryData, $entryId);
    }

    protected function _setPageTitle(&$iaView, array $entryData, $action)
    {
        $iaView->title(iaLanguage::get($action . '_category', $iaView->title()));
    }


    protected function _correctAlias($newAlias, $previousAlias)
    {
        $stmtWhere = '`title_alias` LIKE :alias';
        $this->_iaDb->bind($stmtWhere, ['alias' => $previousAlias . '%']);

        $stmtReplace = sprintf("REPLACE(`title_alias`, '%s', '%s')", $previousAlias, $newAlias);

        $this->_iaDb->update(null, $stmtWhere, ['title_alias' => $stmtReplace], self::getTable());
    }

    protected function _getJsonAlias(array $data)
    {
        $categoryId = isset($data['category']) ? (int)$data['category'] : $this->getHelper()->getRootId();

        $alias = IA_MODULE_URL;
        $alias.= $this->_iaDb->one('title_alias', iaDb::convertIds($categoryId));
        $alias.= iaSanitize::alias($data['title']) . IA_URL_DELIMITER;

        return ['data' => $alias];
    }

    protected function _getJsonConsistency(array $data)
    {
        $output = [];

        if (isset($_POST['action'])) {
            $iaArticle = $this->_iaCore->factoryModule('article', $this->getModuleName(), iaCore::ADMIN);

            switch ($_POST['action']) {
                // fixing paths
                case 'pre_repair_articlecats_paths':
                    $output['total'] = $this->getHelper()->getCount();

                    break;

                case 'rebuild_articlecats_paths':
                    $rows = $this->_iaDb->all([iaDb::ID_COLUMN_SELECTION], iaDb::convertIds(0, 'parent_id', false), (int)$_POST['start'], (int)$_POST['limit']);
                    foreach ($rows as $row) {
                        $this->getHelper()->rebuildAliases($row['id']);
                    }

                    break;

                // recount
                case 'pre_recount_counters':
                    $this->getHelper()->resetCounters();

                    $output['total'] = $this->_iaDb->one(iaDb::STMT_COUNT_ROWS,
                        iaDb::convertIds(iaCore::STATUS_ACTIVE, 'status'), iaArticle::getTable());

                    break;

                case 'recount_counters':
                    $this->getHelper()->recount($_POST['start'], $_POST['limit']);

                    break;

                case 'pre_rebuild_article_paths':
                    $output['total'] = $iaArticle->getCount();

                    break;

                case 'rebuild_article_paths':
                    $rows = $this->_iaDb->all([iaDb::ID_COLUMN_SELECTION], '', (int)$_POST['start'], (int)$_POST['limit'], iaArticle::getTable());

                    foreach ($rows as $row) {
                        $iaArticle->rebuildArticleAliases($row['id']);
                    }
            }
        }

        return $output;
    }
}
