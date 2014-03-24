<?php
	require EXTENSIONS . '/workspace_manager/lib/class.helpers.php';

	Class extension_workspace_manager extends Extension{

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePostCallback',
					'callback' => 'postCallback'
				)/*,
				array(
					'page' => '/backend/',
					'delegate' => 'ExtensionsAddToNavigation',
					'callback' => 'addToNavigation'
				)*/
			);
		}

		public function postCallback(&$context){
			$parts = $context['parts'];
			$where = array_shift($parts);
			$routes = array('workspace' => 'index', 'editor' => 'file');
			if(array_key_exists($where, $routes)){
				$callback_context = array(0 => $routes[$where]);
				if(!empty($parts)) $callback_context['path'] = implode('/', $parts);
				if(array_key_exists('ajax', $_GET) or array_key_exists('ajax', $_POST)){
					$context['callback'] = array(
						'driver' => 'ajax',
						'driver_location' => EXTENSIONS . '/workspace_manager/content/content.ajax.php',
						'pageroot' => '/extensions/workspace_manager/content/',
						'classname' => 'contentExtensionWorkspace_managerAjax',
						'context' => $callback_context
					);
				}
				else{
					$context['callback'] = array(
						'driver' => 'view',
						'driver_location' => EXTENSIONS . '/workspace_manager/content/content.view.php',
						'pageroot' => '/extensions/workspace_manager/content/',
						'classname' => 'contentExtensionWorkspace_managerView',
						'context' => $callback_context
					);
				}
			}
		}

		/*
		* Set naviagtion
		*/
		public function fetchNavigation(){
			return array($this->navigation());
		}

		private function navigation(){
			$children = array(
				array(
					'relative' => false,
					'link' => 'workspace',
					'name' => 'Home',
					'visible' => 'yes'
				)
			);
			$entries = scandir(WORKSPACE);
			foreach($entries as $entry){
				if($entry == '.' or $entry == '..') continue;
				if(is_dir(WORKSPACE . '/' . $entry)){
					array_push($children,
						array(
							'link' => '/workspace/' . $entry . '/',
							'name' => Helpers::capitalizeWords($entry),
							'visible' => 'yes'
						)
					);
				}
			}
			return array(
				'name' => 'Workspace',
				'type' => 'structure',
				'index' => '250',
				'children' => $children
			);
		}

		/*
		* Set navigation using delegate
		*/
		//public function addToNavigation(&$context){
		//	$context['navigation'][250] = $this->navigation();
		//}

	}
?>