<?php

namespace xpl\Routing;

/**
 * Route describes a request destination.
 */
interface RouteInterface extends \Serializable
{
	
	/**
	 * Returns a unique ID for the route.
	 * 
	 * @return string
	 */
	public function getID();
	
	/**
	 * Returns the route HTTP method.
	 * 
	 * @return string
	 */
	public function getMethod();
	
	/**
	 * Returns the route action.
	 * 
	 * Leaving this somewhat vague allows the implementation to require, or not, 
	 * that the action actually be a callable.
	 * 
	 * @return mixed
	 */
	public function getAction();
	
	/**
	 * Returns the route URI with token placeholders.
	 * 
	 * @return string
	 */
	public function getUri();
	
	/**
	 * Sets the compiled URI, which replaces each token placeholder with its regex pattern.
	 * 
	 * @param string $compiled_uri The compiled URI string.
	 */
	public function setCompiledUri($compiled_uri);
	
	/**
	 * Returns the compiled URI.
	 * 
	 * @return string
	 */
	public function getCompiledUri();
	
	/**
	 * Returns whether the compiled URI has been set.
	 * 
	 * @return boolean
	 */
	public function isCompiled();
	
	/**
	 * Sets the tokens used by the route.
	 * 
	 * @param array $tokens
	 */
	public function setTokens(array $tokens = array());
	
	/**
	 * Returns the tokens used by the route.
	 * 
	 * @return array
	 */
	public function getTokens();
	
	/**
	 * Sets the matched route parameters.
	 * 
	 * Parameters are simply the tokens with values from the request URI.
	 * 
	 * @param array $params
	 */
	public function setParams(array $params = array());
	
	/**
	 * Returns the matched route parameters.
	 * 
	 * @return array
	 */
	public function getParams();
	
}
