//jQuery UI style "widget" for displaying a kanban
$(function()
{
	// the widget definition, where "itop" is the namespace,
	// "kanban_handler" the widget name
	$.widget( "itop.kanban_handler",
{
		// default options
		options:
		{
			labels:  'Description:',
			groupingAttribute:  'Status',
			ownership_token:'',
			groups: [],
		},
		// the constructor
		_create: function()
		{
			var me = this;
			this.element.addClass('kanban_handler');
			this._addLoader();
			this._removePlaceholder();
			me.element.find(".kb-ticket").not(".ui-widget")
				.addClass("ui-widget ui-helper-clearfix ui-corner-all")
				.find(".kb-ticket-header")
				.addClass("ui-corner-all")
				.prepend("<div class='itop_popup'><ul><li><i class=\"fas fa-caret-down\"></i></li></ul></div>");

			$(".itop_popup").one("click", function () {
				blockReloadScreen($(this));
				if ($(this).find('.kb-ticket-action').length==0)
				{
					$(this).find('ul > li').append('<ul class="kb-ticket-action "></ul>');
					var $ul = $(this).first('ul');
					$ul.popupmenu().css('display', 'block');
					$ul.append('<i class=\"ajax-spin fas fa-spinner fa-spin fa-4x\"></i>');
					var $action = $(this).find('.kb-ticket-action');
					var sId = $(this).parent().parent().data("id");
					var sClass = $(this).parent().parent().data("class");
					var oMap = { };
					oMap['ownership_token'] = me.options.ownership_token;
					$.post(GetAbsoluteUrlModulesRoot() +
					 'combodo-kanban-view/ajax.php?operation=action_list&class=' + sClass + '&id=' + sId,oMap, null,'json')
						.done(function(data)
						{
							$.each(data, function(i, elt){
								$action.append('<li data-action-id="' + i + '"><a href="' + elt.url + '">' + elt.label + '</a></li>');
							});
							$ul.find('.ajax-spin').css('display', 'none');
							$ul.find('li').popupmenu();
							$ul.find('li')[0].click();
						})
						.fail(function(data){
							$ul.find('.ajax-spin').remove();
							$action.remove();
							alert(data.responseText);
						})
						.always(function(){
							unblockReloadScreen();
						});
				}
				else
				{
					$(this).find('.kb-ticket_action').show();
				}
			});
			$(".kb-ticket-content")
				.prepend("<span class='fas fa-ellipsis-h kb-etc'></span>");

			$('.kb-etc').tooltip({
				items: ".kb-etc",
				content: function(){
					return '<div class="kb-ticket-over">'+$(this).closest('.kb-ticket').find('.kb-ticket-over').html()+'</div>';
				},
				show: "fold",
				close: function(event, ui) {
					ui.tooltip.hover(function() {
							$(this).stop(true).fadeTo(500, 1);
						},
						function() {
							$(this).fadeOut('500', function() {
								$(this).remove();
							});
						});
				}
			});
			//add drag and drop
			if (this.options.bEdit)
			{
				this._editMode();
			}
			else
			{
				this._bindEvents();
			}
		},
		// called when created, and later when changing options
		_refresh: function()
		{

		},
		// events bound via _bind are removed automatically
		// revert other modifications here
		destroy: function()
		{
			this.element.removeClass('kanban_handler');
			this._super();
		},
		// _setOptions is called with a hash of all options that are changing
		_setOptions: function()
		{
            this._superApply(arguments);
		},
		// _setOption is called for each individual option that is changing
		_setOption: function( key, value )
		{
			this._super(key, value);
		},
		_editMode: function()
		{

		},
		// Helpers
		_bindEvents: function()
		{
			$this=$(this);
			$groups=this.options.groups;
			me=this;
			//distroy event already created
			$.each(this.options.groups, function (keyG,group) {
				me.element.find("[data-status='"+group.value+"']").unbind();
			});
			//2 different comportment depending of the type of the grouping
			if (this.options.bWithLifeCycle)
			{
				$.each(this.options.groups, function (keyG,group)	{
					var transitions= '';
					$.each(group.transition, function (keyT,valueT){
						if (transitions != '')
						{
							transitions += ",";
						}
						transitions+="[data-status = '"+keyT+"']";
					});
					me.element.find("[data-status='" + group.value + "']").sortable({
						scroll: true,
						items: ".kb-ticket:not(.ui-state-disabled)",
						connectWith:transitions,
						handle: ".kb-ticket-handle",
						cancel: ".kb-ticket-toggle",
						placeholder: "kb-ticket-placeholder ui-corner-all",
						start: function( event, ui ) {
							$(".kb-group").not(transitions).not("[data-status='" + group.value + "']").wrapInner('<div' +
								' class="kb-group-disabled"></div>');
							$(ui.item).find(".kb-ticket-header p").addClass("dragging");
						},
						stop:function( event, ui ) {
							$(".kb-group > .kb-group-disabled").contents().unwrap();
							$(ui.item).find(".kb-ticket-header p").removeClass("dragging");
						},
						receive: function (event, ui) {
							blockReloadScreen($(this));
							$.blockUI({
								message: '<div class="ajax-spin fas fa-spinner fa-spin fa-5x" role="status"></div>',
								css : { cursor: 'auto',
										width: 'auto',
										top: '50%',
										left: '50%',
										border:'none',
										backgroundColor:'none'}
							});
							//get the element of the param groups witch is the target of the drag and drop
							var targetGroup = $.grep( $groups, function( gr, i ) {
								return gr.value!=ui.sender["0"].dataset.status;
							}, true );
							var sStimulus = targetGroup[0].transition[ event.target.getAttribute('data-status')];
							var sClass = ui.item["0"].dataset.class;
							var sId = ui.item["0"].dataset.id;
							var sState = ui.sender["0"].dataset.status;
							var sParent = ui.item.eq(0).parent();
							loadApplyStimulus($(ui.sender),sStimulus, sClass, sId, sState, sParent);
						}
					});
				});
			}
			else
			{
				//grouping without lifeCycle
				var intRightHandler = null;
				var intLeftHandler = null;
				var distance = 70;
				var timer = 100;
				var step = 10;

				var offset = me.element.offset();
				var offsetWidth = this.element[0].offsetWidth;

				$.each(this.options.groups, function (keyG,group)	{
					me.element.find("[data-status='" + group.value + "']").sortable({
						scroll: true,
						items: ".kb-ticket:not(.ui-state-disabled)",
						connectWith:".kb-group",
						handle: ".kb-ticket-handle",
						cancel: ".kb-ticket-toggle",
						vertical: false,
						appendTo: '.kanban-container',
						placeholder: "kb-ticket-placeholder ui-corner-all",
						start: function( event, ui ) {
							$(ui.item).find(".kb-ticket-header p").addClass("dragging");
						},
						sort: function( event, ui ) {
							var isMoving = false;
							//Left
							if((event.pageX - offset.left) <= distance)
							{
								isMoving = true;
								clearInterval(intRightHandler);
								clearInterval(intLeftHandler);
								intLeftHandler= setInterval(function(){
									me.element.closest('.dashlet-content').scrollLeft(me.element.closest('.dashlet-content').scrollLeft() - step);
								},timer);
							}

							//Right
							if(event.pageX >= (offsetWidth - distance))
							{
								isMoving = true;
								clearInterval(intRightHandler);
								clearInterval(intLeftHandler);
								intRightHandler = setInterval(function(){
									me.element.closest('.dashlet-content').scrollLeft(me.element.closest('.dashlet-content').scrollLeft() + step);
								},timer);
							}
							//No events
							if(!isMoving)
							{
								clearInterval(intRightHandler);
								clearInterval(intLeftHandler);
							}
						},
						stop:function( event, ui ) {
							clearInterval(intRightHandler);
							clearInterval(intLeftHandler);
							$(ui.item).find(".kb-ticket-header p").removeClass("dragging");
						},

						receive: function (event, ui) {
							$.blockUI({ message: '<div class="kb-loader"><div class="fa fa-fw fa-spin fa-refresh" style="font-size:' +
									' 5em;" role="status"></div></div>' , css : { cursor: 'auto', width: 'auto' } });
							var sGroupingAttribute = ui.item["0"].dataset.groupingattribute;
							var sClass = ui.item["0"].dataset.class;
							var sId = ui.item["0"].dataset.id;
							var sState = ui.item["0"].parentElement.dataset.status;
							var sParent = ui.item.eq(0).parent();
							loadChangeValue($(ui.sender),sGroupingAttribute, sClass, sId, sState, sParent);
						}
					});
					me.element.find("[data-status='" + group.value + "']").disableSelection();
				});
			}
		},
		_fetchEvents: function()
		{
		},
		_addLoader: function()
		{
			this.element.find('.kb-content .kb-view-container').append('<div class="kb-loader"><span class="fa fa-fw fa-spin fa-refresh"></span></div>');
		},
		_showLoader: function()
		{
			this.element.find('.kb-loader').show();
		},
		_hideLoader: function()
		{
            this.element.find('.kb-loader').hide();
		},
		_removePlaceholder: function()
		{
            this.element.find('.kb-placeholder').remove();
		},
	});

	loadApplyStimulus=function($sender, $stimulus, $class, $id,$state, $sParent) {
		if ($stimulus.indexOf(';') != -1)
		{
			$stimulusArray = $stimulus.split(';');
			StimulusOptions = "";
			$.each( $stimulusArray, function( key, value ) {
				StimulusOptions+="<option value='" + value + "'>"+value+"</option>";
			});
			$("#pop_apply_change").html('<select id="myStimulus">'+StimulusOptions+'</select>' );

			$("#pop_apply_change").dialog({
				autoOpen: false,
				height: 200,
				width: 350,
				maxHeight: $(window).height()-50,
				modal: true,
				buttons: {
					"Select": function()
					{
						loadApplyOneStimulus($("#pop_apply_change").data('uiSender'), $('#myStimulus option:selected').val(), $class, $id,$state)
					},
					"Cancel": function()
					{
						$("#pop_apply_change").dialog("close");
					}
				},
				close: function ()
				{
					$("[data-status='" + $state + "']").sortable('cancel');
					$("#pop_apply_change").html('Loading...');
					$("#pop_apply_change").dialog("close");
					unblockReloadScreen();
				}
			});
			$('#pop_apply_change').dialog('open');
			$.unblockUI();			
		}
		else
		{
			loadApplyOneStimulus($sender, $stimulus, $class, $id,$state, $sParent);
		}
	};
	loadApplyOneStimulus=function ($sender, $stimulus, $class, $id,$state, $sParent)
	{
		var urlToApply = GetAbsoluteUrlModulesRoot() + "combodo-kanban-view/ajax.php?operation=stimulus";
		var oMap = {};
		oMap['stimulus'] = $stimulus;
		oMap['class'] = $class;
		oMap['id'] = $id;
		oMap['state'] = $state;
		//try to apply stimulus directly
		$.post(urlToApply, oMap)
			.done(function (data) {
				if (data.sSeverity != 'ok')
				{
					//additional information are necessary to execute the transition -> open a popup to ask this informations
					$("#pop_apply_change").dialog({
						autoOpen: false,
						height: 200,
						width: 350,
						maxHeight: $(window).height()-50,
						modal: true,
						close: function () {
							if ($("#click_apply_stimulus").val() != "1")
							{
								$("[data-status='" + $state+ "']").sortable('cancel');
								$("#pop_apply_change").html('Loading...');
							}
							else
							{
								//update amount of tickets
								$sender.eq(0).find(".CountTicket").html(parseInt($sender.eq(0).find(".CountTicket").html())-1);
								$sParent.find(".CountTicket").first().html( parseInt($sParent.find(".CountTicket").html())+1);
							}
							unblockReloadScreen();
						}
					});
					$("#pop_apply_change").html(data);
					$.unblockUI();
					$('#pop_apply_change').dialog('open');
				}
				else
				{
					//transition is done  (not additional information are necessary to execute the transition) 
					//update amount of tickets
					$sender.eq(0).find(".CountTicket").html( parseInt($sender.eq(0).find(".CountTicket").html())-1);
					$sParent.find(".CountTicket").first().html( parseInt($sParent.find(".CountTicket").html())+1);
					$.unblockUI();
					unblockReloadScreen();
					$('#pop_apply_change').dialog( "close" );
				}

			})
			.fail(function(data){
				alert(data.responseText);
				$.unblockUI();
				unblockReloadScreen();
			});
	};
	loadChangeValue=function($sender, $groupingAttribute, $class, $id,$state, $sParent)
	{
		var urlToApply = GetAbsoluteUrlModulesRoot() + "combodo-kanban-view/ajax.php?operation=change_attribute";
		var oMap = {};
		oMap['groupingAttribute'] = $groupingAttribute;
		oMap['class'] = $class;
		oMap['id'] = $id;
		oMap['state'] = $state;
		//try to apply stimulus directly
		$.post(urlToApply,	oMap)
			.done(function (data) {
				if (data.sSeverity != 'ok')
				{
					$("#pop_apply_change").html('<div class="message message_'+data.sSeverity+'">'+data.sMessage+'</div>');
					$("#pop_apply_change").dialog({
						autoOpen: false,
						height: 200,
						width: 350,
						maxHeight: $(window).height()-50,
						modal: true,
						close: function () {
							$sender.sortable('cancel');
							$("#pop_apply_change").dialog("close");
							unblockReloadScreen();
						}
					});
					$('#pop_apply_change').dialog('open');
				}
				else
				{
					//update amount of tickets
					$sender.eq(0).find(".CountTicket").html( parseInt($sender.eq(0).find(".CountTicket").html())-1);
					$sParent.find(".CountTicket").html( parseInt($sParent.find(".CountTicket").html())+1);
					unblockReloadScreen();
				}
			})
			.fail(function(data){
				alert(data.responseText);
				unblockReloadScreen();
			})
			.always(function(){
				$.unblockUI();
			});
	};
	ajaxSaveStimulus=function(sFormId,sOperation,  sId, func)
	{
		if (CheckFields(sFormId, true))
		{
			var oMap = ReadFormParams('apply_stimulus');
			$('#'+sFormId+' :input').each( function() {
				if ($(this).parent().is(':visible'))
				{
					var sName = $(this).attr('name');
					if (sName && sName != '')
					{
						if (this.type == 'checkbox')
						{
							oMap[sName] = $(this).prop('checked');
						}
						else
						{
							oMap[sName] = $(this).val();
						}
					}
				}
			});
			$.post(GetAbsoluteUrlModulesRoot() + 'combodo-kanban-view/ajax.php?operation='+sOperation,
				oMap)
				.done(function(data){
					if (typeof data.sSeverity === "undefined")
					{
						$("#click_apply_stimulus").val('0');
						$( "#apply_stimulus_error" ).removeClass();
						$( "#apply_stimulus_error" ).addClass('header_message');
						$( "#apply_stimulus_error" ).addClass('message_error');
						$( "#apply_stimulus_error" ).append(data);
						$( "#apply_stimulus_error" ).show();
					}
					else
					{
						if (data.sSeverity=='ok')
						{
							$("#click_apply_stimulus").val('1');
							$( "#apply_stimulus_error" ).hide();
							$( '#pop_apply_change' ).dialog('close');
							$( "#pop_apply_change" ).html('Loading..');
						}
						else
						{
							$("#click_apply_stimulus").val('0');
							$( "#apply_stimulus_error" ).append( data.message );
							$( "#apply_stimulus_error" ).addClass('header_message');
							$( "#apply_stimulus_error" ).addClass('message_'+data.severity);
							$( "#apply_stimulus_error" ).show();
						}
					}
				})
				.fail(function(data){
					alert(data.responseText);
				})
				.always(function(){
					unblockReloadScreen();
					$.unblockUI();
				});
		}
		return false; // do NOT submit the form
	};
	blockReloadScreen=function (elt) {
		elt.append('<div id="blockReloadScreen" class="ui-dialog" style="visibility: hidden;"></div>');
	};
	unblockReloadScreen=function(){
		$("#blockReloadScreen").remove();
	};
});