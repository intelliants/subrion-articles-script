<?php
//##copyright##

class iaArticle extends abstractPublishingPackageAdmin
{
	protected static $_table = 'articles';

	protected $_itemName = 'articles';

	protected $_statuses = array(iaCore::STATUS_ACTIVE, iaCore::STATUS_APPROVAL, iaCore::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_HIDDEN, self::STATUS_SUSPENDED, self::STATUS_PENDING);

	public $dashboardStatistics = array('icon' => 'news');


	public function sendMail($action, $email, $data)
	{
		if ($this->iaCore->get($action) && $email)
		{
			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate($action);
			$iaMailer->addAddress($email);
			$iaMailer->setReplacements(array(
				'title' => $data['title'],
				'reason' => isset($data['reason']) ? $data['reason'] : '',
				'view_url' => IA_URL . 'article/' . $data['category_alias'] . $data['id'] . '-' . $data['title_alias'] . '.html',
				'edit_url' => IA_PACKAGE_URL . 'edit/' . $data['id'] . '/'
			));

			return $iaMailer->send();
		}

		return false;
	}

	public function getSitemapEntries()
	{
		$result = array();

		$this->iaCore->factoryPackage('articlecat', $this->getPackageName(), iaCore::ADMIN);

		$sql =
			'SELECT a.`id`, a.`title_alias`, c.`title_alias` `category_alias` ' .
			'FROM `:table` a ' .
			'LEFT JOIN `:table_categories` c ON (c.`id` = a.`category_id`) ' .
			"WHERE a.`status` = ':status'";
		$sql = iaDb::printf($sql, array(
			'table' => self::getTable(true),
			'table_categories' => iaArticlecat::getTable(true),
			'status' => iaCore::STATUS_ACTIVE
		));

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

	public function getDashboardStatistics($defaultProcessing = true)
	{
		$statuses = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 = 1 GROUP BY `status`', self::getTable());
		$total = 0;

		$listingStatuses = $this->getStatuses();
		$listingStatuses = array_diff($listingStatuses, array(self::STATUS_HIDDEN));
		foreach ($listingStatuses as $status)
		{
			isset($statuses[$status]) || $statuses[$status] = 0;
			$total += $statuses[$status];
		}

		if ($defaultProcessing)
		{
			$data = array();
			$max = 0;
			$weekDay = getdate();
			$weekDay = $weekDay['wday'];
			$rows = $this->iaDb->all('DAYOFWEEK(DATE(`date_added`)) `day`, `status`, `date_added`', 'DATE(`date_added`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW())', null, null, self::getTable());

			foreach ($listingStatuses as $status) $data[$status] = array();
			foreach ($rows as $row)
			{
				isset($data[$row['status']][$row['day']]) || $data[$row['status']][$row['day']] = 0;
				$data[$row['status']][$row['day']]++;
			}
			foreach ($data as $key => &$days)
			{
				$i = null;
				for ($i = 1; $i < 8; $i++)
				{
					isset($days[$i]) || $days[$i] = 0;
					$max = max($max, $days[$i]);
				}
				ksort($days, SORT_NUMERIC);
				$days = implode(',', $days);
				$stArray[] = $key;
			}
		}

		return array_merge(array(
			'_format' => 'package',
			'data' => $defaultProcessing
				? array('array' => implode('|', $data), 'max' => $max, 'statuses' => implode('|', $stArray))
				: implode(',', $statuses),
			'rows' => $statuses,
			'item' => $this->getItemName(),
			'total' => number_format($total)
		), $this->dashboardStatistics);
	}
}