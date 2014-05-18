<?php

namespace Phpf\Route;

class RouteException extends \RuntimeException {
	
	public $statusCode;
	
	public $allowed;
	
	public $message;
	
	/**
	 * Appends a string to the message.
	 */
	public function appendToMessage($str) {
		$this->message .= "\r\n" . $str;
	}
	
	/**
	 * Prepends a string to the message.
	 */
	public function prependToMessage($str) {
		$this->message = $str . "\r\n" . $this->message;
	}
	
	/**
	 * Sets the HTTP status code that should be used in response.
	 */
	public function setStatusCode($code) {
		$this->statusCode = $code;
	}
	
	public function hasStatusCode() {
		return isset($this->statusCode);
	}
	
	public function getStatusCode() {
		return $this->statusCode;
	}
	
	/**
	 * Sets the values that would be allowed (i.e. not trigger this error).
	 * @var mixed $allowed
	 */
	public function setAllowedValues($allowed) {
		$this->allowed = $allowed;
	}
	
	public function hasAllowedValues() {
		return isset($this->allowed);
	}
	
	public function getAllowedValues() {
		return $this->allowed;
	}
	
	public function __toString() {
		return $this->getMessage();
	}
		
}
