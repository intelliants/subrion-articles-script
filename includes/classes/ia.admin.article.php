<?php
//##copyright##

class iaArticle extends abstractPublishingModuleAdmin
{
	protected static $_table = 'articles';

	protected $_itemName = 'articles';

	protected $_statuses = [iaCore::STATUS_ACTIVE, iaCore::STATUS_APPROVAL, iaCore::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_HIDDEN, self::STATUS_SUSPENDED, self::STATUS_PENDING];

	public $dashboardStatistics = ['icon' => 'news'];


	public function init()
	{
		iaCore::instance()->factoryModule('articlecat', $this->getModuleName(), iaCore::ADMIN);

		parent::init();
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

		if ($entries = $this->iaDb->getAll($sql))
		{
			$baseUrl = $this->getInfo('url');

			foreach ($entries as $entry)
			{
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

	protected function _editCounter($categId, $action)
	{
		$sql = <<<SQL
UPDATE `:table` SET `num_articles` = IF(`id` = :catId, `num_articles` :action 1, `num_articles`), 
	`num_all_articles` = `num_all_articles` :action 1 
WHERE FIND_IN_SET(:catId, `child`)
SQL;
		$sql = iaDb::printf($sql, [
			'table' => iaArticlecat::getTable(true),
			'action' => $action,
			'catId' => $categId
		]);

		return $this->iaDb->query($sql);
	}

	public function recount($entryId, array $newData, $oldData)
	{
		if (!isset($newData['status']) || !isset($oldData['status']))
		{
			return;
		}

		$newData = $this->getById($entryId);

		if ($newData['status'] == iaCore::STATUS_ACTIVE && $oldData['status'] != iaCore::STATUS_ACTIVE) // status of the listing has been changed to Active
		{
			$this->_editCounter($newData['category_id'], self::COUNTER_ACTION_INCREMENT);
		}
		elseif ($oldData['status'] == iaCore::STATUS_ACTIVE && $newData['status'] != iaCore::STATUS_ACTIVE) // listing has been deactivated
		{
			$this->_editCounter($oldData['category_id'], self::COUNTER_ACTION_DECREMENT);
		}
		elseif ($newData['status'] == iaCore::STATUS_ACTIVE && $oldData['status'] == iaCore::STATUS_ACTIVE) // listing has only been moved to another category
		{
			if (isset($newData['category_id']) && $newData['category_id'] != $oldData['category_id'])
			{
				$this->_editCounter($newData['category_id'], self::COUNTER_ACTION_INCREMENT);
				$this->_editCounter($oldData['category_id'], self::COUNTER_ACTION_DECREMENT);
			}
		}
	}

	public function getCount()
	{
		return $this->iaDb->one(iaDb::STMT_COUNT_ROWS, null, self::getTable());
	}

	public function sendMail($action, $email, $data)
	{
		if ($this->iaCore->get($action) && $email)
		{
			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate($action);
			$iaMailer->addAddress($email);
			$iaMailer->setReplacements([
				'title' => $data['title'],
				'reason' => isset($data['reason']) ? $data['reason'] : '',
				'view_url' => IA_URL . 'article/' . $data['category_alias'] . $data['id'] . '-' . $data['title_alias'] . '.html',
				'edit_url' => IA_MODULE_URL . 'edit/' . $data['id'] . '/'
			]);

			return $iaMailer->send();
		}

		return false;
	}
}