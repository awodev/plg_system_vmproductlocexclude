<?php
/**
 * @plugin VMProductLocExclude
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL 
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.event.plugin');
class plgSystemVMProductLocExclude extends JPlugin {
	
	function onAfterRoute(){
		if(JFactory::getApplication()->isAdmin()) return;
		$option = JRequest::getCmd('option');
		if($option != 'com_virtuemart') return;
		
		if(!class_exists('vmVersion')) require JPATH_ROOT.'/administrator/components/com_virtuemart/version.php';
		$VMVERSION = new vmVersion();
		$version = isset( $VMVERSION->RELEASE) ? $VMVERSION->RELEASE : '';
		if(empty($version)) $version = isset(vmVersion::$RELEASE) ? vmVersion::$RELEASE : '';
		
		
		if(substr($version,0,1) == '1') $this->onAfterRouteVM1();
		elseif(substr($version,0,1) == '2') $this->onAfterRouteVM2();
		elseif(substr($version,0,1) == '3') $this->onAfterRouteVM2();
				
	}
	
	function onAfterRouteVM1(){
		$option = JRequest::getCmd('option');
		$page = JRequest::getCmd('page');
		if($option != 'com_virtuemart' || $page!='checkout.index' ) return;
			

		$ship_to_info_id = JRequest::getCmd('ship_to_info_id');
		$user = & JFactory::getUser();
		if(empty($user->id) && empty($ship_to_info_id)) return;
		
			
		$db = JFactory::getDBO();
		$sql = !empty($ship_to_info_id) 
					? 'SELECT state,country FROM #__vm_user_info WHERE user_info_id="'.$ship_to_info_id.'"'
					: 'SELECT state,country FROM #__vm_user_info WHERE user_id='.$user->id.' AND address_type="BT"';
		$db->setQuery($sql);
		$tmp = $db->loadObject();
		if(empty($tmp)) return;
		$country = $tmp->country;
		$state = $tmp->state;

		$rules = array();
		for($i=0; $i<20; $i++) {

			$products = $this->_toarray('product'.($i+1));
			$countries = $this->_toarray('country'.($i+1));
			$states = $this->_toarray('state'.($i+1));
			
			if(empty($products) || empty($countries)) continue;
			if(!empty($states) && count($countries)>1) continue;
			
			foreach($products as $product) {
				foreach($countries as $country) {
					$product = strtolower($product);
					if(!empty($rules[$product][$country])) $rules[$product][$country] = array_merge($rules[$product][$country],$states);
					else $rules[$product][$country] = $states;
				}
			}
		}
		if(empty($rules)) return;
		
		$db->setQuery('SELECT product_id,product_sku FROM #__vm_product WHERE product_sku IN ("'.implode('","',array_keys($rules)).'")');
		$productIndex = $db->loadObjectList('product_id');
//echo '<pre>'; print_r($rules); exit;
		
		$new_cart = array();
		$new_idx = 0;
		for ($i=0;$i<$_SESSION['cart']['idx'];$i++) {
			$add_product = false;

			if(!isset($productIndex[$_SESSION['cart'][$i]['product_id']])) $add_product = true;
			else {
				$product_key = strtolower($productIndex[$_SESSION['cart'][$i]['product_id']]->product_sku);
				if(!isset($rules[$product_key][$country])) $add_product = true;
				else {
					if(empty($rules[$product_key][$country]));
					elseif(in_array($state,$rules[$product_key][$country]));
					else $add_product = true;
				}
			}
			
			if($add_product) $new_cart[$new_idx++] = $_SESSION['cart'][$i];
		}
		if(!empty($new_idx)) $new_cart["idx"] = $new_idx;
		if($new_idx != $_SESSION['cart']['idx']) {
			global $mainframe;
			JFactory::getLanguage()->load('plg_system_vmProductLocExclude',JPATH_ADMINISTRATOR);
			$mainframe->enqueueMessage(JText::_('VMPRODUCTLOCEXCLUDE_WARNING'), 'error');
		}
		$_SESSION['cart'] = $new_cart;			
		
	}
	
	function onAfterRouteVM2(){
		$option = JRequest::getCmd('option');
		if($option != 'com_virtuemart') return;

		if (!class_exists('VmConfig')) require JPATH_ROOT.'/administrator/components/com_virtuemart/helpers/config.php';
		if (!class_exists('VmImage')) require JPATH_ROOT.'/administrator/components/com_virtuemart/helpers/image.php'; // needs to be loaded or receive "ensure that the class definition "VmImage" of the object you are trying to operate on was loaded..."
		if (!class_exists('VirtueMartCart')) require JPATH_ROOT.'/components/com_virtuemart/helpers/cart.php';
		VmConfig::loadConfig();
		$cart = VirtueMartCart::getCart();
		
		
		if(empty($cart->BT)) return;
		if(empty($cart->products) && empty($cart->cartProductsData)) return; 
		
		
		if (!class_exists('VirtueMartModelCountry')) require JPATH_ROOT.'/administrator/components/com_virtuemart/models/country.php';
		if (!class_exists('VirtueMartModelState')) require JPATH_ROOT.'/administrator/components/com_virtuemart/models/state.php';
		
		$address = empty($cart->ST) ? $cart->BT : $cart->ST;
		
		$c_class = new VirtueMartModelCountry();
		$c_class->_id = $address['virtuemart_country_id'];
		$c_obj = $c_class->getData();
		$u_country = $c_obj->country_3_code;
		
		if(empty($u_country)) return;

		$s_class = new VirtueMartModelState();
		$s_class->_id = $address['virtuemart_state_id'];
		$s_obj = $s_class->getData();
		$u_state = $s_obj->state_2_code;
		
		

		$rules = array();
		for($i=0; $i<20; $i++) {

			$products = $this->_toarray('product'.($i+1));
			$countries = $this->_toarray('country'.($i+1));
			$states = $this->_toarray('state'.($i+1));
			
			if(empty($products) || empty($countries)) continue;
			if(!empty($states) && count($countries)>1) continue;
			
			foreach($products as $product) {
				foreach($countries as $country) {
					$product = strtolower($product);
					if(!empty($rules[$product][$country])) $rules[$product][$country] = array_merge($rules[$product][$country],$states);
					else $rules[$product][$country] = $states;
				}
			}
		}
		if(empty($rules)) return;
		
		$items_to_delete = array();
		if(empty($cart->products) && !empty($cart->cartProductsData) && method_exists($cart,'prepareCartData')) $cart->prepareCartData();
		foreach($cart->products as $product_cart_id=>$item) {
			//if(!isset($item->product_sku)) continue;
			
			$product_key = strtolower($item->product_sku);
			if(!isset($rules[$product_key][$u_country])) continue;

			if(empty($rules[$product_key][$u_country]) || in_array($u_state,$rules[$product_key][$u_country])) $items_to_delete[] = $product_cart_id;
		}
		if(!empty($items_to_delete)) {
			foreach($items_to_delete as $product_id) $cart->removeProductCart($product_id);
			JFactory::getLanguage()->load('plg_system_vmProductLocExclude',JPATH_ADMINISTRATOR);
			JFactory::getApplication()->enqueueMessage(JText::_('VMPRODUCTLOCEXCLUDE_WARNING'), 'error');
		}
		
	}
	
	
	function _toarray($name) {
		$var = $this->params->get($name,'');
		if(empty($var)) return array();
		$var = explode(',',$var);
		
		$o = array();
		foreach($var as $row) if(!empty($row)) $o[] = trim($row);
		return $o;
	}
	
}


