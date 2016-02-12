<?php

namespace PHPDocMD;

use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_SimpleFilter;

/**
 * This class takes the output from 'parser', and generate the markdown
 * templates.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @license   MIT
 */
class Generator
{
    /**
     * Output directory.
     *
     * @var string
     */
    protected $outputDir;

    /**
     * The list of classes and interfaces.
     *
     * @var array
     */
    static protected $classDefinitions;

    /**
     * Directory containing the twig templates.
     *
     * @var string
     */
    protected $templateDir;

    /**
     * A simple template for generating links.
     *
     * @var string
     */
    static protected $linkTemplate;

    /**
     * Filename for API Index.
     *
     * @var string
     */
    protected $apiIndexFile;

    /**
     * @param array  $classDefinitions
     * @param string $outputDir
     * @param string $templateDir
     * @param string $linkTemplate
     * @param string $apiIndexFile
     */
    function __construct(array $classDefinitions, $outputDir, $templateDir, $linkTemplate = '%c.md', $apiIndexFile = 'ApiIndex.md')
    {
        self::$classDefinitions = $classDefinitions;
        $this->outputDir = $outputDir;
        $this->templateDir = $templateDir;
        self::$linkTemplate = $linkTemplate;
        $this->apiIndexFile = $apiIndexFile;
    }

    /**
     * Starts the generator.
     */
    function run()
    {
        $loader = new Twig_Loader_Filesystem($this->templateDir, [
            'cache' => false,
            'debug' => true,
        ]);

        $twig = new Twig_Environment($loader);

        $filter = new Twig_SimpleFilter('classLink', ['PHPDocMd\\Generator', 'classLink']);
        $twig->addFilter($filter);

        foreach (self::$classDefinitions as $definition) {
            $output = $twig->render('class.twig', ['definition' => $definition]);

            file_put_contents($this->outputDir . '/' . $definition->fileName, $output);
        }

        $index = $this->createIndex();

        $index = $twig->render('index.twig',
            [
                'index'            => $index,
                'classDefinitions' => self::$classDefinitions,
            ]
        );

        file_put_contents($this->outputDir . '/' . $this->apiIndexFile, $index);
    }

    /**
     * Creates an index of classes and namespaces.
     *
     * I'm generating the actual markdown output here, which isn't great...But it will have to do.
     * If I don't want to make things too complicated.
     *
     * @return array
     */
    protected function createIndex()
    {
        $tree = [];

        foreach (self::$classDefinitions as $className => $definition) {
            $current = & $tree;

            foreach (explode('\\', $className) as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }

                $current = & $current[$part];
            }
        }

        /**
         * This will be a reference to the $treeOutput closure, so that it can be invoked
         * recursively. A string is used to trick static analysers into thinking this might be
         * callable.
         */
        $treeOutput = '';

        $treeOutput = function($item, $fullString = '', $depth = 0) use (&$treeOutput) {
            $output = '';

            foreach ($item as $name => $subItems) {
                $fullName = $name;

                if ($fullString) {
                    $fullName = $fullString . '\\' . $name;
                }

                $output .= str_repeat(' ', $depth * 4) . '* ' . Generator::classLink($fullName, $name) . "\n";
                $output .= $treeOutput($subItems, $fullName, $depth + 1);
            }

            return $output;
        };

        return $treeOutput($tree);
    }

    /**
     * This is a twig template function.
     *
     * This function allows us to easily link classes to their existing pages.
     *
     * Due to the unfortunate way twig works, this must be static, and we must use a global to
     * achieve our goal.
     *
     * @param string      $className
     * @param null|string $label
     *
     * @return string
     */
    static function classLink($className, $label = null)
    {
        $returnedClasses = [];

        foreach (explode('|', $className) as $oneClass) {
            $oneClass = trim($oneClass, '\\ ');

            if (!$label) {
                $label = $oneClass;
            }

            if (!isset(self::$classDefinitions[$oneClass])) {
                $returnedClasses[] = $oneClass;
            } else {
                $link = str_replace('\\', '-', $oneClass);
                $link = strtr(self::$linkTemplate, ['%c' => $link]);

                $returnedClasses[] = sprintf("[%s](%s)", $label, $link);
            }
        }

        return implode('|', $returnedClasses);
    }
}
