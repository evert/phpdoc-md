<?php

namespace PHPDocMD\Definitions;

/**
 * Maintains all the information for a single function definition.
 *
 * @copyright Copyright (C) Eric Dowell. All rights reserved.
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
class FunctionDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    public $functionName;

    /**
     * @return string
     */
    function getName()
    {
        // TODO: Implement getName() method.
    }

    /**
     * @return string
     */
    function getTemplate()
    {
        return 'function.twig';
    }

    /**
     * @return $this
     */
    function parse()
    {
        // TODO: Implement parse() method.

        return $this;
    }

    /**
     * @param array $definitions
     *
     * @return $this
     */
    function expand(array $definitions)
    {
        // TODO: Implement expand() method.

        return $this;
    }
}
