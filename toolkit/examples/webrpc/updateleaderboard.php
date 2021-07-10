<?php

$ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once($ROOT . "/vendor/autoload.php");

use ImpossibleOdds\Photon\WebRpc\WebRpcRequest;
use ImpossibleOdds\Photon\WebRpc\WebRpcResponseData;

abstract class ErrorCode
{
	const None = 0;
	const InvalidLeaderboardId = 1;
	const InvalidScore = 2;
}

abstract class UpdateLeaderboardCodes
{
	const None = 0;
	const ScoreUpdated = 1;
	const ScoreUnchanged = 2;
}

class UpdateLeaderboardRequest extends WebRpcRequest
{
	// Example set of valid leaderboard IDs.
	const ValidLeaderboardIds = array(
		"best_race_times",
		"best_lap_time",
		"best_freestyle_scores"
	);

	/**
	 * @var int
	 * @required body
	 */
	public $UserId;
	/**
	 * @var string
	 * @required body
	 */
	public $LeaderboardId;
	/**
	 * @var int
	 * @required body
	 */
	public $Score;
	/**
	 * Set to true when the score should be updated, regardless of
	 * whether the current/previous score saved is better.
	 *
	 * @var bool
	 */
	public $ForceUpdate = false;

	protected function ConstructResponse(): ?WebRpcResponseData
	{
		return new UpdateLeaderboardResponseData();
	}

	protected function ProcessRequest(): void
	{
		$this->Response->Message = "This is a leaderboard on the Impossble Odds demo network. No real data is stored.";

		// Check the leaderboard ID
		if (!in_array($this->LeaderboardId, self::ValidLeaderboardIds)) {
			$this->Response->Data->ErrorCode = ErrorCode::InvalidLeaderboardId;
			return;
		}

		// Generate a previous score
		$previousScore = 0;
		switch ($this->LeaderboardId) {
			case self::ValidLeaderboardIds[0]:
			case self::ValidLeaderboardIds[1]:
				$this->Response->Data->CurrentScore = random_int(0, $this->Score - 1);
				break;
			case self::ValidLeaderboardIds[2]:
				$this->Response->Data->CurrentScore = random_int($this->Score + 1, PHP_INT_MAX);
				break;
		}

		// "Update" the score
		if ($this->ForceUpdate) {
			$this->Response->Data->UpdateCode = UpdateLeaderboardCodes::ScoreUpdated;
		} else {
			$this->Response->Data->UpdateCode =
				(random_int(PHP_INT_MIN, PHP_INT_MAX) % 2) ?
				UpdateLeaderboardCodes::ScoreUnchanged :
				UpdateLeaderboardCodes::ScoreUpdated;
		}

		if ($this->Response->Data->UpdateCode === UpdateLeaderboardCodes::ScoreUpdated) {
			$this->Response->Data->CurrentScore = $this->Score;
		} else {
			$this->Response->Data->CurrentScore = $previousScore;
		}
	}
}

class UpdateLeaderboardResponseData extends WebRpcResponseData
{
	/**
	 * @var int
	 */
	public $ErrorCode = ErrorCode::None;
	/**
	 * @var int
	 */
	public $UpdateCode = UpdateLeaderboardCodes::None;
	/**
	 * @var int
	 */
	public $CurrentScore = 0;
}

WebRpcRequest::Process(new UpdateLeaderboardRequest());
