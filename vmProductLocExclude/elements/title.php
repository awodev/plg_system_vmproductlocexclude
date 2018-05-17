<?php
/**
 * @plugin VMProductLocExclude
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL 
 **/

defined('JPATH_BASE') or die();

class JElementTitle extends JElement {
	function fetchElement($name, $value, &$node, $control_name) {
		$html = '';
		if (!empty($value)) $html .= '<div style="margin: 10px 0 5px 0; font-weight: bold; padding: 5px; background-color: #cacaca;">'.JText::_($value).'</div>';
		return $html;
	}
}
