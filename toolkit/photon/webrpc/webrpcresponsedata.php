<?php

namespace ImpossibleOdds\Photon\WebRpc;

$ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once($ROOT . "/vendor/autoload.php");

/**
 * Abstract class that should contain more detailed response data that will be forwarded to the player.
 */
abstract class WebRpcResponseData
{
	/**
	 * @var string
	 */
	public $ResponseId;
}
