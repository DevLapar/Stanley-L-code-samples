<?php
/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * BaseControllerIFace is an interface that defines a set of 
 * default functions a controller class should abide by
 */
interface BaseControllerIFace {
	public function display(array $params);
	public function displayAll(array $params);
	public function updateEntity(array $params);
	public function createEntity(array $params);
	public function deleteEntity(array $params);
}