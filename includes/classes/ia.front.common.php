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

class iaCommon extends abstractCore
{
    protected static $_table = 'articles_categories';


    /**
     * Builds categories trees
     *
     * @param array $categories categories array
     * @param int $parentId parent category id
     * @param bool $selected
     *
     * @return string
     */
    protected function _buildCategoriesTree($categories, $parentId = 0, $selected = false)
    {
        $out = '';
        $iaCore = iaCore::instance();
        $iaView = &$iaCore->iaView;
        $title = 'title_'. $this->iaCore->language['iso'];

        $isBackend = (iaCore::ACCESS_ADMIN == $iaCore->getAccessType());

        foreach ($categories as $cat) {
            if ($cat['parent_id'] == $parentId) {
                $cat[$title] = ($cat['level'] > 1 || $isBackend ? str_repeat('&nbsp;&nbsp;', $cat[$title] - ($isBackend ? 0 : 1)) : '') . $cat[$title];
                if ($isBackend && $iaView->name() == 'articlecat_edit' && isset($_GET['id']) && $_GET['id'] == $cat['id']) {
                    $out .= '<optgroup label="' . $cat['title'] . ' [' . iaLanguage::get('self', 'SELF CATEGORY') . ']" disabled="disabled">';
                    $out .= $this->_buildCategoriesTree($categories, $cat['id'], $selected);
                    $out .= '</optgroup>';
                } else {
                    $locked = isset($cat['locked']) && $cat['locked'] == 1 ? true : false;

                    if ($locked) {
                        $cat['title'] = $cat['title'] . ' [' . iaLanguage::get('locked', 'Locked') . ']';
                    }

                    if (!$locked && iaCore::ACCESS_FRONT == $iaCore->getAccessType()
                        || iaCore::ACCESS_ADMIN == $iaCore->getAccessType()) {
                        $out .= '<option value="' . $cat['id'] . '" ' . ($selected == $cat['id'] ? ' selected="selected"' : '') . ' ' . ($isBackend ? ' alias="' . $cat['title_alias'] . '"' : '') . '>' . $cat[$title] . '</option>';
                    } else {
                        $out .= '<optgroup label="' . $cat['title'] . '"></optgroup>';
                    }
                    $out .= $this->_buildCategoriesTree($categories, $cat['id'], $selected);
                }
            }
        }

        return $out;
    }

    /**
     * Wrapper for _buildCategoriesTree() to return HTML-code for tree select box
     *
     * @param bool $selected
     *
     * @return string
     */
    public function getCategoriesTree($selected = false)
    {
        $title = 'title_'. $this->iaCore->language['iso'];

        $fields = ['id', 'parent_id', $title, 'level', 'locked', 'title_alias'];
        $stmt = '`status` = :status AND `locked` = 0 ';
        $order = 'ORDER BY `' . ('by ' . $title == $this->iaCore->get('articles_categs_sort', 'by ' . $title) ? $title : 'order') . '`';

        $this->iaDb->bind($stmt, ['status' => iaCore::STATUS_ACTIVE]);

        $rows = $this->iaDb->all($fields, $stmt . $order, null, null, self::getTable());

        $rootId = 0;
        foreach ($rows as $c) {
            if (0 == $c['parent_id']) {
                $rootId = $c['id'];
                break;
            }
        }

        return $this->_buildCategoriesTree($rows, $rootId, $selected);
    }
}
