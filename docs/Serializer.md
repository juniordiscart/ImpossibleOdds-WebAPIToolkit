# ![Impossible Odds Logo][Logo] Serialization Tools

At the heart of this framework is the `Serializer`, which parses the incoming requests to usable objects. It allows you to parse associative or sequential arrays to and from PHP objects using annotations placed above your objects and its members. By transforming between concrete objects and arrays, it's easier to work with the data in between and having functioning intelli-sense in most editors is a nice bonus as well!

## Annotations

By adding annotations to your objects, you can fully customize how the serializer goes through your data. The following annotations are supported and recognized:

* `@var`: field-based annotation. This is used to define the type of the fields of your object. The types used should either be available in the root namespace, or the fully qualified typename should be jotted down.
* `@deserialize-alias`: field-based annotation. By default, when this annotation is not present on a field, it will use the name of the field to check if the data is present. When an alias is defined, it will look for the alias instead. Multiple deserialization aliases can be defined for a single field.
* `@serialize-alias`: field-based annotation. By default, when this annotation is not present on a field, it will use the field's name to during serialization. However, setting this annotation allows you to serialize it under a different name. Contrary to the `@deserialize-alias`, only one of this can be defined.
* `@ignore`: field-based annotation. By default, any public field of your object will get serialized. By placing this annotation on a field, it will be skipped.
* `@required`: field-based annotation. States that a particular value is required to be present in the data when decoding. An exception will be thrown when data is not present. This annotation can also be appended with a 'parsing context', stating that a value is only required to be present during while parsing in a specific context, e.g. when a single object is constructed from multiple data sources at once.
* `@context`: field-based annotation. In some cases, data of an object can come from multiple sources at once (URL parameters, headers, POST-body, etc.). By stating a 'parsing context' for a field, it will only serialize the data when this parsing context is active.
* `@data-decode`: field-based annotation. When a field expects a more complex object as defined by the `@var` annotation, but a string value is matched during deserialization, it will perform a `json_decode()` operation on the string first.
* `@sub-class`: class-based annotation. This can be used for serializing type-information so that the inheritance chain of the object can remain in tact during deserialization. Multiple sub-class annotations can be added to a single class. Note that the field name defined in the annotation should be that of what is expected to be found in the data to deserialize, i.e. if a `@decode-alias` is expected, then the name of the alias should used.
* `@map-sequential`: class-based annotaion. By default, objects are assumed to be mapped on a key-value pair basis. By annotating an object this, it expects the data to be put in a sequential array, rather than an associative one. This works in conjunction with the `@index` annotation.
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



## Deserialization

```php
$incomingData = json_decode(file_get_contents("php://input"), true);
$leaderboard = Serializer::Deserialize("\Examples\Leaderboard\Leaderboard", $incomingData)
```

## Caching

Much of the serializer's work is done by parsing annotations on classes and fields using reflection. These are not cheap operations and their results should be cached. However, the runtime largely uses memory on a per-request basis and does not foresee for it to persist between requests. Luckily, some runtimes do provide shared memory pools, such as the Windows Cache Extension (`win_cache`). When such an extension is detected to be present, it will store and cache these annotation results.

Custom caching implementations can be created through the `IAnnotationsCache` interface and provide it to the serializer by calling:

```php
Serializer::__Init(new MyCustomCache());
```

## Gotcha's

* PHP does not allow to touch fields that are protected or private. Only public fields can be (de)serialized.

[Logo]: ./images/ImpossibleOddsLogo.png
