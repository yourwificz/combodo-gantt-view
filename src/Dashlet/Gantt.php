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

use Combodo\iTop\Application\TwigBase\Twig\TwigHelper;

/**
 * Display of a named gantt into any WebPage
 */
class Gantt
{
	const MODULE_CODE = 'combodo-gantt-view';

	const DEFAULT_TITLE = '';

	protected static $bIsKBLoaderLoaded = false;
	protected $sTitle;
	protected $aExtraParams;
	protected $sOql;
	protected $sLabel;
	protected $sStartDate;
	protected $sEndDate;
	protected $sPercentage;
	protected $sDependsOn;
	protected $sAdditionalInformation1;
	protected $sAdditionalInformation2;
	protected $bEditMode;
	protected $sParent;
	protected $aParentFields;
	protected $aScope;


	/**
	 * gantt constructor.
	 *
	 * @param array $aScope
	 * @param string $sTitle
	 * @param string $sMode
	 * @param string $sTimeframe
	 */
	public function __construct($aScope,  $bEditMode = false)
	{
		$this->sTitle = $aScope['title'];
		$this->bEditMode = $bEditMode;
		$this->sOql = $aScope['oql'];
		$this->sDependsOn = $aScope['depends_on'];
		$this->sLabel = $aScope['label'];
		$this->sStartDate = $aScope['start_date'];
		$this->sEndDate = $aScope['end_date'];
		$this->sPercentage = $aScope['percentage'];
		$this->sAdditionalInformation1 = $aScope['additional_info1'];
		$this->sAdditionalInformation2 = $aScope['additional_info2'];
		$this->sParent = $aScope['parent'];
		if ($this->sParent !='')
		{
			$this->aParentFields =new ParentFields($aScope['parent_fields']);
		}
		$this->aScope=$aScope;
	}

	public function GetGanttValues()
	{
		$aDescription = array();
		$aLinkedTable = array();

		$oQuery = DBSearch::FromOQL($this->sOql);
		$aFields = array($this->sLabel, $this->sStartDate, $this->sEndDate, $this->sDependsOn, 'id');
		if ($this->sPercentage != null && $this->sPercentage != '')
		{
			array_push($aFields, $this->sPercentage);
		}
		if ($this->sAdditionalInformation1 != '')
		{
			array_push($aFields, $this->sAdditionalInformation1);
		}
		if ($this->sAdditionalInformation2 != '')
		{
			array_push($aFields, $this->sAdditionalInformation2);
		}
		if ($this->sParent != '')
		{
			array_push($aFields, $this->sParent);
		}

		$oResultSql = new DBObjectSet($oQuery);
		$oResultSql->OptimizeColumnLoad($aFields);
		$sClass = $oResultSql->GetClass();
		$i = 0;
		$aLevelParent1 = array();
		$aLevelParent2 = array();
		while ($oRow = $oResultSql->Fetch())
		{
			$Level = 0;
			if ( $this->sParent != '' && $oRow->Get($this->sParent) != null)
			{
				if (!array_key_exists($oRow->Get($this->sParent), $aLevelParent1))
				{
					$aFieldsParent1 = $this->aParentFields;
					$oObj = MetaModel::GetObject($aFieldsParent1->sClass, $oRow->Get($this->sParent), false /* MustBeFound */);
					if ($oObj != null)
					{
						if ($aFieldsParent1->sParent!='' && $oObj->Get($aFieldsParent1->sParent) != '' && !array_key_exists($oObj->Get($aFieldsParent1->sParent), $aLevelParent2))
						{
							$aFieldsParent2 = $aFieldsParent1->aParentFields;
							$oObj = MetaModel::GetObject($aFieldsParent2->sClass, $oRow->Get($aFieldsParent1->sParent),
								false /* MustBeFound */);
							if ($oObj != null)
							{
								$aDescription[$i] = $this->createRow($oObj, $aFieldsParent2->sClass, $aFieldsParent2, $Level, true);
								$i++;
								$aLevelParent2[$oRow->Get($this->sParent)] = $Level;
								$Level++;
							}
						}
						$aDescription[$i] = $this->createRow($oObj, $aFieldsParent1->sClass, $aFieldsParent1, $Level, true);
						$i++;
						$aLevelParent1[$oRow->Get($this->sParent)] = $Level;
						$Level++;
					}
				}
				else
				{
					$Level = $aLevelParent1[$oRow->Get($this->sParent)] + 1;
				}
			}

			$aDescription[$i] = $this->createRow($oRow, $sClass, $this, $Level);
			$aLinkedTable[$oRow->Get("id")] = $i;
			$i++;
		}

		return $this->renameLink($aDescription, $aLinkedTable);
	}

	private function createRow($oRow, $sClass, $aFields, $iLevel, $hasChild=false)
	{
		$aRow = array();
		$aRow['id'] = $sClass.'_'.$oRow->GetKey();
		$aRow['name'] = $oRow->Get($aFields->sLabel);
		if ($aFields->sPercentage != null && $aFields->sPercentage != '')
		{
			$aRow['progress'] = $oRow->Get($aFields->sPercentage);
		}
		else
		{
			$aRow['progress'] = 0;
		}
		$aRow['progressByWorklog'] = true;
		$aRow['relevance'] = 0;
		$aRow['type'] = "";
		$aRow['typeId'] = "";
		$aRow['description'] = "";
		$aRow['code'] = "";
		$aRow['level'] = $iLevel;
		$aRow['status'] = "STATUS_ACTIVE";
		if ($hasChild == false)
		{
			if (count($oRow->Get($aFields->sDependsOn)) == 0)
			{
				$aRow['dependson'] = [];
			}
			else
			{
				$aRow['dependson'] = $oRow->Get($aFields->sDependsOn)->GetColumnAsArray('id');
			}
		}
		else{
			$aRow['dependson'] = [];
		}
		$aRow['canWrite'] = true;
		$format = "Y-m-d H:i:s";

		$aRow['start'] = date_format(date_create_from_format($format, $oRow->Get($aFields->sStartDate)), 'U') * 1000;

		if ($oRow->Get($aFields->sEndDate) != null)
		{
			$aRow['end'] = date_format(date_create_from_format($format, $oRow->Get($aFields->sEndDate)), 'U') * 1000;
		}
		else
		{
			//add one year to the start_date
			$aRow['end'] = (date_format(date_create_from_format($format, $oRow->Get($aFields->sStartDate)), 'U') + 33072000) * 1000;
		}
		if ($this->sAdditionalInformation1 != '')
		{
			$aRow['info1'] =  $oRow->Get($aFields->sAdditionalInformation1);
		}
		if ($this->sAdditionalInformation2 != '')
		{
			$aRow['info2'] =  $oRow->Get($aFields->sAdditionalInformation2);
		}
		$aRow['duration'] = 1;
		$aRow['collapsed'] = true;
		$aRow['assigs'] = [];
		$aRow['hasChild'] = $hasChild;

		return $aRow;
	}

	/*function moveKeyAfter($arr, $find, $move) {
		if (!isset($arr[$find], $arr[$move])) {
			return $arr;
		}

		$elem = [$move=>$arr[$move]];  // cache the element to be moved
		$start = array_splice($arr, 0, array_search($find, array_keys($arr)));
		unset($start[$move]);  // only important if $move is in $start
		return $start + $elem + $arr;
	}


	private function orderTask($aDescription)
	{
		$aOrderedTasks=array();
		foreach(array_keys( $aDescription) as $key)
		{
			if($aDescription[$key])
			{

			}
		}
		return $aDescription;
	}*/
	private function renameLink($aDescription, $aLinkedTable)
	{
		foreach ($aDescription as &$aRow)
		{
			if (sizeof($aRow['dependson']) > 0)
			{
				$newLink = array();
				foreach ($aRow['dependson'] as $sIdRow)
				{
					array_push($newLink, $aLinkedTable[$sIdRow]+1);
				}
				$aRow['depends'] = implode(",", $newLink);
			}
			else
			{
				$aRow['depends'] = "";
			}
		}

		return $aDescription;
	}

	public function GetGanttDescription()
	{
		$oQuery = DBSearch::FromOQL($this->sOql);
		$sClass = $oQuery->GetClassAlias();
		$oAttLabelDef = MetaModel::GetAttributeDef($sClass, $this->sLabel);
		$oAttLabelStartDate = MetaModel::GetAttributeDef($sClass, $this->sStartDate);
		$oAttLabelEndDate = MetaModel::GetAttributeDef($sClass, $this->sEndDate);
		$oAttAdditionalInformation1Def = null;
		$oAttAdditionalInformation2Def = null;
		if ($this->sAdditionalInformation1 != '')
		{
			$oAttAdditionalInformation1Def = MetaModel::GetAttributeDef($sClass, $this->sAdditionalInformation1);
		}
		if ($this->sAdditionalInformation2 != '')
		{
			$oAttAdditionalInformation2Def = MetaModel::GetAttributeDef($sClass, $this->sAdditionalInformation2);
		}
		$aHandlerOptions = array(
			'attr_label' => $oAttLabelDef->GetLabel(),
			'attr_start_date' => $oAttLabelStartDate->GetLabel(),
			'attr_end_date' => $oAttLabelEndDate->GetLabel(),
			'attr_add_info1' => ($oAttAdditionalInformation1Def!=null)?$oAttAdditionalInformation1Def->GetLabel():'',
			'attr_add_info2' => ($oAttAdditionalInformation2Def!=null)?$oAttAdditionalInformation2Def->GetLabel():'',
		);

		return $aHandlerOptions;
	}

	/**
	 * Inserts the gantt (as a div) in the dashboard
	 *
	 * @param \WebPage $oP The page used for the display
	 * @param string $sId
	 *
	 * @throws \Exception
	 * @throws \OQLException
	 */
	public function DisplayDashlet(\WebPage $oP, $sId = 'gantt')
	{
		//render
		$aData = array('sId' => $sId, 'sTitle' => $this->sTitle, 'bEditMode'=>$this->bEditMode,'sScope'=>json_encode($this->aScope),'aDescription'=>$this->GetGanttDescription());
		$oP->add_twig_template(MODULESROOT.'combodo-gantt-view/view', 'GanttViewerDashlet', $aData);
		//TwigHelper::RenderIntoPage($oP, MODULESROOT.'combodo-gantt-view/view', 'GanttViewer', $aData);
	}

	/**
	 * Inserts the gantt (as a div) at the current position into the given page
	 *
	 * @param \WebPage $oP The page used for the display
	 * @param string $sId
	 *
	 * @throws \Exception
	 * @throws \OQLException
	 */
	public function Display(\WebPage $oP, $sId = 'gantt')
	{
		//CSS
		$oP->add_linked_stylesheet(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/dateField/jquery.dateField.css');
		$oP->add_linked_stylesheet(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/platform.css');
		$oP->add_linked_stylesheet(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/gantt.css');
		$oP->add_linked_stylesheet(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/valueSlider/mb.slider.css');
		//JS
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/jquery.livequery.1.1.1.min.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/jquery.timers.js?v=$sModuleVersion');

		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/utilities.js?v=$sModuleVersion');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/forms.js?v=$sModuleVersion');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/date.js?v=$sModuleVersion');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/dialogs.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/layout.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/i18nJs.js');

		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/dateField/jquery.dateField.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/JST/jquery.JST.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/valueSlider/jquery.mb.slider.js');

		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/svg/jquery.svg.min.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/libs/jquery/svg/jquery.svgdom.1.8.js');

		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/ganttUtilities.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/ganttTask.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/ganttDrawerSVG.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/ganttZoom.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/ganttGridEditor.js');
		$oP->add_linked_script(utils::GetAbsoluteUrlModulesRoot().'combodo-gantt-view/asset/lib/jQueryGantt/ganttMaster.js');
		//render
		$aData = array('sId' => $sId, 'sTitle' => $this->sTitle, 'bEditMode'=>$this->bEditMode,'sScope'=>json_encode($this->aScope),'aDescription'=>$this->GetGanttDescription());
		TwigHelper::RenderIntoPage($oP, MODULESROOT.'combodo-gantt-view/view', 'GanttViewer', $aData);
	}

	/**
	 * @return array
	 */
	private function getArrayScopeDate($oQuery, $oSet, $sDate, $oDateingExp, $aDateingValues, $sDateingAttr, $aStimuli, $bWithLifeCycle)
	{
		$sHtmlLabel = $oDateingExp->MakeValueLabel($oQuery, $sDate, '');
		$iCount = 0;
		foreach ($aDateingValues as $aDateingValue)
		{
			if ($sDate == $aDateingValue['grouped_by_1'])
			{
				$iCount = $aDateingValue['_itop_count_'];
			}
		}

		return array(
			'value' => $sDate,
			'label' => strip_tags($sHtmlLabel),
			'count' => $iCount,
			'is_fake' => false,
		);
	}
}

class ParentFields
{
	public $sClass;
	public $sLabel;
	public $sStartDate;
	public $sEndDate;
	public $sPercentage;
	public $sAdditionalInformation1;
	public $sAdditionalInformation2;
	public $sParent;
	public $aParentFields;

	/**
	 * DependsOnObject constructor.
	 *
	 * @param array $aScope
	 * @param int $nb
	 */
	public function __construct($aScope)
	{
		$this->sLabel = $aScope['label'];
		$this->sStartDate = $aScope['start_date'];
		$this->sEndDate = $aScope['end_date'];
		$this->sPercentage = $aScope['percentage'];
		$this->sAdditionalInformation1 = $aScope['additional_info1'];
		$this->sAdditionalInformation2 = $aScope['additional_info2'];
		$this->sParent = $aScope['parent'];
		$this->sClass = $aScope['class'];
		if ($aScope['parent'] != null && $aScope['parent'] != '')
		{
			$this->aParentFields = new ParentFields($aScope['parent_fields']);
		}
	}


}