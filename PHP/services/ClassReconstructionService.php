<?php

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * ClassReconstructionService class is a utility class to reconstruct objects from an array to its designated class
 * this utility service is mostly used in conjuction with HistoryService in order to reparse back-up entities
 */
class ClassReconstructionService {
	private DocumentManager $dbManager;

	function __construct() {
		$this->dbManager = (DatabaseManager::getInstance())->initializeDbManager();
	}

	/**
	 * Function that iterates over the provided data to see if the expected class contains such a property
	 * if it does, set the property value and reconstruct the class
	 * @template T of object
	 * @param object $entityClass
	 * @param array $data
	 * @return T|null 
	 */
	function reconstruct(mixed $entityClass, array $data): object {
		$ref = new ReflectionClass($entityClass);
		$entity = $ref->newInstanceWithoutConstructor();
		$cmf = $this->dbManager->getMetadataFactory();
		$entity->reconstruct($data, $cmf);
		return $entity;
	}
}
