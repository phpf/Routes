<?php

namespace Phpf\Routes\Simple;

use SimpleXMLElement;

class Manager {
	
	const DEFAULT_XML_CLASS = 'XmlElement';
	
	protected $parameters = array();
	
	protected $endpoints = array();
	
	public static function loadXml($str_or_file, $class_name = self::DEFAULT_XML_CLASS, $options = 0, $ns = null, $is_prefix = false) {
		if (is_file($str_or_file) && is_readable($str_or_file)) {
			return simplexml_load_file($str_or_file, $class_name);
		} else {
			return simplexml_load_string($str_or_file, $class_name);
		}
	}
	
	public static function getCached($cache) {
		if ($cache->exists('simple-routes', 'simple-routes')) {
			$data = $cache->get('simple-routes', 'simple-routes');
			return id(new static)->import($data);
		}
		return null;
	}
		
	public static function newFromXml(SimpleXMLElement $xml, $cache = null) {
		
		$_this = new static();
		
		foreach($xml->parameter as $param) {
			$_this->parameters[$param->getAttribute('name')] = Parameter::newFromXml($param);
		}
		
		foreach($xml->endpoint as $ep) {
			$_this->endpoints[$ep->getAttribute('name')] = Endpoint::newFromXml($ep);
		}
		
		if (isset($cache)) {
			$cache->set('simple-routes', serialize($_this), 'simple-routes');
		}
		
		return $_this;
	}
	
	public function import($data) {
		foreach($data as $key => $value) {
			$this->$key = $value;
		}
		return $this;
	}
	
	public function getParameter($name) {
		return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
	}
	
	public function getEndpoint($name) {
		return isset($this->endpoints[$name]) ? $this->endpoints[$name] : null;
	}
	
	public function serialize() {
		return serialize(array(
			'parameters' => $this->parameters,
			'endpoints' => $this->endpoints,
		));
	}
	
	public function unserialize($serialized) {
		$this->import(unserialize($serialized));
	}
	
}
