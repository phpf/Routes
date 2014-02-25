<?php
/**
 * @package Phpf.Routes
 * @subpackage Response
 */

namespace Phpf\Routes;

use Phpf\Util\Http;

class Response {
	
	const DEFAULT_CONTENT_TYPE = 'text/html';
	
	protected $statusCode;
	
	protected $contentType;
	
	protected $allowedContentTypes = array(
		'html' => 'text/html',
		'json' => 'application/json',
		'jsonp' => 'text/javascript',
		'xml' => 'text/xml',
	);
	
	protected $headers = array();
	
	protected $body;
	
	protected $gzip;
	
	protected static $_instance;
	
	public static function i(){
		if ( ! isset( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	/**
	 * Send the response headers and body.
	 */
	public function send(){
			
		if ( ! isset($this->statusCode) )
			$this->statusCode = 200; // assume success
		
		// Status header
		$status_header = Http::getStatusHeader($this->statusCode);
		header($status_header, true, $this->statusCode);
		
		// Content-Type header
		header($this->getContentTypeHeader());
		
		if ( ! isset( $this->headers['Cache-Control'] ) ){
			$this->nocache();
		}
		
		// Rest of headers
		foreach( $this->headers as $name => $value ){
			@header( "$name: $value", true );
		}
		
		// Output the body
		ob_start();
		
		echo $this->body;
		
		ob_end_flush();
		
		exit;
	}
	
	/**
	 * Sets up Response, grabbing a few properties from Request.
	 */
	public function init(){
		
		$_this = self::i();
		$request = Request::i();
		
		if ( isset( $request->headers['accept-encoding'] ) 
			&& false !== strpos( $request->headers['accept-encoding'], 'gzip' ) 
			&& extension_loaded('zlib') )
		{
			$_this->gzip = true;
		} else {
			$_this->gzip = false;
		}
		
		if ( $request->isXhr() ){
			$_this->nocache();
		}
		
		if ( isset($request->contentType) && $_this->maybeSetContentType( $request->contentType ) ) {
			return;
		} elseif ( isset($request->headers['accept']) ){
			foreach ( explode(',', $request->headers['accept']) as $type ){
				if ( $_this->maybeSetContentType($type) ){
					return;
				}
			}
		}
	}
	
	/**
	 * Sets the body content.
	 */
	public function setBody( $value, $overwrite = true ){
			
		if ( $overwrite || empty($this->body) ){
			
			if ( is_object($value) ){
				$value = $this->objectToString($value);
			}
			
			$this->body = $value;
		}
		
		return $this;
	}
	
	/**
	 * Adds content to the body.
	 */
	public function addBody( $value, $where = 'append' ){
			
		if ( is_object($value) ){
			$value = $this->objectToString($value);
		}
		
		if ( 'prepend' === $where || 'before' === $where ){
			$this->body = $value . $this->body;
		} else {
			$this->body .= $value;
		}
		
		return $this;
	}
	
	/**
	 * Sets the HTTP response status code.
	 */
	public function setStaus( $code ){
		$this->statusCode = (int) $code;
		return $this;
	}
	
	/**
	 * Sets the content type.
	 */
	public function setContentType( $type ){
		$this->contentType = $type;
		return $this;
	}
	
	/**
	 * Returns true if given response content-type/media type is allowed.
	 */
	function isContentTypeAllowed( $type ){
		return isset($this->allowedContentTypes[ $type ]);	
	}
	
	/**
	 * Sets $content_type, but only if $type is a valid content-type.
	 */
	public function maybeSetContentType( $type ){
		
		if ( $this->isContentTypeAllowed($type) ){
			$this->setContentType($type);
			return true;
		} elseif ( in_array($type, $this->allowedContentTypes) ){
			$this->setContentType( array_search($type, $this->allowedContentTypes) );
			return true;
		}
		
		return false;
	}
	
	/**
	 * Sets a header. Replaces existing by default.
	 */
	public function setHeader( $name, $value, $overwrite = true ){
		
		if ( $overwrite || ! isset( $this->headers[ $name ] ) ){
			$this->headers[ $name ] = $value;
		}
		
		return $this;
	}
	
	/**
	 * Adds a header. Does not replace existing.
	 */
	public function addHeader( $name, $value ){
		return $this->setHeader( $name, $value, false );
	}
	
	/**
	 * Returns array of currently set headers.
	 */
	 public function getHeaders(){
	 	return $this->headers;
	 }
	
	/**
	 * Sets the various cache headers.
	 * 
	 * @param int|bool $expires_offset The offset in seconds to cache. Pass 0 or false for no cache.
	 */
	public function setCacheHeaders( $expires_offset = DAY_IN_SECONDS ){
		
		if ( ! $expires_offset || empty($expires_offset) ){
				
			$expires = 'Thu, 19 Nov 1981 08:52:00 GMT';
			$pragma = 'no-cache';
			$control = 'no-cache, must-revalidate, max-age=0';
			
			header_remove('Last-Modified');
			
			if ( isset( $this->headers['Last-Modified'] ) )
				unset( $this->headers['Last-Modified'] );
			
		} else {
			$control = "Public, max-age=$expires_offset";
			$expires = gmdate('D, d M Y H:i:s', time() + $expires_offset) . ' GMT';
		}
		
		$this->setHeader('Expires', $expires);
		$this->setHeader('Cache-Control', $control);
		
		if ( isset($pragma) )
			$this->setHeader('Pragma', $pragma);
		
		return $this;
	}
	
	/**
	 * Sets the "X-Frame-Options" header. Default is 'SAMEORIGIN'.
	 */
	public function setFrameOptionsHeader( $value = 'SAMEORIGIN' ){
			
		switch( $value ){
		
			case 'SAMEORIGIN':
			case 'sameorigin':
			case true:
			default:
				$value = 'SAMEORIGIN';
				break;
				
			case 'DENY':
			case 'deny':
			case false:
				$value = 'DENY';
				break;
		}
		
		return $this->setHeader('X-Frame-Options', $value);
	}
	
	/**
	 * Sets the "X-Content-Type-Options" header. Default is 'nosniff'.
	 */
	public function setContentTypeOptionsHeader( $value = 'nosniff' ){
		return $this->setHeader('X-Content-Type-Options', $value);
	}
	
	/**
	 * Sets no cache headers.
	 */
	public function nocache(){
		return $this->setCacheHeaders(false);
	}
	
	/**
	 * Sets 'X-Frame-Options' header to 'DENY'.
	 */
	public function denyIframes(){
		return $this->setFrameOptionsHeader('DENY');
	}
	
	/**
	 * Sets 'X-Frame-Options' header to 'SAMEORIGIN'.
	 */
	public function sameoriginIframes(){
		return $this->setFrameOptionsHeader('SAMEORIGIN');
	}
	
	/**
	 * Sets 'X-Content-Type-Options' header to 'nosniff'.
	 */
	public function nosniff(){
		return $this->setContentTypeOptionsHeader('nosniff');
	}
	
	/**
	 * Alias for setBody()
	 * @see Request\Response::setBody()
	 */
	public function setContent( $value ){
		return $this->setBody($value);
	}
	
	/**
	 * Alias for addBody()
	 * @see Request\Response::addBody()
	 */
	public function addContent( $value, $where = 'append' ){
		return $this->addBody($value, $where);
	}
	
	protected function objectToString( $value ){
			
		try {
			$value = $value->__toString();
		} catch (Exception $e){
			trigger_error("Cannot set Response body - object passed with no __toString() method. Error message: $e->getMessage().");
			return null;
		}
	
		return $value;
	}
	
	/**
	 * Returns string for the 'Content-Type' header.
	 */
	protected function getContentTypeHeader(){
		
		if ( isset($this->contentType) && $this->isContentTypeAllowed($this->contentType) ){
			$type = $this->allowedContentTypes[ $this->contentType ];
		} else {
			$type = self::DEFAULT_CONTENT_TYPE;
		}
		
		$charset = CHARSET;
		
		return "Content-Type: $type; charset=$charset";
	}
	
}

