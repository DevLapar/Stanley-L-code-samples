<?php

use MongoDB\BSON\UTCDateTime as MongoDate;

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * GlobalService is an Independent Singleton utility class that consists out of a collection of generic functions
 * this utility service is used to combat duplicate code by providing reusable functions in one place
 */
class GlobalService extends GlobalConfig {
	private static $instance = null;

	private function __construct() {
	}

	public static function getInstance() {
		if (!self::$instance) self::$instance = new GlobalService();
		return self::$instance;
	}

	function getServerTime() {
		return new DateTime('NOW');
	}

	function getServerResetTime() {
		return new DateTime('tomorrow');
	}

	/**
	 * Function to validate if an email-address is a valid email-address
	 * Based on a set of rules that an email-address should abide by
	 * @param string $rawEmail
	 * @return bool
	 */
	function validEmail(string $rawEmail): bool {
		$email = strtolower($rawEmail);
		return !(strpos($email, '@') === false || strrpos($email, '.') === false || !(strrpos($email, '.') > strrpos($email, '@')) ||  preg_match('/\s/', $email) || (strlen($email) - 1) === strrpos($email, '.') || strrpos($email, '.') === (strrpos($email, '@') + 1) || strpos($email, 'speakuni') !== false);
	}

	/**
	 * Function to check if a given date is equal to todays date
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @return bool
	 */
	function isToday(MongoDate | DateTime | array | string $targetDate): bool {
		$databaseDate = clone $this->getDateTimeFromDateInput($targetDate);
		if (!$databaseDate) return true;

		$currentDate = $this->getServerTime();
		$tdstr = $targetDate->format("Y-m-d");
		$cdstr = $currentDate->format("Y-m-d");
		return $tdstr === $cdstr;
	}

	/**
	 * Function to check if a given date is same (day) to another given date
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @return bool
	 */
	function isSameDay(MongoDate | DateTime | array | string $checkDate, MongoDate | DateTime | array | string $targetDate): bool {
		$checkDateDate = clone $this->getDateTimeFromDateInput($checkDate);
		$targetDateDate = clone $this->getDateTimeFromDateInput($targetDate);
		if (!$checkDateDate || !$targetDateDate) return false;

		$tdstr = $targetDate->format("Y-m-d");
		$cdstr = $checkDateDate->format("Y-m-d");
		return $tdstr === $cdstr;
	}

	/**
	 * Function to check if a given date is in the past based on given time periods
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @param int $daysAdded
	 * @param string $timePeriod
	 * @return bool
	 */
	function inThePast(MongoDate | DateTime | array | string $targetDate, int $daysAdded = 0, string $timePeriod = 'day'): bool {
		$databaseDate = clone $this->getDateTimeFromDateInput($targetDate);
		if (!$databaseDate) return false;

		$targetDate =  $this->addTime($databaseDate, $timePeriod, $daysAdded);
		$currentDate = $this->getServerTime();

		$tdstr = ($targetDate instanceof MongoDate) ? $targetDate->toDateTime()->format("Y-m-d H:i:s") : $targetDate->format("Y-m-d H:i:s");
		$cdstr = ($currentDate instanceof MongoDate) ? $currentDate->toDateTime()->format("Y-m-d H:i:s") : $currentDate->format("Y-m-d H:i:s");
		return $cdstr > $tdstr;
	}

	/**
	 * Function to check if a given date is between two other dates
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @param MongoDate|DateTime|array|string $startDate
	 * @param MongoDate|DateTime|array|string $endDate
	 * @param bool $timeSpecific
	 * @param bool $includeToday
	 * @return bool
	 */
	function isBetweenDates(mixed $targetDate, mixed $startDate, mixed $endDate = null, bool $timeSpecific = true, bool $includeToday = true): bool {
		if ($targetDate && $startDate) {
			if ($endDate === null) $endDate = $this->getServerTime();
			$targetDate = clone $this->getDateTimeFromDateInput($targetDate);

			if ($startDate instanceof MongoDate) $startDate = $startDate->toDateTime();
			if ($endDate instanceof MongoDate) $endDate = $endDate->toDateTime();

			$sdstr = ($timeSpecific) ? $startDate->format("Y-m-d H:i:s") : $startDate->format("Y-m-d");
			$tdstr = ($timeSpecific) ? $targetDate->format("Y-m-d H:i:s") : $targetDate->format("Y-m-d");
			$edstr = ($timeSpecific) ? $endDate->format("Y-m-d H:i:s") : $endDate->format("Y-m-d");

			if ($timeSpecific) return $tdstr >= $sdstr && $tdstr <= $edstr;
			else return $includeToday ? $tdstr >= $sdstr && $tdstr <= $edstr : $tdstr >= $sdstr && $tdstr < $edstr;
		}
		return false;
	}

	/**
	 * Function to check if a given date is in the future from a specific date
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @param MongoDate|DateTime|array|string $startDate
	 * @param MongoDate|DateTime|array|string $endDate
	 * @param bool $timeSpecific
	 * @param bool $includeToday
	 * @return bool
	 */
	function inTheFuture(mixed $targetDate, mixed $fromDate = null, bool $includeToday = false): bool {
		if ($targetDate) {
			if ($fromDate === null) $fromDate = $this->getServerTime();
			$targetDate = clone $this->getDateTimeFromDateInput($targetDate);

			$tdstr = ($targetDate instanceof MongoDate) ? $targetDate->toDateTime()->format("Y-m-d") : $targetDate->format("Y-m-d");
			$cdstr = ($fromDate instanceof MongoDate) ? $fromDate->toDateTime()->format("Y-m-d") : $fromDate->format("Y-m-d");

			return $tdstr > $cdstr || ($includeToday && $tdstr === $cdstr);
		}
		return false;
	}

	/**
	 * Function to check if a given date a new day based in server time
	 * @param MongoDate|DateTime|array|string $date
	 * @return bool
	 */
	function isNewDay(MongoDate | DateTime | array | string $date): bool {
		if ($date) {
			$currentDate = $this->getServerTime();
			$date = clone $this->getDateTimeFromDateInput($date);

			$tdstr = ($date instanceof MongoDate) ? $date->toDateTime()->format("Y-m-d") : $date->format("Y-m-d");
			$cdstr = ($currentDate instanceof MongoDate) ? $currentDate->toDateTime()->format("Y-m-d") : $currentDate->format("Y-m-d");

			return $cdstr > $tdstr;
		}

		return false;
	}

	/**
	 * Function to check if a given date is expired ie. in the past based on server time
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @return bool
	 */
	function isExpired(MongoDate|DateTime|array|string $targetDate): bool {
		$databaseDate = clone $this->getDateTimeFromDateInput($targetDate);
		if (!$databaseDate) return false;

		$currentDate = $this->getServerTime();

		$tdstr = ($databaseDate instanceof MongoDate) ? $databaseDate->toDateTime()->format("Y-m-d H:i:s") : $databaseDate->format("Y-m-d H:i:s");
		$cdstr = ($currentDate instanceof MongoDate) ? $currentDate->toDateTime()->format("Y-m-d H:i:s") : $currentDate->format("Y-m-d H:i:s");

		return $cdstr > $tdstr;
	}

	/**
	 * Function to check if a given date is expired ie. in the past based on a specific time period
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @param string $timePeriod
	 * @param int $duration
	 * @return bool
	 */
	function isExpiredTimeBased(MongoDate|DateTime|array|string $time, int $duration = 1, string $timePeriod = 'minute'): bool {
		$endDate = $this->endDate($time, $timePeriod, $duration);

		$currentDate = new DateTime();

		$tdstr = $endDate->format("Y-m-d H:i");
		$cdstr = $currentDate->format("Y-m-d H:i");

		return $cdstr > $tdstr;
	}

	/**
	 * Function to modify a date with a certain time period to return a modified date that represents an end-date
	 * @param MongoDate|DateTime|array|string $targetDate
	 * @param string $timePeriod
	 * @param int $duration
	 * @return DateTime
	 */
	function endDate(MongoDate|DateTime|array|string $targetDate, string $timePeriod, int $duration = 1): DateTime {
		$currentDate = clone $this->getDateTimeFromDateInput($targetDate);
		if (!$currentDate) $currentDate = $this->getServerTime();

		$day = $currentDate->format('j');

		$currentDate = $this->addTime($currentDate, $timePeriod, $duration);
		$next_month_day = $currentDate->format('j');

		if (($timePeriod === 'month' || $timePeriod === 'year') && $day != $next_month_day) {
			$currentDate->modify('last day of last month');
		}

		return $currentDate;
	}

	/**
	 * Function to modify (add) a date with a certain time period to return a modified date
	 * @param MongoDate|DateTime|array|string $date
	 * @param string $timePeriod
	 * @param int $duration
	 * @return DateTime
	 */
	function addTime(MongoDate|DateTime|array|string $date, string $timePeriod, int $duration = 1): DateTime {
		$date = clone $this->getDateTimeFromDateInput($date);
		$day = $date->format('j');

		$date->modify("+{$duration} {$timePeriod}");
		$next_month_day = $date->format('j');

		if (($timePeriod === 'month' || $timePeriod === 'year') && $day != $next_month_day) {
			$date->modify('last day of last month');
		}

		return $date;
	}

	/**
	 * Function to modify (remove) a date with a certain time period to return a modified date
	 * @param MongoDate|DateTime|array|string $date
	 * @param string $timePeriod
	 * @param int $duration
	 * @return DateTime
	 */
	function removeTime(MongoDate|DateTime|array|string $date, $timePeriod, $duration = 1): DateTime {
		$date = clone $this->getDateTimeFromDateInput($date);
		$day = $date->format('j');

		$date->modify("-{$duration} {$timePeriod}");
		$next_month_day = $date->format('j');

		if (($timePeriod === 'month' || $timePeriod === 'year') && $day != $next_month_day) {
			$date->modify('last day of last month');
		}

		return $date;
	}

	/**
	 * Function to retrieve a readable date string from a date object
	 * @param MongoDate|DateTime|array|string $date
	 * @param string $searchKey
	 * @param User $user
	 * @param string $formatType
	 * @return string
	 */
	function getReadableDate(MongoDate|DateTime|array|string $date, User $user = null, string $formatType = "date_hour"): string {
		$formats = [
			"date" => "d-M-y",
			"date_presently" => "d-M-y",
			"date_hour" => "d-M-y H:i",
			"date_time" => "d-M-y H:i:s",
			"date_pro" => "d-m-y H:i:s",
			"date_number" => "d-m-Y",
			"date_number_reversed" => "Y-m-d",
			"time" => "H:i:s",
			"timestamp" => "U",
		];

		$theDate = $this->getDateTimeFromDateInput($date);

		$format = (isset($formats[$formatType])) ? $formats[$formatType] : $formats["date_hour"];
		if ($theDate) {
			if ($formatType === "date_presently") {
				$isToday = $this->isToday($theDate);
				if ($isToday) return 'Today';
				$tomorrow = $this->addTime('NOW', 'day', 1);
				$isTomorrow = $this->isSameDay($theDate, $tomorrow);
				if ($isTomorrow) return 'Tomorrow';
			}
			if ($user) {
				$userSettings = $user->getSettings();
				$preferenceSettings =  ($userSettings) ? $userSettings->getPreferenceSettings() : null;
				$userTimeZone = ($preferenceSettings) ? $preferenceSettings->getTimezone() : null;
				if ($userTimeZone) {
					$theDate->setTimezone(new DateTimeZone($userTimeZone));
					return $theDate->format($format);
				}
			}
			return $theDate->format($format);
		}
		return null;
	}


	/**
	 * Function to retrieve a DateTime objet from an date mixed value
	 * @param MongoDate|DateTime|array|string $date
	 * @param bool $fallback
	 * @return DateTime
	 */
	function getDateTimeFromDateInput(MongoDate|DateTime|array|string $date, bool $fallback = false): DateTime {
		$theDate = null;
		if ($date instanceof MongoDate) $theDate = $date->toDateTime();
		else if ($date instanceof \DateTime) $theDate = $date;
		else if (is_array($date)) $theDate = new DateTime((string) $date['date']);
		else if ($date) $theDate = new DateTime((string) $date);

		if (!$theDate && $fallback) $theDate = $this->getServerTime();
		return $theDate;
	}

	/**
	 * Function to return a single value that is found based on the potential search keys
	 * As soon as one of the keys exist in the target array return it
	 * @param array $target
	 * @param array $searchKeys
	 * @return mixed
	 */
	function getArrayValueByOptions(array $target, array $searchKeys): mixed {
		foreach ($searchKeys as $sKey) {
			if (isset($target[$sKey])) return $target[$sKey];
		}
		return null;
	}

	/**
	 * Function to check if a value is in an array (expansion on PHP in_array()) that also can search in nested values
	 * @param string $searchVal
	 * @param array $searchArr
	 * @param bool $deep
	 * @return bool
	 */
	function inMyArray(string $searchVal, array $searchArr, bool $deep = false): bool {
		if (!$searchArr || !$searchVal) return false;

		if ($deep) {
			foreach ($searchArr as $value) {
				if (in_array($searchVal, $value, true))  return true;
			}
		} else if (in_array($searchVal, $searchArr, true)) return true;
		return false;
	}

	/**
	 * Function to check if a value is in an array based a specifc key and value
	 * @param string $searchVal
	 * @param string $searchKey
	 * @param array $inspectLoop
	 * @return bool
	 */
	function inArrByKeyValue(array $inspectLoop, string | array $searchKey, mixed $searchVal): bool {
		foreach ($inspectLoop as $iloop) {
			if (is_array($searchKey)) {
				foreach ($searchKey as $sKey) {
					if (isset($iloop[$sKey]) && $iloop[$sKey] === $searchVal) return true;
				}
			} else {
				if (isset($iloop[$searchKey]) && $iloop[$searchKey] === $searchVal) return true;
			}
		}
		return false;
	}

	/**
	 * Function to retrieve timezones
	 * @return array
	 */
	function timezone_list(): array {
		static $timezones = null;

		if ($timezones === null) {
			$timezones = [];
			$now = new DateTime('now', new DateTimeZone('UTC'));
			$identifiers = DateTimeZone::listIdentifiers();

			foreach ($identifiers as $timezone) {
				$now->setTimezone(new DateTimeZone($timezone));
				$offset = $now->getOffset();
				$value = '(' . $this->formatGMToffset($offset) . ') ' . $this->formatTimezoneName($timezone);
				$timezones[] = ['key' => $timezone, 'zone' => $value];
			}
		}

		return $timezones;
	}

	/**
	 * Function order array by date based on order type and specific date_key
	 * @param string $order
	 * @param string $dateProperty
	 */
	function orderByDate(array &$array, string $dateProperty, string $order = 'desc') {
		usort($array, function ($a1, $a2) use ($dateProperty, $order) {
			$v1 = strtotime($a1[$dateProperty]);
			$v2 = strtotime($a2[$dateProperty]);
			return $order === 'desc' ? $v2 - $v1 : $v1 - $v2;
		});
	}

	/**
	 * Function order array by date based on order type and specific date_key
	 * @param array $toSortArr
	 * @param bool $desc
	 * @param string $orderBy
	 * @return array
	 */
	function orderByMultiDimensional(array $toSortArr = array(), string $orderBy = 'key', bool $desc = true): array {
		$sortedArr = [];
		if (sizeof($toSortArr) > 0) {
			$sortedArr = $toSortArr;
			$orderColumn = array_column($sortedArr, $orderBy);
			array_multisort($orderColumn, $desc ? SORT_DESC : SORT_ASC, $sortedArr);
		}
		return $sortedArr;
	}

	/**
	 * Function to pretify and format given offset
	 * @param mixed $offset
	 * @return string
	 */
	function formatGMToffset(mixed $offset): string {
		if (!$offset) return 'GMT';
		$hours = intval($offset / 3600);
		$minutes = abs(intval($offset % 3600 / 60));
		return 'GMT' . sprintf('%+03d:%02d', $hours, $minutes);
	}

	/**
	 * Function to pretify given timezone name
	 * @param string $name
	 * @return string
	 */
	function formatTimezoneName(string $name): string {
		$name = str_replace('/', ', ', $name);
		$name = str_replace('_', ' ', $name);
		$name = str_replace('St ', 'St. ', $name);
		return $name;
	}


	/**
	 * Function to generate a unique ID based on a set of specifics
	 * @param string $name
	 * @param string $timestamp
	 * @param string $specials
	 * @return string
	 */
	function generateId(int $length = 15, bool $timestamp = true, bool $specials = false): string {
		$text = '';
		$possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		if ($specials) $possible .= '._()+-!@';

		if (is_array($length) && sizeof($length) === 2 && is_numeric($length[0]) && is_numeric($length[1])) $length = rand($length[0], $length[1]);

		for ($i = 0; $i < $length; $i++) {
			$text .= substr($possible, (floor($this->randomFloat() * strlen($possible))), 1);
		}

		if ($timestamp) {
			$timenow   = new DateTimeImmutable();
			$issuedAt   = $timenow->getTimestamp();
			$text .= $issuedAt;
		}

		return $text;
	}

	/**
	 * Function to generate a unique quickurl or string based on input and unique array
	 * if such a quickurl already exists call oneself and add a suffix, then check again
	 * @param array $uniqueArr
	 * @param string $quickUrl
	 * @param int $newIndex
	 * @return string
	 */
	function uniqueQuickurlFromDupes(array $uniqueArr, string $quickUrl, int $newIndex = 1): string {
		$pos = strrpos($quickUrl, '-');
		$lastIndex = $pos === false ? $quickUrl : substr($quickUrl, $pos + 1);
		$dupeIndex = (is_int($lastIndex)) ? $lastIndex++ : $newIndex;
		$quicklink = $quickUrl . '-' . $dupeIndex;
		if ($this->isDuplicateUrl($uniqueArr, $quicklink)) {
			$dupeIndex++;
			return $this->uniqueQuickurlFromDupes($uniqueArr, $quickUrl, $dupeIndex);
		} else return $quicklink;
	}

	function randomFloat() {
		return mt_rand() / (mt_getrandmax() + 1);
	}

	/**
	 * Function to check if quickurl or string is duplicate
	 * @param array $entries
	 * @param string $seoUrl
	 * @param string $key
	 * @return bool
	 */
	function isDuplicateUrl(array $entries, string $seoUrl, string $key = 'quickurl'): bool {
		if ($entries && is_array($entries) && sizeof($entries) > 0) {
			foreach ($entries as $entry) {
				if ($entry[$key] === $seoUrl) return true;
			}
		}
		return false;
	}

	/**
	 * Function to create a pretty URL string, with the following options
	 * Retains language specific characters like accent marks
	 * Repaces white spaces with dashes
	 * Repaces lower_dashes with dashes
	 * Removes trailing white space
	 * Optionally removes only allows only numeric and latin alphabet
	 * @param string $string
	 * @param bool $replaceAllSpecialAndAccents
	 * @return string 
	 */
	function seoUrl(string $string, bool $replaceAllSpecialAndAccents = false): string {
		$string = strtolower($string);
		$string = preg_replace("/[\s-]+/", " ", $string);
		$string = preg_replace("/[\s_]/", "-", $string);
		$string = preg_replace('/[^\p{L}0-9-\-]/u', '', $string);
		$string = preg_replace('/-+/', '-', $string);
		if ($replaceAllSpecialAndAccents) $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
		return $string;
	}



	/**
	 * Function to create a pretty URL string, with the following options
	 * Retains language specific characters like accent marks
	 * Retains white spaces
	 * Retains dashes
	 * Retains capitalization
	 * Removes trailing white space
	 * Replaces underscore with spaces
	 * Optionally removes numeric
	 * Optionally removes only allows only numeric and latin alphabet
	 * @param string $string
	 * @param bool $replaceAllSpecialAndAccents
	 * @param bool $replaceNumeric
	 * @return string 
	 */
	function seoName(string $string, bool $replaceAllSpecialAndAccents = false, bool $replaceNumeric = false): string {
		$string = preg_replace("/[\s_]/", "_", $string);
		$string = preg_replace('/[^\p{L}0-9-_\-]/u', '', $string);
		$string = preg_replace('/-+/', '-', $string);
		$string = preg_replace('/_+/', '_', $string);
		$string = trim($string, '-');
		$string = str_replace("_", " ", $string);

		if ($replaceNumeric) $string = preg_replace("/[0-9]/", "", $string);
		if ($replaceAllSpecialAndAccents) $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
		return $string;
	}

	/**
	 * Function to create a pretty URL string, with the following options
	 * Retains language specific characters like accent marks
	 * Retains dashes
	 * Retains capitalization
	 * Removes trailing white space
	 * Replaces white spaces with dash
	 * Replaces lowercase with dash
	 * Optionally removes numeric
	 * Optionally removes only allows only numeric and latin alphabet
	 * @param string $string
	 * @param bool $replaceAllSpecialAndAccents
	 * @param bool $replaceNumeric
	 * @param bool $tolowercase
	 * @return string 
	 */
	function seoQuickName(string $string, bool $replaceAllSpecialAndAccents = false, bool $replaceNumeric = false, bool $tolowercase = false): bool {
		$string = preg_replace("/[\s_]/", "_", $string);
		$string = preg_replace('/[^\p{L}0-9-_\-]/u', '', $string);
		$string = preg_replace('/-+/', '-', $string);
		$string = preg_replace('/_+/', '_', $string);
		$string = trim($string, '-');
		$string = str_replace("_", "-", $string);

		if ($replaceNumeric) $string = preg_replace("/[0-9]/", "", $string);
		if ($replaceAllSpecialAndAccents) $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
		if ($tolowercase) $string = strtolower($string);
		return $string;
	}

	function iterableEmpty($iterable) {
		return !$iterable || ((is_array($iterable) && sizeof($iterable) === 0) || iterator_count($iterable) === 0);
	}


	/**
	 * Function to create a URL safe string and trim 
	 * @param bool $entry
	 * @return string 
	 */
	function cleanAndSeo(string $entry): string {
		return ($entry) ? (string) trim($this->seoUrl($this->escape_regex_for_mysql(trim(strtolower($entry)))), '-') : null;
	}

	/**
	 * Function to create a URL safe string
	 * @param bool $entry
	 * @return string 
	 */
	function cleanAndTrim(string $entry): string {
		return ($entry) ? (string) $this->seoName($this->escape_regex_for_mysql(trim($entry))) : null;
	}

	function escape_regex_for_mysql(string $dangerous_string): string {
		return preg_replace('/&/', '\\&', preg_quote($dangerous_string));
	}

	function arraysEqual(array $array1, array $array2): bool {
		array_multisort($array1);
		array_multisort($array2);
		return (serialize($array1) === serialize($array2));
	}

	function getCleanName(string $entry): string {
		return ($entry) ? (string) $this->escape_regex_for_mysql(trim(strtolower($entry))) : null;
	}

	function arrayContains(array $newArr, array $oldArr) {
		return !array_diff($oldArr, $newArr);
	}

	function missingValues(array $searchArr, array $missingArr): array {
		return array_diff($searchArr, $missingArr);
	}

	function encodeData(mixed $data): string {
		return base64_encode(serialize($data));
	}

	function decodeData(mixed $data): mixed {
		return unserialize(base64_decode($data));
	}

	function encodeDataJson(mixed $data): string {
		return json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	function decodeDataJson(mixed $data): mixed {
		return json_decode($data, true);
	}
}
