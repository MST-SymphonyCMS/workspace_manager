<?php

	class Helpers{
		public static function capitalizeWords($string){
			return ucwords(str_replace('-', ' ', $string));
		}
		
		public static function url_encode($string){
			$array_out = array();
			foreach(explode('/', $string) as $part){
				array_push($array_out, rawurlencode($part));
			}
			return implode('/', $array_out);
		}

	}