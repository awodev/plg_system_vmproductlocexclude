<?php
/**
 * @plugin VMProductLocExclude
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL 
 **/

namespace AwoDev\Plugin\System\Vmproductlocexclude\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;

class Vmproductlocexclude extends CMSPlugin {
	
	public function onAfterRoute() {
		if ( ! JFactory::getApplication()->isClient( 'site' ) ) {
			return;
		}
		if ( JFactory::getApplication()->input->get( 'option' ) != 'com_virtuemart' ) {
			return;
		}

		$this->check_location();
	}
	
	private function check_location(){
		if ( ! class_exists( 'VmConfig' ) ) {
			require JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
		}
		\VmConfig::loadConfig();
		if ( ! class_exists( 'VirtueMartCart' ) ) {
			require JPATH_VM_SITE . '/helpers/cart.php';
		}
		$cart = \VirtueMartCart::getCart();
		if ( empty( $cart->BT ) ) {
			return;
		}
		if ( empty( $cart->products ) && empty( $cart->cartProductsData ) ) {
			return; 
		}

		if ( ! class_exists( 'VirtueMartModelCountry' ) ) {
			require JPATH_ROOT . '/administrator/components/com_virtuemart/models/country.php';
		}
		if ( ! class_exists( 'VirtueMartModelState' ) ) {
			require JPATH_ROOT . '/administrator/components/com_virtuemart/models/state.php';
		}
		$address = empty( $cart->ST['virtuemart_country_id'] ) ? $cart->BT : $cart->ST;
		$c_class = new \VirtueMartModelCountry();
		$c_class->_id = $address['virtuemart_country_id'];
		$c_obj = $c_class->getData();
		$u_country = $c_obj->country_3_code;
		if ( empty( $u_country ) ) {
			return;
		}
		$s_class = new \VirtueMartModelState();
		$s_class->_id = $address['virtuemart_state_id'];
		$s_obj = $s_class->getData();
		$u_state = $s_obj->state_2_code;

		$tmp = $this->params->get( 'rule' );
		if ( empty( $tmp ) ) {
			return;
		}
		$rules = [];
		foreach ( $tmp as $row ) {

			$products = $this->_toarray( $row->product );
			$countries = $this->_toarray( $row->country );
			$states = $this->_toarray( $row->state );
			
			if ( empty( $products ) || empty( $countries ) ) {
				continue;
			}
			if ( ! empty( $states ) && count( $countries ) !=1 ) {
				continue;
			}

			foreach ( $products as $product ) {
				foreach ( $countries as $country ) {
					$product = strtolower( $product );
					$rules[ $product ][ $country ] = ! empty( $rules[ $product ][ $country ] ) ? array_merge( $rules[ $product ][ $country ], $states ) : $states;
				}
			}
		}
		if ( empty( $rules ) ) {
			return;
		}

		$items_to_delete = [];
		if ( empty($cart->products ) && ! empty( $cart->cartProductsData ) ) {
			$cart->prepareCartData();
		}
		foreach ( $cart->products as $product_cart_id => $item ) {
			if ( empty( $item->product_sku ) ) {
				continue;
			}

			$product_key = strtolower( $item->product_sku );
			if ( ! isset( $rules[ $product_key ][ $u_country ] ) ) {
				continue;
			}

			if ( empty( $rules[ $product_key ][ $u_country ] ) || in_array( $u_state, $rules[ $product_key ][ $u_country ] ) ) {
				$items_to_delete[] = $product_cart_id;
			}
		}
		if ( empty( $items_to_delete ) ) {
			return;
		}

		foreach ( $items_to_delete as $cart_product_key ) {
			$cart->removeProductCart( $cart_product_key );
		}

		$jlang = JFactory::getLanguage();
		$jlang->load( 'plg_system_vmProductLocExclude', JPATH_ADMINISTRATOR, 'en-GB', true );
		$jlang->load( 'plg_system_vmProductLocExclude', JPATH_ADMINISTRATOR, $jlang->getDefault(), true );
		$jlang->load( 'plg_system_vmProductLocExclude', JPATH_SITE . '/plugins/system/vmproductlocexclude/', 'en-GB', true );
		$jlang->load( 'plg_system_vmProductLocExclude', JPATH_SITE . '/plugins/system/vmproductlocexclude/', $jlang->getDefault(), true );

		JFactory::getApplication()->enqueueMessage( JText::_( 'VMPRODUCTLOCEXCLUDE_WARNING' ), 'error' );
		
	}

	private function _toarray( $var ) {
		if ( empty( $var ) ) {
			return [];
		}
		$var = explode( ',', $var );

		$o = [];
		foreach ( $var as $row ) {
			if ( ! empty( trim( $row ) ) ) {
				$o[] = trim( $row );
			}
		}
		return $o;
	}


}


