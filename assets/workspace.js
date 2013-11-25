(function($) {
		
	var $contents,
		templated_areas;

	var buttons = {
		'directories': {},
		'files': {},
		'upload_queue': {}
	}
	var	$add_files_true_button;

	File.prototype.request = null;
	File.prototype.status = "Inactive";
	File.prototype.transferred = 0;
	File.prototype.start = function(){
		var self = this;
		self.status = 'Queued';
		var fd = new FormData();
		fd.append('uploaded_file', this);
		fd.append("ajax", "1");
		this.request = $.ajax({
			//'contentType': 'multipart/form-data',
			'type': 'POST',
			'contentType': false,
			'url': document.URL,
			'data': fd,
			'processData': false,
			'xhr': function(){
				var xhr = $.ajaxSettings.xhr();
				if(xhr.upload){
					$(xhr.upload).on('progress', function(event){
						self.transferred = event.loaded;
						self.status = event.loaded + " bytes transferred";
						$contents.trigger('apply-templates', upload_data);
					});
				}
				return xhr;
			},
			'dataType': 'json',
			'error': function(xhr, msg) {
				alert(msg);
			},
			'success': function(data) {
				$contents.trigger('apply-templates', data);
				self.status = false;
				for(var i in upload_data.uploads){
					if(!upload_data.uploads[i].status){
						upload_data.uploads.splice(i, 1);
						break;
					}
				}
				$contents.trigger('apply-templates', upload_data);
				/*if(upload_data.uploads.length == 0) $('#upload-queue').hide();
				else table_upload_queue.display(upload_data);*/
			}
		});
	}

	var upload_queue_visible = false;
	var upload_data = {'uploads': []};

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

		$('button[name="show.upload_queue"]').click(function(event){
			if(!upload_queue_visible){
				$('#upload-queue').slideDown(280);
				upload_queue_visible = true;
				$contents.trigger('apply-templates', upload_data);
				$(event.target).text('Hide Upload Queue');
				$add_files_true_button.parent()
					.delay(20)
					.width(buttons.upload_queue.add_files.clientWidth + 3)
					.height(buttons.upload_queue.add_files.clientHeight + 3);
			}
			else {
				$('#upload-queue').slideUp(280);
				upload_queue_visible = false;
				$(event.target).text('Show Upload Queue');
			}
		});

		$add_files_true_button.change(function(event) {
			$(buttons.upload_queue.upload).attr('disabled', null);
			var files = event.target.files;
			for(var i in files){
				if(typeof(files[i]) == 'object'){
					upload_data.uploads.push(files[i]);
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
						//$(target).attr('disabled', 'disabled');
						for(var i in upload_data.uploads){
							//upload_data.uploads[i].run();
							upload_data.uploads[i].start();
						}
						break;
					case 'cancel':
						for(i in upload_data.uploads){
							try{
								upload_data.uploads[i].request.abort();
								//upload_data.uploads[i].status = "Aborted";
							}
							catch(error){}
						}
						upload_data.uploads = [];
						$contents.trigger('apply-templates', upload_data);
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