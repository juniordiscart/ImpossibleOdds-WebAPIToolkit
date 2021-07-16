# ![Impossible Odds Logo][Logo] Photon WebRPC Server-side Tools

Photon is an asset/framework by Exit Games GmbH, a powerful tool for Unity developers to easily implement multiplayer into their games. One of its features is to communicate with a custom web server through [Webhooks][PhotonWebhooks] and [WebRPC][PhotonWebRPC]. The game can make a request to your server, and while it passes through Photon's network, it will append additional information about the player so that you are absolutely sure it's coming from an authenticated and validated client. This way, you can easily and securely implement features that need persistent data such as leaderboards, player progression, etc.

Perhaps also useful when you're using Unity in combination with Photon is this [C# Toolkit for Unity][ImpossibleOddsCSharpToolkit]. It can speed up development on the client-side with its Photon assembly and contains an intuitive messenger-tool for easily setting up WebRPC request objects and process incoming responses.

To get started, the `ImpossibleOdds\Photon\WebRpc` namespace contains all the tools related to Photon's WebRPC feature.

## WebRPC Requests

To implement a custom request, start by checking out the `WebRpcRequest` class. It has a few predefined fields already that are shipped by default by Photon:

* `$AppId`: The app ID of the game or application, as registered in the Photon application dashboard. This allows you to check whether the request is originating from a legitimate source.
* `$AppVersion`: The version set by the game or application when connecting to Photon.
* `$Region`: The region the player is connected to.
* `$UserId`: The ID of the user as set during authentication of the player.

Besides the fields above, there's one other field which originates from the [Photon tools in the C# Toolkit for Unity][ImpossibleOddsCSharpToolkit]:

* `$RequestId`: the ID of the request. This is copied linea recta and returned in the response so that the client-side can match the response as Photon itself does not keep track of which operations receive a response.

To create your custom request, simply inherit from the `WebRpcRequest` class and implement its abstract methods:

```php
/**
 * A custom WebRPC request.
 */
class MyWebRpcRequest extends WebRpcRequest
{
	/**
	 * A example value that is included in the request data.
	 * @var int
	 * @required body
	 */
	public $UserId;

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

**Note**: Photon can also send over an optional [AuthCookie][PhotonAuthCookie] object, but its structure is dependent on what values you set during authentication. So no default implementation is provided. If you want to include the AuthCookie, simply add a public `$AuthCookie` field to your request class with the appropriate `@var` annotation, and it will be accessible for you to use.

**Another note**: if your request is a _fire&forget_-kind of request without a response, your request can also just return null in the `ConstructResponse()` function.

### Request Parsing Contexts

A single request can contain multiple different sources of data that are relevant for processing, e.g. URL parameters and POST-body. All of them are combined into a single request object using, what is called, [_parsing contexts_][SerializerParsingContexts] of the `Serializer`.

The following data sources are available for Photon's WebRPC feature along with their parsing context name:

* `url`: parameters embedded in the URL of the request can be set using this context.
* `body`: this is data directly from the POST-body of the request.
* `rpcparams`: depending on how the request was structured on the client, Photon may deliver the data in an embedded `RpcParams` value of the POST-body. When this field is detected to be there, it is extracted and applied specifically under this parsing context name.

Check out the [Serializer's documentation][Serializer] for full details on supported annotations and how  parsing contexts work. Also check out the [Photon WebRPC request][PhotonWebRPCRequestFormat] documentation in what format you can expect data to flow in.

## WebRPC Response Data

Photon's WebRPC feature expects a response in a predefined format. Check out [photon WebRPC response][PhotonWebRPCResponseFormat] documentation for more details on the format itself.

To reduce the amount of boilerplate-code, this is already defined in the `WebRpcResponse` class, and it's not expected that you change or inherit from this class. That's also why it's declared `final`.

On this response object you'll find the following public fields and you can set them as you like:

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

Contrary to the multiple different sources of data for a request, a response only has a single output channel that is available for the client to access: the response's POST-body. So by default, every public field defined in your custom `WebRpcResponseData` object will get serialized unless annotated with `@ignore`.

## Execution Pipeline

Now that you've defined your request and response, all that is left is a single call to fire up the whole process:

```php
// Start the request-to-response process.
WebRpcRequest::Process(new MyWebRpcRequest());
```

This will start the whole WebRPC request-to-response pipeline. At first, the URL parameters are parsed and applied to the request object, if any. This happens in the `url` parsing context. Next, POST-body data is read and parsed to an array. This data is checked for the `RpcParams` value, and when present, extracted from the POST-body data temporarily. The left-over data is applied to your request object under the `body` parsing context, and after that the `RpcParams` value under the `rpcparams` parsing context.

This is followed up by the creation of the response object by calling the `ConstructResponse()` function. This function should be implemented by the inheriting request class, as it knows best what kind of a response will be sent back. Note that it is merely instantiating the response object.

After creating the response object, the request is actually processed by calling the `ProcessRequest()` function. Here, it is expected that it acts upon the data of the request to further fill in any response data, including any error information that might be of interest for the client to show or act upon.

And finally, assuming the response object now contains all of the necessary data, it is converted to JSON and sent to the client.

## Examples

Take a look at the `updateleader.php` script as an example that is used as an endpoint in the [Impossible Odds - Photon extensions example scene][ImpossibleOddsCSharpToolkit] demo application.

[Logo]: ./images/ImpossibleOddsLogo.png
[Serializer]: ./Serializer.md
[SerializerParsingContexts]: ./Serializer.md#parsing-contexts
[ImpossibleOddsCSharpToolkit]: https://www.impossible-odds.net/csharp-toolkit/
[PhotonWebhooks]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webhooks
[PhotonWebRPC]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webrpc
[PhotonAuthcookie]: https://doc.photonengine.com/en-US/realtime/current/connection-and-authentication/authentication/custom-authentication
[PhotonWebRPCRequestFormat]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webrpc#request
[PhotonWebRPCResponseFormat]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webrpc#response
