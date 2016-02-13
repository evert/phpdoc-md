<?php

namespace PHPDocMD\Definitions;

use SimpleXMLElement;
use RuntimeException;

/**
 * Maintains all the information for a collection of definitions.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
abstract class AbstractCollection
{
    /**
     * The abstract definition class every definition must inherit.
     */
    const ABSTRACT_DEFINITION = 'PHPDocMD\Definitions\AbstractDefinition';
    /**
     * The definition class to create when looping over the list of definitions.
     */
    const CREATE_CLASS = '';
    /**
     * The search path for getting all the definitions from the SimpleXMLElement object.
     */
    const SEARCH_PATH = '';
    /**
     * The list of api definitions.
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * The entry point where the definitions are defined on the collection class.
     *
     * @param array $definitions
     */
    final function __construct($definitions = [])
    {
        $this->definitions = $definitions;
    }

    /**
     * This method is used for expanding the definitions.
     *
     * @return void
     */
    function expand()
    {
    }

    /**
     * Create a new definition collection.
     *
     * @param array $definitions
     *
     * @return static
     */
    static function make($definitions = [])
    {
        $definitions = (array)$definitions;

        return new static($definitions);
    }

    /**
     * Searches SimpleXMLElement by SEARCH_PATH, creates definitions, calls parse on definitions and expands collection.
     *
     * @param \SimpleXMLElement $structureXml
     *
     * @return static
     */
    static function parse(SimpleXMLElement $structureXml)
    {
        $className = self::validateCreateClass();

        $definitions = [];

        foreach ($structureXml->xpath(static::SEARCH_PATH) as $xml) {
            /** @var AbstractDefinition $object */
            $object = new $className($xml);

            $object->parse();

            $definitions[$object->getName()] = $object;
        }

        $collection = self::make($definitions);

        $collection->expand();

        return $collection;
    }

    /**
     * Pushes key/value onto definition array
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    function push($key, $value)
    {
        $this->definitions[$key] = $value;

        return $this;
    }

    /**
     * Get key from definition array
     *
     * @param $key
     *
     * @return mixed|void
     */
    function get($key)
    {
        if (isset($this->definitions[$key])) {
            return $this->definitions[$key];
        }
    }

    /**
     * Returns the definitions array.
     *
     * @return array
     */
    function all()
    {
        return $this->definitions;
    }

    /**
     * Returns all definitions when casting object to an array.
     *
     * @return array
     */
    function __toArray()
    {
        return $this->all();
    }

    /**
     * Validates the CREATE_CLASS, making sure it inherits the ABSTRACT_CLASS.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    final protected static function validateCreateClass()
    {
        $className = static::CREATE_CLASS;
        $abstract = self::ABSTRACT_DEFINITION;

        if (!in_array($abstract, class_parents($className))) {
            throw new RuntimeException("The definition class {$className} must extend the class {$abstract}");
        }

        return $className;
    }
}
