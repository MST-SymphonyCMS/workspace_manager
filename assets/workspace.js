(function($) {
		
	var $contents,
		templated_areas;

	var buttons = {
		'directories': {},
		'files': {},
		'upload_queue': {}
	}
	var	$add_files_true_button;

	var Uploader = function(file){
		this.file = file;
	}

	Uploader.prototype.run = function(){
		var self = this;
		var fd = new FormData();
		fd.append('uploaded_file', this.file);
		fd.append("ajax", "1");
		this.request = $.ajax({
			//'contentType': 'multipart/form-data',
			'type': 'POST',
			'contentType': false,
			'url': document.URL,
			'data': fd,
			'processData': false,
			/*'xhr': function(){
				var xhr = $.ajaxSettings.xhr();
				if(xhr.upload){
					$(xhr.upload).on('progress', function(event){
						console.log("Loaded: " + event.loaded + " Total: " + event.total);
					});
				}
				return xhr;
			},*/
			'dataType': 'json',
			'error': function(xhr, msg) {
				alert(msg);
			},
			'success': function(data) {
				$contents.trigger('apply-templates', data);
				self.file = null
				for(var i in upload_data.uploads){
					if(upload_data.uploads[i].file == null){
						upload_data.uploads.splice(i, 1);
						break;
					}
				}
				if(upload_data.uploads.length == 0) $('#upload-queue').hide();
				else table_upload_queue.display(upload_data);
			}
		});
	}

	var upload_data = {'uploads': []};
	var upload_buttons = {};

	$(document).ready(function() {
		templated_areas = $('[data-tmpl|="tmpl"]');
		$contents = $('#contents:first');
		$contents.on('apply-templates', function(event, data) {
			$(templated_areas).each(function() {
				if(data[$(this).data('data')]) {
					$(this).empty().append($('#' + $(this).attr('data-tmpl')).tmpl(data));
				}
			});
		});

		$.getJSON(
			document.URL + '?ajax=index',
			function(data) {
				$contents.trigger('apply-templates', data);
			}
		);

		var button_tags = $('button[type="button"]');
		$(button_tags).each(function() {
			var name_parts = this.name.split(".");
			try {
				buttons[name_parts[1]][name_parts[0]] = this;
			}
			catch(error) {}
		});

		$add_files_true_button = $('input[name="add-files-true-button"]:first');
		$add_files_true_button.width(buttons.upload_queue.add_files.clientWidth + 2);
		$add_files_true_button.height(buttons.upload_queue.add_files.clientHeight + 2);
		$add_files_true_button.click(function(event) {
			$('#upload-queue').show();
			$contents.trigger('apply-templates', upload_data);
		});
		$add_files_true_button.change(function(event) {
			$(buttons.upload_queue.upload).attr('disabled', null);
			var files = event.target.files;
			for(var i in files){
				if(typeof(files[i]) == 'object'){
					upload_data.uploads.push(new Uploader(files[i]));
				}
			}
			$contents.trigger('apply-templates', upload_data);
			$(buttons.upload_queue.upload).attr('disabled', null);
		});

		$contents.click(function(event){
			var target = event.target;
			if(!(target.tagName == 'BUTTON' && target.type == 'button')) return;
			var which_button = target.name.split(".");
			var command = which_button[0],
				namespace = which_button[1];
			var local_buttons;
			if(namespace == 'directories') {
				switch(command) {
					case 'create_new':
						$('div.new-directories').show();
						$('div.new-directories textarea').val('').focus();
						break;
					case 'create':
						var parts = ($('div.new-directories textarea').val()).split("\n");
						var names = [];
						for(var i in parts) {
							var name = parts[i].trim();
							if(name != '') names.push(name);
						}
						$.ajax({
							'type': 'POST',
							'data': {
								'ajax': 'index',
								'action': 'create-dir',
								'fields': {'names': names}
							},
							'dataType': 'json',
							'error': function(xhr, msg){
								alert(msg);
							},
							'success': function(data){
								$('div.new-directories').hide();
								$contents.trigger('apply-templates', data);
							}
						});
						break;
					case 'cancel':
						$('div.new-directories').hide();
						break;
				}
			}
			else if(namespace == 'files' && command == 'create_new') {		
				window.location = target.getAttribute('data-url');
			}
			else if(namespace == 'upload_queue') {
				local_buttons = buttons.upload_queue;
				switch(command) {
					case 'upload':
						$(target).attr('disabled', 'disabled');
						for(var i in upload_data.uploads){
							upload_data.uploads[i].run();
						}
						break;
					case 'cancel':
						$('#upload-queue').hide();
						for(i in upload_data.uploads){
							try{
								upload_data.uploads[i].request.abort();
							}
							catch(error){}
						}
						upload_data.uploads = [];
						break;
				}
			}
		});

		$('#contents form')
			.on('submit', function(event) {
				var self = this;
				var selected = $(this).find('option:selected')[0];
				if(selected.getAttribute('value') == ''){
					event.preventDefault();
					return;
				}
				if(selected.getAttribute('value') == 'delete'){
					event.preventDefault();
					$.ajax({
						'type': 'POST',
						'url': document.URL,
						'data': $(self).serialize() + '&ajax=index',
						'dataType': 'json',
						'error': function(xhr, msg) {
							alert(msg);
						},
						'success': function(data) {
							$contents.trigger('apply-templates', data);
						}
					});
				}
			});

	});

})(jQuery.noConflict());