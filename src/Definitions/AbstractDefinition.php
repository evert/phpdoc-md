<?php

namespace PHPDocMD\Definitions;

use SimpleXmlElement;

/**
 * The base contract for any given definition.
 *
 * @copyright Copyright (C) Eric Dowell. All rights reserved.
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
abstract class AbstractDefinition
{
    /***
     * @var string
     */
    public $fileName;

    /**
     * @var SimpleXmlElement
     */
    protected $xml;

    /**
     * @param SimpleXmlElement $xml
     */
    function __construct(SimpleXmlElement $xml)
    {
        $this->xml = $xml;
    }

    /**
     * @return $this
     */
    abstract function parse();

    /**
     * @param array $definitions
     *
     * @return $this
     */
    abstract function expand(array $definitions);

    /**
     * @return string
     */
    abstract function getName();

    /**
     * @return string
     */
    abstract function getTemplate();

    /**
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
}
