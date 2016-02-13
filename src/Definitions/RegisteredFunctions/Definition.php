<?php

namespace PHPDocMD\Definitions\RegisteredFunctions;

use PHPDocMD\Definitions\AbstractDefinition;

/**
 * Maintains all the information for a single function definition.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Eric Dowell (https://ericdowell.com/)
 * @license   MIT
 */
class Definition extends AbstractDefinition
{
    /**
     * The file path relative to the source that was parsed.
     *
     * @var string
     */
    public $file;
    /**
     * The key that identify file in function collection.
     *
     * @var string
     */
    public $name;
    /**
     * List of function in file.
     *
     * @var array
     */
    public $functions = [];

    /**
     * Returns the name of the file containing the functions.
     *
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Returns the function template for rendering.
     *
     * @return string
     */
    function getTemplate()
    {
        return 'function.twig';
    }

    /**
     * Parses all the information for a single file containing one or more functions.
     *
     * @return $this
     */
    function parse()
    {
        $this->file = $this->xml->attributes()[ 'path' ];
        $filePath = str_replace('.php', '', $this->file);
        $this->name = str_replace('/', '\\', $filePath);
        $this->fileName = str_replace(DIRECTORY_SEPARATOR, '-', $filePath) . '.md';

        $fileFunctions = [];

        foreach ($this->xml->function as $function) {

            $functionName = $this->parseDocName($function);
            $description = (string)$function->docblock->description;
            $longDescription = (string)$function->docblock->{'long-description'};
            $arguments = $this->parseDocArguments($function);
            $return = $this->parseDocReturn($function);

            $argumentStr = $this->docArgumentsToStr($arguments);

            $signature = sprintf('%s %s(%s)', $return, $functionName, $argumentStr);

            $fileFunctions[$functionName] = [
                'name'        => $functionName,
                'description' => $description . "\n\n" . $longDescription,
                'deprecated'  => count($function->xpath('docblock/tag[@name="deprecated"]')) > 0,
                'signature'   => $signature,
                'arguments'   => $arguments,
                'definedBy'   => $this->file,
            ];
        }

        $this->functions = $fileFunctions;

        return $this;
    }
}
