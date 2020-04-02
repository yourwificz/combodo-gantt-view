<?php
/**
 * Copyright (C) 2013-2020 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */

/**
 *
 * Dashlet to embed a gantt into a Dashboard
 *
 */
class GanttDashlet extends Dashlet
{
	protected $iLevelCount;

	/**
	 * GanttDashlet constructor.
	 *
	 * @param \ModelReflection $oModelReflection
	 * @param string $sId
	 */
	public function __construct(\ModelReflection $oModelReflection, $sId)
	{
		parent::__construct($oModelReflection, $sId);
		if ($this->oModelReflection->IsValidClass("UserRequest")
			&& $this->oModelReflection->IsValidAttCode("UserRequest", "title")
			&& $this->oModelReflection->IsValidAttCode("UserRequest", "start_date")
			&& $this->oModelReflection->IsValidAttCode("UserRequest", "end_date")
			&& $this->oModelReflection->IsValidAttCode("UserRequest", "related_request_list"))
		{
			$this->aProperties['title'] = Dict::S('GanttDashlet/Prop:DefaultTitle');
			$this->aProperties['oql'] = 'SELECT UserRequest';
			$this->aProperties['class_0'] = 'UserRequest';
			$this->aProperties['depends_on'] = 'parent_request_id';
			$this->aProperties['target_depends_on'] = '';
			$this->aProperties['label_0'] = 'title';
			$this->aProperties['start_date_0'] = 'start_date';
			$this->aProperties['end_date_0'] = 'end_date';
		}
		else
		{
			$this->aProperties['title'] = Dict::S('GanttDashlet/Prop:DefaultTitle2');
			$this->aProperties['oql'] = 'SELECT Contact ';
			$this->aProperties['class_0'] = 'Contact';
			$this->aProperties['depends_on'] = '';
			$this->aProperties['target_depends_on'] = '';
			$this->aProperties['label_0'] = '';
			$this->aProperties['start_date_0'] = '';
			$this->aProperties['end_date_0'] = '';
		}
		$this->aProperties['additional_info1_0'] = '';
		$this->aProperties['additional_info2_0'] = '';
		$this->aProperties['percentage_0'] = '';
		$this->aProperties['status_0'] = '';
		$this->aProperties['status_active_0'] = '';
		$this->aProperties['status_suspended_0'] = '';
		$this->aProperties['status_waiting_0'] = '';
		$this->aProperties['status_done_0'] = '';
		$this->aProperties['status_failed_0'] = '';
		//STATUS_UNDEFINED for others
		$this->aProperties['parent_0'] = '';
		$this->aProperties['class_1'] = '';
		$this->aProperties['label_1'] = '';
		$this->aProperties['start_date_1'] = '';
		$this->aProperties['end_date_1'] = '';
		$this->aProperties['additional_info1_1'] = '';
		$this->aProperties['additional_info2_1'] = '';
		$this->aProperties['percentage_1'] = '';
		$this->aProperties['parent_1'] = '';
		$this->aProperties['class_2'] = '';
		$this->aProperties['label_2'] = '';
		$this->aProperties['start_date_2'] = '';
		$this->aProperties['end_date_2'] = '';
		$this->aProperties['additional_info1_2'] = '';
		$this->aProperties['additional_info2_2'] = '';
		$this->aProperties['percentage_2'] = '';
		$this->aProperties['parent_2'] = '';
		$this->aProperties['save_allowed'] = 0;
	}

	/**
	 * @inheritdoc
	 * @throws \OQLException
	 */
	public function Render($oPage, $bEditMode = false, $aExtraParams = array())
	{
		// Prepare scopes for Gantt object
		$aScope = array(
			'title' => $this->aProperties['title'],
			'oql' => $this->aProperties['oql'],
			'depends_on' => $this->aProperties['depends_on'],
			'target_depends_on' => $this->aProperties['target_depends_on'],
			'save_allowed' => $this->aProperties['save_allowed'],
		);
		$aScope = array_merge($aScope, $this->addFieldsToScope(0));

		if ($this->sOql = $aScope['oql'] == ''
			|| $this->sLabel = $aScope['label'] == ''
			|| $this->sStartDate = $aScope['start_date'] == ''
			|| $aScope['end_date'] == '')
		{
			throw new Exception(Dict::Format('GanttDashlet/Error:ParametersMissing'));
		}
		$oView = new Gantt($aScope, $bEditMode);
		$sViewId = 'gantt_'.$this->sId.($bEditMode ? '_edit' : ''); // make a unique id (edition occuring in the same DOM)
		$oView->DisplayDashlet($oPage, $sViewId);

		if ($bEditMode)
		{
			$oPage->add('<div class="gantt-view-blocker"></div>');
		}
	}

	protected function addFieldsToScope($idx)
	{
		$aScope = array(
			'label' => $this->aProperties['label_'.$idx],
			'start_date' => $this->aProperties['start_date_'.$idx],
			'end_date' => $this->aProperties['end_date_'.$idx],
			'additional_info1' => $this->aProperties['additional_info1_'.$idx],
			'additional_info2' => $this->aProperties['additional_info2_'.$idx],
			'percentage' => $this->aProperties['percentage_'.$idx],
			'parent' => $this->aProperties['parent_'.$idx],
			'class' => $this->aProperties['class_'.$idx],
			'status' => $this->aProperties['status_'.$idx],
		);
		if ($idx==0)
		{
			$aScope = array_merge($aScope,
				array(
					'status_active' => $this->aProperties['status_active_'.$idx],
					'status_suspended' => $this->aProperties['status_suspended_'.$idx],
					'status_waiting' => $this->aProperties['status_waiting_'.$idx],
					'status_done' => $this->aProperties['status_done_'.$idx],
					'status_failed' => $this->aProperties['status_failed_'.$idx]
				));
		}
		if ($this->aProperties['parent_'.$idx])
		{
			try
			{
				$aScope['parent_fields'] = $this->addFieldsToScope($idx + 1);
			}
			catch (Exception $e)
			{
				$aScope['parent_fields'] = array();
			}
		}

		return $aScope;
	}

	/**
	 * @inheritdoc
	 * @throws \Exception
	 */
	public function GetPropertiesFields(\DesignerForm $oForm)
	{
		//title
		$oField = new DesignerTextField('title', Dict::S('UI:DashletHeaderDynamic:Prop-Title'), $this->aProperties['title']);
		$oForm->AddField($oField);

		//oql
		$oField = new DesignerLongTextField('oql', Dict::S('UI:DashletHeaderDynamic:Prop-Query'), $this->aProperties['oql']);
		$oField->SetMandatory();
		$oForm->AddField($oField);

		// Date by field: build the list of possible values (attribute codes + ...)
		$oQuery = null;
		$sClass = null;
		$aField = null;
		try
		{
			$oQuery = $this->oModelReflection->GetQuery($this->aProperties['oql']);
			$sClass = $oQuery->GetClass();
			$aLink = $this->GetOptions($sClass, false, false, true);
		}
		catch (Exception $e)
		{
			$oQuery = null;
			$sClass = null;
			$aLink = null;
		}

		//depends_on
		if ($aLink != null)
		{
			$oField = new DesignerComboField('depends_on', Dict::S('GanttDashlet/Prop:DependsOn'), $this->aProperties['depends_on']);
			$oField->SetAllowedValues($aLink);
		}
		else
		{
			$oField = new DesignerTextField('depends_on', Dict::S('GanttDashlet/Prop:DependsOn'), $this->aProperties['depends_on']);
			$oField->SetReadOnly();
		}
		$oForm->AddField($oField);

		if ($this->aProperties['depends_on'] != '')
		{
			if (MetaModel::GetAttributeDef($sClass, $this->aProperties['depends_on']) instanceof AttributeLinkedSetIndirect)
			{
				$this->aProperties['target_depends_on'] = MetaModel::GetAttributeDef($sClass,
					$this->aProperties['depends_on'])->GetExtKeyToRemote();
			}
			elseif (MetaModel::GetAttributeDef($sClass, $this->aProperties['depends_on']) instanceof AttributeLinkedSet)
			{
				$this->aProperties['target_depends_on'] = MetaModel::GetAttributeDef($sClass,
					$this->aProperties['depends_on'])->GetExtKeyToMe();
			}
		}

		$oForm->AddField(new DesignerHiddenField('target_depends_on', '', $this->aProperties['target_depends_on']));

		$idx = 0;
		while ($idx < 2)
		{
			$aDateOption = null;
			$aField = null;
			$aLinkParent = null;
			$aNumberField = null;
			try
			{
				$aDateOption = $this->GetOptions($sClass, true, false, false, false);
				$aField = $this->GetOptions($sClass, false, false, false, false);
				$aLinkParent = $this->GetOptions($sClass, false, false, false, true);
				$aNumberField = $this->GetOptions($sClass, false, true);
			}
			catch (Exception $e)
			{
				$aDateOption = null;
				$aField = null;
				$aLinkParent = null;
				$aNumberField = null;
			}

			if ($idx != 0 && $sClass != null)
			{
				$oForm->StartFieldSet(Dict::Format('GanttDashlet/Prop:ParentInformations', ($idx)));
				$oField = new DesignerTextField('class_'.$idx, '', $sClass);
				$oField->SetReadOnly();
				$oForm->AddField($oField);
			}
			else
			{
				$oField = new DesignerHiddenField('class_'.$idx, '', $sClass);
				$oForm->AddField($oField);
			}

			//label
			$oForm->AddField($this->DisplayDesignerComboField('label_'.$idx, Dict::S('GanttDashlet/Prop:name'),
				$this->aProperties['label_'.$idx], $aField, ($sClass != null), true));

			//start date
			$oForm->AddField($this->DisplayDesignerComboField('start_date_'.$idx, Dict::S('GanttDashlet/Prop:StartDate'),
				$this->aProperties['start_date_'.$idx], $aDateOption, ($sClass != null), true));

			//end date
			$oForm->AddField($this->DisplayDesignerComboField('end_date_'.$idx, Dict::S('GanttDashlet/Prop:EndDate'),
				$this->aProperties['end_date_'.$idx], $aDateOption, ($sClass != null), true));

			//percentage
			$oForm->AddField($this->DisplayDesignerComboField('percentage_'.$idx, Dict::S('GanttDashlet/Prop:Percentage'),
				$this->aProperties['percentage_'.$idx], $aNumberField, ($sClass != null), false));

			if ($idx == 0 )
			{
				//status_attr
				$sAttributeStatus=MetaModel::GetStateAttributeCode($sClass);
				if ($sAttributeStatus!='')
				{
					$aTargetStatus = $this->oModelReflection->GetAllowedValues_att($sClass, $sAttributeStatus);
					$oField = new DesignerTextField('status_'.$idx, '', $sAttributeStatus);
					$oField->SetReadOnly();
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_active_'.$idx, $sAttributeStatus. ' '.Dict::S('GanttDashlet/Prop:Status:active'),
						$this->aProperties['status_active_'.$idx]);
					$oField->MultipleSelection(true);
					$oField->SetAllowedValues($aTargetStatus);
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_suspended_'.$idx, $sAttributeStatus. ' '.Dict::S('GanttDashlet/Prop:Status:suspended'),
						$this->aProperties['status_suspended_'.$idx]);
					$oField->MultipleSelection(true);
					$oField->SetAllowedValues($aTargetStatus);
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_done_'.$idx, $sAttributeStatus. ' '.Dict::S('GanttDashlet/Prop:Status:done'),
						$this->aProperties['status_done_'.$idx]);
					$oField->MultipleSelection(true);
					$oField->SetAllowedValues($aTargetStatus);
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_waiting_'.$idx, $sAttributeStatus. ' '.Dict::S('GanttDashlet/Prop:Status:waiting'),
						$this->aProperties['status_waiting_'.$idx]);
					$oField->MultipleSelection(true);
					$oField->SetAllowedValues($aTargetStatus);
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_failed_'.$idx, $sAttributeStatus. ' '.Dict::S('GanttDashlet/Prop:Status:failed'),
						$this->aProperties['status_failed_'.$idx]);
					$oField->MultipleSelection(true);
					$oField->SetAllowedValues($aTargetStatus);
					$oForm->AddField($oField);
				}
				else
				{
					$aTargetStatus = MetaModel::EnumTransitions($sClass, $sAttributeStatus);
					$oField = new DesignerTextField('status_'.$idx, '', $sAttributeStatus);
					$oField->SetReadOnly();
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_active_'.$idx, Dict::S('GanttDashlet/Prop:Status:active'),
						$this->aProperties['status_active_'.$idx]);
					$oField->MultipleSelection(true);
						$oField->SetReadOnly();
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_suspended_'.$idx, Dict::S('GanttDashlet/Prop:Status:suspended'),
						$this->aProperties['status_suspended_'.$idx]);
					$oField->MultipleSelection(true);
						$oField->SetReadOnly();
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_done_'.$idx, Dict::S('GanttDashlet/Prop:Status:done'),
						$this->aProperties['status_done_'.$idx]);
					$oField->MultipleSelection(true);
						$oField->SetReadOnly();
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_waiting_'.$idx, Dict::S('GanttDashlet/Prop:Status:waiting'),
						$this->aProperties['status_waiting_'.$idx]);
					$oField->MultipleSelection(true);
						$oField->SetReadOnly();
					$oForm->AddField($oField);

					$oField = new DesignerComboField('status_failed_'.$idx, Dict::S('GanttDashlet/Prop:Status:failed'),
						$this->aProperties['status_failed_'.$idx]);
					$oField->MultipleSelection(true);
						$oField->SetReadOnly();
					$oForm->AddField($oField);
				}
			}

			//additional_info
			$oForm->AddField($this->DisplayDesignerComboField('additional_info1_'.$idx,
				Dict::Format('GanttDashlet/Prop:AdditionalInfoLeft'),
				$this->aProperties['additional_info1_'.$idx], $aField, ($sClass != null), false));

			//additional_info2
			$oForm->AddField($this->DisplayDesignerComboField('additional_info2_'.$idx,
				Dict::Format('GanttDashlet/Prop:AdditionalInfoRight'),
				$this->aProperties['additional_info2_'.$idx], $aField, ($sClass != null), false));

			//parent item
			$oForm->AddField($this->DisplayDesignerComboField('parent_'.$idx, Dict::S('GanttDashlet/Prop:ParentField'),
				$this->aProperties['parent_'.$idx], $aLinkParent, ($sClass != null), false));

			if ($this->aProperties['parent_'.$idx] != '')
			{
				try
				{
					$sClass = $this->oModelReflection->GetAttributeProperty($sClass, $this->aProperties['parent_'.$idx], 'targetclass');
				}
				catch (Exception $e)
				{
					$sClass = null;
				}
			}
			else
			{
				$sClass = null;
			}
			$idx++;
		}
	}

	private function DisplayDesignerComboField($sName, $sLabel, $sValue, $aAllowedValues, $bDisplay, $bMandatory)
	{
		$oField = null;
		if ($bDisplay)
		{
			$oField = new DesignerComboField($sName, $sLabel, $sValue);
			if ($aAllowedValues != null)
			{
				$oField->SetAllowedValues($aAllowedValues);
				$oField->SetDisplayed($bDisplay);
				if ($bMandatory && $bDisplay)
				{
					$oField->SetMandatory();
				}
			}
			else
			{
				$oField->SetDisplayed(false);
			}
		}
		else
		{
			$oField = new DesignerHiddenField($sName, '', $sValue);
		}

		return $oField;
	}

	public function FromParams($aParams)
	{
		parent::FromParams($aParams);
		$this->aProperties['class_0'] = $this->oModelReflection->GetQuery($aParams['oql'])->GetClass();
		$this->aProperties['target_depends_on'] = '';
		if ($this->aProperties['depends_on'] != '')
		{
			if (MetaModel::GetAttributeDef($this->aProperties['class_0'],
					$this->aProperties['depends_on']) instanceof AttributeLinkedSetIndirect)
			{
				$this->aProperties['target_depends_on'] = MetaModel::GetAttributeDef($this->aProperties['class_0'],
					$this->aProperties['depends_on'])->GetExtKeyToRemote();
			}
			elseif (MetaModel::GetAttributeDef($this->aProperties['class_0'],
					$this->aProperties['depends_on']) instanceof AttributeLinkedSet)
			{
				$this->aProperties['target_depends_on'] = MetaModel::GetAttributeDef($this->aProperties['class_0'],
					$this->aProperties['depends_on'])->GetExtKeyToMe();
			}
		}
	}

	public function Update($aValues, $aUpdatedFields)
	{
		if (in_array('oql', $aUpdatedFields))
		{
			try
			{
				$sCurrQuery = $aValues['oql'];
				$oCurrSearch = $this->oModelReflection->GetQuery($sCurrQuery);
				$sCurrClass = $oCurrSearch->GetClass();

				$sPrevQuery = $this->aProperties['oql'];
				$oPrevSearch = $this->oModelReflection->GetQuery($sPrevQuery);
				$sPrevClass = $oPrevSearch->GetClass();

				if ($sCurrClass != $sPrevClass)
				{
					$this->bFormRedrawNeeded = true;
					// wrong but not necessary - unset($aUpdatedFields['group_by']);
					$this->aProperties['depends_on'] = '';
					$this->aProperties['target_depends_on'] = '';
					$this->aProperties['label_0'] = '';
					$this->aProperties['start_date_0'] = '';
					$this->aProperties['end_date_0'] = '';
					$this->aProperties['additional_info1_0'] = '';
					$this->aProperties['additional_info2_0'] = '';
					$this->aProperties['percentage_0'] = '';
					$this->aProperties['parent_0'] = '';
					$this->aProperties['class_0'] = $sCurrClass;
					$aValues['class_0'] = $sCurrClass;
					array_push($aUpdatedFields, 'class_0');
				}
			}
			catch (Exception $e)
			{
				$this->bFormRedrawNeeded = true;
			}
		}
		if (in_array('depends_on', $aUpdatedFields))
		{
			$this->bFormRedrawNeeded = true;
			if (MetaModel::GetAttributeDef($aValues['class_0'], $aValues['depends_on']) instanceof AttributeLinkedSetIndirect)
			{
				$aValues['target_depends_on'] = MetaModel::GetAttributeDef($aValues['class_0'],
					$aValues['depends_on'])->GetExtKeyToRemote();
				$this->aProperties['target_depends_on'] = $aValues['target_depends_on'];
			}
			elseif (MetaModel::GetAttributeDef($aValues['class_0'], $aValues['depends_on']) instanceof AttributeLinkedSet)
			{
				$aValues['target_depends_on'] = MetaModel::GetAttributeDef($aValues['class_0'], $aValues['depends_on'])->GetExtKeyToMe();
				$this->aProperties['target_depends_on'] = $aValues['target_depends_on'];
			}
			$this->aProperties['target_depends_on'] = $aValues['target_depends_on'];
			array_push($aUpdatedFields, 'target_depends_on');
		}
		if (in_array('parent_0', $aUpdatedFields))
		{
			//redraw
			$this->bFormRedrawNeeded = true;
			array_push($aUpdatedFields, 'class_1');
		}

		return parent::Update($aValues, $aUpdatedFields);
	}

	/**
	 * @inheritdoc
	 */
	public static function GetInfo()
	{
		return array(
			'label' => Dict::S('GanttDashlet:Label'),
			'icon' => 'env-'.utils::GetCurrentEnvironment().'/combodo-gantt-view/asset/img/gantt-dashlet.png',
			'description' => Dict::S('GanttDashlet:Description'),
		);
	}

	/**
	 * @param \DesignerForm $oForm
	 * @param string|null $sOQL
	 */
	public function GetPropertiesFieldsFromOQL(DesignerForm $oForm, $sOQL = null)
	{
		// Default: do nothing since it's not supported
	}

	/**
	 * @param string $sOql
	 *
	 * @return array
	 */
	protected function GetOptions($sClass, $isDate = false, $isNumber = false, $isLink = false, $isLinkParent = false)
	{
		$aFields = array();
		try
		{
			foreach ($this->oModelReflection->ListAttributes($sClass) as $sAttCode => $sAttType)
			{
				// For external fields, find the real type of the target
				$sExtFieldAttCode = $sAttCode;
				$sTargetClass = $sClass;
				if ($isDate)
				{
					if (!is_a($sAttType, 'AttributeDateTime', true)
						&& !is_a($sAttType, 'AttributeDate', true))
					{
						continue;
					}
				}
				elseif ($isLink)
				{
					if (!is_a($sAttType, 'AttributeLinkedSet', true)
						&& !is_a($sAttType, 'AttributeLinkedSetIndirect', true)
						&& !is_a($sAttType, 'AttributeExternalKey', true)
						&& !is_a($sAttType, 'AttributeHierarchicalKey', true))
					{
						continue;
					}
				}
				elseif ($isLinkParent)
				{
					if (!is_a($sAttType, 'AttributeExternalKey', true)
						&& !is_a($sAttType, 'AttributeHierarchicalKey', true))
					{
						continue;
					}
				}
				else
				{
					if (is_a($sAttType, 'AttributeLinkedSet', true)
						|| is_a($sAttType, 'AttributeLinkedSetIndirect', true))
					{
						continue;
					}
				}

				while (is_a($sAttType, 'AttributeExternalField', true))
				{
					$sExtKeyAttCode = $this->oModelReflection->GetAttributeProperty($sTargetClass, $sExtFieldAttCode, 'extkey_attcode');
					$sTargetAttCode = $this->oModelReflection->GetAttributeProperty($sTargetClass, $sExtFieldAttCode, 'target_attcode');
					$sTargetClass = $this->oModelReflection->GetAttributeProperty($sTargetClass, $sExtKeyAttCode, 'targetclass');
					$aTargetAttCodes = $this->oModelReflection->ListAttributes($sTargetClass);
					$sAttType = $aTargetAttCodes[$sTargetAttCode];
					$sExtFieldAttCode = $sTargetAttCode;
				}
				if (is_a($sAttType, 'AttributeMetaEnum', true)
					|| is_a($sAttType, 'AttributeOneWayPassword', true))
				{
					continue;
				}
				if (!$isDate && !$isLink)
				{
					if (is_a($sAttType, 'AttributeFriendlyName', true)
						|| is_a($sAttType, 'AttributeDateTime', true)
						|| is_a($sAttType, 'AttributeCaseLog', true)
						|| is_a($sAttType, 'AttributeText', true)
						|| is_a($sAttType, 'AttributeLongText', true)
						|| is_a($sAttType, 'AttributeStopWatch', true)
						|| is_a($sAttType, 'AttributeHTML', true)
						|| is_a($sAttType, 'AttributeImage', true)
						|| is_a($sAttType, 'AttributeBlob', true)
						|| is_a($sAttType, 'AttributeSubItem', true)
						|| is_a($sAttType, 'AttributeDuration', true)
						|| is_a($sAttType, 'AttributeObsolescenceFlag', true)
					)
					{
						continue;
					}
				}
				$sLabel = $this->oModelReflection->GetLabel($sClass, $sAttCode);
				if (!in_array($sLabel, $aFields))
				{
					$aFields[$sAttCode] = $sLabel;
				}
			}
			asort($aFields);
		}
		catch (Exception $e)
		{
			// Fallback in case of OQL problem
		}

		return $aFields;
	}
}