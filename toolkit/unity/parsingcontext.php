<?php

namespace ImpossibleOdds\WebRequests;

/**
 * Serialization contexts for parsing Unity web requests and responses.
 */
abstract class ParsingContext
{
	const Headers = "headers";
	const Body = "body";
	const URL = "url";
}
