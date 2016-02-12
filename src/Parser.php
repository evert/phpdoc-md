<?php

namespace PHPDocMD;

use RuntimeException;
use SimpleXMLElement;
use PHPDocMD\Definition\AbstractDefinition;

/**
 * This class parses structure.xml and generates the api documentation.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @license   MIT
 */
class Parser
{
    const ABSTRACT_DEFINITION = '\PHPDocMD\Definition\AbstractDefinition';

    const CLASS_DEFINITION = '\PHPDocMD\Definition\ClassDefinition';

    const FUNCTION_DEFINITION = '\PHPDocMD\Definition\FunctionDefinition';

    /**
     * Path to the structure.xml file.
     *
     * @var string
     */
    protected $structureXmlFile;

    /**
     * The structure.xml file parsed into SimpleXMLElement.
     *
     * @var \SimpleXMLElement
     */
    protected $structureXml;

    /**
     * The list of classes and interfaces.
     *
     * @var array
     */
    protected $classDefinitions;

    /**
     * The list of functions.
     *
     * @var array
     */
    protected $functionDefinitions;

    /**
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
        $this->structureXml = simplexml_load_file($this->structureXmlFile);

        $this->setupClassDefinitions();

        return $this->classDefinitions;
    }

    /**
     * Gets all classes and interfaces from the file and puts them in an easy to use array.
     *
     * @return $this
     */
    protected function setupClassDefinitions()
    {
        return $this->setupDefinitions('file/class|file/interface', self::CLASS_DEFINITION, 'classDefinitions');
    }

    /**
     * Gets all functions from the file and puts them in an easy to use array.
     *
     * @return $this
     */
    protected function setupFunctionDefinitions()
    {
        return $this->setupDefinitions('file/function', self::FUNCTION_DEFINITION, 'functionDefinitions');
    }

    /**
     * Parses xml by given $path, expands definitions
     *
     * @param $path
     * @param $className
     * @param $definitionString
     *
     * @return $this
     */
    protected function setupDefinitions($path, $className, $definitionString)
    {
        $abstract = self::ABSTRACT_DEFINITION;
        if (!in_array($abstract, class_parents($className))) {
            throw new RuntimeException("The definition class {$className} must extend the class {$abstract}");
        }

        $names = [];

        foreach ($this->structureXml->xpath($path) as $xml) {
            /** @var AbstractDefinition $definition */
            $definition = new $className($xml);

            $definition->parse();

            $names[$definition->getName()] = $definition;
        }

        $this->{$definitionString} = array_map(function(AbstractDefinition $class) use ($names) {
            return $class->expand($names);
        }, $names);

        return $this;
    }
}
