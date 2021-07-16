# ![Impossible Odds Logo][Logo] Serialization Tools

At the heart of this framework is the `Serializer`, which parses incoming requests to usable objects and transforms response objects back to an acceptable layout for transmission. It allows you to parse associative or sequential arrays to and from PHP objects using annotations placed above your objects and its members. By transforming between concrete objects and arrays, it's easier to work with the data in between and having functioning code completion in most editors is a nice bonus as well!

## Annotations

By adding annotations to your objects, you can fully customize how the serializer goes through your data. The following annotations are supported and recognized:

* `@var`: field-based annotation. This is used to define the type of the fields of your object. The types used should either simple types (`int`, `string`, etc.), be available in the root namespace, or the fully qualified typename should be jotted down.
* `@deserialize-alias`: field-based annotation and only used during deserialization. By default, when this annotation is not present on a field, it will use the name of the field to check if the data is present. When an alias is defined, it will look for the alias instead. Multiple deserialization aliases can be defined for a single field.
* `@serialize-alias`: field-based annotation and only used during serialization. By default, when this annotation is not present on a field, it will use the field's name to during serialization. However, setting this annotation allows you to serialize it under a different name. Contrary to the `@deserialize-alias`, only one such annotation can be defined per field.
* `@ignore`: field-based annotation and only used during serialization. By default, any public field of your object will get serialized. By placing this annotation on a field, it will be skipped.
* `@required`: field-based annotation and only used during deserialization. States that a particular value is required to be present in the data. An exception will be thrown when data could not be found. This annotation can also be appended with a [parsing context](#parsing-contexts), stating that a value is only required to be present while parsing in a specific context, e.g. when a single object is constructed from multiple data sources at once.
* `@context`: field-based annotation and only used during serialization. In some cases, data of an object can come from multiple sources at once (URL parameters, headers, POST-body, etc.). By stating a 'parsing context' for a field, it will only serialize the data when this parsing context is active.
* `@data-decode`: field-based annotation and only used during deserialization. When a field expects a more complex object as defined by the `@var` annotation, but a string value is matched during deserialization, it will perform a `json_decode()` operation on the string first.
* `@sub-class`: class-based annotation. This can be used for serializing type-information so that the inheritance chain of the object can remain in tact during deserialization. Multiple sub-class annotations can be added to a single class. Note that the field name defined in the annotation should be that of what is expected to be found in the data to deserialize, i.e. if a `@decode-alias` is expected, then the name of the alias should used.
* `@map-sequential`: class-based annotaion. By default, objects are assumed to be mapped on a key-value pair basis. By annotating an object this, it expects the data to be put in a sequential array, rather than an associative one. This works in conjunction with the `@index` annotation. During serialization, it will lay out the data in a sequential way, rather than an associative way.
* `@index`: field-based annotation. States at which index the value for the field can be found in a sequentially-structured array of data.

Below you'll find a few example classes that are annotated to help the serializer processing data:

```php
/**
 * A leaderboard with multiple child classes. Based on the value in the
 * DisplayType field, it is determined what kind of actual leaderboard is used.
 * E.g. when the DisplayType field holds the 'race_times' value, it will create
 * an instance of the RaceTimesLeaderboard type.
 *
 * @sub-class DisplayType race_times \Examples\Leaderboard\RaceTimesLeaderboard
 * @sub-class DisplayType freestyle_scores \Examples\Leaderboard\FreestyleLeaderboard
 */
class Leaderboard
{
	/**
	 * @var int
	 * @decode-alias id
	 * @required
	 */
	public $Id;
	/**
	 * @var string
	 * @decode-alias displaytype
	 * @required
	 */
	public $DisplayType;
	/**
	 * How are the entries sorted? Ascending or descending based on their score value?
	 *
	 * @var string
	 * @decode-alias sorting
	 * @decode-alias sort_type
	 * @required
	 */
	public $Sorting;
	/**
	 * Custom types should have their fully qualified type name.
	 *
	 * @var \Examples\Leaderboard\LeaderboardEntry[]
	 * @decode-alias entries
	 */
	public $Entries;
	/**
	 * Can contain some extra information about the leaderboard. It's frequently
	 * stored in the DB as a JSON-string. It might need to be decoded first before
	 * being assigned.
	 *
	 * @var array
	 * @json-decode
	 * @decode-alias extra_data
	 */
	public $ExtraData;
}

/**
 * Having potentially many entries in a leaderboard, it's more
 * space-efficient to have them stored as sequential data.
 *
 * @map-sequential
 */
class LeaderboardEntry
{
	/**
	 * @var int
	 * @index 0
	 */
	public $UserId;
	/**
	 * @var int
	 * @index 1
	 */
	public $Value;
	/**
	 * @var int
	 * @index 2
	 */
	public $Rank;
}
```

## Serialization

_Serialization_ in this tools is not strictly old-school serialization. Rather, the object is transformed to an associative or sequential array of data so that it can be used by the `json_encode` function (or any other tool for a different format, if you like).

To start the transformation of your object to an array, simply call:

```php
$result = Serializer::Serialize($myData);
```

Or, additionally add a [parsing context](#parsing-contexts) if you only want to extract a sub-set of data:

```php
$result = Serializer::Serialize($myData, "body");
```

## Deserialization

Likewise, _deserialization_ here is also not directly taking data from a stream (e.g. from disk or the network) and processing it. Rather, it requires it to be in an associative or sequential array format already, after which it transforms it into your custom objects.

Deserialization can happen in one of several ways, however, it requires you to know beforehand what you're gonna process:

* You can supply the deserializer with a fully qualified type name of the object (as seen from the root namespace). It will create an instance and apply the data to it:

```php
$result = Serializer::Deserialize("\Fully\Qualified\Path\To\Type", $myData);
```

* Or, you can supply it with an instance of the object already onto which it should map the data:

```php
$result = new MyCustomObject();
Serializer::Deserialize($result, $myData);
```

## Parsing Contexts

The serializer is capable of applying data onto an already existing object. This is useful when you want to synthesize the object with data coming from multiple different sources, e.g. a web request's URL parameters and POST-body. By default, this serializer will apply a data source onto the object and match each field, overriding any previous value from an earlier mapping. However, you may want to define that a certain value is required in the data using the `@required` annotation, or the data is invalid alltogether, e.g. a user ID that must be included at all times.

The problem here is that the value is required to be present in each data stream being mapped onto the object, otherwise the stream would fail the requirement. This is, of course, pretty silly and would be a waste of network capacity each each data stream would need to contain the parameter. Instead, the `@required` annotation can be appended by a _parsing context_ name. When a data stream is mapped onto your object, the framework will set the current parsing context depending on which stream is currently being mapped. This will keep the required values restricted to a data stream that shares the same name.

Conversely, you may also want to split an object into multiple outgoing streams during serialization, e.g. a set of response headers and a POST-body response. When no parsing context is set on the serializer, by default it will pick up every public value of the object and process it. If you want it to extract only data in a certain parsing context, you can apply the `@context` annotation on the fields along with the name of the parsing context in which they should be picked up.

* `@required [parsing context]` will only trigger an exception when a required value is not present in the data stream if active parsing context matches in name.
* `@context [parsing context]` will only pick up values when the parsing context's name matches.

## Caching

Much of the serializer's work is done by parsing annotations on classes and fields using reflection. These are not cheap operations and their results should be cached. However, the runtime largely uses memory on a per-request basis and does not foresee for it to persist between requests. Luckily, some runtimes do provide shared memory pools, such as the Windows Cache Extension (`win_cache`). When this extension is detected to be present, it will store and cache these annotation results using string keys.

Custom caching implementations can be created through the `IAnnotationsCache` interface and provide it to the serializer by calling:

```php
Serializer::__Init(new MyCustomCache());
```

## Gotcha's

* PHP does not allow to touch fields that are protected or private. Only public fields can be (de)serialized.

[Logo]: ./images/ImpossibleOddsLogo.png
[WebRequestsDeserialization]: ./UnityWebRequests.md#requestdeserialization
