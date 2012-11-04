<?php

namespace PHPDocMD;

/**
 * This class parses structure.xml and generates the api documentation.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license Mit
 */
class Parser
{

    /**
     * Path to the structure.xml file
     *
     * @var string
     */
    protected $structureXmlFile;

    /**
     * Output directory
     *
     * @var string
     */
    protected $outputDir;

    /**
     * The list of classes and interfaces
     *
     * @var array
     */
    protected $classDefinitions;

    /**
     * Constructor
     *
     * @param string $structureXmlFile
     * @param string $outputDir
     */
    public function __construct($structureXmlFile, $outputDir)
    {

        $this->structureXmlFile = $structureXmlFile;
        $this->outputDir = $outputDir;

    }

    /**
     * Starts the process
     *
     * @return void
     */
    public function run()
    {

        $xml = simplexml_load_file($this->structureXmlFile);
        $this->getClassDefinitions($xml);

        foreach($this->classDefinitions as $className=>$classInfo) {

            $this->expandMethods($className);

        }

        return $this->classDefinitions;

    }

    /**
     * Gets all classes and interfaces from the file and puts them in an easy
     * to use array.
     *
     * @param \SimpleXmlElement $xml
     * @return void
     */
    protected function getClassDefinitions(\SimpleXmlElement $xml) {

        foreach($xml->xpath('file/class|file/interface') as $class) {

            $className = (string)$class->full_name;
            $className = ltrim($className,'\\');

            $fileName = str_replace('\\','-', $className) . '.md';

            $implements = array();

            if (isset($class->implements)) foreach($class->implements as $interface) {

                $implements[] = ltrim((string)$interface, '\\');

            }

            $extends = array();
            if (isset($class->extends)) foreach($class->extends as $parent) {

                $extends[] = ltrim((string)$parent, '\\');

            }

            $methods = array();
            foreach($class->method as $method) {

                $methodName = (string)$method->full_name;

                $return = $method->xpath('docblock/tag[@name="return"]');
                if (count($return)) {
                    $return = (string)$return[0]['type'];
                } else {
                    $return = 'mixed';
                }

                $arguments = array();

                foreach($method->argument as $argument) {

                    $nArgument = array(
                        'type' => (string)$argument->type,
                        'name' => (string)$argument->name
                    );
                    if (count($tag = $method->xpath('docblock/tag[@name="param" and @variable="' . $nArgument['name'] . '"]'))) {

                        $tag = $tag[0];
                        if ((string)$tag['type']) {
                            $nArgument['type'] = (string)$tag['type'];
                        }
                        if ((string)$tag['description']) {
                            $nArgument['description'] = (string)$tag['description'];
                        }
                        if ((string)$tag['variable']) {
                            $nArgument['name'] = (string)$tag['variable'];
                        }

                    }

                    $arguments[] = $nArgument;

                }

                $argumentStr = implode(', ', array_map(function($argument) {
                    return ($argument['type']?$argument['type'] . ' ':'') . $argument['name'];
                }, $arguments));

                $signature = $return . ' ' . $className . '::' . $methodName . '('.$argumentStr.')';

                $methods[$methodName] = array(
                    'name' => $methodName,
                    'description' => (string)$method->docblock->description . "\n\n" . (string)$method->docblock->{"long-description"},
                    'signature' => $signature,
                    'arguments' => $arguments
                );

            }

            $classNames[$className] = array(
                'fileName' => $fileName,
                'className' => $className,
                'shortClass' => (string)$class->name,
                'namespace' => (string)$class['namespace'],
                'description' => (string)$class->docblock->description,
                'longDescription' => (string)$class->docblock->{"long-description"},
                'implements' => $implements,
                'extends' => $extends,
                'isClass' => $class->getName()==='class',
                'isInterface' => $class->getName()==='interface',
                'abstract' => (string)$class['abstract']=='true',
                'deprecated' => count($class->xpath('docblock/tag[@name="deprecated"]'))>0,
                'methods' => $methods
            );

        }

        $this->classDefinitions = $classNames;

    }

    /**
     * This method goes through all the class definitions, and adds
     * non-overriden method information from parent classes.
     *
     * @return void
     */
    protected function expandMethods($className)
    {

        $class = $this->classDefinitions[$className];

        $newMethods = array();
        foreach($class['implements'] as $implements) {

            if (!isset($this->classDefinitions[$implements])) {
                continue;
            }

            foreach($this->classDefinitions[$implements]['methods'] as $methodName => $methodInfo) {

                if (!isset($class[$methodName])) {
                    $newMethods[$methodName] = $methodInfo;
                }

            }

            $newMethods = array_merge($newMethods, $this->expandMethods($implements));

        }
        foreach($class['extends'] as $extends) {

            if (!isset($this->classDefinitions[$extends])) {
                continue;
            }

            foreach($this->classDefinitions[$extends]['methods'] as $methodName => $methodInfo) {

                if (!isset($class[$methodName])) {
                    $newMethods[$methodName] = $methodInfo;
                }

            }

            $newMethods = array_merge($newMethods, $this->expandMethods($extends));

        }

        $this->classDefinitions[$className]['methods']+=$newMethods;
        return $newMethods;

    }

}
