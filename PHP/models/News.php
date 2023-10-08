<?php
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\UTCDateTime as MongoDate;
/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * News class is a model class that defines the properties of a News object
 * A typical model with getters and setters to access and modify its properties
 * Inherits the EntityBase class which holds base entity properties
 */

/** @ODM\Document(collection="News") */
class News extends EntityBase{
	/** @ODM\Field(type="date") */
	protected mixed $publish_date;
	/** @ODM\Field(type="string") */
	protected string $title;
	/** @ODM\Field(type="string") */
	protected string $message;
	/** @ODM\Field(type="string") */
	protected string $image;
	/** @ODM\Field(type="string") */
	protected string $redirect;
	/** @ODM\Field(type="collection") */
	protected array $tags;
	/** @ODM\Field(type="string") */
	protected string $banner;
	
	function __construct($title, $message, $redirect){
		$this->title = $title;
		$this->message = $message;
		$this->redirect = $redirect;
		$this->publish_date = new MongoDate();
	}

	public function getPublish_date(){
		return $this->publish_date;
	}

	public function setPublish_date($publish_date){
		$this->publish_date = $publish_date;
	}

	public function getTitle(){
		return $this->title;
	}

	public function setTitle(string $title){
		$this->title = $title;
	}

	public function getMessage(){
		return $this->message;
	}

	public function setMessage(string $message){
		$this->message = $message;
	}

	public function getImage(){
		return $this->image;
	}

	public function setImage(string $image){
		$this->image = $image;
	}

	public function getRedirect(){
		return $this->redirect;
	}

	public function setRedirect(string $redirect){
		$this->redirect = $redirect;
	}
	
	public function getTags(){
		return $this->tags;
	}

	public function setTags(array $tags){
		$this->tags = $tags;
	}

	public function getBanner(){
		return $this->banner;
	}

	public function setBanner(string $banner){
		$this->banner = $banner;
	}
}
