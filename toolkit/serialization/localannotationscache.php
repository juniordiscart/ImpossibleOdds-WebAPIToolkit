<?php

namespace ImpossibleOdds\Serialization;

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
