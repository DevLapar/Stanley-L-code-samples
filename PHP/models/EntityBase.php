<?php
require_once('BaseDocument.php');

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * EntityBase class is a model class that is a super class which holds base entity properties
 * Inherits the BaseDocument class which contains basic logic functions
 */

/** @ODM\MappedSuperclass */
class EntityBase extends BaseDocument {
	/** @ODM\EmbedMany(targetDocument="Feedback") */
	protected array $feedback;
	/** @ODM\EmbedMany(targetDocument="Report") */
	protected array $reports;
	/** @ODM\EmbedMany(targetDocument="Ban") */
	protected array $bans;
	/** @ODM\EmbedMany(targetDocument="Mark") */
	protected array $marks = [];
	/** @ODM\ReferenceOne(storeAs="id", targetDocument="History", orphanRemoval=false) */
	protected History $history;
	/** @ODM\EmbedMany(targetDocument="Announcement") */
	protected array $announcements;

	function __construct() {
	}

	public function getReports() {
		return $this->reports;
	}

	public function setReports(array $reports) {
		$this->reports = $reports;
	}

	public function addReports(Report $report) {
		$this->reports[] = $report;
	}

	public function getFeedback() {
		return $this->feedback;
	}

	public function setFeedback(Feedback $feedback) {
		$this->feedback[] = $feedback;
	}

	public function getBans() {
		return $this->bans;
	}

	public function setBans(Ban $bans) {
		$this->bans[] = $bans;
	}

	public function getAnnouncements() {
		return $this->announcements;
	}

	public function setAnnouncements(Announcement $announcements) {
		$this->announcements[] = $announcements;
	}


	public function getMarks() {
		return $this->marks;
	}

	public function setMarks(array $marks) {
		$this->marks = $marks;
	}

	public function addMark(Mark $mark) {
		$this->marks[] = $mark;
	}

	public function getHistory() {
		return $this->history;
	}

	public function setHistory(History $history) {
		$this->history = $history;
	}

	public function removeReport(Report $target) {
		foreach ($this->reports as $idx => $report) {
			if ($target->getId() === $report->getId()) {
				unset($this->reports[$idx]);
				break;
			}
		}
	}

	public function removeMark(Mark $target) {
		foreach ($this->marks as $idx => $mark) {
			if ($target->getId() === $mark->getId()) {
				unset($this->marks[$idx]);
				break;
			}
		}
	}

	public function removeBan(Ban $target) {
		foreach ($this->bans as $idx => $ban) {
			if ($target->getId() === $ban->getId()) {
				unset($this->bans[$idx]);
				break;
			}
		}
	}
}
