<?php

namespace PHPDocMD\Definitions\RegisteredClasses;

use PHPDocMD\Definitions\AbstractCollection;

/**
 * Maintains all the information for a single class or interface definition.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
class Collection extends AbstractCollection
{
    /**
     * The definition class to create when looping over the list of classes/interfaces.
     */
    const CREATE_CLASS = 'PHPDocMD\Definitions\RegisteredClasses\Definition';

    /**
     * The search path for getting all the classes/interfaces from the SimpleXMLElement object.
     */
    const SEARCH_PATH = 'file/class|file/interface|file/trait';

    /**
     * Expands all the methods/properties for every class/interface definition
     *
     * @return void
     */
    function expand()
    {
        foreach ($this->definitions as $class) {
            /** @var Definition $class */
            $this->expandMethods($class->getName());
            $this->expandProperties($class->getName());
        }
    }

    /**
     * This method goes through all the class definitions, and adds non-overridden method
     * information from parent classes.
     *
     * @param string $className
     *
     * @return array
     */
    protected function expandMethods($className)
    {
        $class = $this->get($className);

        $newMethods = [];

        foreach (array_merge($class->extends, $class->implements) as $extends) {
            if (!isset($this->definitions[$extends])) {
                continue;
            }
            foreach ($this->definitions[$extends]->methods as $methodName => $methodInfo) {
                if (!isset($class->{$methodName})) {
                    $newMethods[$methodName] = $methodInfo;
                }
            }

            $newMethods = array_merge($newMethods, $this->expandMethods($extends));
        }

        $this->definitions[$className]->methods = array_merge(
            $this->definitions[$className]->methods,
            $newMethods
        );

        return $newMethods;
    }

    /**
     * This method goes through all the class definitions, and adds non-overridden property
     * information from parent classes.
     *
     * @param string $className
     *
     * @return array
     */
    protected function expandProperties($className)
    {
        $class = $this->get($className);

        $newProperties = [];

        foreach (array_merge($class->implements, $class->extends) as $extends) {
            if (!$this->get($extends)) {
                continue;
            }
            foreach ($this->get($extends)->properties as $propertyName => $propertyInfo) {
                if ($propertyInfo['visibility'] === 'private') {
                    continue;
                }
                if (!isset($class->{$propertyName})) {
                    $newProperties[$propertyName] = $propertyInfo;
                }
            }

            $newProperties = array_merge($newProperties, $this->expandProperties($extends));
        }

        $this->definitions[$className]->properties += $newProperties;

        return $newProperties;
    }
}
