<?php

namespace xpl\Routing\Route;

use xpl\Routing\RouteInterface;

class Compiler 
{
	
	/**
	 * @var \xpl\Routing\Route\Tokens
	 */
	protected $tokens;
	
	/**
	 * Constructor.
	 * 
	 * @param \xpl\Routing\Route\Tokens $tokens
	 */
	public function __construct(Tokens $tokens) {
		$this->tokens = $tokens;
	}
	
	/**
	 * Compiles a route URI path.
	 * 
	 * Parses the path for tokens and replaces with regex. The matched tokens
	 * are added to the route as the "$tokens" property. The route then knows
	 * what to accept as parameters if and when it is matched.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 */
	public function compile(RouteInterface $route) {
		
		$uri = $route->getUri();
		$tokens = $search = $replace = array();
		
		if (preg_match_all('#\{(\w+)\}(\?)?#', $uri, $vars)) {
			
			foreach($vars[1] as $i => $token) {
				
				if (! $regex = $this->tokens->get($token)) {
					throw new \InvalidArgumentException("Unknown route token: '$token'.");
				}
				
				$regex = "/?({$regex})";
				
				if (! empty($vars[2][$i])) {
					// Optional parameter
					$regex .= '?';
				}
				
				$search[] = '/'.$vars[0][$i];
				$replace[] = $regex;
				$tokens[$token] = $token;
			}
			
			$uri = str_replace($search, $replace, $uri);
		}
		
		$route->setCompiledUri($uri);
		$route->setTokens($tokens);
	}
	
}
