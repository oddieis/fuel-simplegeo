<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @OAuthor     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

Autoloader::add_classes(array(
	'SimpleGeo\\Client'           => __DIR__.'/classes/client.php',
	'SimpleGeo\\GeoPoint'         => __DIR__.'/classes/client.php',
	'SimpleGeo\\GeoRecord'        => __DIR__.'/classes/client.php',
));


/* End of file bootstrap.php */