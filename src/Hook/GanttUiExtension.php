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
 * Class GanttUIextension
 *
 * Used to loaded resources necessary for Gantt display and edition (dashboard editor)
 */
class GanttUiExtension implements iPageUIExtension
{

    /**
     * @inheritdoc
     */
    public function GetNorthPaneHtml(\iTopWebPage $oPage)
    {
    	// SCSS files can't be loaded asynchroniously before of a bug in the output() method prior to iTop 2.6
	    $oPage->add_saas('env-'.utils::GetCurrentEnvironment().'/'.Gantt::MODULE_CODE.'/asset/css/style.scss');

    }

    /**
     * @inheritdoc
     */
    public function GetSouthPaneHtml(\iTopWebPage $oPage)
    {
        // Do nothing.
    }

    /**
     * @inheritdoc
     */
    public function GetBannerHtml(\iTopWebPage $oPage)
    {
        // Do nothing.
	}

}
