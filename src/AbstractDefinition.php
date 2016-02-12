<?php
/**
 * Created by PhpStorm.
 * User: eric
 * Date: 2/11/16
 * Time: 7:40 PM
 */

namespace PHPDocMD;

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
     * @param array $classDefinitions
     *
     * @return $this
     */
    abstract function expand(array $classDefinitions);

    /**
     * @return string
     */
    abstract function getName();

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
