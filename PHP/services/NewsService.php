<?php

use Doctrine\ODM\MongoDB\DocumentManager;
use \MongoDB\BSON\Regex;

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * NewsService class is a utility class for the NewsController and News models
 * This utility class fetches the database, and performs actions based on fetched models.
 */
class NewsService {
	private DocumentManager $dbManager;
	private GlobalService $globalService;
	private ClassLoaderService $classLoaderService;
	private const fetch_range = 30;

	function __construct(AuthorizationService $authorizationService = null) {
		$this->dbManager = (DatabaseManager::getInstance())->initializeDbManager();
		$this->globalService = GlobalService::getInstance();
		$this->classLoaderService = new ClassLoaderService($authorizationService);
	}

	/**
	 * Function to fetch the database for most recent news articles in descending order (based on publish date)
	 * Based on the $newsLastDays how recent, and $limit and $skip how many entries to fetch.
	 * @param int $newsLastDays
	 * @param int $limit
	 * @param int $skip
	 * @return Iterator
	 */
	function getRecentNewsArticles(int $newsLastDays = null, int $limit = 0, int $skip = 0): Iterator {
		if (!$newsLastDays) $newsLastDays = self::fetch_range;

		$dateNow = new DateTime();
		$startFromDate = $this->globalService->removeTime($dateNow, 'day', $newsLastDays);

		$query = $this->dbManager->createQueryBuilder('News')
			->field('publish_date')->gte($startFromDate)
			->field('publish_date')->lte($dateNow)
			->sort(array(
				'publish_date' => 'desc',
			))
			->limit($limit)
			->skip($skip);
		$items = $query->getQuery()->execute();
		return $items;
	}

	/**
	 * Function to fetch the database for all news articles in descending order (based on publish date)
	 * Based on $limit and $skip how many entries to fetch. 
	 * Returns an array that holds the results and total amount of fetchable entries
	 * @param int $limit
	 * @param int $skip
	 * @return array
	 */
	function getNewsArticles(int $limit = 0, int $skip = 0): array {
		$results = [
			'total' => 0,
			'results' => null,
		];

		$query = $this->dbManager->createQueryBuilder('News')->sort('publish_date', 'desc');
		$cQuery = clone $query;

		$results['results'] = $query->limit($limit)->skip($skip)->getQuery()->execute();
		$results['total'] = $cQuery->count()->getQuery()->execute();

		return $results;
	}

	/**
	 * Function to fetch the database for Unpubliziced news articles in descending order (based on publish date)
	 * Based on $limit and $skip how many entries to fetch. 
	 * Returns an array that holds the results and total amount of fetchable entries
	 * @param int $limit
	 * @param int $skip
	 * @return array
	 */
	function getUnpublizicedNewsArticles(int $limit = 0, int $skip = 0): array {
		$results = [
			'total' => 0,
			'results' => null,
		];

		$dateNow = new DateTime();

		$query = $this->dbManager->createQueryBuilder('News')
			->field('publish_date')->gte($dateNow)
			->sort(array(
				'publish_date' => 'desc',
			));

		$cQuery = clone $query;

		$results['results'] = $query->limit($limit)->skip($skip)->getQuery()->execute();
		$results['total'] = $cQuery->count()->getQuery()->execute();

		return $results;
	}

	/**
	 * Function to fetch the database for outdated news articles in descending order (based on publish date)
	 * Based on $limit and $skip how many entries to fetch. 
	 * Returns an array that holds the results and total amount of fetchable entries
	 * @param int $limit
	 * @param int $skip
	 * @return array
	 */
	function getOutdatedNewsArticles(int $limit = 0, int $skip = 0, $newsLastDays = 0): array {
		if (!$newsLastDays) $newsLastDays = self::fetch_range;

		$dateNow = new DateTime();
		$startFromDate = $this->globalService->removeTime($dateNow, 'day', $newsLastDays);
		$results = [
			'total' => 0,
			'results' => null,
		];

		$query = $this->dbManager->createQueryBuilder('News')
			->field('publish_date')->lt($startFromDate)
			->sort(array(
				'publish_date' => 'desc',
			));

		$cQuery = clone $query;

		$results['results'] = $query->limit($limit)->skip($skip)->getQuery()->execute();
		$results['total'] = $cQuery->count()->getQuery()->execute();

		return $results;
	}

	/**
	 * Function to fetch the database for news articles that are marked in descending order (based on publish date)
	 * Based on $limit and $skip how many entries to fetch. 
	 * Returns an array that holds the results and total amount of fetchable entries
	 * @param int $limit
	 * @param int $skip
	 * @return array
	 */
	function getMarkedNewsArticles(int $limit = 0, int $skip = 0): array {
		$results = [
			'total' => 0,
			'results' => null,
		];

		$query = $this->dbManager->createQueryBuilder('News')
			->field('marks')->notEqual(null)
			->sort(array(
				'publish_date' => 'desc',
			));

		$cQuery = clone $query;

		$results['results'] = $query->limit($limit)->skip($skip)->getQuery()->execute();
		$results['total'] = $cQuery->count()->getQuery()->execute();

		return $results;
	}

	/**
	 * Function that first fetches the database for all news articles
	 * Then gathers generic data that can be used in the front-end
	 * Returns an array of parsed News objects
	 * @param Iterator|array $articles
	 * @return array
	 */
	function getAllNews(array | Iterator $articles): array {
		$articlesItemsArr = array();
		if ($articles && !$this->globalService->iterableEmpty($articles)) {
			foreach ($articles as $item) {
				$tmpArticle = $this->newsArticleGenericData($item);
				array_push($articlesItemsArr, $tmpArticle);
			}
		}
		return $articlesItemsArr;
	}

	/**
	 * Function that first fetches the database for recent news articles
	 * Then gathers generic data that can be used in the front-end
	 * Returns an array of parsed News objects
	 * @return array
	 */
	function getActualNews(): array {
		$articlesItemsArr = array();
		$articles = $this->getRecentNewsArticles();

		if ($articles && !$this->globalService->iterableEmpty($articles)) {
			foreach ($articles as $entry) {
				$article = $this->newsArticleGenericData($entry);
				array_push($articlesItemsArr, $article);
			}
		}
		return $articlesItemsArr;
	}

	/**
	 * Function that gathers generic data that can be used in the front-end
	 * Returns a parsed News object as an array
	 * @param News|array $articles
	 * @param bool $snapshot
	 * @return array
	 */
	function newsArticleGenericData(News | array $item, bool $snapshot = false): array {
		$tmpArticle = !is_array($item) ? $item->toArray() : $item;
		$tmpArticle['publish_date'] = $this->globalService->getReadableDate($tmpArticle['publish_date'], null, 'date_number_reversed');

		if (!$snapshot) {
			$historyService = $this->classLoaderService->getClassInstance(HistoryService::class);
			$markService = $this->classLoaderService->getClassInstance(MarkService::class);

			$tmpArticle['marks'] = $markService->getMarks($item);
			$tmpArticle['actuality'] = $this->determineActuality($item);

			$history = $historyService->historyToData($item);
			$tmpArticle['history'] = $this->historyToData($history);
		}

		return $tmpArticle;
	}

	/**
	 * Function that gathers generic data that can be used in the front-end with the snapshot flag
	 * Then reassigns the $item['data] with its parsed data.
	 * Returns an array of history entries
	 * @param array $history
	 * @return array
	 */
	function historyToData(array $history): array {
		foreach ($history as &$item) {
			$item['data'] = $this->newsArticleGenericData($item['data'], true);
		}
		return $history;
	}

	/**
	 * Function that fetches news articles based on search input (title)
	 * Based on $limit and $skip how many entries to fetch. 
	 * Returns an array that holds the results and total amount of fetchable entries
	 * @param string $targetTitle
	 * @param int $limit
	 * @param int $skip
	 * @return array
	 */
	function searchArticlesByTitle(string $targetTitle, int $limit = 0, int $skip = 0): array {
		$results = [
			'total' => 0,
			'results' => null,
		];
		$regex = new Regex(preg_quote($targetTitle, '/'), 'i');

		$query = $this->dbManager->createQueryBuilder('News')->field('title')->equals($regex);
		$cQuery = clone $query;

		$results['results'] = $query->limit($limit)->skip($skip)->getQuery()->execute();
		$results['total'] = $cQuery->count()->getQuery()->execute();
		return $results;
	}

	/**
	 * Function that fetches news articles based on search input (title)
	 * but excludes specific IDs from the search
	 * Based on $limit and $skip how many entries to fetch. 
	 * Returns an array that holds the results and total amount of fetchable entries
	 * @param string $targetTitle
	 * @param array $exclude
	 * @param int $limit
	 * @param int $skip
	 * @return array
	 */
	function searchArticlesByTitleExclusion(string $targetTitle, int $limit = 0, int $skip = 0, array $exclude = []): array {
		if (!is_array($exclude)) $exclude = [$exclude];
		$results = [
			'total' => 0,
			'results' => null,
		];

		$regex = new Regex(preg_quote($targetTitle, '/'), 'i');

		$qy = $this->dbManager->createQueryBuilder('News')->field('title')->equals($regex);
		$qy->addAnd($qy->expr()->field('id')->not($qy->expr()->in($exclude)));

		$cQuery = clone $qy;

		$results['results'] = $qy->limit($limit)->skip($skip)->getQuery()->execute();
		$results['total'] = $cQuery->count()->getQuery()->execute();
		return $results;
	}

	/**
	 * Function that determines whether the News article is old news
	 * Based on its publish date if its too long ago
	 * @param News $item
	 * @return bool
	 */
	function isOldNews(News $item): bool {
		if ($item && $item->getPublish_date()) {
			$publish = $item->getPublish_date()->getTimestamp();
			$publish = date('Y-m-d', $publish);
			$publish_date = $this->globalService->addTime(new DateTime($publish), 'day', self::fetch_range);
			$today = new DateTime('NOW');
			return ($today > $publish_date) ? true : false;
		}
		return false;
	}

	/**
	 * Function that determines the actuality of a News article
	 * Returns the article's actuality which can be used in the front end
	 * @param News $item
	 * @return string
	 */
	function determineActuality(News $item): string {
		if ($item && $item->getPublish_date()) {
			$publish = $this->globalService->getReadableDate($item->getPublish_date(), null, 'timestamp');

			$dateNow = new DateTime();
			$timestampNow = $dateNow->getTimestamp();
			$startFromDate = ($this->globalService->removeTime($dateNow, 'day', self::fetch_range))->getTimestamp();


			if ($publish > $timestampNow) return 'future';
			else if ($publish < $startFromDate) return 'past';
			else return 'actual';
		}
		return 'past';
	}
}
