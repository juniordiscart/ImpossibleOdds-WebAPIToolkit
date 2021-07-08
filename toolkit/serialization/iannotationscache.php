<?php

namespace ImpossibleOdds\Serialization;

interface IAnnotationsCache
{
	public function AnnotationExists(string $cacheKey): bool;
	public function GetAnnotation(string $cacheKey): array;
	public function SetAnnotation(string $cacheKey, array $annotations);
}
