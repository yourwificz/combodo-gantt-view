<?php
/**
 *  @copyright   Copyright (C) 2010-2019 Combodo SARL
 *  @license     http://opensource.org/licenses/AGPL-3.0
 */

// Load current environment
if (file_exists(__DIR__.'/../../approot.inc.php'))
{
	require_once __DIR__.'/../../approot.inc.php';   // When in env-xxxx folder
}
else
{
	require_once __DIR__.'/../../../approot.inc.php';   // When in datamodels/x.x folder
}

require_once APPROOT.'application/application.inc.php';
require_once APPROOT.'application/itopwebpage.class.inc.php';
require_once APPROOT.'application/startup.inc.php';
require_once APPROOT.'application/loginwebpage.class.inc.php';

$oAjaxGanttController = new AjaxGanttViewController(MODULESROOT.'combodo-gantt-view/view', 'combodo-gantt-view');
$oAjaxGanttController->HandleOperation();