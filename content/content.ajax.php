<?php

	class contentExtensionWorkspace_managerAjax{

		public $_context;
		public $_errors;
		private $_output;

		public function __construct(){}

		public function build(array $context = array()){
			$this->_output = array();
			$path_abs = WORKSPACE;
			if(isset($context['path'])){
				$path_abs .= '/' . $context['path'];
			}
			$context['path_abs'] = $path_abs;
			$this->_context = $context;
			$function = '__ajax' . ucfirst($context[0]);
			if(method_exists($this, $function)) {
				$this->$function();
			}
		}

		public function __ajaxIndex(){
			$path_abs = $this->_context['path_abs'];
			chdir($path_abs);
			if(isset($_FILES['uploaded_file'])){
				move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $_FILES['uploaded_file']['name']);
			}
			elseif(isset($_POST['action'])){
				$fields = $_POST['fields'];
				//error_log($fields['names'][0]." ".$fields['names'][1]);
				switch($_POST['action']){
					case 'create-dir':
						foreach($fields['names'] as $name){
							if($name != '') @mkdir($name);
						}
						break;
				}
			}
			elseif(isset($_POST['with-selected'])){
				$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;
				if(is_array($checked) && !empty($checked)){
					switch($_POST['with-selected']){
						case 'delete':
							//$canProceed = true;
							foreach($checked as $name){
								$message .= ' ' . $name;
								if(is_dir($name)) rmdir($name);
								if(is_file($name)) unlink($name);
	/*							if(preg_match('/\/$/', $name) == 1){
									$name = trim($name, '/');
									try {
										rmdir($dir_abs . '/' . $name);
									}
									catch(Exception $ex) {
										$this->pageAlert(
											__('Failed to delete %s.', array('<code>' . $name . '</code>'))
											. ' ' . __('Directory %s not empty or permissions are wrong.', array('<code>' . $name . '</code>'))
											, Alert::ERROR
										);
										$canProceed = false;
									}
								}
								elseif(!General::deleteFile($dir_abs . '/'. $name)) {
									$this->pageAlert(
										__('Failed to delete %s.', array('<code>' . $name . '</code>'))
										. ' ' . __('Please check permissions on %s.', array('<code>/workspace/' . $this->_context['target_d'] . '/' . $name . '</code>'))
										, Alert::ERROR
									);
									$canProceed = false;
								}*/
							}

							//if ($canProceed) redirect(Administration::instance()->getCurrentPageURL());
							break;
					}
				}
			}
				//$output = array('directories' => array(), 'files' => array());
				///$current_dir = WORKSPACE . '/' . $context[1];
			$format = Symphony::Configuration()->get('date_format', 'region') . ' ' . Symphony::Configuration()->get('time_format', 'region');
			$directories = array();
			$files = array();
			foreach(scandir('./') as $item){
				if($item == '.' or $item == '..') continue;
				if(is_dir($item)){
					array_push($directories, array('name' => $item));
				}
				elseif(is_file($item)){
					$stat = stat($item);
					array_push($files, array(
							'name' => $item,
							'size' => $stat['size'],
							'modified' => date($format, $stat['mtime'])
							//'modified' => $stat['mtime']
						)
					);
				}
			}
			$this->_output['directories'] = $directories;
			$this->_output['files'] = $files;
		}

		/*
		* Editor Page.
		*/
		public function __ajaxFile(){
			$path_abs = $this->_context['path_abs'];
			if(is_file($path_abs)){
				$existing_file = basename($path_abs);
				$dir_abs = dirname($path_abs);
				//array_pop($path_split);
				//array_push($path_split, $existing_file);
			}
			else{
				$dir_abs = $path_abs;
			}
			$action = $_POST['action'];
			if($action == 'delete'){
				@unlink($path_abs);
			}
			elseif($action == 'save' and isset($_POST['fields'])){
				$fields = $_POST['fields'];
				$name = $fields['name'];
				$file = $dir_abs . '/' . $name;
				if(isset($existing_file)){
					if($existing_file != $name && is_file($file))
						$this->_errors['name'] = __('A file with that name already exists. Please choose another.');
				}
				elseif(is_file($file)) $this->_errors['name'] = __('A file with that name already exists. Please choose another.');

				if(empty($this->_errors)){
					if(!$existing_file){
						/**
						* Just before the Utility has been created
						*
						* @delegate UtilityPreCreate
						* @since Symphony 2.2
						* @param string $context
						* '/blueprints/css/'
						* @param string $file
						*  The path to the Utility file
						* @param string $contents
						*  The contents of the `$fields['body']`, passed by reference
						*/
						//Symphony::ExtensionManager()->notifyMembers('FilePreCreate', '/assets/' . $this->category . '/', array('file' => $file, 'contents' => &$fields['body']));
					}
					else {
						/**
						* Just before the Utility has been updated
						*
						* @delegate UtilityPreEdit
						* @since Symphony 2.2
						* @param string $context
						* '/blueprints/css/'
						* @param string $file
						*  The path to the Utility file
						* @param string $contents
						*  The contents of the `$fields['body']`, passed by reference
						*/
						//Symphony::ExtensionManager()->notifyMembers('FilePreEdit', '/assets/' . $this->category . '/', array('file' => $file, 'contents' => &$fields['body']));
					}

					// Write the file
					if(!$write = General::writeFile($file, $fields['body'], Symphony::Configuration()->get('write_mode', 'file'))){
						$this->_output['alert_type'] = 'error';
						$this->_output['alert_msg'] = __('File could not be written to disk.')
							. ' ' . __('Please check permissions on %s.', array('<code>/workspace/' . '' . '</code>'));
					}
					// Write Successful, add record to the database
					else {
						$this->_output['alert_type'] = 'success';
						$path_encoded = $fields['path_encoded'];
						$workspace_url = SYMPHONY_URL . '/workspace/' . $path_encoded;
						$editor_url = SYMPHONY_URL . '/editor/' . $path_encoded;
						// Remove any existing file if the filename has changed
						if(isset($existing_file)){
							if($name != $existing_file) General::deleteFile($path_abs);

							$this->_output['alert_msg'] =
								__('File updated at %s.', array(DateTimeObj::getTimeAgo()))
								. ' <a href="' . $editor_url . '" accesskey="c">'
								. __('Create another?')
								. '</a> <a href="' . $workspace_url . '" accesskey="a">'
								. __('View current directory')
								. '</a>';
						}
						else{
							$this->_output['alert_msg'] =
								__('File created at %s.', array(DateTimeObj::getTimeAgo()))
								. ' <a href="' . $editor_url . '" accesskey="c">'
								. __('Create another?')
								. '</a> <a href="' . $workspace_url . '" accesskey="a">'
								. __('View current directory')
								. '</a>';
						}
						// Set new page URL
						$this->_output['filename'] = $name;
						$this->_output['filename_encoded'] = rawurlencode($name);
					}
				}
			}
			$files = array();
			chdir($dir_abs);
			foreach(scandir('./') as $item){
				if($item == '.' or $item == '..') continue;
				if(is_file($item)){
					array_push($files, array('name' => $item));
				}
			}
			$this->_output['files'] = $files;			
		}

		public function generate($page = NULL){
			header('Content-Type: text/javascript');
			echo json_encode($this->_output);
			exit();
		}
	}