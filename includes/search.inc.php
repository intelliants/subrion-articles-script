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

function articles_search($aQuery, $aFields, $aStart, $aLimit, &$aNumAll, $aWhere = '', $cond = 'AND')
{
    $iaCore = iaCore::instance();
    $output = [];
    $match = [];

    if ($aQuery && empty($aFields)) {
        $match[] = sprintf(" MATCH (t1.`title`, t1.`body`) AGAINST('%s') ", iaSanitize::sql($aQuery));
    }
    if ($aWhere) {
        $match[] = '' . $aWhere;
    }

    // additional fields
    if ($aFields && is_array($aFields)) {
        foreach ($aFields as $fname => $data) {
            if ('LIKE' == $data['cond']) {
                $data['val'] = "%{$data['val']}%";
            }
            // for multiple values, like combo or checkboxes
            if (is_array($data['val'])) {
                if ('!=' == $data['cond']) {
                    $data['cond'] = count($data['val']) > 1 ? 'NOT IN' : '!=';
                } else {
                    $data['cond'] = count($data['val']) > 1 ? 'IN' : '=';
                }
                $data['val'] = count($data['val']) > 1 ? '(' . implode(',', $data['val']) . ')' : array_shift($data['val']);
            } elseif (preg_match('/^(\d+)\s*-\s*(\d+)$/', $data['val'], $range)) {
                // search in range
                $data['cond'] = sprintf('BETWEEN %d AND %d', $range[1], $range[2]);
                $data['val'] = '';
            } else {
                $data['val'] = "'" . iaSanitize::sql($data['val']) . "'";
            }

            $match[] = "t1.`{$fname}` {$data['cond']} {$data['val']} ";
        }
    }

    $iaArticle = $iaCore->factoryModule('article', 'publishing');

    $articles = $match
        ? $iaArticle->get(' AND (' . implode(' ' . $cond . ' ', $match) . ')', $aStart, $aLimit)
        : [];
    $aNumAll += $iaCore->iaDb->foundRows();

    empty($articles) || $iaArticle->wrapValues($articles);

    $iaSmarty = &$iaCore->iaView->iaSmarty;

    $iaSmarty->assign('config', $iaCore->getConfig());
    $iaSmarty->assign('member', iaUsers::getIdentity(true));
    $iaSmarty->assign('page', $iaCore->iaView->getParams());
    $iaSmarty->assign('packages', $iaCore->modulesData);

    $resourceName = 'list-articles.tpl';
    $resourceName = is_file(IA_FRONT_TEMPLATES . $iaCore->get('tmpl') . IA_DS . 'packages' . IA_DS . 'publishing' . IA_DS . $resourceName)
        ? IA_FRONT_TEMPLATES . $iaCore->get('tmpl') . IA_DS . 'packages' . IA_DS . 'publishing' . IA_DS . $resourceName
        : IA_MODULES . 'publishing/templates/common/' . $resourceName;

    $iaSmarty->assignGlobal('img', IA_TPL_URL . 'img/');
    foreach ($articles as $art) {
        $iaSmarty->assign('article', $art);
        $output[] = $iaSmarty->fetch($resourceName);
    }

    return $output;
}

function articlecats_search($aQuery, $aFields, $aStart, $aLimit, &$aNumAll)
{
    $iaCore = iaCore::instance();
    $output = [];
    $where = "`title` LIKE '%$aQuery%' OR `description` LIKE '%$aQuery%'";
    $cats = $iaCore->iaDb->all(iaDb::STMT_CALC_FOUND_ROWS . ' `title`, `title_alias`', $where, $aStart, $aLimit, 'articles_categories');
    $aNumAll += $iaCore->iaDb->foundRows();

    foreach ($cats as $c) {
        $output[] = sprintf('<p><a href="%s">%s</a></p>', $iaCore->modulesData['publishing']['url'] . $c['title_alias'], $c['title']);
    }

    return $output;
}
