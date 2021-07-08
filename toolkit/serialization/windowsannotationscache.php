<?php

namespace ImpossibleOdds\Serialization;

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
