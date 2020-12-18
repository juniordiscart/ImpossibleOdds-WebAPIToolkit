<?php

namespace ImpossibleOdds\WebRequests;

use ImpossibleOdds\Serialization\Serializer;

abstract class UnityWebRequestMode
{
	const Get = "GET";
	const Post = "POST";
	const Put = "PUT";
}

abstract class UnityWebContexts
{
	const Headers = "headers";
	const PostBody = "post-body";
	const URL = "url";
}

abstract class UnityWebRequest
{
	/**
	 * @var \ImpossibleOdds\WebRequests\UnityWebResponse
	 */
	protected $Response;

	public function ProcessResponse(): array
	{
		$responseData = array();
		$responseData[UnityWebContexts::Headers] = Serializer::Serialize($this->Response, UnityWebContexts::Headers);
		$responseData[UnityWebContexts::PostBody] = Serializer::Serialize($this->Response, UnityWebContexts::PostBody);
		return $responseData;
	}

	/**
	 * Extracts the header-data part from the response data, and sets each of the individual headers.
	 * Reminder: headers MUST be set before outputting any other type of data on the output stream!
	 *
	 * @param array $responseData
	 * @return void
	 */
	protected function SendResponseHeaders(array $responseData): void
	{
		$headers = $responseData[UnityWebContexts::Headers];
		if (isset($headers)) {
			foreach ($headers as $key => $value) {
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
	protected function SendResponsePostData(array $responseData): void
	{
		$postData = $responseData[UnityWebContexts::PostBody];
		if (isset($postData) && !empty($postData)) {
			echo json_encode($postData);
		}
	}

	public abstract function ConstructResponse(): void;
	public abstract function IsRequestValid(): bool;
	public abstract function IsActionValid(): bool;
	public abstract function ProcessRequest(): bool;
	public abstract function SendResponseData(array $responseData): void;
	public abstract function RequiresDatabaseConnection(): bool;
}

abstract class UnityWebResponse
{
}

abstract class UnityWebRequestUtils
{
	public static function ProcessUnityWebRequest(UnityWebRequest $request): void
	{
		// Parse the URL parameters and headers, and if we're in a POST request, we take the POST body as well
		Serializer::Deserialize($request, $_GET, UnityWebContexts::URL);
		Serializer::Deserialize($request, getallheaders(), UnityWebContexts::Headers);
		if ($_SERVER["REQUEST_METHOD"] === UnityWebRequestMode::Post) {
			$postData = json_decode(urldecode(file_get_contents("php://input")), true);
			Serializer::Deserialize($request, $postData, UnityWebContexts::PostBody);
		}

		$request->ConstructResponse();
		$request->ProcessRequest();
		$request->SendResponseData($request->ProcessResponse());
		exit;
	}
}
