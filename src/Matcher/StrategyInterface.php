<?php

namespace xpl\Routing\Matcher;

use xpl\Routing\RouteInterface;

interface StrategyInterface
{
	
	public function matchRoute(RouteInterface $route);
	
}
