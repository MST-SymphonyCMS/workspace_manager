<?php
	class WidgetExtra {
		public static function sectionHead($legend){
			$div = new XMLElement('div', NULL, array('class' => 'section-header'));
			$div->appendChild(new XMLElement('h3', $legend));
			return $div;
		}
		
		public static function UList(array $elements){
			$ul = new XMLElement('ul', NULL, array('class' => 'action'));
			foreach($elements as $element){
				$ul->appendChild(new XMLElement('li', $element));
			}
			return $ul;
		}
		
		public static function button($legend, $attributes){
			$button = new XMLElement('button', __($legend));
		}
	}