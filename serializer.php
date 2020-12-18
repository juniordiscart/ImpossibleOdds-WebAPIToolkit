<?php

namespace ImpossibleOdds\Serialization;

use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use UnexpectedValueException;

/**
 * Exception class to denote that a property that is marked as required, was not found in the source data.
 */
class RequiredPropertyException extends Exception
{
	/**
	 * Displays a message that required property $rp was not found in class $rc.
	 *
	 * @param ReflectionClass $rc
	 * @param ReflectionProperty $rp
	 */
	public function __construct(ReflectionClass $rc, ReflectionProperty $rp)
	{
		parent::__construct("Property " . $rp->name . " of class " . $rc->name . " is marked as required, but no match could be made.");
	}
}

abstract class SupportedAnnotationTags
{
	/**
	 * Usage: @var [type[|null]] [?description]
	 *
	 * Property annotation. Used to define the target type during
	 * the decoding process. The matched Json data will get assigned
	 * or converted to the type defined in this annotation.
	 */
	const Variable = "var";
	/**
	 * Usage: @map-sequential
	 *
	 * Class-annotation.
	 * Add this when the class should be serialized/deserialized sequentially.
	 * Use the @index annotation to identify the index of a property.
	 */
	const MapSequential = "map-sequential";
	/**
	 * Usage: @index [value]
	 *
	 * Property-annotation used on combination with @map-sequential.
	 * Defines the index of the property when encoding/decoding the object.
	 */
	const Index = "index";
	/**
	 * Usage: @required [context]
	 *
	 * Property-annotation. Defines that a certain property must be present
	 * and not be null during decoding from Json. A context is optional.
	 * If a context is given, then the property is only required when parsing
	 * during that context.
	 */
	const Required = "required";
	/**
	 * Usage: @decode-alias [name]
	 *
	 * Property-annotation for associative decoding. When defined,
	 * the decode-alias is also checked for in the Json-data. If the alias
	 * isn't present in the Json data, then the property's real name
	 * is used to check for before determining whether to skip it or not.
	 * Multiple decode-alias annotations may be defined.
	 */
	const DecodeAlias = "decode-alias";
	/**
	 * Usage: @encode-alias [name]
	 *
	 * Property-annotation for associative encoding. When defined,
	 * the property will be encoded under the alias instead of
	 * the property's real name. Only a single encode-alias is supported.
	 * Defining more than one encode-alias will have undefined results.
	 */
	const EncodeAlias = "encode-alias";
	/**
	 * Usage: @sub-class [fieldname] [fieldvalue] [full sub-class name]
	 *
	 * Class-annotation that can be used to define a certain sub-class
	 * to get instantiated. This can be useful when working with
	 * abstract classes.
	 */
	const SubClass = "sub-class";
	/**
	 * Usage: @ignore
	 *
	 * Property annotation that is used during encoding.
	 * By default, all public properties are encoded. Using
	 * the @ignore annotation will exclude it from the result.
	 */
	const Ignore = "ignore";
	/**
	 * Usage: @json-decode
	 *
	 * Property annotation that can be used when the property
	 * expects a more complex object or array while the matched
	 * Json-data is a string. In this case, the matched string
	 * of the Json-data is decoded first using a call to
	 * json_decode($value, true) before further analysis.
	 */
	const JsonDecode = "json-decode";
	/**
	 * Usage: @encode-context [context]
	 *
	 * Property annotation that can be used to define whether
	 * a property should only be encoded in a specific context.
	 * When no encoding-context is defined for the property,
	 * it will always be encoded. If a context is specified, it
	 * is only encoded if the current encoding context matches.
	 */
	const EncodeContext = "encode-context";
}

class SubClassDefinition
{
	/**
	 * @var string
	 */
	public $FieldName;
	/**
	 * @var string
	 */
	public $FieldValue;
	/**
	 * @var ReflectionClass
	 */
	public $SubClass;
}

interface IAnnotationsCache
{
	public function AnnotationExists(string $cacheKey): bool;
	public function GetAnnotation(string $cacheKey): array;
	public function SetAnnotation(string $cacheKey, array $annotations);
}

/**
 * Implements the cache locally. This cache will not be stored persistently across sessions.
 */
class LocalAnnotationsCache implements IAnnotationsCache
{
	private $cache;

	public function __construct()
	{
		$this->cache = array();
	}

	public function AnnotationExists(string $cacheKey): bool
	{
		return array_key_exists($cacheKey, $this->cache);
	}

	public function GetAnnotation(string $cacheKey): array
	{
		if ($this->AnnotationExists($cacheKey)) {
			return $this->cache[$cacheKey];
		} else {
			return array();
		}
	}

	public function SetAnnotation(string $cacheKey, array $annotations)
	{
		$this->cache[$cacheKey] = $annotations;
	}
}

/**
 * Uses the WinCache module which will exists across sessions.
 */
class WindowsAnnotationsCache implements IAnnotationsCache
{
	public function AnnotationExists(string $cacheKey): bool
	{
		return wincache_ucache_exists($cacheKey);
	}

	public function GetAnnotation(string $cacheKey): array
	{
		$success = false;
		if ($this->AnnotationExists($cacheKey)) {
			$annotations = wincache_ucache_get($cacheKey, $success);
			if ($success) {
				return $annotations;
			}
		}

		return array();
	}

	public function SetAnnotation(string $cacheKey, array $annotations)
	{
		wincache_ucache_set($cacheKey, $annotations);
	}
}

class Serializer
{
	/**
	 * @var array
	 */
	private static $subClassDefinitionsCache = array();

	/**
	 * @var AnnotationsCache
	 */
	private static $annotationsCache;

	/**
	 * Defines the current context for required values.
	 *
	 * @var string
	 */
	private static $parsingContext = null;

	/**
	 * Internal use only! Initialization function.
	 *
	 * @return void
	 */
	public static function __Init(bool $useCache = true)
	{
		if ($useCache && is_null(self::$annotationsCache)) {
			// If on Windows, we use the wincache solution.
			// As a last resort, we use a local solution.
			// TODO: support for other platform caches
			if (extension_loaded("wincache")) {
				self::$annotationsCache = new WindowsAnnotationsCache();
			} else {
				self::$annotationsCache = new LocalAnnotationsCache();
			}
		} else {
			self::$annotationsCache = new LocalAnnotationsCache();
		}
	}

	/**
	 * Casts an object and all of its sub-objects to an associative array variant.
	 * https://stackoverflow.com/questions/13567939/convert-multidimensional-objects-to-array
	 *
	 * @param object $obj
	 * @param array $arr
	 * @return mixed
	 */
	public static function ObjectToArray($obj, &$arr)
	{
		if (!is_object($obj) && !is_array($obj)) {
			$arr = $obj;
			return $arr;
		}

		foreach ($obj as $key => $value) {
			if (!empty($value)) {
				$arr[$key] = array();
				self::ObjectToArray($value, $arr[$key]);
			} else {
				$arr[$key] = $value;
			}
		}
		return $arr;
	}

	/**
	 * Deserializes the $json array and decodes it to the target object.
	 *
	 * @param mixed $target
	 * @param array $json
	 * @param string $parsingContext
	 * @return void
	 */
	public static function Deserialize($target, array $json, string $parsingContext = ""): void
	{
		if (!isset($target)) {
			throw new InvalidArgumentException("The target object is null.");
		}

		self::$parsingContext = $parsingContext;

		$rc = new ReflectionClass($target);
		$rcAnno = self::GetClassAnnotations($rc);

		// Check whether the target and the json data match in sequential data decoding.
		if (isset($rcAnno[SupportedAnnotationTags::MapSequential]) !== self::IsArraySequential($json)) {
			throw new InvalidArgumentException("The target object and JSON data do not agree on sequential decoding.");
		}

		if (self::IsArraySequential($json)) {
			self::DeserializeSequential($rc, $target, $json);
		} else {
			self::DeserializeAssociative($rc, $target, $json);
		}
	}

	/**
	 * Encodes the given $object to an associative array, ready to be encoded by the built-in PHP json encoder.
	 *
	 * @param mixed $object
	 * @return mixed
	 */
	public static function Serialize($object, string $parsingContext = "")
	{
		// If the given object is null or a scalar object then we just return it.
		if (!isset($object) || is_scalar($object)) {
			return $object;
		}

		self::$parsingContext = $parsingContext;

		// If the object is an array (associative or sequential) then we process each of the array's elements.
		// Else, the object is a class, and we search for its fields and process those.
		if (is_array($object)) {
			if (empty($object)) {
				return $object;
			}

			// Check whether the given object is a sequential array
			$jsonArray = array();
			if (self::IsArraySequential($object)) {
				foreach ($object as $value) {
					$jsonArray[] = self::Serialize($value, self::$parsingContext);
				}
			} else {
				foreach ($object as $key => $value) {
					$jsonValue = self::Serialize($value, self::$parsingContext);
					if (isset($jsonValue)) {
						$jsonArray[$key] = $jsonValue;
					}
				}
			}

			return $jsonArray;
		} else {
			$rc = new ReflectionClass($object);
			$rprops = $rc->getProperties(ReflectionProperty::IS_PUBLIC);
			$classAnnotations = self::GetClassAnnotations($rc);

			// Check whether we want to encode this data as a sequential array or as an associative array
			if (array_key_exists(SupportedAnnotationTags::MapSequential, $classAnnotations)) {
				$indexProps = array();

				// To construct a sequential array, we need to add the elements in
				// the correct order, or PHP will see that array as an assoc array...
				$maxIndex = 0;
				foreach ($rprops as $rprop) {
					$propAnnotations = self::GetPropertyAnnotations($rprop);
					if (!isset($propAnnotations[SupportedAnnotationTags::Index])) {
						continue;
					}

					$propIndex = (int)$propAnnotations["index"][0];
					$maxIndex = max($maxIndex, $propIndex);
					$indexProps[$propIndex] = $rprop;
				}

				$jsonArray = array_fill(0, $maxIndex + 1, null);
				foreach ($indexProps as $index => $rprop) {
					$jsonArray[$index] = self::Serialize($rprop->getValue($object), self::$parsingContext);
				}

				return $jsonArray;
			} else {
				$jsonArray = array();

				foreach ($rprops as $rprop) {
					$rvalue = $rprop->getValue($object);

					// I don't want to encode null values...
					if (is_null($rvalue)) {
						continue;
					}

					// If the property whishes to be ignored...
					$propAnnotations = self::GetPropertyAnnotations($rprop);
					if (isset($propAnnotations[SupportedAnnotationTags::Ignore])) {
						continue;
					}

					// If the property has a specific encoding context defined...
					if (isset($propAnnotations[SupportedAnnotationTags::EncodeContext]) && !in_array(self::$parsingContext, $propAnnotations[SupportedAnnotationTags::EncodeContext])) {
						continue;
					}

					$encodedValue = self::Serialize($rvalue, self::$parsingContext);
					if (isset($encodedValue)) {
						$serializedName = $rprop->getName();

						// Check whether an encoding alias is defined
						if (isset($propAnnotations[SupportedAnnotationTags::EncodeAlias])) {
							$alias = $propAnnotations[SupportedAnnotationTags::EncodeAlias][0];
							if (strcmp($alias, "") !== 0) {
								$serializedName = $alias;
							}
						}

						$jsonArray[$serializedName] = $encodedValue;
					}
				}

				return $jsonArray;
			}
		}

		return null;
	}

	/**
	 * Copied from PHPUnit 3.7.29, Util/Test.php
	 *
	 * @param string $docblock Full method docblock
	 * @return array
	 */
	public static function ParseAnnotations($docblock): array
	{
		// Strip away the docblock header and footer to ease parsing of one line annotations
		$docblock = substr($docblock, 3, -2);

		$annotations = array();
		$re = "/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m";
		if (preg_match_all($re, $docblock, $matches)) {
			$numMatches = count($matches[0]);

			for ($i = 0; $i < $numMatches; ++$i) {
				$annotations[$matches["name"][$i]][] = $matches["value"][$i];
			}
		}

		return $annotations;
	}

	/**
	 * Gets the annotations of a class.
	 *
	 * @param ReflectionClass $class
	 * @return array
	 */
	public static function GetClassAnnotations(ReflectionClass $class): array
	{
		$cacheKey = "anno_class_" . $class->name;
		return self::GetAnnotations($class, $cacheKey);
	}

	/**
	 * Get the annotations of a method.
	 *
	 * @param ReflectionMethod $method
	 * @return array
	 */
	public static function GetMethodAnnotations(ReflectionMethod $method): array
	{
		$cacheKey = "anno_meth_" . $method->class . "::" . $method->name;
		return self::GetAnnotations($method, $cacheKey);
	}

	/**
	 * Get the annotations of a property.
	 *
	 * @param ReflectionProperty $property
	 * @return array
	 */
	public static function GetPropertyAnnotations(ReflectionProperty $property): array
	{
		$cacheKey = "anno_prop_" . $property->class . "::" . $property->name;
		return self::GetAnnotations($property, $cacheKey);
	}

	/**
	 * Tests whether the given array has its elements ordered sequentially.
	 * Alternate version of: https://stackoverflow.com/a/173479
	 *
	 * @param array $arr
	 * @return bool
	 */
	public static function IsArraySequential(array $arr): bool
	{
		if (!is_array($arr) || empty($arr)) {
			return false;
		}

		return array_keys($arr) === range(0, count($arr) - 1);
	}

	/**
	 * Checks whether $typename already has a namespace defined.
	 * If not not, then the namespace $namespace is added to created a fully qualified namespace.
	 *
	 * @param string $typename
	 * @param string $namespace
	 * @return string
	 */
	public static function GetFullNamespace(string $typename, string $namespace): string
	{
		if (($typename !== "") && ($typename[0] != "\\") && ($namespace != "")) {
			$typename = "\\" . $namespace . "\\" . $typename;
		}
		return $typename;
	}

	/**
	 * Treats the $json data as associative, and will attempt to match
	 * each of the properties to the $target. If a property of the target class
	 * is marked as being required, then the value has to be present in the
	 * $json data. Unresolved required fields will throw an exception.
	 * Deserialization aliases can be used to alter the name of a field in the $json
	 * data. Aliases are given priority to the real name of the property.
	 *
	 * @param ReflectionClass $rc
	 * @param mixed $target
	 * @param array $json
	 * @return void
	 */
	private static function DeserializeAssociative(ReflectionClass $rc, $target, $json): void
	{
		// Go over each of the properties in the class and check whether there is an
		// entry in the JSON data
		$properties = $rc->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property) {
			// See whether there are decode aliases defined for this property
			$propAnnotations = self::GetPropertyAnnotations($property);
			$nameCandidates = array();
			if (isset($propAnnotations[SupportedAnnotationTags::DecodeAlias])) {
				$nameCandidates = $propAnnotations[SupportedAnnotationTags::DecodeAlias];
			}

			// Add the property's name as a last resort.
			$nameCandidates[] = $property->name;

			// Go over the different name candidates to see if there is a match in the JSON data.
			$nameMatch = null;
			foreach ($nameCandidates as $name) {
				if (isset($json[$name])) {
					$nameMatch = $name;
					break;
				}
			}

			// If no match is found
			if (is_null($nameMatch)) {
				// If it is a required property, then we quit. Else, we can just skip it.
				if (self::IsPropertyRequired($propAnnotations)) {
					throw new RequiredPropertyException($rc, $property);
				} else {
					continue;
				}
			}

			// Check whether a type definition is given. If not,
			// then just assign the value, and continue.
			if (!self::HasTypeDefinition($property)) {
				$property->setValue($target, $json[$nameMatch]);
				continue;
			}

			// You've found a match!
			// Fucking Tinder... -.-'
			self::DeserializeValue($rc, $property, $target, $json[$nameMatch]);
		}
	}

	/**
	 * Threats the $json data as sequential, and will attempt to match each of the elements to a property with the @index annotation.
	 * If a property of the target is defined as required, but no element exists at that index in the $json data, an exception is thrown.
	 *
	 * @param ReflectionClass $rc
	 * @param mixed $target
	 * @param array $json
	 * @return void
	 */
	private static function DeserializeSequential(ReflectionClass $rc, $target, array $json): void
	{
		// Go over each of the properties in the class and check whether there is an
		// entry in the JSON data
		$properties = $rc->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property) {
			$propAnnotations = self::GetPropertyAnnotations($property);

			// If no index annotation is defined, then we can skip it.
			if (!isset($propAnnotations[SupportedAnnotationTags::Index])) {
				continue;
			}

			$indexValue = (int)$propAnnotations[SupportedAnnotationTags::Index][0];

			// If no such index exists...
			if (!isset($json[$indexValue])) {
				if (self::IsPropertyRequired($propAnnotations)) {
					throw new RequiredPropertyException($rc, $property);
				} else {
					continue;
				}
			}

			// Check whether a type definition is given. If not,
			// then just assign the value, and continue.
			if (!self::HasTypeDefinition($property)) {
				$property->setValue($target, $json[$indexValue]);
				continue;
			}

			// You've found a match!
			// Fucking Tinder... -.-'
			self::DeserializeValue($rc, $property, $target, $json[$indexValue]);
		}
	}

	/**
	 * Attempts to deserialize the $jsonValue to the requested type
	 * of the property (including resolving inheritance) and assign it
	 * to the property associated with $target.
	 *
	 * @param ReflectionProperty $property
	 * @param mixed $target
	 * @param mixed $jsonValue
	 * @return void
	 */
	private static function DeserializeValue(ReflectionClass $rc, ReflectionProperty $property, $target, $jsonValue): void
	{
		$propAnnotations = self::GetPropertyAnnotations($property);
		$annotatedType = explode(" ", $propAnnotations[SupportedAnnotationTags::Variable][0])[0];

		// If a type is nullable, assign it when the value is null and remove the nullability if it isn't.
		if (self::IsNullable($annotatedType)) {
			if (is_null($jsonValue)) {
				$property->setValue($target, $jsonValue);
				return;
			}

			$annotatedType = self::RemoveNullability($annotatedType);
		} else if (is_null($jsonValue)) {
			throw new UnexpectedValueException("Property " . $property->name . " is non-nullable, but a null-value is given.");
		}

		// Check whether the value should get deserialized first before further processing.
		if (self::IsJsonDecodable($property, $jsonValue)) {
			$jsonValue = json_decode($jsonValue, true);
		}

		if ((strcmp($annotatedType, "mixed") === 0) || self::IsObjectOfSameType($annotatedType, $jsonValue)) {
			// A mixed type can be anything, so we just assign it, or
			// if the objects are of the same type, we can just assign it.
			$property->setValue($target, $jsonValue);
			return;
		} else if (self::IsSimpleType($annotatedType)) {
			// If it's a simple type, we can just try to assign it.
			if (!settype($jsonValue, $annotatedType)) {
				throw new Exception("Failed to set the type to " . $annotatedType . " for value " . $jsonValue . ".");
			}

			$property->setValue($target, $jsonValue);
			return;
		} else if (self::IsTypeDefinedArray($annotatedType)) {
			// TODO: support for ArrayObjects

			// Check whether the given value is an array itself
			if (!is_array($jsonValue)) {

				// If it is a string, then we attempt to convert it to JSON
				if (is_string($jsonValue)) {
					$jsonValue = json_decode($jsonValue, true);
				}

				if (!is_array($jsonValue)) {
					throw new UnexpectedValueException("Property " . $property->name . " expects an array.");
				}
			}

			$elementType = self::RemoveNullability(self::RemoveArrayAnnotation($annotatedType));
			$result = self::DeserializeTypeDefinedArray($rc, $elementType, $jsonValue);
			$property->setValue($target, $result);
		} else {
			// We're dealing with a custom object here
			$determinedType = new ReflectionClass(self::GetFullNamespace($annotatedType, $rc->getNamespaceName()));
			$determinedType = self::DetermineSubType($determinedType, $jsonValue);
			$instance = self::InstantiateObject($determinedType);
			self::Deserialize($instance, $jsonValue, self::$parsingContext);
			$property->setValue($target, $instance);
		}
	}

	/**
	 * Deserialize $jsonValue as an array with predefined element types $elementType.
	 * In case of custom classes, sub-types for the elements are also determined and instantiated.
	 *
	 * @param ReflectionClass $rc
	 * @param string $elementType
	 * @param array $jsonValue
	 * @return array
	 */
	private static function DeserializeTypeDefinedArray(ReflectionClass $rc, string $elementType, array $jsonValue): array
	{
		$result = array();
		if (self::IsSimpleType($elementType)) {
			// Simple elements, we try to convert each of the elements to the desired element type.
			foreach ($jsonValue as $key => $value) {
				if (settype($value, $elementType)) {
					$result[$key] = $value;
				} else {
					throw new Exception("Failed to set the type to " . $elementType . " for element " . $value . ".");
				}
			}
		} else if (self::IsTypeDefinedArray($elementType)) {
			// If the elements themselves are to be arrays, we deserialize each element to an array in turn.
			foreach ($jsonValue as $key => $value) {
				$subElementType = self::RemoveNullability(self::RemoveArrayAnnotation($elementType));
				$result[$key] = self::DeserializeTypeDefinedArray($rc, $subElementType, $value);
			}
		} else {
			// If we get here, then the desired elements are complex objects,
			// we determine the target type for each element and deserialize it.
			$reflectedElementType = new ReflectionClass(self::GetFullNamespace($elementType, $rc->getNamespaceName()));
			foreach ($jsonValue as $key => $value) {
				$classDef = self::DetermineSubType($reflectedElementType, $value);
				$instance = self::InstantiateObject($classDef);
				self::Deserialize($instance, $value, self::$parsingContext);
				$result[$key] = $instance;
			}
		}

		return $result;
	}

	/**
	 * Gets the annotations for the given reflector. If the cache key is null, then the annotations are fetched and
	 * parsed again. If a cache key is given, then the cache is searched for an entry.
	 *
	 * @param string $cacheKey
	 * @param mixed $reflector
	 * @return array|null
	 */
	private static function GetAnnotations($reflector, string $cacheKey = null): ?array
	{
		if (self::$annotationsCache->AnnotationExists($cacheKey)) {
			return self::$annotationsCache->GetAnnotation($cacheKey);
		} else {
			$annotations = self::ParseAnnotations($reflector->getDocComment());
			self::$annotationsCache->SetAnnotation($cacheKey, $annotations);
			return $annotations;
		}
	}

	/**
	 * Checks whether the property has a type definition defined.
	 *
	 * @param ReflectionProperty $rp
	 * @return bool
	 */
	private static function HasTypeDefinition(ReflectionProperty $rp): bool
	{
		$annoations = self::GetPropertyAnnotations($rp);
		return
			isset($annoations[SupportedAnnotationTags::Variable][0]) &&
			!empty($annoations[SupportedAnnotationTags::Variable][0]);
	}

	/**
	 * Checks whether a property has been defined as being nullable.
	 *
	 * @param string $annotatedType
	 * @return bool
	 */
	private static function IsNullable(string $annotatedType): bool
	{
		return stripos("|" . $annotatedType . "|", "|null|") !== false;
	}

	/**
	 * Removes the nullability of a type.
	 *
	 * @param string $annotatedType
	 * @return string
	 */
	private static function RemoveNullability(string $annotatedType): string
	{
		return substr(str_ireplace("|null|", "|", "|" . $annotatedType . "|"), 1, -1);
	}

	/**
	 * Removes the '[]' characters at the end of the annotated type, to get to the type of the elements in the array.
	 *
	 * @param string $annotatedType
	 * @return string
	 */
	private static function RemoveArrayAnnotation(string $annotatedType): string
	{
		return substr($annotatedType, 0, -2);
	}

	/**
	 * A property is considered to be json-decodable when the property has the @json-decode tag, and the value is a string.
	 *
	 * @param ReflectionProperty $property
	 * @param mixed $value
	 * @return bool
	 */
	private static function IsJsonDecodable(ReflectionProperty $property, $value): bool
	{
		return is_string($value) && isset(self::GetPropertyAnnotations($property)[SupportedAnnotationTags::JsonDecode]);
	}

	/**
	 * Checks whether type name $annotatedType is a simple type.
	 *
	 * @param string $annotatedType
	 * @return bool
	 */
	private static function IsSimpleType(string $annotatedType): bool
	{
		return
			$annotatedType == "string" ||
			$annotatedType == "boolean" || $annotatedType == "bool" ||
			$annotatedType == "integer" || $annotatedType == "int" ||
			$annotatedType == "double" || $annotatedType == "float" ||
			$annotatedType == "array" || $annotatedType == "object";
	}

	/**
	 * Checks whether the $value is an object, and whether it is of type denoted by the string-representation $type.
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return bool
	 */
	private static function IsObjectOfSameType(string $type, $value): bool
	{
		return is_object($value) ? is_a($value, $type) : false;
	}

	/**
	 * Checks whether the annotated type is defined as an array with a predefined type.
	 *
	 * @param string $annotatedType
	 * @return bool
	 */
	private static function IsTypeDefinedArray(string $annotatedType): bool
	{
		return substr($annotatedType, -2) === "[]";
	}

	/**
	 * Checks whether the given property annotations denote whether it is required in the current parsing context.
	 *
	 * @param array $propertyAnnotations
	 * @return boolean
	 */
	private static function IsPropertyRequired(array $propertyAnnotations): bool
	{
		if (array_key_exists(SupportedAnnotationTags::Required, $propertyAnnotations)) {

			// If no required context is given, or the context matches, then the property is required.
			$contextValue = $propertyAnnotations[SupportedAnnotationTags::Required][0];
			return self::IsNullOrEmptyString($contextValue) || ($contextValue === self::$parsingContext);
		}

		return false;
	}

	/**
	 * Instantiates a new instance of class $classDef.
	 *
	 * @param ReflectionClass $classDef
	 * @return mixed
	 */
	private static function InstantiateObject(ReflectionClass $classDef)
	{
		if ($classDef->isAbstract()) {
			throw new Exception("The class to instantiate " . $classDef->getName() . " is declared abstract.");
		} else if (!$classDef->isInstantiable()) {
			throw new Exception("The class to instantiate " . $classDef->getName() . " is marked as non-instantiable.");
		}

		return $classDef->newInstanceWithoutConstructor();
	}

	/**
	 * Checks whether the given value is a null or empty string.
	 *
	 * @param string $value
	 * @return boolean
	 */
	private static function IsNullOrEmptyString($value): bool
	{
		return (!isset($value) || trim($value) === "");
	}

	/**
	 * Determines the target class type of the value by checking @sub-class annotations of $classDef
	 * and evaluating $jvalue for any matching fields and values. Can be used beforehand to determine
	 * what kind of object to pass to the deserializer.
	 *
	 * @param ReflectionClass $classDef
	 * @param array $jvalue
	 * @return ReflectionClass
	 */
	private static function DetermineSubType(ReflectionClass $classDef, array $jvalue): ReflectionClass
	{
		$annotations = self::GetClassAnnotations($classDef);

		if (!array_key_exists(SupportedAnnotationTags::SubClass, $annotations)) {
			return $classDef;
		}

		$subclassDefs = $annotations[SupportedAnnotationTags::SubClass];

		// If the class has no annotations for any sub-class definitions,
		// then we're not going to bother.
		if (!isset($subclassDefs)) {
			return $classDef;
		}

		// Check if this class was already processed for sub-class definitions
		$className = $classDef->getName();
		if (!array_key_exists($className, self::$subClassDefinitionsCache)) {
			self::$subClassDefinitionsCache[$className] = array();
			foreach ($subclassDefs as $value) {
				$subClassData = explode(" ", $value);

				$subClassName = self::GetFullNamespace($subClassData[2], $classDef->getNamespaceName());
				if (!class_exists($subClassName, true)) {
					throw new Exception("The sub-class definition " . $subClassData[2] . " could not be resolved.");
				}

				$subClassDefinition = new SubClassDefinition();
				$subClassDefinition->FieldName = $subClassData[0];
				$subClassDefinition->FieldValue = $subClassData[1];
				$subClassDefinition->SubClass = new ReflectionClass($subClassName);

				// We cache this statically. Can't use wincache, since reflection-data
				// cannot be stored globally...
				self::$subClassDefinitionsCache[$className][] = $subClassDefinition;
			}
		}

		// Check the sub-class definitions if we can match a value
		foreach (self::$subClassDefinitionsCache[$className] as $subClassDef) {

			// If the field does not exist, then we don't bother
			if (!array_key_exists($subClassDef->FieldName, $jvalue)) {
				continue;
			}

			// If the field value that defines the subclass is a numeric-string, then we match for an exact value,
			if (is_numeric($subClassDef->FieldValue) && ($subClassDef->FieldValue == $jvalue[$subClassDef->FieldName]) || ($subClassDef->FieldValue == $jvalue[$subClassDef->FieldName])) {
				return self::DetermineSubType($subClassDef->SubClass, $jvalue);
			}
		}

		return $classDef;
	}
}

Serializer::__Init();
