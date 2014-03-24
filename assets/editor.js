(function($) {
	var d = document;

	var query_start = document.URL.indexOf("?");
	//var page_url = (query_start > -1) ? document.URL.slice(0, query_start) : document.URL;
		
	var body,
		notifier,
		$breadcrumbs_filename,
		contents,
		actions;

	var in_workspace;
	var new_file,
		document_modified = false;

	var highlighter

	var $list_files,
		$tmpl_files,
		$div_actions,
		$tmpl_actions;

	var page_url,
		workspace_url,
		editor_url;
	var $name_field,
		filename;
	var form_action = "save";

	var text_area;
		
	var last_key_code;
	var gutter_width = 34;
	var x_margin = 3,
		y_margin = 2;

	/*
	 * Set editor up..
	 */

	$().ready(function() {
		if(window.getSelection() == undefined) return;
		body = $('body');
		$breadcrumbs_filename = $('#breadcrumbs h2');
		notifier = $('#header div.notifier');
		contents = $('#contents');
		actions = $('#contents div.actions');

		$list_files = $('#files');
		$tmpl_files = $('#tmpl-files');
		$div_actions = $('#contents div.actions:first');
		$tmpl_actions = $('#tmpl-actions');

		page_url = $('#contents form').attr('action');
		workspace_url = $('#contents form').attr('data-workspace-url');
		editor_url = $('#contents form').attr('data-editor-url');
		$name_field = $(contents).find('input[name="fields[name]"]:first');
		new_file = ($name_field.val() == '');

		$.getJSON(
			page_url + "?ajax",
			function(data) {
				$list_files.append($tmpl_files.tmpl(data));
			}
		);
		$tmpl_actions.tmpl({'new_file': new_file}).appendTo($div_actions);

		in_workspace = $(body).is('#extension-workspace_manager-view');

		text_area = $('body').find('#text-area')[0];
		$(text_area)
			.scrollTop(0)
			.keydown(function(event) {
				var key = event.which;
				last_key_code = key;

				// Allow tab insertion
				if(key == 9 && in_workspace) {
					event.preventDefault();

					var start = text_area.selectionStart,
						end = text_area.selectionEnd,
						position = text_area.scrollTop;
					// Add tab
					text_area.value = text_area.value.substring(0, start) + "\t" + text_area.value.substring(end, text_area.value.length);
					text_area.selectionStart = start + 1;
					text_area.selectionEnd = start + 1;

					// Restore scroll position
					text_area.scrollTop = position;
				}
				else if(event.metaKey || event.ctrlKey && key == 83) {
					event.preventDefault();
					$('input[name="action[save]"]').trigger('click');
				}
			});
/*			if(([8, 9, 13, 32].indexOf(key) != -1) || (key >= 48 && key <= 90) || (key >= 163 && key <= 222)){
				//if(!$(body).hasClass('unsaved-changes')) $(body).addClass('unsaved-changes');
				/*if(!document_modified) {
					document_modified = true;
					breadcrumbs_filename.html(breadcrumbs_filename.html() + ' <small>↑</small>');
				}*/

		$('#contents div.actions').click(function(event){
			switch(event.target.name){
				case 'action[save]':
					form_action = "save";
					break;
				case 'action[delete]':
					form_action = "delete";
					break;
			}
		});

		$('#contents form').on('submit', function(event){
			var self = this;
			event.preventDefault();
			switch(form_action){
				case 'save':
					filename = $('#contents input[name="fields[name]"]:first').val();
					if(!filename) return;
					$('#saving-popup').show();
					$.ajax({
						'type': 'POST',
						'url': page_url,
						'data': $(self).serialize() + '&action=save&ajax=1',
						'dataType': 'json',
						'error': function(xhr, msg, error){
							$('#saving-popup').hide();
							alert(error);
						},
						'success': function(data){
							$('#saving-popup').hide();
							$breadcrumbs_filename.text(filename);
							page_url = editor_url + data.filename_encoded + '/';
							history.replaceState({'a': 'b'}, '', page_url);
							$list_files.empty();
							$tmpl_files.tmpl(data).appendTo($list_files);
							if(new_file){
								new_file = false;
								$div_actions.empty();
								$tmpl_actions.tmpl({'new_file': false}).appendTo($div_actions);
							}
							$(notifier).trigger('attach.notify', [data.alert_msg, data.alert_type]);
						}
					});
					break;
				case 'delete':
					$.ajax({
						'type': 'POST',
						'url': page_url,
						'data': '&action=delete&ajax=1',
						'dataType': 'json',
						'error': function(xhr, msg, error){
							alert(error);
						},
						'success': function(data){
							window.history.replaceState({'a': 'b'}, '', workspace_url);
							location.reload();
						}
					});
					break;
			}
					
		});

	});

})(window.jQuery); //(jQuery.noConflict());