<?php

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * Description: Singleton class that controls the final REST API output
 */
class OutputService {
	private $GLOBAL_CONFIG;
	private $outputQueue = [];
	private static $instance = null;

	private function __construct() {
		$this->GLOBAL_CONFIG = new GlobalConfig();
	}

	public static function getInstance() {
		if (!self::$instance) self::$instance = new OutputService();
		return self::$instance;
	}

	/**
	 * Function that adds an array of data as input to the output array, 
	 * if applicable sets a specific destination, also checks for duplicate keys, creates new output key if necessary
	 * @param array $data
	 * @param array|string $destination
	 * @return OutputService
	 */
	function input(array $data, array | string $destination = null): OutputService {
		if ($data) {
			$outputCopy = $this->outputQueue;

			if ($destination) {
				if (is_array($destination)) $this->targetNestedDestination($destination, $data, $outputCopy);
				else $this->checkKeyDataDestination($destination, $data, $outputCopy);
			} else {
				foreach ($data as $key => $value) {
					$this->checkKeyDataDestination($key, $value, $outputCopy);
				}
			}
			$this->outputQueue = $outputCopy;
		}
		return $this;
	}

	/**
	 * Function that creates a nested output destination and assigns the data to it 
	 * @param array $nestedKeyNames
	 * @param array $destination
	 * @param array|string $data
	 */
	function targetNestedDestination(array $nestedKeyNames, array | string $data, array &$destination) {
		$level = &$destination;
		foreach ($nestedKeyNames as $nkName) {
			if (!array_key_exists($nkName, $level)) $level[$nkName] = [];
			$level = &$level[$nkName];
		}
		$level = $data;
	}

	/**
	 * Function that checks if key is duplicate, if not assigns the data to it
	 * if so create a similar named key and assign the data to it
	 * @param string $key
	 * @param array $outputCopy
	 * @param array|array|string $data
	 */
	function checkKeyDataDestination(string $key, array | string $data, array &$outputCopy) {
		$destination = &$outputCopy[$key];
		if (isset($destination)) {
			if (is_array($destination)) {
				if (is_array($data)) {
					foreach ($data as $valueEntry) {
						array_push($destination, $valueEntry);
					}
				} else array_push($destination, $data);
			} else {
				$existingValue = $destination;
				if ($existingValue !== $data) {
					$newKey = $this->getUniqueKeyName($outputCopy, $key);
					$outputCopy[$newKey] = $data;
				}
			}
		} else $destination = $data;
	}

	/**
	 * Function that creates a similar named unique key based on the keynames of an array
	 * @param string $key
	 * @param array $arr
	 * @param int $index
	 * @return string
	 */
	function getUniqueKeyName(array $arr, string $key, int $index = 1): string {
		if (!isset($arr[$key])) return $key;

		$newName = $key . '_' . $index;
		if (isset($arr[$newName])) return $this->getUniqueKeyName($arr, $key, ++$index);
		return $newName;
	}

	function check() {
		return $this->outputQueue;
	}

	function reset() {
		$this->outputQueue = [];
		return $this;
	}

	/**
	 * Function that outputs the collected data as JSON representation 
	 * so that it can be read and accessed in the front end.
	 * By default also appends the app & api version.
	 * @param bool $versionData
	 */
	function output(bool $versionData = true) {

		//assign new var with outputQueue so that original stays untouched
		$finalOutput = $this->outputQueue;

		$this->reset();

		if ($versionData) {
			$finalOutput['app_version'] = $this->GLOBAL_CONFIG->getAppVersion();
			$finalOutput['api_version'] = $this->GLOBAL_CONFIG->getApiVersion();
		}
		echo json_encode($finalOutput);
		exit();
	}
}
