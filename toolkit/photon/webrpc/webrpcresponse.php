<?php

namespace ImpossibleOdds\Photon\WebRpc;

/**
 * Base response class with the data Photon expects to be returned by default.
 * Any custom response data should assigned to the WebRpcResponse::Data field.
 */
final class WebRpcResponse
{
	/**
	 * The result code to indicate success or failure.
	 * A value of 0 indicates success. Any other value indicates an error.
	 * @var int
	 */
	public $ResultCode = 0;	// Signals that the request was processed correctly by default.
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
}
