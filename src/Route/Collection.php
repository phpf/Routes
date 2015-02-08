<?php

namespace xpl\Routing\Route;

use xpl\Routing\RouteInterface as Route;
use xpl\Common\Storage\Collection as BaseCollection;

class Collection extends BaseCollection
{
	
	/**
	 * @var \xpl\Routing\Route\Tokens
	 */
	protected $tokens;
	
	/**
	 * @var \xpl\Routing\Route\Compiler
	 */
	protected $compiler;
	
	/**
	 * @var \xpl\Routing\Route\Generator
	 */
	protected $generator;
	
	/**
	 * @var boolean
	 */
	protected $prepend;
	
	/**
	 * Sets the tokens to use for routes in the collection.
	 * 
	 * @param \xpl\Routing\Route\Tokens $tokens
	 */
	public function setTokens(Tokens $tokens) {
		$this->tokens = $tokens;
	}
	
	/**
	 * Sets the compiler used to compile routes.
	 * 
	 * @param \xpl\Routing\Route\Compiler $compiler
	 */
	public function setCompiler(Compiler $compiler) {
		$this->compiler = $compiler;
	}
	
	/**
	 * Sets the route generator.
	 * 
	 * @param \xpl\Routing\Route\Generator $generator
	 */
	public function setGenerator(Generator $generator) {
		$this->generator = $generator;
	}
	
	/**
	 * Returns the tokens instance for this collection.
	 * 
	 * @return \xpl\Routing\Route\Tokens
	 */
	public function getTokens() {
		if (! isset($this->tokens)) {
			$this->tokens = new Tokens();
		}
		return $this->tokens;
	}
	
	/**
	 * Returns the route compiler for this collection.
	 * 
	 * @return \xpl\Routing\Route\Compiler
	 */
	public function getCompiler() {
		if (! isset($this->compiler)) {
			$this->compiler = new Compiler($this->getTokens());
		}
		return $this->compiler;
	}
	
	/**
	 * Returns the route generator for this collection.
	 * 
	 * @return \xpl\Routing\Route\Generator
	 */
	public function getGenerator() {
		if (! isset($this->generator)) {
			$this->generator = new Generator($this);
		}
		return $this->generator;
	}
	
	/**
	 * Adds a route to the collection.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 * @return void
	 */
	public function add(Route $route) {
		if (true === $this->prepend) {
			$this->unshift($route);
		} else {
			$this->push($route);
		}
	}
	
	/**
	 * Sets the collection to prepend routes when added.
	 * 
	 * @return void
	 */
	public function prepend() {
		$this->prepend = true;
	}
	
	/**
	 * Sets the collection to append routes when added.
	 * 
	 * @return void
	 */
	public function append() {
		$this->prepend = false;
	}
	
}
