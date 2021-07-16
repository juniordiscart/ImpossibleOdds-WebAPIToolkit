<?php

namespace ImpossibleOdds\Photon\WebRpc;

use ImpossibleOdds\Serialization\Serializer;

/**
 * Abstract request class that represents incoming Photon WebRPC requests.
 * It has all default parameters sent by Photon defined already.
 */
abstract class WebRpcRequest
{
	/**
	 * Process the incoming request and send a response back.
	 *
	 * @param WebRpcRequest $request
	 * @return void
	 */
	public static function Process(WebRpcRequest $request): void
	{
		Serializer::Deserialize($request, $_GET, ParsingContext::URL);

		$postData = json_decode(urldecode(file_get_contents("php://input")), true);

		if (isset($postData)) {

			// Extract a separate RpcParams value.
			$rpcParams = null;
			if (array_key_exists("RpcParams", $postData)) {
				$rpcParams = $postData["RpcParams"];
				unset($postData["RpcParams"]);	// Remove the value so that it doesn't get processed during the 'body' parsing context.
			}

			// Apply an empty array in this case for required value validation.
			if (is_null($rpcParams)) {
				$rpcParams = array();
			}

			Serializer::Deserialize($request, $postData, ParsingContext::Body);
			Serializer::Deserialize($request, $rpcParams, ParsingContext::RpcParams);
		} else {
			// When no post data is set, apply an empty array to check for potential required values.
			Serializer::Deserialize($request, array(), ParsingContext::Body);
			Serializer::Deserialize($request, array(), ParsingContext::RpcParams);
		}

		// Create a response, and set it's response ID so that it may be properly matched in Unity again.
		$request->Response = new WebRpcResponse();
		$request->Response->Data = $request->ConstructResponse();

		if (!is_null($request->Response->Data)) {
			$request->Response->Data->ResponseId = $request->RequestId;
		}

		$request->ProcessRequest();
		$request->SendResponse();
		exit;
	}

	/**
	 * @var string
	 * @required body
	 */
	public $AppId;
	/**
	 * @var string
	 * @required body
	 */
	public $AppVersion;
	/**
	 * @var string
	 * @required body
	 */
	public $Region;
	/**
	 * @var string
	 * @required body
	 */
	public $UserId;
	/**
	 * @var string
	 */
	public $RequestId;

	/**
	 * @var WebRpcResponse
	 */
	protected $Response;

	/**
	 * Let the request create the appropriate response object.
	 *
	 * @return WebRpcResponseData|null
	 */
	protected abstract function ConstructResponse(): ?WebRpcResponseData;

	/**
	 * Process the request's data and set the response data from here.
	 *
	 * @return void
	 */
	protected abstract function ProcessRequest(): void;

	/**
	 * Outputs the resposnse to the client by serializing the Response property.
	 *
	 * @return void
	 */
	protected function SendResponse(): void
	{
		$responseData = Serializer::Serialize($this->Response/* , ParsingContext::Body */);
		if (isset($responseData) && !empty($responseData)) {
			echo json_encode($responseData);
		}
	}
}
