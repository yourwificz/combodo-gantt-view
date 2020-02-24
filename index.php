<?php
/**
 *  @copyright   Copyright (C) 2010-2019 Combodo SARL
 *  @license     http://opensource.org/licenses/AGPL-3.0
 */

require_once '../approot.inc.php';
require_once APPROOT.'application/application.inc.php';
require_once APPROOT.'application/itopwebpage.class.inc.php';
require_once APPROOT.'application/startup.inc.php';
require_once APPROOT.'application/loginwebpage.class.inc.php';

$oGanttController = new GanttViewController(MODULESROOT.'combodo-gantt-view/view', 'combodo-gantt-view');
$oGanttController->SetDefaultOperation('GanttViewer');
$oGanttController->HandleOperation();