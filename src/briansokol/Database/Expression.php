<?php

namespace Database;

class Expression {

	protected $expression;

	public function __construct($expr) {
		$this->setExpression($expr);
	}
	
	public function getExpression() {
		return $this->expression;
	}
	
	public function setExpression($expr) {
		$this->expression = (String) $expr;
	}
	
	public function __toString() {
		return $this->getExpression();
	}
}