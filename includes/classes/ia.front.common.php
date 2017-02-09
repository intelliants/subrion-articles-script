<?php
//##copyright##

class iaCommon extends abstractCore
{
	protected static $_table = 'articles_categories';

	/**
	 * @param arr $aCategories array with categories
	 * @param int $aIdParent used only for recusive
	 * @param int $aSelected selected category ID
	 */
	protected function _buildCategoriesTree($aCategories, $aIdParent = 0, $aSelected = false)
	{
		$out = '';
		$iaCore = iaCore::instance();
		$iaView = &$iaCore->iaView;

		$isBackend = (iaCore::ACCESS_ADMIN == $iaCore->getAccessType());

		foreach ($aCategories as $cat)
		{
			if ($cat['parent_id'] == $aIdParent)
			{
				$cat['title'] = ($cat['level'] > 1 || $isBackend ? str_repeat('&nbsp;&nbsp;', $cat['level'] - ($isBackend ? 0 : 1)) : '') . $cat['title'];
				if ($isBackend && $iaView->name() == 'articlecat_edit' && isset($_GET['id']) && $_GET['id'] == $cat['id'])
				{
					$out .= '<optgroup label="' . $cat['title'] . ' [' . iaLanguage::get('self', 'SELF CATEGORY') . ']" disabled="disabled">';
					$out .= $this->_buildCategoriesTree($aCategories, $cat['id'], $aSelected);
					$out .= '</optgroup>';
				}
				else
				{
					$locked = isset($cat['locked']) && $cat['locked'] == 1 ? true : false;

					if ($locked)
					{
						$cat['title'] = $cat['title'] . ' [' . iaLanguage::get('locked', 'Locked') . ']';
					}

					if (!$locked && iaCore::ACCESS_FRONT == $iaCore->getAccessType()
						|| iaCore::ACCESS_ADMIN == $iaCore->getAccessType())
					{
						$out .= '<option value="' . $cat['id'] . '" ' . ($aSelected == $cat['id'] ? ' selected="selected"' : '') . ' ' . ($isBackend ? ' alias="' . $cat['title_alias'] . '"' : '') . '>' . $cat['title'] . '</option>';
					}
					else
					{
						$out .= '<optgroup label="' . $cat['title'] . '"></optgroup>';
					}
					$out .= $this->_buildCategoriesTree($aCategories, $cat['id'], $aSelected);
				}
			}
		}

		return $out;
	}

	/**
	 * Wrapper for _buildCategoriesTree()
	 * return HTML-code for tree select box
	 *
	 * @param int $selected selected category ID
	 */
	public function getCategoriesTree($selected = false)
	{
		$fields = ['id', 'parent_id', 'title', 'level', 'locked', 'title_alias'];
		$stmt = '`status` = :status AND `locked` = 0 ';
		$order = 'ORDER BY `' . ('by title' == $this->iaCore->get('articles_categs_sort', 'by title') ? 'title' : 'order') . '`';

		$this->iaDb->bind($stmt, ['status' => iaCore::STATUS_ACTIVE]);

		$rows = $this->iaDb->all($fields, $stmt . $order, null, null, self::getTable());

		$rootId = 0;
		foreach ($rows as $c)
		{
			if (0 == $c['parent_id'])
			{
				$rootId = $c['id'];
				break;
			}
		}

		return $this->_buildCategoriesTree($rows, $rootId, $selected);
	}
}