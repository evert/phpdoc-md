<?php

namespace PHPDocMD;

use
    Twig_Loader_String,
    Twig_Environment;

/**
 * This class takes the output from 'parser', and generate the markdown
 * templates.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license Mit
 */
class Generator 
{

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
    public function __construct(array $classDefinitions, $outputDir)
    {

        $this->classDefinitions = $classDefinitions;
        $this->outputDir = $outputDir;

    }

    /**
     * Starts the generator
     * 
     * @return void
     */
    public function run() {

        $loader = new Twig_Loader_String();
        $twig = new Twig_Environment($loader);
        $twig->addFilter('classLink', new Twig_Filter_Function('PHPDocMd\\Generator::classLink'));
        foreach($this->classDefinitions as $className=>$data) {

            $output = $twig->render(file_get_contents(__DIR__ . '/../templates/class.twig'),
                $data
            );
            file_put_contents($this->outputDir . '/' . $data['fileName'], $output);

        }

    }
    function classLink($className) {

        global $classDefinitions;

        $returnedClasses = array();

        foreach(explode('|', $className) as $oneClass) {

            $oneClass = trim($oneClass,'\\ ');

            if (!isset($classDefinitions[$oneClass])) {

                /*
                $known = array('string', 'bool', 'array', 'int', 'mixed', 'resource', 'DOMNode', 'DOMDocument', 'DOMElement', 'PDO', 'callback', 'null', 'Exception', 'integer', 'DateTime');
                if (!in_array($oneClass, $known)) {
                    file_put_contents('/tmp/classnotfound',$oneClass . "\n", FILE_APPEND);
                }*/

                $returnedClasses[] = $oneClass;

            } else {

                $returnedClasses[] = "[" . $oneClass . "](" . str_replace('\\', '-', $oneClass) . ')';

            }

        }

       return implode('|', $returnedClasses);

    }

}
