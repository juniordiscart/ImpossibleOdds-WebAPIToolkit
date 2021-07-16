<?php

use ImpossibleOdds\Unity\WebRequests\WebRequest;
use ImpossibleOdds\Unity\WebRequests\WebResponse;

$ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once($ROOT . "/vendor/autoload.php");

class Leaderboard
{
	/**
	 * @var string
	 */
	public $Id;
	/**
	 * @var string
	 */
	public $Name;
	/**
	 * @var LeaderboardEntry[]
	 */
	public $Entries;
}

/**
 * @map-sequential
 */
class LeaderboardEntry
{
	/**
	 * @var int
	 * @index 0
	 */
	public $Rank;
	/**
	 * @var int
	 * @index 1
	 */
	public $PlayerID;
	/**
	 * @var int
	 * @index 2
	 */
	public $Score;
}

class ErrorCodes
{
	public const NONE = 0;
	public const INVALID_ID = 1;
	public const INVALID_ENTRIES = 1 << 1;
	public const INVALID_OFFSET = 1 << 2;
}

class GetLeaderboardRequest extends WebRequest
{
	/**
	 * @var string
	 * @required body
	 */
	public $LeaderboardId;
	/**
	 * @var int
	 * @required body
	 */
	public $NrOfEntries;
	/**
	 * @var int
	 * @required body
	 */
	public $Offset;

	protected function ConstructResponse(): ?WebResponse
	{
		return new GetLeaderboardResponse();
	}

	protected function ProcessRequest(): void
	{
		$response = $this->GetResponse();
		if (empty($this->LeaderboardId)) {
			$response->ErrorCode += ErrorCodes::INVALID_ID;
		}

		if ($this->NrOfEntries <= 0) {
			$response->ErrorCode += ErrorCodes::INVALID_ENTRIES;
		}

		if ($this->Offset < 0) {
			$response->ErrorCode += ErrorCodes::INVALID_OFFSET;
		}

		// Don't process if an error has been registered
		if ($response->ErrorCode !== ErrorCodes::NONE) {
			return;
		}

		$response->Leaderboard = $this->GetLeaderboard();
	}

	private function GetResponse(): GetLeaderboardResponse
	{
		return $this->Response;
	}

	private function GetLeaderboard(): ?Leaderboard
	{
		$leaderboard = new Leaderboard();
		$leaderboard->Id = $this->LeaderboardId;
		$leaderboard->Name = "Best Race Times";
		$leaderboard->Entries = array();

		for ($i = 0; $i < $this->NrOfEntries; ++$i) {
			$entry = new LeaderboardEntry();
			$entry->PlayerID = random_int(1, 9999);
			$entry->Rank = $i + $this->Offset;
			$entry->Score = random_int(1, 9999);
			$leaderboard->Entries[] = $entry;
		}

		return $leaderboard;
	}
}

class GetLeaderboardResponse extends WebResponse
{
	/**
	 * @var int
	 */
	public $ErrorCode = ErrorCodes::NONE;
	/**
	 * @var Leaderboard
	 */
	public $Leaderboard;
}

WebRequest::Process(new GetLeaderboardRequest());
