<?php

namespace ImpossibleOdds\Unity\WebRequests;

/**
 * Serialization contexts for parsing Unity web requests and responses.
 */
abstract class ParsingContext
{
	const Headers = "header";
	const Body = "body";
	const URL = "url";
}
