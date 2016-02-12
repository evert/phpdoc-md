<?php

namespace PHPDocMD\Definitions;

use SimpleXmlElement;

abstract class AbstractDefinition
{
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
