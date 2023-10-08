<?php

use Doctrine\Common\Util\ClassUtils as dbClassUtils;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * ClassReconstructionService class is a utility class to create and parse Snapshots
 * this utility service is mostly used in conjuction with HistoryService in order to reparse back-up entities
 */
class HistoryService {
	private GlobalService $globalService;
	private DocumentManager $dbManager;
	function __construct() {
		$this->globalService = GlobalService::getInstance();
		$this->dbManager = (DatabaseManager::getInstance())->initializeDbManager();
	}

	/**
	 * Function to create a snapshot of an entity's (Class) current state, store it in the $data property
	 * Adds this Snapshot to the history property of said entity
	 * @param object $entity
	 * @param string user (id)
	 */
	function snapshot(object $entity, string $user = null) {
		$entityArr = $entity->toArray();
		unset($entityArr['history']);

		$snapshotData = $this->globalService->encodeDataJson($entityArr);
		$snapshot = new Snapshot($this->globalService->getAppVersion(), $this->globalService->getApiVersion(), $snapshotData, $user);

		$entityHistory = $entity->getHistory();
		if (!$entityHistory) {
			$entityClass = dbClassUtils::getClass($entity);
			$entityHistory = new History($entityClass, $entity->getId());
			$this->dbManager->persist($entityHistory);
		}

		$entityHistory->snapshot($snapshot);
		$entity->setHistory($entityHistory);
	}

	/**
	 * Function to retrieve an entity its history (Snapshots) and parse its data to be used in code
	 * @param object|array $item
	 * @return array
	 */
	function historyToData(object | array $item): array {
		$historyArr = [];
		$history = is_array($item) ? $item['history'] : $item->getHistory();
		if (!empty($history)) {
			$snapshots = is_array($history) ? $history['snapshots'] : $history->getSnapshots();
			foreach ($snapshots as $snapshot) {
				$historyArr[] = $this->snapshotToData($snapshot);
			}
		}
		return $historyArr;
	}

	/**
	 * Function to parse a Snapshot's data to be used in code
	 * @param Snapshot|array $item
	 * @return array
	 */
	private function snapshotToData(Snapshot | array $snapshot): array {
		$snapshotArr = is_array($snapshot) ? $snapshot : $snapshot->toArray();
		$snapshotArr['data'] = $this->globalService->decodeDataJson($snapshotArr['data']);
		$snapshotArr['date'] = $this->globalService->getReadableDate($snapshotArr['date']);
		return $snapshotArr;
	}
}
