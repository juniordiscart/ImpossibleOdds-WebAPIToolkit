<?php

namespace ImpossibleOdds\Unity\WebRequests;

/**
 * Supported request modes for incoming requests.
 */
abstract class WebRequestMode
{
	const Get = "GET";
	const Post = "POST";
	const Put = "PUT";
}
