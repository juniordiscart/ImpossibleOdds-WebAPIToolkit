# ![Impossible Odds Logo][Logo] Unity Web Request Server-side Tools

Unity provides a way of communicating with web servers through its `UnityWebRequest` API. It allows the client/player to send out several types of requests to a web server, much like the actions you can do through a web browser, e.g. update or post data, or download resources such as images/textures, audio or other types of assets.

Perhaps also useful when you're using Unity is this [C# Toolkit for Unity][ImpossibleOddsCSharpToolkit]. It can speed up development on the client-side with its HTTP assembly and contains an intuitive messenger-tool for easily setting up web request objects and process incoming responses.

All of the scripts related to this framework can be found in the `ImpossibleOdds\Unity\WebRequests` namespace.

## Requests

Creating custom handlers for a request is done by extending the `WebRequest` class. This class in and of itself doesn't do very much except for providing a `$Response` field for you to set the data you'd like to send in a response back to the client as well as several functions that define the pipeline of processing a request to a response:

* `ConstructResponse()` is an abstract function that is required to be implemented in your custom request classes, and asks you to return an instance of the response that will be sent back.
* `ProcessRequest()` is also an abstract function that is required to be implemented in your custom request classes, and asks you to perform the different actions that should be taken in this request, e.g. update a leaderboard, or prepare a resource for download, etc.
* `SendResponse()` is a protected function that will serialize the response object. You can override this in case you want something custom to happen when sending over the data. By default though, it will serialize your $Response object and send it as the body of the response to the client.

```php
/**
 * A custom web request object.
 */
class MyWebRequest extends WebRequest
{
	/**
	 * A example value that is included in the request data.
	 * @var int
	 * @required body
	 */
	public $UserId;

	protected function ConstructResponse() : ?WebResponse
	{
		// Create and return an instance of the intended response.
	}

	protected function ProcessRequest() : void
	{
		// Process the request and set any response values.
	}
}
```

### Request Deserialization

A single request can contain multiple different sources of data that are relevant for processing, e.g. URL parameters and POST-body. All of them are combined into a single request object using, what is called, [_parsing contexts_][SerializerParsingContexts] of the `Serializer`.

The following parsing contexts apply for web requests:

* `url`: parameters embedded in the URL of the request.
* `header`: parameters embedded in the header of the request.
* `body`: when the request is of type POST, it will perform a `json_decode` operation on the body and apply these values on the request object under this parsing context name.

Check out the [Serializer's documentation][Serializer] for full details on supported annotations and how  parsing contexts work.

## Responses

When your request is being processed and is expecting to send back a result to the client, it should construct a response object. This object should inherit from the `WebResponse` class. It doesn't do anything special, really... You can populate it with whatever data is necessary.

```php
class MyWebResponse extends WebResponse
{
	/**
	 * @var int
	 */
	public $ResultCode;
}
```

When a response is sent back to the client, it is serialized to JSON using the `Serializer` class and its result is put as the body of the response. By default, it will pick up every public value of your response object. Protected and private values cannot be serialized. However, if you also have public values you don't want to include in your result, you can annotate them with `@ignore`. This will instruct the serializer to exclude the value from the result.

### Advanced

In cases where the response is not simple result or strutured data, but rather a blob like an image or audio asset, you may want to customize the actual sending of the response. The `SendResponse()` function can be overridden in your request class and provide a custom or alternative way of sending data back to the client. E.g. you can setup a gzip stream with multiple files and unzip them on the client again.

## Execution Pipeline

When you've defined the data structure of your request and response, perform the following call to start the whole process:

```php
// Start the request-to-response process.
WebRequest::Process(new MyCustomRequest());
```

This will start the whole request-to-response pipeline. At first, the URL parameters are parsed and applied to the request object, if any. This happens in the `url` parsing context. Next, POST-body data is read and parsed to an array. This data is applied under the `body` parsing context.

Next up, the creation of the response object by calling the `ConstructResponse()` function. This function should be implemented by the inheriting request class, as it knows best what kind of a response will be sent back. Note that it is merely instantiating the response object.

After creating the response object, the request is actually processed by calling the `ProcessRequest()` function. Here, it is expected that it acts upon the data of the request to further fill in any response data, including any error information that might be of interest for the client to show or act upon.

And finally, assuming the response object now contains all of the necessary data, it is converted to JSON and sent to the client.

## Examples

The `getleaderboard.php` script is an example of how you might want to implement custom requests and responses. The [Impossible Odds - Http example scene][ImpossibleOddsCSharpToolkit] contains a demo scene that can serve as the client-side.

[Logo]: ./images/ImpossibleOddsLogo.png
[Serializer]: ./Serializer.md
[SerializerParsingContexts]: ./Serializer.md#parsing-contexts
[SerializerAnnotations]: ./Serializer.md#annotations
[SerializerGotchas]: ./Serializer.md#gotchas
[ImpossibleOddsCSharpToolkit]: https://www.impossible-odds.net/csharp-toolkit/
