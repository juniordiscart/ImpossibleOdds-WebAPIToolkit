<?php

namespace ImpossibleOdds\Photon\WebRpc;

$ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once($ROOT . "/vendor/autoload.php");

/**
 * Base response class with the data Photon expects to be returned by default.
 * Any custom response data should assigned to the WebRpcResponse::Data field.
 */
class WebRpcResponse
{
	/**
	 * The result code to indicate success or failure.
	 * A value of 0 indicates success. Any other value indicates an error.
	 * @var int
	 */
	public $ResultCode;
	/**
	 * A debug message that can be included in the response.
	 * @var string
	 */
	public $Message;
	/**
	 * The response data that is being sent back.
	 * @var \ImpossibleOdds\WebRpc\WebRpcResponseData
	 * @required
	 */
	public $Data;

	public function __construct()
	{
		// Start with a result code that is OK.
		$this->ResultCode = 0;
	}
}
