<?php

namespace PHPDocMD\Definitions\RegisteredClasses;

use PHPDocMD\Definitions\AbstractDefinition;

/**
 * Maintains all the information for a single class, interface or trait definition.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
class Definition extends AbstractDefinition
{
    /**
     * The key that identify class, interface or trait in class collection.
     *
     * @var string
     */
    public $className;
    /**
     * The name of the class, interface or trait without namespacing prefixing.
     *
     * @var string
     */
    public $shortClass;
    /**
     * The namespace the class belongs to.
     *
     * @var string
     */
    public $namespace;
    /**
     * The interfaces the class or interface implements
     *
     * @var array
     */
    public $implements = [];
    /**
     * The classes the class extends.
     *
     * @var array
     */
    public $extends = [];
    /**
     * The traits the class inherits.
     *
     * @todo: add logic to link inheritance from traits.
     *
     * @var array
     */
    public $traits = [];
    /**
     * Indicates if definition is a class.
     *
     * @var bool
     */
    public $isClass;
    /**
     * Indicates if definition is a interface.
     *
     * @var bool
     */
    public $isInterface;
    /**
     * Indicates if definition is an abstract class.
     *
     * @var bool
     */
    public $abstract;
    /**
     * Indicates if definition is deprecated.
     *
     * @var bool
     */
    public $deprecated;
    /**
     * List of methods that are defined on the class, interface or trait.
     *
     * @var array
     */
    public $methods;
    /**
     * List of properties defined on class or trait.
     *
     * @var array
     */
    public $properties;
    /**
     * List of constants defined on class or trait.
     *
     * @var array
     */
    public $constants;

    /**
     * Returns the name of the class, interface or trait.
     *
     * @return string
     */
    function getName()
    {
        return $this->className;
    }

    /**
     * Returns the class, interface or trait template for rendering.
     *
     * @return string
     */
    function getTemplate()
    {
        return 'class.twig';
    }

    /**
     * Parses all the information for a single class, interface or trait.
     *
     * @return $this
     */
    function parse()
    {
        $className = (string)$this->xml->full_name;
        $className = ltrim($className, '\\');

        $fileName = str_replace('\\', '-', $className) . '.md';

        $implements = [];

        if (isset($this->xml->implements)) {
            foreach ($this->xml->implements as $interface) {
                $implements[] = ltrim((string)$interface, '\\');
            }
        }

        $extends = [];

        if (isset($this->xml->extends)) {
            foreach ($this->xml->extends as $parent) {
                $extends[] = ltrim((string)$parent, '\\');
            }
        }

        $this->fileName = $fileName;
        $this->className = $className;
        $this->shortClass = (string)$this->xml->name;
        $this->namespace = (string)$this->xml['namespace'];
        $this->description = (string)$this->xml->docblock->description;
        $this->longDescription = (string)$this->xml->docblock->{'long-description'};
        $this->implements = $implements;
        $this->extends = $extends;
        $this->isClass = $this->xml->getName() === 'class';
        $this->isInterface = $this->xml->getName() === 'interface';
        $this->abstract = (string)$this->xml['abstract'] == 'true';
        $this->deprecated = count($this->xml->xpath('docblock/tag[@name="deprecated"]')) > 0;
        $this->methods = $this->parseMethods();
        $this->properties = $this->parseProperties();
        $this->constants = $this->parseConstants();

        return $this;
    }

    /**
     * Parses all the method information for a single class, interface or trait.
     *
     * @return array
     */
    protected function parseMethods()
    {
        $methods = [];

        $className = ltrim($this->parseFullName($this->xml), '\\');

        foreach ($this->xml->method as $method) {
            $methodName = $this->parseDocName($method);

            $return = $this->parseDocReturn($method);

            $arguments = $this->parseDocArguments($method);

            $argumentStr = $this->docArgumentsToStr($arguments);

            $signature = sprintf('%s %s::%s(%s)', $return, $className, $methodName, $argumentStr);

            $methods[$methodName] = [
                'name'        => $methodName,
                'description' => (string)$method->docblock->description . "\n\n" . (string)$method->docblock->{'long-description'},
                'visibility'  => (string)$method['visibility'],
                'abstract'    => ((string)$method['abstract']) == "true",
                'static'      => ((string)$method['static']) == "true",
                'deprecated'  => count($this->xml->xpath('docblock/tag[@name="deprecated"]')) > 0,
                'signature'   => $signature,
                'arguments'   => $arguments,
                'definedBy'   => $className,
            ];
        }

        return $methods;
    }

    /**
     * Parses all property information for a single class, interface or trait.
     *
     * @return array
     */
    protected function parseProperties()
    {
        $properties = [];

        $className = (string)$this->xml->full_name;
        $className = ltrim($className, '\\');

        foreach ($this->xml->property as $xProperty) {
            $type = 'mixed';
            $propName = (string)$xProperty->name;
            $default = (string)$xProperty->default;

            $xVar = $xProperty->xpath('docblock/tag[@name="var"]');

            if (count($xVar)) {
                $type = $xVar[0]->type;
            }

            $visibility = (string)$xProperty['visibility'];
            $signature = sprintf('%s %s %s', $visibility, $type, $propName);

            if ($default) {
                $signature .= ' = ' . $default;
            }

            $properties[$propName] = [
                'name'        => $propName,
                'type'        => $type,
                'default'     => $default,
                'description' => (string)$xProperty->docblock->description . "\n\n" . (string)$xProperty->docblock->{'long-description'},
                'visibility'  => $visibility,
                'static'      => ((string)$xProperty['static']) == 'true',
                'signature'   => $signature,
                'deprecated'  => count($this->xml->xpath('docblock/tag[@name="deprecated"]')) > 0,
                'definedBy'   => $className,
            ];
        }

        return $properties;
    }

    /**
     * Parses all constant information for a single class, interface or trait.
     *
     * @return array
     */
    protected function parseConstants()
    {
        $constants = [];

        $className = (string)$this->xml->full_name;
        $className = ltrim($className, '\\');

        foreach ($this->xml->constant as $xConstant) {
            $name = (string)$xConstant->name;
            $value = (string)$xConstant->value;

            $signature = sprintf('const %s = %s', $name, $value);

            $constants[$name] = [
                'name'        => $name,
                'description' => (string)$xConstant->docblock->description . "\n\n" . (string)$xConstant->docblock->{'long-description'},
                'signature'   => $signature,
                'value'       => $value,
                'deprecated'  => count($this->xml->xpath('docblock/tag[@name="deprecated"]')) > 0,
                'definedBy'   => $className,
            ];
        }

        return $constants;
    }
}
