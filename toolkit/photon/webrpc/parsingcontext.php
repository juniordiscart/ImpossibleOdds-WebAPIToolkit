<?php

namespace ImpossibleOdds\Photon\WebRpc;

/**
 * Serialization contexts for parsing Photon requests and responses.
 */
abstract class ParsingContext
{
	const Body = "body";
	const RpcParams = "rpcparams";
	const URL = "url";
}
