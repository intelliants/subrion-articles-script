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
	protected $_itemName = 'articles';

	protected $_helperName = 'article';

	protected $_gridColumns = ['title', 'title_alias', 'body', 'date_added', 'date_modified', 'sticky', 'status'];
	protected $_gridFilters = ['status' => self::EQUAL, 'title' => self::LIKE];
	protected $_gridQueryMainTableAlias = 'a';

	protected $_phraseAddSuccess = 'article_added';

	protected $_activityLog = true;

	private $_validUrlProtocols = ['http://', 'https://'];

	private $_iaArticlecat;


	public function init()
	{
		$this->_iaArticlecat = $this->_iaCore->factoryModule('articlecat', $this->getModuleName(), iaCore::ADMIN);
	}

	protected function _modifyGridParams(&$conditions, &$values, array $params)
	{
		if (!empty($params['member']))
		{
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

	protected function _entryAdd(array $entryData)
	{
		$entryData['date_added'] = date(iaDb::DATETIME_FORMAT);
		$entryData['date_modified'] = date(iaDb::DATETIME_FORMAT);

		if (isset($entryData['url']))
		{
			$entryData['url'] = $this->_processUrl($entryData['url']);
		}

		return parent::_entryAdd($entryData);
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		$entryData['date_modified'] = date(iaDb::DATETIME_FORMAT);

		if (isset($entryData['url']))
		{
			$entryData['url'] = $this->_processUrl($entryData['url']);
		}

		return parent::_entryUpdate($entryData, $entryId);
	}

	public function updateCounters($entryId, array $entryData, $action, $previousData = null)
	{
		if (iaCore::ACTION_EDIT == $action)
		{
			// notify owner on status change
			if (isset($entryData['status']) && in_array($entryData['status'], [iaArticle::STATUS_SUSPENDED, iaArticle::STATUS_REJECTED, iaCore::STATUS_ACTIVE]))
			{
				$entry = $this->getById($entryId);
				$owner = $this->_iaCore->factory('users')->getInfo($entry['member_id']);
				$action = $entryData['status'];

				if (iaCore::STATUS_ACTIVE == $entryData['status'])
				{
					$action = iaCore::STATUS_APPROVAL;
				}

				$this->getHelper()->sendMail('article_' . $action, $owner['email'], $entry);
			}
		}

		$this->getHelper()->recount($entryId, $previousData);
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

		$langCode = $this->_iaCore->language['iso'];

		$entry['category_id'] = (int)$data['tree_id'];
		$entry['sticky'] = (int)$data['sticky'];
		$entry['title_alias'] = iaSanitize::alias(empty($data['title_alias']) ? $data['title'][$langCode] : $data['title_alias']);

		if ($this->_iaCore->get('auto_generate_keywords')
			&& empty($entry['meta_keywords_' . $langCode]) && !empty($data['body'][$langCode]))
		{
			$entry['meta_keywords_' . $langCode] = iaUtil::getMetaKeywords($data['body'][$langCode]);
		}

		if (empty($data['summary'][$langCode]))
		{
			$entry['summary_' . $langCode] = iaSanitize::snippet($data['body'][$langCode], $this->_iaCore->get('snip_len'));
		}

		return !$this->getMessages();
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		parent::_assignValues($iaView, $entryData);

		if (isset($entryData['url']) && empty($entryData['url']))
		{
			$entryData['url'] = $this->_validUrlProtocols[0];
		}

		// category
		$parent = $this->_iaArticlecat->getById($entryData['category_id']);

		$entryData['parents'] = $parent['parents'];

		$iaView->assign('parent', $parent);
		$iaView->assign('statuses', $this->getHelper()->getStatuses());
	}


	protected function _getJsonAlias($params)
	{
		$title = isset($params['title']) ? $params['title'] : '';
		$id = empty($params['id']) ? $this->_iaDb->getNextId() : (int)$params['id'];

		$category = isset($params['category']) ? (int)$params['category'] : 0;
		$alias = '';
		if ($category)
		{
			$alias = $this->_iaDb->one('title_alias', iaDb::convertIds($category), 'articles_categories');
		}

		if (!$this->_iaCore->get('articles_compact_url'))
		{
			$alias = 'article' . IA_URL_DELIMITER . $alias;
		}

		$alias = IA_MODULE_URL . $alias . $id . '-' . iaSanitize::alias($title) . '.html';

		return ['data' => $alias];
	}

	private function _processUrl($url)
	{
		$result = $url;

		if (in_array($result, $this->_validUrlProtocols))
		{
			$result = '';
		}
		else
		{
			$found = false;
			foreach ($this->_validUrlProtocols as $protocol)
			{
				if (stripos($result, $protocol) !== false)
				{
					$found = true;
				}
			}
			if (!$found)
			{
				$result = $this->_validUrlProtocols[0] . $result;
			}
		}

		return $result;
	}
}