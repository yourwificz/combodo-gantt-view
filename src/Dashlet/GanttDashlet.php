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
			$this->aProperties['title'] = Dict::S('GanttDashlet:Prop-Title:Default');
			$this->aProperties['oql'] = 'SELECT UserRequest';
			$this->aProperties['depends_on'] = 'related_request_list';
			$this->aProperties['label_0'] = 'title';
			$this->aProperties['start_date_0'] = 'start_date';
			$this->aProperties['end_date_0'] = 'end_date';
			$this->aProperties['additional_info1_0'] = '';
			$this->aProperties['additional_info2_0'] = '';
			$this->aProperties['percentage_0'] = '';
			$this->aProperties['parent_0'] = '';
		}
		else
		{
			$this->aProperties['title'] = Dict::S('GanttDashlet:Prop-Title:Default2');
			$this->aProperties['oql'] = 'SELECT Contact ';
			$this->aProperties['depends_on'] = '';
			$this->aProperties['label_0'] = '';
			$this->aProperties['start_date_0'] = '';
			$this->aProperties['end_date_0'] = '';
			$this->aProperties['additional_info1_0'] = '';
			$this->aProperties['additional_info2_0'] = '';
			$this->aProperties['percentage_0'] = '';
			$this->aProperties['parent_0'] = '';
		}
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

		);
		$aScope = array_merge($aScope,$this->addFieldsToScope(0));

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
			'additional_info2'=> $this->aProperties['additional_info2_'.$idx],
			'percentage'=> $this->aProperties['percentage_'.$idx],
			'parent'=> $this->aProperties['parent_'.$idx],
		);
		if ($this->aProperties['parent_'.$idx])
		{
			$aScope['parent_fields']=addFieldsToScope($idx+1);
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
			$aLink = $this->GetOptions($sClass, false,false,  true);
		}
		catch (Exception $e)
		{
			$oQuery = null;
			$sClass = null;
			$aLink= null;
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

		$idx=0;
		while ($sClass != null)
		{
			$aDateOption = null;
			$aField = null;
			try
			{
				$aDateOption = $this->GetOptions($sClass, true,false,  false, false);
				$aField = $this->GetOptions($sClass, false, false, false, false);
				$aLinkParent = $this->GetOptions($sClass, false,false,  false, false, true);
			}
			catch (Exception $e)
			{
				$aDateOption = null;
				$aField = null;
				$aLinkParent = null;
			}
			if ($idx!=0)
			{
				$oField = new DesignerTextField(Dict::S('GanttDashlet/Prop:ParentInformations'), Dict::S('GanttDashlet/Prop:ParentInformations'), '');
				$oField->SetReadOnly();
				$oForm->AddField($oField);
			}
			//label
			if ($aField != null)
			{
				$oField = new DesignerComboField('label', Dict::S('Class:Query/Attribute:name'), $this->aProperties['label_'.$idx]);
				$oField->SetMandatory();
				$oField->SetAllowedValues($aField);
			}
			else
			{
				$oField = new DesignerTextField('label', Dict::S('Class:Query/Attribute:name'), $this->aProperties['label_'.$idx]);
				$oField->SetReadOnly();
			}
			$oForm->AddField($oField);

			//start date
			if ($aDateOption != null)
			{
				$oField = new DesignerComboField('start_date', Dict::S('GanttDashlet/Prop:StartDate'), $this->aProperties['start_date_'.$idx]);
				$oField->SetMandatory();
				$oField->SetAllowedValues($aDateOption);
			}
			else
			{
				$oField = new DesignerTextField('start_date', Dict::S('GanttDashlet/Prop:StartDate'), $this->aProperties['start_date_'.$idx]);
				$oField->SetReadOnly();
			}
			$oForm->AddField($oField);

			//end date
			if ($aDateOption != null)
			{
				$oField = new DesignerComboField('end_date', Dict::S('GanttDashlet/Prop:EndDate'), $this->aProperties['end_date_'.$idx]);
				$oField->SetMandatory();
				$oField->SetAllowedValues($aDateOption);
			}
			else
			{
				$oField = new DesignerTextField('end_date', Dict::S('GanttDashlet/Prop:EndDate'), $this->aProperties['end_date_'.$idx]);
				$oField->SetReadOnly();
			}
			$oForm->AddField($oField);

			//percentage
			if ($aField != null)
			{
				$oField = new DesignerComboField('pourcentage', Dict::S('GanttDashlet/Prop:Percentage'), $this->aProperties['percentage_'.$idx]);
				$aNumberField = $this->GetOptions($sClass, false,true);
				$oField->SetAllowedValues($aNumberField);
			}
			else
			{
				$oField = new DesignerTextField('pourcentage', Dict::S('GanttDashlet/Prop:Percentage'), $this->aProperties['percentage_'.$idx]);
				$oField->SetReadOnly();
			}
			$oForm->AddField($oField);
			//additional_info
			if ($aField != null)
			{
				$oField = new DesignerComboField('additional_info1', Dict::Format('GanttDashlet/Prop:AdditionalInfoLeft'),
					$this->aProperties['additional_info1_'.$idx]);
				$oField->SetAllowedValues($aField);
			}
			else
			{
				$oField = new DesignerTextField('additional_info1', Dict::Format('GanttDashlet/Prop:AdditionalInfoLeft'),
					$this->aProperties['additional_info1_'.$idx]);
				$oField->SetReadOnly();
			}
			$oForm->AddField($oField);

			//additional_info2
			if ($aField != null)
			{
				$oField = new DesignerComboField('additional_info2', Dict::Format('GanttDashlet/Prop:AdditionalInfoRight'),
					$this->aProperties['additional_info2_'.$idx]);
				$oField->SetAllowedValues($aField);
			}
			else
			{
				$oField = new DesignerTextField('additional_info2', Dict::Format('GanttDashlet/Prop:AdditionalInfoRight'),
					$this->aProperties['additional_info2_'.$idx]);
				$oField->SetReadOnly();
			}
			$oForm->AddField($oField);

			//parent item
			if ($aLinkParent != null)
			{
				$oField = new DesignerComboField('parent', Dict::S('GanttDashlet/Prop:ParentField'), $this->aProperties['parent_'.$idx]);
				$oField->SetAllowedValues($aLinkParent);
			}
			else
			{
				$oField = new DesignerTextField('parent', Dict::S('GanttDashlet/Prop:ParentField'), $this->aProperties['parent_'.$idx]);
				$oField->SetReadOnly();
			}
			$oForm->AddField($oField);
			if($this->aProperties['parent_'.$idx]!='')
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
					//$this->aProperties['group_by'] = '';
					//$this->aProperties['grouping_attr'] = '';
				}
			}
			catch (Exception $e)
			{
				$this->bFormRedrawNeeded = true;
			}
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
				elseif($isLinkParent)
				{
					if ( !is_a($sAttType, 'AttributeExternalKey', true)
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