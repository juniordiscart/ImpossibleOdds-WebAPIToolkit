<?php

namespace ImpossibleOdds\Serialization;

use Exception;
use ReflectionClass;
use ReflectionProperty;

/**
 * Exception class to denote that a property that is marked as required, was not found in the source data.
 */
class RequiredPropertyException extends Exception
{
	/**
	 * Displays a message that required property $rp was not found in class $rc.
	 *
	 * @param ReflectionClass $rc
	 * @param ReflectionProperty $rp
	 */
	public function __construct(ReflectionClass $rc, ReflectionProperty $rp)
	{
		parent::__construct("Property " . $rp->name . " of class " . $rc->name . " is marked as required, but no match could be made.");
	}
}
