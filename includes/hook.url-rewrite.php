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

$package = 'publishing';
$accessGranted = false;
$isDefaultPackage = (bool)($iaCore->get('default_package') == $package);
$isCompactUrlMode = (bool)$iaCore->get('articles_compact_url');
$isCategory = true;
$isArticle = false;
$extras = $iaCore->getModules($package);

if ($iaCore->checkDomain() && $isDefaultPackage) {
    $accessGranted = true;
} elseif (!$iaCore->checkDomain()) {
    if (isset($extras['url']) && $extras['url'] == $iaView->domainUrl) {
        $accessGranted = true;
    }
} elseif ($isCompactUrlMode && count($iaCore->requestPath) > 0) {
    $accessGranted = true;
} elseif (!$isCompactUrlMode && 'article' == $iaCore->requestPath[0]) {
    $accessGranted = true;
}

if ($accessGranted) {
    $url = end($iaView->url);

    // check for category to avoid filtering articles that start from a digit
    $categoryPath = implode(IA_URL_DELIMITER, $iaView->url) . IA_URL_DELIMITER;
    if ($iaDb->exists('`status` = :status AND `title_alias` = :path', ['status' => 'active', 'path' => $categoryPath], 'articles_categories')) {
        $isPageExist = $iaDb->exists('`status` = :status AND `alias` = :path', ['status' => 'active', 'path' => $categoryPath], 'pages');
        if (!$isPageExist) {
            if ($isDefaultPackage) {
                if ($pageUrl = $iaDb->one_bind('alias', '`name` = :page AND `status` = :status',
                    ['page' => 'publishing_home', 'status' => iaCore::STATUS_ACTIVE], 'pages')
                ) {
                    $pageUrl = explode(IA_URL_DELIMITER, trim($pageUrl, IA_URL_DELIMITER));
                    $pageUrl = array_shift($pageUrl);
                    $pageUrl = ('publishing_home' == $iaCore->get('home_page')) ? $pageUrl . '_home' : $pageUrl;

                    $iaView->name($pageUrl);
                    $iaCore->requestPath = $iaView->url;
                }
            } else {
                $iaCore->requestPath = $iaView->url;
                $iaView->name('publishing_home');
            }
        }
    } else {
        if ($articleData = $iaDb->row(['id', 'category_id', 'title_alias'], iaDb::convertIds($url), 'articles')) {
            if ($articleData['title_alias']) {
                $alias = substr($url, strpos($url, '-') + 1);
                if ($alias == $articleData['title_alias']) {
                    $isArticle = true;
                }
            }
        }
    }

    if ($isArticle) {
        if ($iaCore->get('articles_url_validation')) {
            $category = $iaDb->row_bind(['title_alias'], '`status` = :status AND `id` = :id',
                ['status' => iaCore::STATUS_ACTIVE, 'id' => $articleData['category_id']], 'articles_categories');

            if (!$category || empty($iaView->url)) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            } else {
                $categoryPath = $iaView->url;

                unset($categoryPath[count($categoryPath) - 1]);

                $categoryPath = implode(IA_URL_DELIMITER, $categoryPath) . IA_URL_DELIMITER;
                $categoryUrl = $category['title_alias'];

                if ($categoryPath !== $categoryUrl) {
                    $url = $iaView->domainUrl . IA_URL_LANG . ($extras['url'] != '/' ? $extras['url'] : '') . ($isCompactUrlMode ? '' : 'article/') . $categoryUrl . $articleData['id'] . '-' . $articleData['title_alias'] . '.html';

                    // get current url to prevent incorrect redirect
                    $isHTTPS = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
                    $port = (isset($_SERVER['SERVER_PORT']) && ((!$isHTTPS && $_SERVER['SERVER_PORT'] != '80') || ($isHTTPS && $_SERVER['SERVER_PORT'] != '443')));
                    $port = ($port) ? ':' . $_SERVER['SERVER_PORT'] : '';
                    $currentUrl = ($isHTTPS ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];

                    if ($currentUrl !== $url) {
                        header('HTTP/1.1 301');
                        header('Location: ' . $url);
                        exit();
                    }
                }
            }
        }

        array_unshift($iaCore->requestPath, 'article');

        $pageName = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('view_article', false);
        $pageName = explode(IA_URL_DELIMITER, $pageName);
        $pageName = array_shift($pageName);

        $iaView->name($pageName);
    }

    // do not rewrite page name for non-default packages
    if ($this->checkDomain() && $isDefaultPackage) {
        if ($plugins = $iaDb->onefield('name', "`status` = 'active'", 0, 0, 'modules')) {
            foreach ($plugins as $key => $pluginName) {
                $plugins[$key] = $iaCore->getModules($pluginName);
                if (isset($iaCore->requestPath[0]) && (($iaCore->requestPath[0] . IA_URL_DELIMITER) == $plugins[$key]['url'])) {
                    $iaView->name($iaCore->requestPath[0]);
                    array_shift($iaCore->requestPath);
                }
            }
        }
    }
}
