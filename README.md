# ![Impossible Odds Logo][Logo] Impossible Odds - PHP Web API Toolkit

Enriching your game or application with a server-side component can be a daunting task. Saving data and retrieving resources are essential operations. This Web API toolkit hopes to provide a few ways for your game or application to communicate with your web services in an easy and secure way.

## Serialization

Serialization plays a huge part in what makes the other tools, found in this package, tick. The data provided in requests and sent back in responses, or data retrieved from databases is usually presented in a textual format or in the form of arrays, either layed out associative or sequentially. You can, of course, deal with keys and indices of these arrays, but they are tedious to work with and prone to errors. With this serialization tool, you can easily work with defined classes and make the translation back and forth between your objects and arrays, ready to process requests, send back responses and deal with databases.

You can read all about it [here][SerializationDocumentation].

## Unity Web Requests

Unity has built-in web communication tools, capable of making simple requests, or downloading resources. This Web API toolkit helps you in setting up the receiver-side of these requests and sending back appropriate responses in a very easy way.

Take a look [here][UnityWebRequestsDocumentation] for more information.

## Photon WebRPC

Photon, by Exit Games GmbH, is a simple yet powerful asset for many developers to implement multiplayer in their games. It also offers a feature to securely communicate with your web server. This Web API Toolkit provides a kickstarting framework to create custom request handlers.

Check out how to get started [here][PhotonWebRPCDocumentation].

## Contributing

Contributions are more than welcome! If you have ideas on how to improve the concepts or structure of these web tools, have additional ideas for features to add, come across a bug, or want to let us know you're using these in your project, feel free to [get in touch][Contact]!

## License

This package is provided under the [MIT][License] license.

## Changelog

View the update history of this package [here][Changelog].

[Logo]: ./docs/images/ImpossibleOddsLogo.png
[License]: ./LICENSE.md
[Changelog]: ./CHANGELOG.md
[SerializationDocumentation]: ./docs/Serializer.md
[UnityWebRequestsDocumentation]: ./docs/UnityWebRequests.md
[PhotonWebRPCDocumentation]: ./docs/PhotonWebRPC.md
[Contact]: https://www.impossible-odds.net/support-request/
[ImpossibleOddsCSharpToolkit]: https://www.impossible-odds.net/csharp-toolkit/
