<?php

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime as MongoDate;

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * NewsController class is a controller class that is the logic between the REST API output
 * and the utility services. Also controls CRUD operations on its related models.
 */
class NewsController implements BaseControllerIFace{
	private AuthorizationService $authorizationService;
	private SearchUtilityService $searchUtilityService;
	private HistoryService $historyService;
	private NewsService $newsService;
	private OutputService $ioService;
	private DocumentManager $dbManager;
	private int $limit = 15;

	function __construct(AuthorizationService $authorizationService = null) {
		$this->authorizationService = $authorizationService;
		$this->ioService = OutputService::getInstance();
		$this->dbManager = (DatabaseManager::getInstance())->initializeDbManager();
		$this->searchUtilityService = new SearchUtilityService();
		$this->newsService = new NewsService($this->authorizationService);
		$this->historyService = new HistoryService();
	}

	/**
	 * Function to fetch a single News article by id and parse its generic date
	 * which can be used on the front end after output
	 * @param array $params
	 */
	function display(array $params) {
		$id = (isset($params['id'])) ? (string) $params['id'] : false;
		$article = $this->dbManager->find('News', $id);

		if ($article) {
			$articleData = $this->newsService->newsArticleGenericData($article);
			if ($articleData) $this->ioService->input($articleData)->output();
			else $this->ioService->input(['error' => 'no_access'])->output();
		} else $this->ioService->input(['error' => 'no_course'])->output();
	}


	/**
	 * Function to fetch News articles based on a certain category
	 * which can be used on the front end after output
	 * @param array $params
	 */
	function displayAll(array $params) {
		$source = (string) (isset($params['planet'])) ? $params['planet'] : 'all';

		$rawPageNr = (isset($params['iteration'])) ? $params['iteration'] : 0;
		$limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;

		$pageNr = (is_numeric($rawPageNr)) ? abs(intval($rawPageNr)) : 0;
		$skip = $pageNr * $limit;

		$params['skip'] = $skip;
		$params['limit'] = $limit;
		$articlesData = [];
		switch ($source) {
			case "all":
			default:
				$articlesData = $this->newsService->getNewsArticles($params['limit'], $params['skip']);
				break;
			case "unpublicized":
				$articlesData = $this->newsService->getUnpublizicedNewsArticles($params['limit'], $params['skip']);
				break;
			case "outdated":
				$articlesData = $this->newsService->getOutdatedNewsArticles($params['limit'], $params['skip']);
				break;
			case "marked":
				$articlesData = $this->newsService->getMarkedNewsArticles($params['limit'], $params['skip']);
				break;
		}

		$returnArr = $this->searchUtilityService->searchResultsArr('news', null, $articlesData, $params);
		$returnArr['results'] = $this->newsService->getAllNews($articlesData['results']);
		$this->ioService->input($returnArr)->output();
	}

	/**
	 * Function to create a News article that requires a title, message and publish date
	 * @param array $params
	 */
	function createEntity(array $params) {
		$returnArr['return'] = false;
		$title = isset($params['title']) ? $params['title'] : null;
		$message = isset($params['message']) ? $params['message'] : null;
		$publishDate = isset($params['publish_date']) ? new DateTime($params['publish_date']) : null;
		$banner = isset($params['banner']) ? $params['banner'] : null;
		$redirect = isset($params['redirect']) ? $params['redirect'] : null;

		if ($title && $message && $publishDate) {
			$newsArticle = new News($title, $message, false);
			$publishDate = ($publishDate instanceof DateTime) ? new MongoDate($publishDate) : $publishDate;
			$newsArticle->setPublish_date($publishDate);

			if ($banner) $newsArticle->setBanner($banner);
			if ($redirect) $newsArticle->setRedirect($redirect);


			$this->dbManager->persist($newsArticle);
			$this->dbManager->flush();

			$returnArr['return'] = true;
			$returnArr['article'] = $this->newsService->newsArticleGenericData($newsArticle);
		}

		$this->ioService->input($returnArr)->output();
	}

	/**
	 * Function to update a News article based on News ID
	 * @param array $params
	 */
	function updateEntity(array $params) {
		$returnArr['return'] = false;
		$id = isset($params['id']) ? $params['id'] : null;

		if ($id) {
			$newsArticle = $this->dbManager->find('News', $id);

			$title = isset($params['title']) ? $params['title'] : null;
			$message = isset($params['message']) ? $params['message'] : null;
			$snapshot = isset($params['snapshot']) ? $params['snapshot'] : null;
			$publishDate = isset($params['publish_date']) ? new DateTime($params['publish_date']) : null;
			$banner = isset($params['banner']) ? $params['banner'] : null;
			$redirect = isset($params['redirect']) ? $params['redirect'] : null;

			if ($newsArticle && $title && $message && $publishDate) {
				if (!$snapshot) $this->historyService->snapshot($newsArticle);
				$publishDate = ($publishDate instanceof DateTime) ? new MongoDate($publishDate) : $publishDate;
				$newsArticle->setPublish_date($publishDate);
				$newsArticle->setBanner($banner);
				$newsArticle->setRedirect($redirect);
				$newsArticle->setTitle($title);
				$newsArticle->setMessage($message);

				$this->dbManager->flush();

				$returnArr['return'] = true;
				$returnArr['article'] = $this->newsService->newsArticleGenericData($newsArticle);
			}
		}

		$this->ioService->input($returnArr)->output();
	}

	function deleteEntity(array $params) {
	}
}
