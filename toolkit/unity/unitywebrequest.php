<?php

namespace ImpossibleOdds\WebRequests;

use ImpossibleOdds\Serialization\Serializer;

abstract class UnityWebRequest
{
	/**
	 * Process the incoming request and send a response back.
	 *
	 * @param UnityWebRequest $request
	 * @return void
	 */
	public static function Process(UnityWebRequest $request): void
	{
		// Parse the URL parameters and headers, and if we're in a POST request, we take the POST body as well
		Serializer::Deserialize($request, $_GET, ParsingContext::URL);
		Serializer::Deserialize($request, getallheaders(), ParsingContext::Headers);
		if ($_SERVER["REQUEST_METHOD"] === UnityWebRequestMode::Post) {
			$postData = json_decode(urldecode(file_get_contents("php://input")), true);
			Serializer::Deserialize($request, $postData, ParsingContext::Body);
		}

		$request->ConstructResponse();
		$request->ProcessRequest();
		$request->SendResponse();
		exit;
	}

	/**
	 * @var \ImpossibleOdds\WebRequests\UnityWebResponse
	 */
	protected $Response;

	/**
	 * Let the request create the appropriate response object.
	 *
	 * @return void
	 */
	public abstract function ConstructResponse(): void;

	/**
	 * Process the request's data and set the response data from here.
	 *
	 * @return void
	 */
	public abstract function ProcessRequest(): void;

	/**
	 * Outputs the resposnse to the client by serializing the $Response
	 *
	 * @return void
	 */
	protected function SendResponse(): void
	{
		$responseData = $this->SerializeResponse();
		$this->SendResponseHeaders($responseData[ParsingContext::Headers]);
		$this->SendResponsePostData($responseData[ParsingContext::Body]);
	}

	/**
	 * Serializes the $Response property.
	 *
	 * @return array
	 */
	protected function SerializeResponse(): array
	{
		$responseData = array();
		$responseData[ParsingContext::Headers] = Serializer::Serialize($this->Response, ParsingContext::Headers);
		$responseData[ParsingContext::Body] = Serializer::Serialize($this->Response, ParsingContext::Body);
		return $responseData;
	}

	/**
	 * Extracts the header-data part from the response data, and sets each of the individual headers.
	 * Reminder: headers MUST be set before outputting any other type of data on the output stream!
	 *
	 * @param array $responseData
	 * @return void
	 */
	protected function SendResponseHeaders(array $headerData): void
	{
		if (isset($headerData)) {
			foreach ($headerData as $key => $value) {
				header($key . ": " . $value);
			}
		}
	}

	/**
	 * Extracts the post-body data part from the response data, converts it to JSON and sends it to the output stream.
	 *
	 * @param array $responseData
	 * @return void
	 */
	protected function SendResponsePostData(array $bodyData): void
	{
		if (isset($bodyData) && !empty($bodyData)) {
			echo json_encode($bodyData);
		}
	}
}
