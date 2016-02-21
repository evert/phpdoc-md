<?php

namespace PHPDocMD;

use RuntimeException;
use SimpleXMLElement;
use PHPDocMD\Definitions\RegisteredClasses\Collection as ClassCollection;
use PHPDocMD\Definitions\RegisteredFunctions\Collection as FunctionCollection;

/**
 * This class parses structure.xml and generates the collection(s) for api documentation.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
class Parser
{
    /**
     * The collection used to parse and expand class/interface/trait definitions.
     */
    const CLASS_COLLECTION = 'PHPDocMD\Definitions\RegisteredClasses\Collection';
    /**
     * The collection used to parse function definitions.
     */
    const FUNCTION_COLLECTION = 'PHPDocMD\Definitions\RegisteredFunctions\Collection';
    /**
     * Path to the structure.xml file.
     *
     * @var string
     */
    protected $structureXmlFile;
    /**
     * The structure.xml file parsed into SimpleXMLElement.
     *
     * @var SimpleXMLElement
     */
    protected $structureXml;
    /**
     * The list of classes and interfaces.
     *
     * @var ClassCollection
     */
    protected $classDefinitions;
    /**
     * The list of functions.
     *
     * @var FunctionCollection
     */
    protected $functionDefinitions;

    /**
     * One entry point where the structureXmlFile can be set
     *
     * @param string $structureXmlFile
     */
    function __construct($structureXmlFile)
    {
        $this->structureXmlFile = $structureXmlFile;
    }

    /**
     * Starts the process.
     *
     * @return array
     */
    function run()
    {
        $this->classDefinitions();
        $this->functionDefinitions();

        return $this->classDefinitions()->all();
    }

    /**
     * Parses the structure.xml file into a SimpleXMLElement object.
     *
     * @param null|string $structureXmlFile
     *
     * @return $this
     */
    function load($structureXmlFile = null)
    {
        if ($structureXmlFile) {
            $this->structureXmlFile = $structureXmlFile;
        }

        $this->structureXml = simplexml_load_file($this->structureXmlFile);

        return $this;
    }

    /**
     * Returns the parsed structure.xml as SimpleXMLElement object.
     *
     * @return SimpleXMLElement
     */
    function xml()
    {
        if ($this->structureXml instanceof SimpleXMLElement) {
            return $this->structureXml;
        }

        return $this->load();
    }

    /**
     * Returns an array of all types of definitions.
     *
     * @return array
     */
    function definitions()
    {
        return array_merge($this->classDefinitions()->all(), $this->functionDefinitions()->all());
    }

    /**
     * Returns the collection containing all the class definitions.
     *
     * @return ClassCollection
     */
    function classDefinitions()
    {
        if ($this->classDefinitions instanceof ClassCollection) {
            return $this->classDefinitions;
        }

        return $this->classDefinitions = forward_static_call([$this->definitionClass(), 'parse'], $this->xml());
    }

    /**
     * Returns the collection containing all the function definitions.
     *
     * @return FunctionCollection
     */
    function functionDefinitions()
    {
        if ($this->functionDefinitions instanceof FunctionCollection) {
            return $this->functionDefinitions;
        }

        return $this->functionDefinitions = forward_static_call([$this->definitionClass(true), 'parse'], $this->xml());
    }

    /**
     * Validates the definition collection, making sure it inherits from the correct base parent collection.
     *
     * @param bool $function
     *
     * @return string
     *
     * @throws RuntimeException
     */
    final protected function definitionClass($function = false)
    {
        $collection = ($function) ? 'FUNCTION_COLLECTION' : 'CLASS_COLLECTION';

        $className = constant("static::$collection");
        $baseClass = constant("self::$collection");

        if ($className !== $baseClass && !in_array($baseClass, class_parents($className))) {
            throw new RuntimeException("The collection class {$className} must extend the class {$baseClass}");
        }

        return $className;
    }
}
