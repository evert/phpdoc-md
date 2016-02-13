<?php

namespace PHPDocMD;

use Twig_Environment;
use Twig_SimpleFilter;
use Twig_Loader_Filesystem;
use PHPDocMD\Definitions\AbstractDefinition;
use PHPDocMD\Definitions\RegisteredFunctions\Definition as FunctionDefinition;

/**
 * This class takes the output from 'parser', and generate the markdown
 * templates.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
class Generator
{
    /**
     * The object that manages the different definitions/collections.
     *
     * @var Parser
     */
    protected static $parser;
    /**
     * Output directory.
     *
     * @var string
     */
    protected $outputDir;
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
    protected static $linkTemplate;
    /**
     * Filename for API Index.
     *
     * @var string
     */
    protected $apiIndexFile;

    /**
     * The entry point where the data come bin command is injected.
     *
     * @param Parser  $parser
     * @param string $outputDir
     * @param string $templateDir
     * @param string $linkTemplate
     * @param string $apiIndexFile
     */
    function __construct(Parser $parser, $outputDir, $templateDir, $linkTemplate = '%c.md', $apiIndexFile = 'ApiIndex.md')
    {
        self::$parser = $parser;
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

        $filter = new Twig_SimpleFilter('classLink', [$this, 'classLink']);
        $twig->addFilter($filter);

        foreach (self::$parser->definitions() as $definition) {
            /** @var AbstractDefinition $definition */
            $output = $twig->render($definition->getTemplate(), ['definition' => $definition]);

            $putFile = rtrim($this->outputDir, '/') . '/' . ltrim($definition->fileName, '/');

            file_put_contents($putFile, $output);
        }

        $tree = ['index' => $this->createIndexTree()];

        $index = $twig->render('index.twig', $tree);

        file_put_contents($this->outputDir . '/' . $this->apiIndexFile, $index);
    }

    /**
     * Generates index tree.
     *
     * @return string
     */
    protected function createIndexTree()
    {
        $index = '';
        $classDefinitions = self::$parser->classDefinitions()->all();
        $functionDefinitions = self::$parser->functionDefinitions()->all();

        $classTree = $this->createIndex($classDefinitions);

        $functionTree = $this->createIndex($functionDefinitions);

        if ($functionTree) {
            $index .= "Classes/Interfaces/Traits\n-------\n";
        }
        $index .= $this->treeOutput($classTree);

        if ($functionTree) {
            $index .= "\nFunctions\n-------\n";
            $index .= $this->treeOutput($functionTree, false);
        }

        return $index;
    }

    /**
     * Creates an index given a list of items.
     *
     * @param array $items
     *
     * @return array
     */
    protected function createIndex(array $items)
    {
        $tree = [];

        foreach ($items as $name => $definition) {
            $current = & $tree;

            foreach (explode('\\', $name) as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }

                $current = & $current[$part];
            }
        }

        return $tree;
    }

    /**
     * Outputs the tree structure as a string
     *
     * I'm generating the actual markdown output here, which isn't great...But it will have to do.
     * If I don't want to make things too complicated.
     *
     * @param array  $item
     * @param bool   $classTree
     * @param string $fullString
     * @param int    $depth
     *
     * @return string
     */
    protected function treeOutput(array $item, $classTree = true, $fullString = '', $depth = 0)
    {
        $output = '';

        foreach ($item as $name => $subItems) {
            $fullName = $name;

            if ($fullString) {
                $fullName = $fullString . '\\' . $name;
            }

            $link = $this->classLink($fullName, $name, $classTree);
            $output .= str_repeat(' ', $depth * 4) . '* ' . $link . "\n";

            $this->expandFunctionLinks($output, $classTree, $fullName, $depth);

            $output .= $this->treeOutput($subItems, $classTree, $fullName, $depth + 1);
        }

        return $output;
    }

    /**
     * Adds links under each file with the correct label and anchor for each function.
     *
     * @param string $output
     * @param bool   $classTree
     * @param string $fullName
     * @param int    $depth
     */
    protected function expandFunctionLinks(&$output, $classTree, $fullName, $depth)
    {
        if ($classTree === false && $definition = self::$parser->functionDefinitions()->get($fullName)) {
            if ($definition instanceof FunctionDefinition) {

                foreach ($definition->functions as $functionName => $function) {
                    $anchor = '#' . $functionName;

                    $link = $this->classLink($fullName, $functionName, $classTree, $anchor);

                    $output .= str_repeat(' ', $depth * 8) . '* ' . $link . "\n";
                }
            }
        }
    }

    /**
     * This is a twig template function.
     *
     * This function allows us to easily link classes to their existing pages.
     *
     * @param string      $name
     * @param null|string $label
     * @param bool        $classLink
     * @param string      $queryString
     *
     * @return string
     */
    function classLink($name, $label = null, $classLink = true, $queryString = '')
    {
        $returnedClasses = [];

        $linkDefinitions = ($classLink) ? self::$parser->classDefinitions()->all() : self::$parser->functionDefinitions()->all();

        foreach (explode('|', $name) as $oneClass) {
            $oneClass = trim($oneClass, '\\ ');

            if (!$label) {
                $label = $oneClass;
            }

            if (!isset($linkDefinitions[$oneClass])) {
                $returnedClasses[] = $oneClass;
            } else {
                $link = str_replace('\\', '-', $oneClass);
                $link = strtr(self::$linkTemplate, ['%c' => $link]) . $queryString;

                $returnedClasses[] = sprintf("[%s](%s)", $label, $link);
            }
        }

        return implode('|', $returnedClasses);
    }
}
