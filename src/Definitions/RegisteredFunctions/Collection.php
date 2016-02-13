<?php

namespace PHPDocMD\Definitions\RegisteredFunctions;

use PHPDocMD\Definitions\AbstractCollection;

/**
 * Maintains all the information for a single function definition.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
class Collection extends AbstractCollection
{
    /**
     * The definition class to create when looping over the list of functions.
     */
    const CREATE_CLASS = 'PHPDocMD\Definitions\RegisteredFunctions\Definition';

    /**
     * The search path for getting all the functions from the SimpleXMLElement object.
     */
    const SEARCH_PATH = 'file/function/parent::*';

}
