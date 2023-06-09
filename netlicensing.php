<?php
/**
 * NetLicensing API
 *
 * @package           	NetLicensing API
 * @author            	Optimanet Schweiz AG
 * @copyright         	2022 Optimanet Schweiz AG
 * @contact				https://hostfactory.ch
 *
 * Plugin Name:       	NetLicensing API
 * Plugin URI:        	https://hostfactory.ch
 * Description:       	NetLicensing API
 * Version:           	0.0.0.2
 * Requires at least: 	5.0
 * Requires PHP:     	8.1
 * Author:           	Optimanet Schweiz AG
 * Author URI:      	https://optimanet.ch
 * License:          	Apache License (V2)
 * GitHubId:			581505695
 */

// HfCore
if (!class_exists('HfCore\System'))
	require_once('core/System.php');

// NetLicensing
require_once('objects/ApiObject.php');
require_once('objects/Licensee.php');
require_once('objects/Product.php');
require_once('NetLicensingSystem.php');
require_once('NetLicensingAPI.php');

// Frontend / Backend
if (is_admin()) {
	require_once('NetLicensingBackend.php');
	new NetLicensingBackend();
}
else {
	new NetLicensingSystem();
}
