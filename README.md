# ![Impossible Odds Logo][Logo] Impossible Odds - Web API Toolkit

Simple tools to speed up creating a web API for your games. It is mainly designed to be used with Unity's web requests and the HTTP framework in [this toolkit][ImpossibleOddsCSharpToolkit] as well as with [Photon's WebRPC][PhotonWebRPC] feature.

**Important note**: This is a pretty bare-bones framework developed for quick testing on a simple web server. This might help you get started for setting up simple or small-scale servers with a database behind them, but I will not pretend these tools will scale well with larger amounts of requests.

## Unity Web Requests

## Photon WebRPC

## Serializer

The static serializer class allows you to parse associative arrays to objects using the annotations placed above your objects and its members. By transforming between concrete objects and associative arrays, it's easier to work with the data in between and having functioning intellisense in most editors is a nice bonus as well!

### Serialization



### Deserialization

### Annotations

By adding annotations to your objects, you can fully customize how the serializer goes through your data. The following annotations are supported and recognized:

* `@var`: field-based annotation. This is used to define the type of the fields of your object. The types used should either be available in the root namespace, or the fully qualified typename should be jotted down.
* `@deserialize-alias`: field-based annotation. By default, when this annotation is not present on a field, it will use the name of the field to check if the data is present. When an alias is defined, it will look for the alias instead. Multiple deserialization aliases can be defined for a single field.
* `@serialize-alias`: field-based annotation. By default, when this annotation is not present on a field, it will use the field's name to during serialization. However, setting this annotation allows you to serialize it under a different name. Contrary to the `@deserialize-alias`, only one of this can be defined.
* `@ignore`: field-based annotation. By default, any public field of your object will get serialized. By placing this annotation on a field, it will be skipped.
* `@required`: field-based annotation. States that a particular value is required to be present in the data when decoding. An exception will be thrown when data is not present. This annotation can also be appended with a 'parsing context', stating that a value is only required to be present during while parsing in a specific context, e.g. when a single object is constructed from multiple data sources at once.
* `@context`: field-based annotation. In some cases, data of an object can come from multiple sources at once (URL parameters, headers, POST-parameter, etc.). By stating a 'parsing context' for a field, it will only serialize the data when this parsing context is active.
* `@data-decode`: field-based annotation. When a field expects a more complex object as defined by the `@var` annotation, but a string value is matched during deserialization, it will perform a `json_decode()` operation on the string first.
* `@sub-class`: class-based annotation. This can be used for serializing type-information so that the inheritance chain of the object can remain in tact during deserialization.
* `@map-sequential`: class-based annotaion. By default, objects are assumed to be mapped on a key-value pair basis. By annotating an object this, it expects the data to be put in a sequential array, rather than an associative one. This works in conjunction with the `@index` annotation.
* `@index`: field-based annotation. States at which index the value for the field can be found in a sequentially-structured array of data.

### Caching

Much of the serializer's work is done by parsing annotations of classes and fields using reflection. These are not cheap operations and their results should be cached. However, the runtime largely uses memory on a per-request basis and does not foresee for it to persist between requests. Some runtimes do provide shared memory pools, such as the Windows Cache Extension (`win_cache`). When such an extension is detected to be present, it will store and cache these annotation results.

Custom caching implementations can be created through the `IAnnotationsCache` interface and provide it to the serializer by calling:

```php
Serializer::__Init(new MyCustomCache());
```

### Gotcha's

* PHP does not allow to touch fields that are protected or private. Only public fields can be (de)serialized.

[Logo]: ./docs/images/ImpossibleOddsLogo.png
[ImpossibleOddsCSharpToolkit]: https://github.com/juniordiscart/ImpossibleOdds-Toolkit
[PhotonWebRPC]: https://doc.photonengine.com/en-us/realtime/current/gameplay/web-extensions/webrpc
