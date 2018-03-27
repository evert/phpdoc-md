<?php


namespace PHPDocMD;

use SimpleXMLElement;

/**
 * This class parses structure.xml and generates the api documentation.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @license   MIT
 */
class Parser
{
    /**
     * Path to the structure.xml file.
     *
     * @var string
     */
    protected $structureXmlFile;

    /**
     * if true, then the api index and the component indices are sorted. Defaults to false.
     *
     * @var bool
     * @author    Rainer Stötter
     *
     */

    protected $m_do_sort_index = false;

    /**
     * if true, then the the see section will be sorted. Defaults to false.
     *
     * @var bool
     * @author    Rainer Stötter
     *
     */

    protected $m_do_sort_see = false;

    /**
     * The list of classes and interfaces.
     *
     * @var array
     */
    protected $classDefinitions;

    /**
     * The contents of the xml file
     *
     * @var SimpleXmlElement $m_xml;
     *
     */

    protected $m_xml;


    /**
     *  if true, then the public components should be suppressed in the output
     *
     * @var int $m_suppress_public
     * @see $m_suppress_public
     * @see $m_suppress_protected
     * @see $m_suppress_private
     *
     * @author    Rainer Stötter
     *
     */

     protected $m_suppress_public = false;

    /**
     *  if true, then the protected components should be suppressed in the output
     *
     * @var int $m_suppress_protected
     * @see $m_suppress_public
     * @see $m_suppress_protected
     * @see $m_suppress_private
     *
     * @author    Rainer Stötter
     *
     */

     protected $m_suppress_protected = false;

    /**
     *  if true, then the pivate components should be suppressed in the output
     *
     * @var int $m_suppress_private
     * @see $m_suppress_public
     * @see $m_suppress_protected
     * @see $m_suppress_private
     *
     * @author    Rainer Stötter
     *
     */

     protected $m_suppress_private = false;




    /**
     * @param string $structureXmlFile
     */
    function __construct($structureXmlFile, $do_sort_index = false, $do_sort_see = false, $protected_off = false, $private_off = false, $public_off = false )
    {
        $this->structureXmlFile = $structureXmlFile;
        $this->m_do_sort_index = $do_sort_index;
        $this->m_do_sort_see = $do_sort_see;
	//
        $this->m_suppress_private = $private_off;
        $this->m_suppress_protected = $protected_off;
        $this->m_suppress_public = $public_off;

	if ( $this->m_suppress_public ) echo "\n suppressing public components";
	if ( $this->m_suppress_protected ) echo "\n suppressing protected components";
	if ( $this->m_suppress_private ) echo "\n suppressing private components";

    }

    /**
     * Starts the process.
     */
    function run()
    {
        $this->m_xml = simplexml_load_file($this->structureXmlFile);

        $this->getClassDefinitions($this->m_xml);

        foreach ($this->classDefinitions as $className => $classInfo) {
            $this->expandMethods($className);
            $this->expandProperties($className);
        }

        return $this->classDefinitions;
    }

    /**
     * Gets all classes and interfaces from the file and puts them in an easy to use array.
     *
     * @param SimpleXmlElement $xml
     */
    protected function getClassDefinitions(SimpleXmlElement $xml)
    {
        $classNames = [];

        foreach ($xml->xpath('file/class|file/interface') as $class) {
            $className = (string)$class->full_name;
            $className = ltrim($className, '\\');

            $fileName = str_replace('\\', '-', $className) . '.md';

            $implements = [];

            if (isset($class->implements)) {
                foreach ($class->implements as $interface) {
                    $implements[] = ltrim((string)$interface, '\\');
                }
            }

            $extends = [];

            if (isset($class->extends)) {
                foreach ($class->extends as $parent) {
                    $extends[] = ltrim((string)$parent, '\\');
                }
            }

            $classNames[$className] = [
                'fileName'        => $fileName,
                'className'       => $className,
                'shortClass'      => (string)$class->name,
                'namespace'       => (string)$class['namespace'],
                'description'     => (string)$class->docblock->description,
                'longDescription' => (string)$class->docblock->{'long-description'},
                'implements'      => $implements,
                'extends'         => $extends,
                'isClass'         => $class->getName() === 'class',
                'isInterface'     => $class->getName() === 'interface',
                'abstract'        => (string)$class['abstract'] == 'true',
                'deprecated'      => count($class->xpath('docblock/tag[@name="deprecated"]')) > 0,
                'methods'         => $this->parseMethods($class),
                'properties'      => $this->parseProperties($class),
                'constants'       => $this->parseConstants($class),
            ];

	    if ( $this->m_do_sort_index ) {

		// Rainer Stötter: sort the parsed structures

		asort( $classNames );

		asort(  $classNames[$className]['methods'] );
		asort(  $classNames[$className]['properties'] );
		asort(  $classNames[$className]['constants'] );
	    }


        }


        $this->classDefinitions = $classNames;
    }

    /**
     *  Sorts an associative array by its subkeays
     *
     * @param array $ary the array to sort
     * @param string $subkey  the subkey which should be sorted
     * @param int $sort_order the way to sort $ary SORT_ASC or SORT_DESC
     * @see LEVEL_CLASS
     * @see LEVEL_COMPONENT
     * @author    Rainer Stötter
     *
     */


     private function SortBySubkey( & $ary, $subkey, $sort_order = SORT_ASC) {
	  foreach ( $ary as $el ) {
	      $keys[] = $el[ $subkey ];
	  }
	  array_multisort( $keys, $sort_order, $ary );
      }

    /**
     * Parses a full_name of a method and splits it its the parts
     *
     * @param string $full_name		the full name of the method
     * @param string $namespace		the found namespace of the method
     * @param string $class_name	the found classname of the method
     * @param string $method_name	the found name of the method
     *
     * @author Rainer Stötter
     *
     * @return array
     */


      private function SplitFullname( $full_name, & $namespace, & $class_name, & $method_name ) {

	$class_name = '';
	$method_name = '';
	$namespace = '';

	$full_name = trim( $full_name );

	  $pos_slash = strrpos( $full_name, '\\' );

	  if ( $pos_slash !== false ) {

	      $namespace = substr( $full_name, 0, $pos_slash );

	  }

	  $pos_colon = strpos( $full_name, ':' );

	  if ( $pos_colon !== false ) {

	      $class_name = substr( $full_name, $pos_slash + 1, $pos_colon - $pos_slash - 1 );
	      $method_name = substr( $full_name, $pos_colon + 2 );

	  } else {
	      if ( $pos_slash !== false ) {
		  $method_name = substr( $full_name, $pos_slash + 1 );
	      } else {
		  $method_name = $full_name;
	      }
	  }

      }

    /**
     * searches for the component $component in the tree $xml and returns the class where the component was found
     * and the type of the component. If the component was not found in the preferred class $class_name_preferred,
     * then the whole tree is searched and the FIRST occurence of $component will be returned
     *
     * @param string $component the name of the component
     * @param string $class_name_preferred the name of the preferred class to search in. If this class owns not
     * $component, then the whole xml will be searched
     * @param SimpleXmlElement $xml the xml to search for $component
     * @param string $found_class_name returns the name of the class, where the $component was found in
     * @param string $found_type returns the type of the component which was found 'M' for method, 'C' for constant
     * and 'P' for property
     *
     * @return boolean true, if the component was found
     *
     * author Rainer Stötter
     *
     */

      protected function FoundComponent( $component, $class_name_preferred, &$found_class_name, &$found_type ) {

	  $found_class_name = '';
	  $found_type = '';
	  $component = trim( $component );

	  $xml = & $this->m_xml;

	  $this->SplitFullname( $component, $srch_namespace, $srch_class, $srch_component  );

	  foreach ($xml->xpath('file/class|file/interface') as $class) {

	      $class_name = (string)$class->full_name;
	      $class_name = ltrim($class_name, '\\');

	      if ( $class->name == $class_name_preferred ) {

		  foreach ($class->method as $method) {

		      if ( $method->name == $srch_component ) {

			  $found_type = 'M';
			  $found_class_name = $class_name;
			  return true;

		      }

		  }

		  foreach ($class->constant as $constant ) {

		      if ( $constant->name == $srch_component ) {

			  $found_type = 'C';
			  $found_class_name = $class_name;

			  return true;

		      }

		  }

		  foreach ($class->property as $property ) {

		      if ( $property->name == $srch_component ) {

			  $found_type = 'P';
			  $found_class_name = $class_name;

			  return true;

		      }

		  }

	      }

	  }

	  // not found in the preferred class -> search the whole tree

	  foreach ($xml->xpath('file/class|file/interface') as $class) {

	      $class_name = (string)$class->full_name;
	      $class_name = ltrim($class_name, '\\');

	      foreach ($class->method as $method) {

		  $method_name = (string)$method->name;

		  if ( $method->name == $srch_component ) {

		      $found_type = 'M';
		      $found_class_name = $class_name;
		      return true;

		  }

	      }

	      foreach ($class->constant as $constant ) {

		  if ( $constant->name == $srch_component ) {

		      $found_type = 'C';
		      $found_class_name = $class_name;

		      return true;

		  }

	      }

	      foreach ($class->property as $property ) {

		  if ( $property->name == $srch_component ) {

		      $found_type = 'P';
		      $found_class_name = $class_name;

		      return true;

		  }

	      }

	  }


	  return false;

      }	// function FoundComponent( )


    /**
     * Parses all the method information for a single class or interface.
     *
     * You must pass an xml element that refers to either the class or interface element from
     * structure.xml.
     *
     * @param SimpleXMLElement $class
     *
     * @return array
     */
    protected function parseMethods(SimpleXMLElement $class)
    {
        $methods = [];

        $className = (string)$class->full_name;
        $className = ltrim($className, '\\');


/*
  <tag name="param" line="758" description="&lt;p&gt;int the id of a message&lt;/p&gt;" type="" variable="$id"/>
  <tag name="return" line="758" description="the message string in a certain language" type="string">
*/

        foreach ($class->method as $method) {

	  // check for suppressed items

	  if ( ( $this->m_suppress_public ) && ( strtolower( (string)$method['visibility'] ) == 'public' ) ) continue;
	  if ( ( $this->m_suppress_protected ) && ( strtolower( (string)$method['visibility'] ) == 'protected' ) ) continue;
	  if ( ( $this->m_suppress_private ) && ( strtolower( (string)$method['visibility'] ) == 'private' ) ) continue;

            $methodName = (string)$method->name;

            $this->SplitFullname( (string)$method->full_name, $name_namespace, $name_class, $name_method  );

            $return = $method->xpath('docblock/tag[@name="return"]');

	    // Rainer Stötter: integrated 'return'-tag

            $a_return = array( );
            if (count($return)) {
                // $return = (string)$return[0]['type'];
                $a_return['type'] = (string)$return[0]['type'];
                $a_return['description'] = (string)$return[0]['description'];

            }

            if (count($return)) {
                $return = (string)$return[0]['type'];

            }  else {
                $return = 'mixed';
            }


	    // Rainer Stötter: integrated 'see'-tags


	    $a_see = array( );

            $a_xml = $method->xpath('docblock/tag[@name="see"]');

            if ( count( $a_xml ) ) {

                foreach( $a_xml as $xml ) {

		    if ( strlen( trim( $xml[ 'link' ] ) ) )  {

			$this->SplitFullname( $xml[ 'link' ], $str_namespace, $str_class, $str_component  );

			if ( $this->FoundComponent( $str_component, $class->name, $found_class_name, $found_type ) ) {

			    // echo "\n found '$str_component' in '$found_class_name' of type '$found_type'";

			    $pos_slash = strrpos( $found_class_name, '\\' );
			    if ( $pos_slash !== false ) {
				$found_class_name = substr( $found_class_name, $pos_slash + 1 );
			    }

			    if ( $found_class_name == $class->name ) {

				if ( $found_type == 'M' ) {
				    $a_see[] = $name_class . '::' .  $str_component . '()';
				 } else {
				    $a_see[] = $name_class . '::' .  $str_component ;
				 }

			    } else {
				$a_see[] = $found_class_name . $str_component . '()';
			    }

			} else {
			    // echo "\n error: could not find component '{$str_component}'";
			}
		    }

                }

                // echo "\n a_see ="; var_dump( $a_see );
            }

            if ( $this->m_do_sort_see ){

		if ( ! sort( $a_see ) ) {
		    die( "\n error sorting see section" );
		}

            }


	    ///

            $arguments = [];

            foreach ($method->argument as $argument) {


		//

                $nArgument = [
                    'type' => (string)$argument->type,
                    'name' => (string)$argument->name
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
                'name'        => $this->PrepMD( $methodName ),
                'description' => (string)$method->docblock->description . "\n\n" . (string)$method->docblock->{'long-description'},
                'visibility'  => (string)$method['visibility'],
                'abstract'    => ((string)$method['abstract']) == "true",
                'static'      => ((string)$method['static']) == "true",
                'deprecated'  => count($class->xpath('docblock/tag[@name="deprecated"]')) > 0,
                'signature'   => $signature ,
                'arguments'   => $arguments,
                'definedBy'   => $className,
                'see'         => $a_see,
                'class_name'  => $this->PrepMD( $name_class ),
                'namespace'   => $this->PrepMD( $name_namespace ),
                'returns'     => $a_return
            ];

        }

        return $methods;
    }


    /**
     * Makes $str compatible for the Markdown (MD) language
     *
     * @param string $str
     *
     * @return string
     */


    private function PrepMD( $str ) {

	return str_replace('\\', '\\\\', $str );


    }	// function PrepMD( )

    /**
     * Parses all property information for a single class or interface.
     *
     * You must pass an xml element that refers to either the class or interface element from
     * structure.xml.
     *
     * @param SimpleXMLElement $class
     *
     * @return array
     */
    protected function parseProperties(SimpleXMLElement $class)
    {
        $properties = [];

        $className = (string)$class->full_name;
        $className = ltrim($className, '\\');

        foreach ($class->property as $xProperty) {

	    if ( ( $this->m_suppress_public ) && ( strtolower( (string)$xProperty['visibility'] ) == 'public' ) ) continue;
	    if ( ( $this->m_suppress_protected ) && ( strtolower( (string)$xProperty['visibility'] ) == 'protected' ) ) continue;
	    if ( ( $this->m_suppress_private ) && ( strtolower( (string)$xProperty['visibility'] ) == 'private' ) ) continue;


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
                'deprecated'  => count($class->xpath('docblock/tag[@name="deprecated"]')) > 0,
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
     * @param SimpleXMLElement $class
     *
     * @return array
     */
    protected function parseConstants(SimpleXMLElement $class)
    {
        $constants = [];

        $className = (string)$class->full_name;
        $className = ltrim($className, '\\');

        foreach ($class->constant as $xConstant) {
            $name = (string)$xConstant->name;
            $value = (string)$xConstant->value;

            $signature = sprintf('const %s = %s', $name, $value);

            $constants[$name] = [
                'name'        => $name,
                'description' => (string)$xConstant->docblock->description . "\n\n" . (string)$xConstant->docblock->{'long-description'},
                'signature'   => $signature,
                'value'       => $value,
                'deprecated'  => count($class->xpath('docblock/tag[@name="deprecated"]')) > 0,
                'definedBy'   => $className,
            ];
        }

        return $constants;
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
        $class = $this->classDefinitions[$className];

        $newMethods = [];

        foreach (array_merge($class['extends'], $class['implements']) as $extends) {
            if (!isset($this->classDefinitions[$extends])) {
                continue;
            }

            foreach ($this->classDefinitions[$extends]['methods'] as $methodName => $methodInfo) {
                if (!isset($class[$methodName])) {
                    $newMethods[$methodName] = $methodInfo;
                }
            }

            $newMethods = array_merge($newMethods, $this->expandMethods($extends));
        }

        $this->classDefinitions[$className]['methods'] = array_merge(
            $this->classDefinitions[$className]['methods'],
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
        $class = $this->classDefinitions[$className];

        $newProperties = [];

        foreach (array_merge($class['implements'], $class['extends']) as $extends) {
            if (!isset($this->classDefinitions[$extends])) {
                continue;
            }

            foreach ($this->classDefinitions[$extends]['properties'] as $propertyName => $propertyInfo) {
                if ($propertyInfo['visibility'] === 'private') {
                    continue;
                }

                if (!isset($class[$propertyName])) {
                    $newProperties[$propertyName] = $propertyInfo;
                }
            }

            $newProperties = array_merge($newProperties, $this->expandProperties($extends));
        }

        $this->classDefinitions[$className]['properties'] += $newProperties;

        return $newProperties;
    }
}
