<?php

namespace ImpossibleOdds\Photon\WebRpc;

use ImpossibleOdds\Serialization\Serializer;

$ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once($ROOT . "/vendor/autoload.php");

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
			Serializer::Deserialize($request, $postData, ParsingContext::Body);

			if (array_key_exists("RpcParams", $postData)) {
				Serializer::Deserialize($request, $postData["RpcParams"], ParsingContext::RpcParams);
			}
		}

		$request->Response = new WebRpcResponse();
		$request->Response->Data = $request->ConstructResponse();
		$request->Response->Data->ResponseId = $request->RequestId;
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
	 * @required body
	 */
	public $RequestId;

	/**
	 * @var WebRpcResponse
	 */
	protected $Response;

	/**
	 * Let the request create the appropriate response object.
	 *
	 * @return WebRpcResponseData
	 */
	protected abstract function ConstructResponse(): WebRpcResponseData;

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
