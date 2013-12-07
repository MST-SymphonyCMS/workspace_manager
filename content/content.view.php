<?php

	require_once TOOLKIT . '/class.administrationpage.php';
	require_once EXTENSIONS . '/workspace_manager/lib/widget_extra.php';

	class contentExtensionWorkspace_managerView extends AdministrationPage{
		public $assets_base_url;
		public $content_base_url;
		public $extension_base_url;
		public $_errors = array();
		public $_existing_file;
		public $_current_dir_abs;
		public $_current_url;
		public $_params;

		//public function generate($page = NULL){
		//}
		
		/**
		* @param $context Array holding current URL and file path.
		*/
		public function build(array $context){
			$this->extension_base_url = URL . '/extensions/workspace_manager/';
			$this->content_base_url = $this->extension_base_url . 'content/';
			$this->assets_base_url = $this->extension_base_url . 'assets/';
			$params = array();
			$path_abs = WORKSPACE;
			if(isset($context['path'])){
				$path = $context['path'];
				$params['path'] = $path;
				$path_abs .= '/' . $path;
				$params['path_encoded'] = Helpers::url_encode($path);
			}
			if(!file_exists($path_abs)) Administration::instance()->errorPageNotFound();
			$params['path_abs'] = $path_abs;
			$this->_params = $params;
			parent::build(array($context[0]));
		}

		public function view(){
			$this->addScriptToHead($this->assets_base_url . 'jquery.tmpl.js');
			parent::view();
		}

		public function __viewIndex(){
			extract($this->_params);
			if(!is_dir($path_abs)) Administration::instance()->errorPageNotFound();
			$this->addStylesheetToHead($this->assets_base_url . 'workspace.css');
			$this->addScriptToHead($this->assets_base_url . 'workspace.js');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Workspace'), __('Symphony'))));
			$this->setPageType('table');
			$page_url_i = SYMPHONY_URL . '/workspace/';
			$page_url_f = SYMPHONY_URL . '/editor/';
			if(isset($path)){
				$page_url_i .= $path_encoded . '/';
				$page_url_f .= $path_encoded . '/';
				$path_parts = explode('/', $path);
				$subheading = Helpers::capitalizeWords(array_pop($path_parts));
				$path_string = SYMPHONY_URL . '/workspace/';
				$breadcrumbs = array(Widget::Anchor(__('Workspace'), $path_string));
				if(!empty($path_parts)){
					foreach($path_parts as $path_part){
						$path_string .= rawurlencode($path_part) . '/';
						array_push($breadcrumbs, Widget::Anchor(__(Helpers::capitalizeWords($path_part)), $path_string));
					}
				}
			}
			else{
				$subheading = 'Workspace';
			}

			$this->appendSubheading(__($subheading));
			$this->insertAction(
				new XMLElement('button', __('Show Create/Upload'), array('type' => 'button', 'name' => 'show.create_upload'))
			);
			$this->insertAction(
				Widget::Anchor(__('Create New File'), $page_url_f, __('Create a new text file'), 'create button')
			);
			if($breadcrumbs) $this->insertBreadcrumbs($breadcrumbs);

			$create_upload = new XMLElement('div', NULL, array('id' => 'create-upload'));
			$fieldset = new XMLElement('fieldset', NULL, array('class' => 'create-dirs'));
			$fieldset->appendChild(new XMLElement('legend', __('Create Directories'), array()));
			$div = new XMLElement('div');
			$div->appendChild(new XMLElement('label', __('Enter directory names on separate lines')));
			$div->appendChild(
				new XMLElement('textarea', null, array('name' => 'directory_names'))
			);
			$div->appendChild(
				new XMLElement('button', __('Create'), array('type' => 'button', 'name' => 'create.directories', 'disabled' => 'disabled'))
			);
			$div->appendChild(
				new XMLElement('button', __('Clear'), array('type' => 'button', 'name' => 'clear.directories', 'disabled' => 'disabled'))
			);
			$fieldset->appendChild($div);
			$create_upload->appendChild($fieldset);
			
			/*
			 * File uploads fieldset.
			 */
			$fieldset = new XMLElement('fieldset', NULL, array('class' => 'table upload-queue'));
			$fieldset->appendChild(new XMLElement('legend', __('Upload Files')));
			$fieldset->appendChild(
				Widget::Table(
					Widget::TableHead(
						array(
							array(__('Name'), 'col'),
							array(__('Size (Bytes)'), 'col'),
							array(__('Status'), 'col')
						)
					),
					NULL,
					new XMLElement(
						'tbody',
						NULL,
						array('data-tmpl' => 'tmpl-uploads', 'data-data' => 'uploads')
					),
					'selectable'
				)
			);
			$buttons = new XMLElement('div', NULL, array('class' => 'upload-queue-buttons'));
			$buttons->appendChild(
				new XMLElement('button', __('Add Files'), array('type' => 'button', 'name' => 'add_files.uploads'))
			);
			$shim = new XMLElement('div', NULL, array('id' => 'aftb'));
			$shim->appendChild(
				Widget::Input('add-files-true-button', '', 'file', array('multiple' => 'multiple'))
			);
			$buttons->appendChild($shim);

			$buttons->appendChild(
				new XMLElement('button', __('Upload'), array('type' => 'button', 'name' => 'upload.uploads', 'disabled' => 'disabled'))
			);
			$buttons->appendChild(
				new XMLElement('button', __('Cancel'), array('type' => 'button', 'name' => 'cancel.uploads', 'disabled' => 'disabled'))
			);
			//$div->appendChild($buttons);
			$fieldset->appendChild($buttons);
			$create_upload->appendChild($fieldset);
			$this->Form->appendChild($create_upload);

			/*
			 * Directories fieldset.
			 */
			$fieldset = new XMLElement('fieldset', NULL, array('class' => 'table'));
			$fieldset->appendChild(new XMLElement('legend', __('Directories')));
			$fieldset->appendChild(
				Widget::Table(
					Widget::TableHead(
						array(
							array(__('Name'), 'col')
						)
					),
					NULL,
					new XMLElement(
						'tbody',
						NULL,
						array('data-tmpl' => 'tmpl-directories', 'data-data' => 'directories')
					),
					'selectable'
				)
			);
			$this->Form->appendChild($fieldset);

			/*
			 * Files fieldset.
			 */
			$fieldset = new XMLElement('fieldset', NULL, array('class' => 'table'));
			$fieldset->appendChild(
				new XMLElement('legend', __('Files'))
			);
			
			$fieldset->appendChild(
				Widget::Table(
					Widget::TableHead(
						array(
							array(__('Name'), 'col'),
							array(__('Size (Bytes)'), 'col'),
							array(__('Last Updated'), 'col')
						)
					),
					NULL,
					new XMLElement(
						'tbody',
						NULL,
						array('data-tmpl' => 'tmpl-files', 'data-data' => 'files')
					),
					'selectable'
				)
			);
			$this->Form->appendChild($fieldset);

			$this->Form->appendChild(
				new XMLElement(
					'div',
					Widget::Apply(
						array(
							array(NULL, false, __('With Selected...')),
							array('delete', false, __('Delete'), 'confirm'),
							array('download', false, __('Download'))
						)
					),
					array('class' => 'actions')
				)
			);

			/**
			 *	jQuery Templates
			 */
			ob_start();
			include EXTENSIONS . '/workspace_manager/content/tmpl.indexview.directories.php';
			$this->Contents->appendChild(
				new XMLElement(
					'script',
					__(PHP_EOL . ob_get_contents() . PHP_EOL),
					array('id' => 'tmpl-directories', 'type' => 'text/x-jquery-tmpl')
				)
			);
			ob_clean();
			include EXTENSIONS . '/workspace_manager/content/tmpl.indexview.files.php';
			$this->Contents->appendChild(
				new XMLElement(
					'script',
					__(PHP_EOL . ob_get_contents() . PHP_EOL),
					array('id' => 'tmpl-files', 'type' => 'text/x-jquery-tmpl')
				)
			);
			ob_clean();
			include EXTENSIONS . '/workspace_manager/content/tmpl.indexview.uploads.php';
			$this->Contents->appendChild(
				new XMLElement(
					'script',
					__(PHP_EOL . ob_get_contents() . PHP_EOL),
					array('id' => 'tmpl-uploads', 'type' => 'text/x-jquery-tmpl')
				)
			);
			ob_end_clean();
		}

		public function __actionIndex(){
			extract($this->_params);
			if(!is_dir($path_abs)) Administration::instance()->errorPageNotFound();

			$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;
			if(is_array($checked) && !empty($checked)){
				if($_POST['with-selected'] == 'download'){
					$name = $checked[0];
					$file = $path_abs . '/' . $name;
					if(is_file($file)) {
						header('Content-Description: File Transfer');
						header('Content-Type: application/octet-stream');
						header('Content-Disposition: attachment; filename=' . $name);
						header('Content-Transfer-Encoding: binary');
						header('Expires: 0');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');
						header('Content-Length: ' . filesize($file));
						ob_clean();
						flush();
						readfile($file);
						exit;
					}
				}
			}
		}

		/*
		* File page view.
		*/
		public function __viewFile(){
			$this->_context[1] = 'single';
			$this->addStylesheetToHead($this->assets_base_url . 'editor.css');
			$this->addScriptToHead($this->assets_base_url . 'editor.js');
			//$this->Form->setAttribute('action', $url_current_dir_f . ($filename ? rawurlencode($filename) . '/' : ''));
			extract($this->_params);
			if(is_file($path_abs)){
				$dir_abs = dirname($path_abs);
				$filename = basename($path_abs);
				$title = $filename;
			}
			else{
				$dir_abs = $path_abs;
				$title = 'Untitled';
			}
			$this->setTitle(__(('%1$s &ndash; %2$s &ndash; %3$s'), array($title, __('Workspace'), __('Symphony'))));
			//$this->setPageType('table');
			$this->Body->setAttribute('spellcheck', 'false');
			$this->appendSubheading($title);
			$workspace_url = SYMPHONY_URL . '/workspace/';
			$editor_url = SYMPHONY_URL . '/editor/';
			$path_encoded = '';
			if(isset($path)){
				$path_parts = explode('/', $path);
				if(isset($filename)) array_pop($path_parts);
				$breadcrumbs = array(Widget::Anchor(__('Workspace'), $workspace_url));
				if(!empty($path_parts)){
					foreach($path_parts as $path_part){
						$path_encoded .= rawurldecode($path_part) . '/';
						array_push($breadcrumbs, Widget::Anchor(__(Helpers::capitalizeWords($path_part)), $workspace_url . $path_encoded));
					}
				}
				$this->insertBreadcrumbs($breadcrumbs);
			}

			$this->Form->setAttribute('class', 'two columns');
			$this->Form->setAttribute('data-workspace-url', $workspace_url . $path_encoded);
			$this->Form->setAttribute('data-editor-url', $editor_url . $path_encoded);
			$this->Form->setAttribute('action', $editor_url . $path_encoded . (isset($filename) ? rawurlencode($filename) . '/' : ''));

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'primary column');
			$fieldset->appendChild(Widget::Input('fields[path_encoded]', $path_encoded, NULL, array('type' => 'hidden')));
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', $filename));
			$fieldset->appendChild($label);
			//$fieldset->appendChild((isset($this->_errors['name']) ? Widget::Error($label, $this->_errors['name']) : $label));

			$label = Widget::Label(__('Body'));
			$label->appendChild(
				Widget::Textarea(
					'fields[body]',
					30,
					80,
					//$this->_existing_file ? @file_get_contents($path_abs, ENT_COMPAT, 'UTF-8') : '',
					$filename ? htmlentities(file_get_contents($path_abs), ENT_COMPAT, 'UTF-8') : '',
					array('id' => 'text-area', 'class' => 'code hidden')
				)
			);
			//$label->appendChild();
			//$fieldset->appendChild((isset($this->_errors['body']) ? Widget::Error($label, $this->_errors['body']) : $label));

 			$fieldset->appendChild($label);
			$this->Form->appendChild($fieldset);
			//$files = General::listStructure($dir_abs , null, false, 'asc', $dir_abs);
			//$files = $files['filelist'];

			//if(is_array($files) && !empty($files)){
				//$this->Form->setAttribute('class', 'two columns');

			$div = new XMLElement('div', NULL, array('class' => 'secondary column'));
			$div->appendChild(
				new XMLElement('p', __('Files'), array('class' => 'label'))
			);
			$frame = new XMLElement('div', null, array('class' => 'frame'));
			$frame->appendChild(new XMLElement('ul', NULL, array('id' => 'files')));
			$div->appendChild($frame);
			$this->Form->appendChild($div);

			$this->Form->appendChild(new XMLElement('div', NULL, array('class' => 'actions')));

			$text = new XMLElement('p', __('Saving'));
			$this->Form->appendChild(new XMLElement('div', $text, array('id' => 'saving-popup')));
			//$this->_context = array('edit', 'pages', 'single');

			/**
			* jQuery Templates
			*/
			$page_url_f = $this->_context['page_url_f'];
			ob_start();
			include EXTENSIONS . '/workspace_manager/content/tmpl.fileview.files.php';
			$this->Contents->appendChild(
				new XMLElement(
					'script',
					__(PHP_EOL . ob_get_contents() . PHP_EOL),
					array('id' => 'tmpl-files', 'type' => 'text/x-jquery-tmpl')
				)
			);
			ob_clean();
			include EXTENSIONS . '/workspace_manager/content/tmpl.fileview.actions.php';
			$this->Contents->appendChild(
				new XMLElement(
					'script',
					__(PHP_EOL . ob_get_contents() . PHP_EOL),
					array('id' => 'tmpl-actions', 'type' => 'text/x-jquery-tmpl')
				)
			);
			ob_end_clean();
        }
	}
?>