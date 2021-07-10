<?php

namespace ImpossibleOdds\Photon\WebRpc;

/**
 * Serialization contexts for parsing Photon requests and responses.
 */
abstract class ParsingContext
{
	/**
	 * When parsing data directly from the POST-body.
	 */
	const Body = "body";
	/**
	 * A separate context specifically for the RpcParams value in the POST-body.
	 */
	const RpcParams = "rpcparams";
	/**
	 * Parameters extracted out of the URL of the request.
	 */
	const URL = "url";
}
