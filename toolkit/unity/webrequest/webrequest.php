<?php

namespace ImpossibleOdds\Unity\WebRequests;

use ImpossibleOdds\Serialization\Serializer;
use PDO;

abstract class WebRequest
{
	/**
	 * Process the incoming request and send a response back.
	 *
	 * @param WebRequest $request
	 * @return void
	 */
	public static function Process(WebRequest $request): void
	{
		// Parse the URL parameters, and if we're in a POST request, we take the POST body as well.
		Serializer::Deserialize($request, $_GET, ParsingContext::URL);

		if ($_SERVER["REQUEST_METHOD"] === WebRequestMode::Post) {
			$postData = json_decode(urldecode(file_get_contents("php://input")), true);
			Serializer::Deserialize($request, $postData, ParsingContext::Body);
		} else {
			// Perform an empty deserialization, to detect unfulfilled requirements.
			Serializer::Deserialize($request, array(), ParsingContext::Body);
		}

		$request->Response = $request->ConstructResponse();
		$request->ProcessRequest();
		$request->SendResponse();
		exit;
	}

	/**
	 * @var \ImpossibleOdds\Unity\WebRequests\WebResponse
	 */
	protected $Response;

	/**
	 * Let the request create the appropriate response object.
	 *
	 * @return WebResponse|null
	 */
	protected abstract function ConstructResponse(): ?WebResponse;

	/**
	 * Process the request's data and set the response data from here.
	 *
	 * @return void
	 */
	protected abstract function ProcessRequest(): void;

	/**
	 * Outputs the resposnse to the client by serializing the $Response
	 *
	 * @return void
	 */
	protected function SendResponse(): void
	{
		if (!is_null($this->Response)) {
			$responseData = Serializer::Serialize($this->Response);
			if (!is_null($responseData) && !empty($responseData)) {
				echo json_encode($responseData);
			}
		}
	}
}
