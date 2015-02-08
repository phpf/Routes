<?php

namespace xpl\Routing\Exception;

class MissingParameter extends \xpl\Routing\Exception {
	
	protected $missing = array();
	
	public function setMissingParams(array $params) {
		$this->missing = $params;
	}
	
	public function getMissingParams() {
		return $this->missing;
	}
}
