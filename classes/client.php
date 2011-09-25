<?php

namespace SimpleGeo;

/**
	Contains a latitude and longitude. Simply represents a coordinate
**/
class GeoPoint {
	public $lat;
	public $lng;
}

/**
	A record object contains data regarding an arbitrary object and the
	layer it resides in
	http://simplegeo.com/docs/getting-started/storage#what-record
	
**/
class GeoRecord {
	
	private $Properties;
	public $Layer;
	public $ID;
	public $Created;
	public $Latitude;
	public $Longitude;
	
	/**
		Create a record with, location is optional if not inserting/updating
		
		@var	string	$layer	The name of the layer
		@var	string	$id		The unique identifier of the record
		
		@param	double	$lat	Latitude
		@param	double	$lng	Longitude
	**/
	public function GeoRecord($layer, $id, $lat = NULL, $lng = NULL) {
		$this->Layer = $layer;
		$this->ID = $id;
		$this->Latitude = $lat;
		$this->Longitude = $lng;
		$this->Properties = array();
		$this->Created = time();
	}
	
	/**
		Returns an array representation of the Record
	**/
	public function ToArray() {
		return array(
			'type' => 'Feature',
			'id' => $this->ID,
			'created' => $this->Created,
			'geometry' => array(
				'type' => 'Point',
				'coordinates' => array($this->Longitude, $this->Latitude),
			),
			'properties' => (object) $this->Properties
		);
	}
	
	
	public function __set($key, $value) {
		$this->Properties[$key] = $value;
	}
	
	public function __get($key) {
		return \Arr::get($this->Properties, $key);
	}
	
}

class Client {

	private $consumer;
	private $token, $secret;
	
	const BASE_URL = 'http://api.simplegeo.com/';
	
	public function __construct($token, $secret)
	{
		$this->token = $token;
		$this->secret = $secret;
		
		$this->consumer = new \OAuth\Consumer(array(
			'key' => $this->token, 
			'secret' => $this->secret,
		));
	}
	
	public static function forge()
	{
		\Config::load('simplegeo', true);
		
		return new static(\Config::get('simplegeo.token'), \Config::get('simplegeo.secret'));
	}
	

	/**
		Extracts the ID from a SimpleGeo ID (SG_XXXXXXXXXXXXXXXXXXXXXX)
		
		@param	string	id	The SimpleGeo ID of a feaure
	
	**/
	public static function ExtractID($id) {
		preg_match('~SG_[A-Za-z0-9]{22}~', $id, $matches);
		return isset($matches[0]) ? $matches[0] : false;
	}
	
	/**
		Returns a list of all possible feature categories
		
	**/
	public function FeatureCategories() {
		return $this->SendRequest('GET', '1.2/features/categories.json');
	}
	
	/**
		Returns detailed information of a feature
		
		@var string $handle		Feature ID
	**/
		
	public function Feature($handle) {
		return $this->SendRequest('GET', '1.2/features/' . $handle . '.json');
	}
	
	/**
		Returns context of an IP
		
		@var string $ip			IP Address

	**/
	
	public function ContextIP($ip, $opts = array()) {
		return $this->SendRequest('GET', '1.2/context/' . $ip . '.json', $opts);
	}
	
	
	/**
		Returns context of a coordinate
		
		@var mixed $lat			Latitude or GeoPoint
		@var float $lng			Longitude

	**/
	public function ContextCoord($lat, $lng = false, $opts = array()) {
		if ($lat instanceof GeoPoint) {
			$lng = $lat->lng;
			$lat = $lat->lat;
			if (is_array($lng)) $opts = $lng;
		}
		return $this->SendRequest('GET', '1.2/context/' . $lat . ',' . $lng . '.json');
	}
	
	
	
	/**
		Returns a specific feature from a coordinate
		
		@var mixed $lat			Latitude or GeoPoint
		@var float $lng			Longitude

	**/
	public function ContextCoordFeature($lat, $lng, $category) {
		if ($lat instanceof GeoPoint) {
			$lng = $lat->lng;
			$lat = $lat->lat;
		}
		return $this->SendRequest('GET', '1.2/context/' . $lat . ',' . $lng . '.json?features__category=' . $category . '&filter=features');
	}
	
	
	
	/**
		Returns context of an address
		
		@var string $address	Human readable address (US only)

	**/
	public function ContextAddress($address, $opts = array()) {
		return $this->SendRequest('GET', '1.2/context/address.json', array('address' => $address));
	}
	
	public function WeatherAddress($address) {
		return $this->SendRequest('GET', '1.2/context/address.json', 
			array('address' => $address, 'filter' => "weather")
		);

	}
	
	
	/**
		Returns places nearby an IP
		
		@var string $ip			IP Address
		
		@param string q			Search query
		@param string category	Filter by a classifer (see https://gist.github.com/732639)
		@param float radius		Radius in km (default=25)

	**/
	
	public function PlacesIP($ip, $opts = array()) {
		return $this->SendRequest('GET', '1.2/places/' . $ip . '.json', $opts);
	}
	
	
	
	/**
		Returns places nearby a coordinate
		
		@var mixed $lat			Latitude or GeoPoint
		@var float $lng			Longitude
		
		
		@param string q			Search query
		@param string category	Filter by a classifer (see https://gist.github.com/732639)
		@param float radius		Radius in km (default=25)

	**/
	public function PlacesCoord($lat, $lng = false, $opts = array()) {
		if ($lat instanceof GeoPoint) {
			if (is_array($lng)) $opts = $lng;
			$lng = $lat->lng;
			$lat = $lat->lat;
		}
		return $this->SendRequest('GET', '1.2/places/' . $lat . ',' . $lng . '.json', $opts);
	}
	
	
	
	/**
		Returns places nearby a coordinate
		
		@var mixed $lat			Latitude or GeoPoint
		@var float $lng			Longitude

		@param string q			Search query
		@param string category	Filter by a classifer (see https://gist.github.com/732639)
		@param float radius		Radius in km (default=25)

	**/
	public function PlacesBounds($sw_lat, $sw_lng, $ne_lat, $ne_lng, $opts = array())
	{
		return $this->SendRequest('GET', '1.2/places/'.$sw_lat.','.$sw_lng.','.$ne_lat.','.$ne_lng.'.json', $opts);
	}
	
	
	/**
		Returns places nearby an address
		
		@var string $address	Human readable address (US only)
		
		@param string q			Search query
		@param string category	Filter by a classifer (see https://gist.github.com/732639)
		@param float radius		Radius in km (default=25)

	**/
	public function PlacesAddress($address, $opts = array()) {
		return $this->SendRequest('GET', '1.2/places/address.json', array_merge(array(
			'address' => $address
		), $opts));
	}
	
	
	/**
		Use the Places endpoint to contribute a new feature to SimpleGeo Places. Each new feature 
		gets a unique, consistent handle; if you insert the same record twice, the more recent 
		insert overwrites the previous one.
		
		When you add a feature, it is visible only to your application until approved for general 
		use. To keep your features private to your application, include "private": true in the GeoJSON 
		properties.
	
		@var Feature $place	A place object to insert
		
	**/
	public function CreatePlace(Place $place) {
		$context = $this->ContextCoord($place->Latitude, $place->Longitude);
		return $this->SendRequest('POST', '1.2/places', json_encode($place->ToArray()));
	}
	
	
	/**
		Update a place
	
		@var Feature $place	A place object to modify
		
	**/
	public function UpdatePlace(Place $place) {
		return $this->SendRequest('POST', '1.2/features/' . $place->ID . '.json', json_encode($place->ToArray()));
	}
	
	
	/**
		Suggests that a Feature be deleted and effectively hides it from your view. Requires a handle.
		Returns a status token.
		
		@var Feature $place	The place to delete
	
	**/
	public function DeletePlace(Place $place) {
		return $this->SendRequest('DELETE', '1.2/features/' . $place->ID . '.json');
	}
	

	/**
		Inserts a record according to the ID and Layer properties of the SimpleGeo Storage record
		
		@var	Record	$record	The record to insert
		
	**/
	public function PutRecord(GeoRecord $record) {
		return $this->SendRequest('PUT', '0.1/records/' . $record->Layer . '/' . $record->ID . '.json', json_encode($record->ToArray()));
	}
	
	
	
	/**
		Gets a record according to the ID and Layer properties of the SimpleGeo Storage record
		
		@var	Record	$record	The record to retrieve
		
	**/
	public function GetRecord(GeoRecord $record) {
		return $this->SendRequest('GET', '0.1/records/' . $record->Layer . '/' . $record->ID . '.json');
	}
	
	
	/**
		Delete a record according to the ID and Layer properties of the SimpleGeo Storage record
		
		@var	Record	$record	The record to delete
		
	**/
	public function DeleteRecord(GeoRecord $record) {
		return $this->SendRequest('DELETE', '0.1/records/' . $record->Layer . '/' . $record->ID . '.json');
	}
	
	
	
	/**
		Retrieve the history of an individual Simplegeo Storage record
		
		@var	Record	$record	The record to retrieve the history of
	**/
	public function RecordHistory(GeoRecord $record) {
		return $this->SendRequest('GET', '0.1/records/' . $record->Layer . '/' . $record->ID . '/history.json');
	}
	
	
	
	/**
		Retrieve SimpleGeo Storage records nearby the coordinate given
		
		@var	string	$layer	The name of the layer to retrieve records from
		@var	double	$lat	Latitude
		@var	double	$lat	Longitude
		
		@params	array	$params	Additional parameters in an associate array (radius, limit, types, start, end)
		
	**/
	public function NearbyRecordsCoord($layer, $lat, $lng, $params = array()) {
		return $this->SendRequest('GET', '0.1/records/' . $layer . '/nearby/' . $lat . ',' . $lng . '.json', $params);
	}
	
	
	/**
		Returns nearby SimpleGeo Storage records near a street address
		
		@var string $address	Human readable address (US only)
		
		@params	array	$params	Additional parameters in an associate array (radius, limit, types, start, end)

	**/
	public function NearbyRecordsAddress($layer, $address, $params = array()) {
		return $this->SendRequest('GET', '0.1/records/' . $layer . '/nearby/address.json', array_merge(array(
			'address' => $address
		), $params));
	}
	
	
	/**
		Retrieve SimpleGeo Storage records nearby a geohash
		
		@var	string	$layer	The name of the layer to retrieve records from
		@var	string	$hash	The geohash (see geohash.org) of the location
		
		@params	array	$params	Additional parameters in an associate array (radius, limit, types, start, end)
		
	**/
	public function NearbyRecordsGeohash($layer, $hash, $params = array()) {
		return $this->SendRequest('GET', '0.1/records/' . $layer . '/nearby/' . $hash . '.json', $params);
	}
	
	
	
	/**
		Retrieve SimpleGeo Storage records near an IP address
		
		@var	string	$layer	The name of the layer to retrieve records from
		@var	string	$ip		The IP address to search around
		
		@params	array	$params	Additional parameters in an associate array (radius, limit, types, start, end)
		
	**/
	public function NearbyRecordsIP($layer, $ip, $params = array())
	{
		return $this->SendRequest('GET', '0.1/records/' . $layer . '/nearby/' . $ip . '.json', $params);
	}
	
	private function SendRequest($method = 'GET', $endpoint, $data = array())
	{
/*
		$this->Revert(self::BASE_URL . $endpoint);
		$this->SetMethod($method);
		if (is_array($data)) $this->AddVars($data);
		else if (is_string($data)) $this->SetBody($data);
		$this->IncludeAuthHeader();
		return $this->Get();
*/	

		// Convert the signature name into an object
		$this->signature = \OAuth\Signature::factory('HMAC-SHA1');

		$request = \OAuth\Request::factory('resource', $method, static::BASE_URL . $endpoint, array_merge(array(
			'oauth_consumer_key'     => $this->token,
			'oauth_consumer_secret'  => $this->secret,
			'oauth_token'            => '',
			'oauth_secret'           => '',
		)));
		
		// Sign the request using the consumer and token
		$request->sign($this->signature, $this->consumer);

		return json_decode($request->execute());
	}
}

/*

(The MIT License)

Copyright (c) 2011 Rishi Ishairzay &lt;rishi [at] ishairzay [dot] com&gt;

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/