# ![Impossible Odds Logo][Logo] Photon WebRPC Server-side Tools

Exit Games' Photon asset/framework, is a powerful tool for Unity developers to easily implement multiplayer in their games. One of its features is to communicate securely with a web server through Webhooks and WebRPCs. The game can make a request to your server, and while it passes through the Photon network, it will append additional information about the player making the request so that you are absolutely sure it's coming from an authenticated and validated client. This way, you can easily and securely implement features that need persistent data such as leaderboards, player progression, etc.

To get started, the `ImpossibleOdds\Photon\WebRpc` namespace contains all the tools related to Photon's WebRPC feature.

## WebRPC Requests

To implement a custom request, start by checking out the `WebRpcRequest` class. It has a few predefined fields already that are shipped by default by Photon:

* `$AppId`: The app ID of the game or application, as registered in the Photon application dashboard. This allows you to check whether the request is originating from a legitimate source.
* `$AppVersion`: The version set by the game or application when connecting to Photon.
* `$Region`: The region the player is connected to.
* `$UserId`: The ID of the user as set during authentication of the player.

Besides the fields above, there's one other field which originates from the [Impossible Odds - Photon extension tools][ImpossibleOddsPhotonTools]:

* `$RequestId`: the ID of the request. This is copied linea recta and returned in the response so that the client-side can match the response.

To create your custom request, simply inherit from the `WebRpcRequest` class and implement its abstract methods:

```php
/**
 * A custom WebRPC request.
 */
class MyWebRpcRequest extends WebRpcRequest
{
	/**
	 * Create and return the response data container.
	 */
	protected function ConstructResponse(): ?WebRpcResponseData
	{
		return new MyWebRpcResponseData();
	}

	/**
	 * Perform the actual request actions, e.g. connect to database,
	 * update the user score, etc.
	 */
	protected function ProcessRequest(): void
	{ }
}
```

**Note**: Photon can also send over an optional [AuthCookie][PhotonAuthCookie] object, but its structure is dependent on what player values you want to save or share. So no default implementation is provided. If you want to include the AuthCookie, simply add a public `$AuthCookie` field to your custom request class with the appropriate `@var` annotation, and it will be accessible for you to use.

**Another note**: if your request is a _fire&forget_-kind of request without a response, your request can also just return null in the `ConstructResponse()` function.

### Request Parsing Contexts

A single request can contain multiple different sources of data that are relevant for processing, e.g. URL parameters, POST-body, etc. When applying the data onto your request object, you may want to state that some fields are required to be present, otherwise the request is considered invalid. But, since multiple sources can apply data on the same object, the required fields may not be present in each source. That's where a 'parsing context' comes into play. When applying data from a specific source, the parsing context on the serializer is set, and required values will only throw an error when it is missing during that particular parsing context.

The data sources available for Photon's WebRPC feature are detailed below:

* `url`: parameters embedded in the URL of the request can be set using this context.
* `body`: this is data directly from the POST-body of the request.
* `rpcparams`: depending on how the request was structured on the client, Photon may deliver the data in a `RpcParams` sub-value of the POST-body. When this field is detected to be there, it is extracted and applied specifically under this context.

Check out the [Serializer's `@required` and `@context` annotations][Serializer] what a parsing context means exactly and the [Photon WebRPC request][PhotonWebRPCRequestFormat] documentation on how they all relate.

## WebRPC Response Data

Photon's WebRPC feature expects a response in a predefined format. Check out [this link][PhotonWebRPCResponseFormat] for more information about the response format.

To reduce the amount of boilerplate-code, this is already defined in the `WebRpcResponse` class, and it's not expected that you change or inherit from this class. That's also why it's declared `final`.

On this response object you'll find the following public fields:

* `$ResultCode`: the result code of the request being processed. This is initialized with a default value of 0, which signals to Photon that the request was processed correctly. Set this value to something else in your game or application to indicate that an error has occurred.
* `$Message`: a debug message for the application or game that receives the response.
* `$Data`: a field that can be set with custom response data.

This `$Data` field however, is where the real meat of the response is supposed to be. It is assumed to be of the type `WebRpcResponseData` and can be inherited from to provide custom responses per request. That's why the request itself is best suited to create a _response data_ object as it knows what it will do and return to the client or player.

```php
class MyWebRpcResponseData extends WebRpcResponseData
{
	// Define any public fields that should be sent back to the client here
	// and set their values in the ProcessRequest method of the request object.
}
```

After the request is done processing, your WebRPC response data will get serialized to be sent to the client again.

### Response Parsing Contexts

Contrary to the multiple different sources of data for a request, a response only has a single output channel that is available for the client to access: the response's POST body. This is why there's no parsing context set when the response data is being serialized. So by default, every public field defined in your custom `WebRpcResponseData` object will get serialized unless annotated with `@ignore`.

## Execution Pipeline

Now that you've defined your request and response, a single call is necessary to fire up the whole process:

```php
// Start the request-to-response process.
WebRpcRequest::Process(new MyWebRpcRequest());
```

This will start the whole WebRPC request-to-response pipeline, including fecting URL query parameters, POST-body parsing, invoking the `ProcessRequest()` function and creating and sending the response data.

## Examples

I highly suggest taking a look at the `updateleader.php` file as an example that is used to communicate in the [Impossible Odds - Photon extension tools][ImpossibleOddsPhotonTools] demo application.

[Logo]: ./images/ImpossibleOddsLogo.png
[Serializer]: ./Serializer.md
[ImpossibleOddsPhotonTools]: https://github.com/juniordiscart/ImpossibleOdds-PhotonExtensions
[PhotonWebRPC]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webrpc
[PhotonAuthcookie]: https://doc.photonengine.com/en-US/realtime/current/connection-and-authentication/authentication/custom-authentication
[PhotonWebRPCRequestFormat]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webrpc#request
[PhotonWebRPCResponseFormat]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webrpc#response
