<?php

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * BaseDocument class is a super class which holds basic logic functions
 */


/**
 * @ODM\MappedSuperclass
 */
abstract class BaseDocument implements \JsonSerializable {
    /** @ODM\Id */
    protected $id;

    /**
     * @return array
     */
    public function jsonSerialize(): mixed {
        return json_encode($this->toArray());
    }

    public function __clone() {
        if ($this->id) {
            $this->id = null;
        }
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }


    
    /**
     * Function that iterates over its accessible getter functions to retrieve its value and
     * add it to an array. In essence it mimics the conversion of an object to an array
     * @return array
     */
    public function toArray(): array {
        $gettableAttributes = [];

        /**Retrieve current object methods based on class name */
        $classMethods = get_class_methods(get_class($this));

        /**Retrieve ClassMetadata object methods based on class name */
        $metaGetterNames = get_class_methods(ClassMetadata::class);

        foreach ($classMethods as $funcName) {

            /**Only interested in getter functions, hence check if function starts with get */
            $isGetter = substr($funcName, 0, 3) === 'get';


            /**If not getter or getter is from 3rd party ClassMetaData class - skip iteration and continue */
            if (!$isGetter || ($isGetter && in_array($funcName, $metaGetterNames))) {
                continue;
            }


            /**Get property name that always follows after 'get' in a method */
            $propName = strtolower(substr($funcName, 3, 1));
            $propName .= substr($funcName, 4);

            /**Get value of getter method by calling it */
            $value = $this->$funcName();

            /**Assign getter value to the new array */
            $gettableAttributes[$propName] = $value;


            /**If value is an object check if it may be converted to an array 
             * and essentially call this function again on the designated object */
            if (is_object($value)) {
                if ($value instanceof PersistentCollection) {
                    $values = [];
                    $collection = $value;
                    foreach ($collection as $obj) {
                        /** @var BaseDocument $obj */
                        if ($obj instanceof \JsonSerializable) {
                            $values[] = $obj->toArray();
                        } else {
                            $values[] = $obj;
                        }
                    }
                    $gettableAttributes[$propName] = $values;
                } elseif ($value instanceof \JsonSerializable) {
                    /** @var BaseDocument $value */
                    if (method_exists($value, 'toArray')) $gettableAttributes[$propName] = $value->toArray();
                    else $gettableAttributes[$propName] = $value;
                }
            }
        }

        return $gettableAttributes;
    }


    /**
     * Function that reconstructs an object/array to a class as 
     * demonstrated in NewsService->historyToData() & ClassReconstructionService
     * @param array|object $objValues
     * @param ClassMetadataFactory $mdFactory
     * @param object|string $object
     * @return object
     */
    public function reconstruct(array | object $objValues, ClassMetadataFactory $mdFactory, object | string $object = null): object {
        if (is_array($objValues) || is_object($objValues)) {

            /**Target one self if no object is given, otherwise instantiate a new object */
            if (!$object) $object = $this;
            else if (is_string($object)) {
                $ref = new ReflectionClass($object);
                $object = $ref->newInstanceWithoutConstructor();
            }

            /**Use Doctrine getMetadataFor method to obtain metadata of object, specifically fieldMappings */
            $classMeta = $mdFactory->getMetadataFor(get_class($object));


            /**Iterate over fieldMappings*/
            foreach ($classMeta->fieldMappings as $fieldMapping) {
                $classProp = $fieldMapping['fieldName'];
                $classPropType = $fieldMapping['type'];

                /**Execute for every field except history field as we are not interested in it*/
                if ($classProp !== 'history') {

                    /**Get Info of specific class property*/
                    $classPropRefl = new \ReflectionProperty($object, $classProp);
                    $classPropPrivate = $classPropRefl->isPrivate();

                    /**Get class property value*/
                    $classPropVal = isset($objValues[$classProp]) ? $objValues[$classProp] : null;


                    /**Get Document (model) if property happens to refer one*/
                    $classPropDoc = isset($fieldMapping['targetDocument']) ? $fieldMapping['targetDocument'] : null;


                    if (!$classPropPrivate) {
                        if (!$classPropDoc) {
                            /**If class prop value is iterable - iterate over it and assign it to object property, 
                             * else directly assign to object propert */
                            if ($classPropVal && ($classPropType === 'many' || $classPropType === 'hash' || $classPropType === 'collection')) {
                                foreach ($classPropVal as $prop => $value) {
                                    $object->$classProp[$prop] = $value;
                                }
                            } else $object->$classProp = $classPropVal;

                        } else if ($classPropDoc && (!is_array($classPropVal) || (is_array($classPropVal) && !empty($classPropVal)))) {
                             /**If class prop happens to be a Document (model) 
                              * essentially call this function on said document 
                              * and assign its return value to object property */
                            if ($classPropVal && $classPropType === 'many' || $classPropType === 'hash' || $classPropType === 'collection') {
                                foreach ($classPropVal as $prop => $value) {
                                    $object->$classProp[$prop] = $this->reconstruct($value, $mdFactory, $classPropDoc);
                                }
                            } else $object->$classProp = $this->reconstruct($classPropVal, $mdFactory, $classPropDoc);
                        }
                    }
                }
            }
            return $object;
        }
    }
}
