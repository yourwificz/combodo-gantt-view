<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-gantt-view/0.1.0',
	array(
		// Identification
		//
		'label' => 'Gantt',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(),
		'mandatory' => false,
		'visible' => true,

		// Components
		//
		'datamodel' => array(
			'src/Dashlet/Gantt.php',
			'src/Dashlet/GanttParentFields.php',
			'src/Dashlet/GanttDashlet.php',
			'src/Controller/AbstractGanttViewController.php',
			'src/Controller/AjaxGanttViewController.php',
			'src/Controller/GanttViewController.php',
			'src/Hook/GanttUiExtension.php',
		),
		'webservice' => array(),
		'data.struct' => array(// add your 'structure' definition XML files here,
		),
		'data.sample' => array(// add your sample data XML files here,
		),

		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
			'default_colors' => array(
				'backgroundcolor' => '#159119',
				'color' => '#fff',
			),
			'classes' => array(
				'UserRequest' => array(
					'default_colors' => array(
						'backgroundcolor' => '#159119',
						'color' => '#fff',
					),
					'name' => 'title',
					'start_date' => 'start_date',
					'end_date' => 'close_date',
					'completion' => '',
					'depends_on' => 'parent_request_id',
					'colored_field' => 'status',
					'values' => array(
						'new' => array('backgroundcolor' => '#1591FF', 'color' => '#fff'),
						'escalated_tto' => array('backgroundcolor' => '#FF9F33', 'color' => '#fff'),
						'escalated_ttr' => array('backgroundcolor' => '#FF9F88', 'color' => '#fff'),
						'assigned' => array('backgroundcolor' => '#159119', 'color' => '#fff'),
						'waiting_for_approval' => array('backgroundcolor' => '#4499F9', 'color' => '#fff'),
						'approved' => array('backgroundcolor' => '#159119', 'color' => '#fff'),
						'rejected' => array('backgroundcolor' => '#FF9F33', 'color' => '#fff'),
						'pending' => array('backgroundcolor' => '#FF9F44', 'color' => '#fff'),
						'dispatched' => array('backgroundcolor' => '#FF9F55', 'color' => '#fff'),
						'redispatched' => array('backgroundcolor' => '#FF9F66', 'color' => '#fff'),
						'closed' => array('backgroundcolor' => '#159180', 'color' => '#fff'),
						'resolved' => array('backgroundcolor' => '#939325', 'color' => '#fff'),
					),
				),
			),
		),
	)
);

