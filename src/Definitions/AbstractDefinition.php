<?php

namespace PHPDocMD\Definitions;

use SimpleXmlElement;

/**
 * The base contract for any given definition.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
abstract class AbstractDefinition
{
    /**
     * The filename the definition will be saved as.
     *
     * @var string
     */
    public $fileName;
    /**
     * The summary/description for definition
     *
     * @var string
     */
    public $description;
    /**
     * The long description for definition
     *
     * @var string
     */
    public $longDescription;
    /**
     * The parsed xml for the definition.
     *
     * @var \SimpleXmlElement
     */
    protected $xml;

    /**
     * The entry point where the parsed xml is set on the definition class.
     *
     * You must pass an xml element that refers to the definition element from structure.xml.
     *
     * @param \SimpleXmlElement $xml
     */
    final function __construct(SimpleXmlElement $xml)
    {
        $this->xml = $xml;
    }

    /**
     * Parses all the information for a single definition.
     *
     * @return $this
     */
    abstract function parse();

    /**
     * Returns the name of the definition.
     *
     * @return string
     */
    abstract function getName();

    /**
     * Returns the definition template for rendering.
     *
     * @return string
     */
    abstract function getTemplate();

    /**
     * Magic getter for all the different properties a definition will have.
     *
     * @param $name
     *
     * @return mixed|void
     */
    function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }

    /**
     * @param SimpleXMLElement $doc
     *
     * @return string
     */
    protected function parseFullName(SimpleXMLElement $doc)
    {
        return (string)$doc->full_name;
    }

    /**
     * @param SimpleXMLElement $doc
     *
     * @return string
     */
    protected function parseDocName(SimpleXMLElement $doc)
    {
        return (string)$doc->name;
    }

    /**
     * @param SimpleXMLElement $doc
     *
     * @return string
     */
    protected function parseDocReturn(SimpleXMLElement $doc)
    {
        $type = 'mixed';

        $return = $doc->xpath('docblock/tag[@name="return"]');

        if (count($return)) {
            return (string)$return[0]['type'];
        }

        return $type;
    }

    /**
     * @param SimpleXMLElement $doc
     *
     * @return string
     */
    protected function parseDocArguments(SimpleXMLElement $doc)
    {
        $arguments = [];

        foreach ($doc->argument as $argument) {
            $nArgument = [
                'type' => (string)$argument->type,
                'name' => (string)$argument->name,
            ];

            $tags = $doc->xpath(
                sprintf('docblock/tag[@name="param" and @variable="%s"]', $nArgument['name'])
            );

            if (count($tags)) {
                $tag = $tags[0];

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

        return $arguments;
    }

    /**
     * @param array $arguments
     *
     * @return string
     */
    protected function docArgumentsToStr(array $arguments)
    {
        $argumentStr = implode(', ', array_map(function($argument) {
            $return = $argument['name'];

            if ($argument['type']) {
                $return = $argument['type'] . ' ' . $return;
            }

            return $return;
        }, $arguments));

        return $argumentStr;
    }
}
