<?php

namespace Avito\Export\Admin\Page;

use Avito\Export\Concerns;
use Avito\Export\Admin\UserField;

abstract class Form extends Page
{
	use Concerns\HasLocale;

	public function hasRequest():bool
	{
		return $this->request->isPost();
	}

	abstract public function processRequest();

	protected function showFields($tab = null):void
	{
		$fields = $this->getFields($tab);
		$groupActive = null;

		foreach ($fields as $fieldCode => $field)
		{
			if (isset($field['GROUP']) && $field['GROUP'] !== $groupActive)
			{
				$groupActive = $field['GROUP'];

				echo $this->showGroupHeading($groupActive);
			}

			$fieldValue = $this->getFieldValueGet($fieldCode);
			if (!$fieldValue)
			{
				$fieldValue = $this->getFieldValue($fieldCode);
			}

			if (($fieldValue === null || $fieldValue === '') && isset($field['DEFAULT']))
			{
				$fieldValue = $field['DEFAULT'];
			}

			echo $this->showField($field, $fieldCode, $fieldValue);
		}
	}

	abstract public function getFields($tab = null);

	protected function showGroupHeading($group):string
	{
		$groups = $this->getFieldGroups();
		$result = '';

		if (isset($groups[$group]))
		{
			$result = '<tr class="heading">';
			$result .= '<td colspan="2">';
			$result .= $groups[$group];
			$result .= '</td>';
			$result .= '</tr>';
		}

		return $result;
	}

	public function getFieldGroups():array
	{
		return [];
	}

	protected function getFieldValueGet($code):array
	{
		return (array)$this->request->get($code);
	}

	abstract public function getFieldValue($fieldCode);

	protected function showField($field, $inputName, $value, $attributes = '') : string
	{
		$fieldTitle = $field['TITLE'] ?? $field['NAME'];
		$result = '';

		if (isset($field['TEXT_TITLE_DESCRIPTION']))
		{
			$result .= '<tr><td class="adm-detail-content-cell-l" colspan="2" style="text-align: center"><b>'
				. $field['TEXT_TITLE_DESCRIPTION'] . ':</b></td></tr>';
		}

		if ($field['HIDDEN'] !== 'Y')
		{
			if (isset($field['REQUIRED']) && $field['REQUIRED'])
			{
				$fieldTitle = '<b>' . $fieldTitle . '</b>';
			}

			$result .= '<tr ' . $attributes . '>';
			$result .= '<td class="adm-detail-content-cell-l" width="50%" valign="middle">' . $fieldTitle . ':</td>';
			$result .= '<td class="adm-detail-content-cell-r" width="50%">';
		}

		$result .= $this->showFieldControl($field, $inputName, $value);

		if (isset($field['NOTE']))
		{
			$result .= BeginNote();
			$result .= $field['NOTE'];
			$result .= EndNote();
		}

		if ($field['HIDDEN'] !== 'Y')
		{
			$result .= '</td>';
			$result .= '</tr>';
		}
		return $result;
	}

	protected function showFieldControl($field, $inputName, $value) : string
	{
		$field['ENTITY_VALUE_ID'] = 1; // ignore defaults
		$field['FIELD_NAME'] = $inputName;

		$html = UserField\Helper\Renderer::editHtml($field, $value);

		if (!empty($field['UNIT']))
		{
			$html .= '&nbsp;' . $field['UNIT'];
		}

		return $html;
	}

	protected function showFormProlog() : void
	{
		$postUrl = $this->request->getRequestUri();
		$formId = $this->getFormId();

		echo '<form method="post" action="' . htmlspecialcharsbx($postUrl) . '" id="' . $formId . '">';
		echo bitrix_sessid_post();
	}

	abstract protected function getFormId() : string;

	protected function showFormButtons():void
	{
		echo '<input class="adm-btn-save" type="submit" value="' . self::getLocale('APPLY') . '">';
		echo '<input class="adm-btn" type="reset" value="' . self::getLocale('RESET') . '">';
	}

	protected function showFormEpilog() : void
	{
		echo '</form>';
	}
}
