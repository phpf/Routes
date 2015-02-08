<?php

namespace xpl\Routing;

/**
 * Router maps a request to a route.
 */
class Router
{
	
	/**
	 * The collection of routes.
	 * 
	 * @var \xpl\Routing\Route\Collection
	 */
	protected $collection;
	
	/**
	 * The route matching strategy.
	 * 
	 * @var \xpl\Routing\Matcher\StrategyInterface
	 */
	protected $strategy;
	
	/**
	 * The matched route.
	 * 
	 * @var \xpl\Routing\RouteInterface
	 */
	protected $match;
	
	/**
	 * Construct the router with a route collection.
	 * 
	 * @param \xpl\Routing\Route\Collection $collection
	 */
	public function __construct(Route\Collection $collection) {
		$this->collection = $collection;
	}
	
	/**
	 * Returns the route collection.
	 * 
	 * @return \xpl\Routing\Route\Collection
	 */
	public function getCollection() {
		return $this->collection;
	}
	
	/**
	 * Sets the matcher strategy.
	 * 
	 * @param \xpl\Routing\Matcher\StrategyInterface $strategy
	 */
	public function setStrategy(Matcher\StrategyInterface $strategy) {
		$this->strategy = $strategy;
	}
	
	/**
	 * Returns the matcher strategy.
	 * 
	 * @return \xpl\Routing\Matcher\StrategyInterface
	 * @throws \RuntimeException if a strategy is not set.
	 */
	public function getStrategy() {
		
		if (isset($this->strategy)) {
			return $this->strategy;
		}
		
		throw new \RuntimeException("No route matcher strategy set.");
	}
	
	/**
	 * Whether a matcher strategy has been set.
	 * 
	 * @return boolean
	 */
	public function hasStrategy() {
		return isset($this->strategy);
	}
	
	/**
	 * Finds a matching route.
	 * 
	 * @return boolean True if a match was found, otherwise false.
	 */
	public function __invoke() {
		
		$strategy = $this->getStrategy();
		
		foreach($this->collection as $route) {
			
			if (! $route->isCompiled()) {
				$this->collection->getCompiler()->compile($route);
			}
			
			if ($strategy->matchRoute($route)) {
				
				$this->match = $route;
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Returns the matched route, if any.
	 * 
	 * @return \xpl\Routing\Route|null
	 */
	public function getMatch() {
		return isset($this->match) ? $this->match : null;
	}
		
}
