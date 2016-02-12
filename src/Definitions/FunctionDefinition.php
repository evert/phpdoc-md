<?php

namespace PHPDocMD\Definitions;

class FunctionDefinition extends AbstractDefinition
{

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
