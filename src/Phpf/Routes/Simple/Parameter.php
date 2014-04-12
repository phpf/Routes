<?php

namespace Phpf\Routes\Simple;

use SimpleXMLElement;

class Parameter {
	
	const ALPHA = 'abcdefghijklmnopqrstuvwxyz';
	
	const NUM = '0123456789';
	
	protected $allowed_chars;
	
	protected static $attribute_keys = array(
		'name', 
		'chars', 
		'min-length', 
		'max-length', 
		'case-sensitive'
	);
	
	public function __construct(array $data) {
		
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
		
		$this->determineAllowedChars();
	}
	
	public static function newFromXml(SimpleXMLElement $xml) {
		
		$data = array();
		
		foreach(static::$attribute_keys as $attr) {
			if ($val = $xml->getAttribute($attr)) {
				$data[$attr] = $val;
			}
		}
		
		$_this = new static($data);
		
		return $_this;
	}
	
	public function validate($str) {

		// Check that given string length is the same as the number of characters 
		// found in $str that match $chars (all should match, hence lengths equal).
		if ('*' !== $this->allowed_chars && strlen($str) !== strspn($str, $this->allowed_chars)) {
			return false;
		}
								
		if (isset($this->{'min-length'}) && strlen($str) < $this->{'min-length'}) {
			return false;
		}
		
		if (isset($this->{'max-length'}) && strlen($str) > $this->{'max-length'}) {
			return false;
		}
		
		return true;
	}
	
	public function isCaseSensitive() {
		if (isset($this->{'case-sensitive'})) {
			return (bool) $this->{'case-sensitive'};
		}
		return false;
	}
	
	/**
	 * Builds a string of allowed characters to use later in strspn()
	 * $allowed_chars is set with the charlist.
	 */
	protected function determineAllowedChars() {
		$chars = '';
		
		if (false === strpos($this->chars, ']')) {
			$chars .= $this->chars; // no brackets => literal charlist
		} elseif (false !== strpos($this->chars, '[alnum]')) {
			$chars .= str_replace('[alnum]', static::ALPHA . static::NUM, $this->chars);
			if (! $this->isCaseSensitive()) {
				$chars .= strtoupper(static::ALPHA);
			}
		} elseif (false !== strpos($this->chars, '[alpha]')) {
			$chars .= str_replace('[alpha]', static::ALPHA, $this->chars);
			if (! $this->isCaseSensitive()) {
				$chars .= strtoupper(static::ALPHA);
			}
		} elseif (false !== strpos($this->chars, '[numeric]')) {
			$chars .= str_replace('[numeric]', static::NUM, $this->chars);
		}
		
		$this->allowed_chars = $chars;
	}
	
}
