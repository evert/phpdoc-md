<?php

namespace PHPDocMD\Definitions;

class ClassDefinition extends AbstractDefinition
{
    /***
     * @var string
     */
    public $fileName;
    /**
     * @var string
     */
    public $className;
    /**
     * @var string
     */
    public $shortClass;
    /**
     * @var string
     */
    public $namespace;
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $longDescription;
    /**
     * @var array
     */
    public $implements = [];
    /**
     * @var array
     */
    public $extends = [];
    /**
     * @var bool
     */
    public $isClass;
    /**
     * @var bool
     */
    public $isInterface;
    /**
     * @var bool
     */
    public $abstract;
    /**
     * @var bool
     */
    public $deprecated;
    /**
     * @var array
     */
    public $methods;
    /**
     * @var array
     */
    public $properties;
    /**
     * @var array
     */
    public $constants;

    /**
     * @return string
     */
    function getName()
    {
        return $this->className;
    }

    /**
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
     * @param array $classDefinitions
     *
     * @return $this
     */
    function expand(array $classDefinitions)
    {
        $this->expandMethods($classDefinitions, $this->getName());
        $this->expandProperties($classDefinitions, $this->getName());

        return $this;
    }

    /**
     * Parses all the method information for a single class or interface.
     *
     * You must pass an xml element that refers to either the class or interface element from
     * structure.xml.
     *
     * @return array
     */
    protected function parseMethods()
    {
        $methods = [];

        $className = (string)$this->xml->full_name;
        $className = ltrim($className, '\\');

        foreach ($this->xml->method as $method) {
            $methodName = (string)$method->name;

            $return = $method->xpath('docblock/tag[@name="return"]');

            if (count($return)) {
                $return = (string)$return[0]['type'];
            }
            else {
                $return = 'mixed';
            }

            $arguments = [];

            foreach ($method->argument as $argument) {
                $nArgument = [
                    'type' => (string)$argument->type,
                    'name' => (string)$argument->name,
                ];

                $tags = $method->xpath(
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

            $argumentStr = implode(', ', array_map(function($argument) {
                $return = $argument['name'];

                if ($argument['type']) {
                    $return = $argument['type'] . ' ' . $return;
                }

                return $return;
            }, $arguments));

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
     * Parses all property information for a single class or interface.
     *
     * You must pass an xml element that refers to either the class or interface element from
     * structure.xml.
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
     * Parses all constant information for a single class or interface.
     *
     * You must pass an xml element that refers to either the class or interface element from
     * structure.xml.
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

    /**
     * This method goes through all the class definitions, and adds non-overridden method
     * information from parent classes.
     *
     * @param array  $classDefinitions
     * @param string $className
     *
     * @return array
     */
    protected function expandMethods(array $classDefinitions, $className)
    {
        $class = $classDefinitions[$className];
        $newMethods = [];
        foreach (array_merge($class->extends, $class->implements) as $extends) {
            if (!isset($classDefinitions[$extends])) {
                continue;
            }
            foreach ($classDefinitions[$extends]->methods as $methodName => $methodInfo) {
                if (!isset($class->{$methodName})) {
                    $newMethods[$methodName] = $methodInfo;
                }
            }
            $newMethods = array_merge($newMethods, $this->expandMethods($classDefinitions, $extends));
        }

        $this->methods = array_merge(
            $this->methods,
            $newMethods
        );

        return $newMethods;
    }

    /**
     * This method goes through all the class definitions, and adds non-overridden property
     * information from parent classes.
     *
     * @param array  $classDefinitions
     * @param string $className
     *
     * @return array
     */
    protected function expandProperties(array $classDefinitions, $className)
    {
        $class = $classDefinitions[$className];

        $newProperties = [];

        foreach (array_merge($class->implements, $class->extends) as $extends) {
            if (!isset($classDefinitions[$extends])) {
                continue;
            }
            foreach ($classDefinitions[$extends]->properties as $propertyName => $propertyInfo) {
                if ($propertyInfo['visibility'] === 'private') {
                    continue;
                }
                if (!isset($class->{$propertyName})) {
                    $newProperties[$propertyName] = $propertyInfo;
                }
            }

            $newProperties = array_merge($newProperties, $this->expandProperties($classDefinitions, $extends));
        }

        $this->properties += $newProperties;

        return $newProperties;
    }
}
