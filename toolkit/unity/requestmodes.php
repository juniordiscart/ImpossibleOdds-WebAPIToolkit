<?php

namespace ImpossibleOdds\WebRequests;

/**
 * Supported request modes for incoming requests.
 */
abstract class UnityWebRequestMode
{
	const Get = "GET";
	const Post = "POST";
	const Put = "PUT";
}
