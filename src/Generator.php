<?php

// Rainer Stötter: added argument --sort-see, April 2017
// Rainer Stötter: added return values and descriptions, April 2017
// Rainer Stötter: removed file extension .md from link, April 2017
// Rainer Stötter: added argument --public-off, April 2017
// Rainer Stötter: added argument --private-off, April 2017
// Rainer Stötter: added argument --protected-off, April 2017
// Rainer Stötter: added argument --level, April 2017
// Rainer Stötter: added argument --sort-index, April 2017

namespace PHPDocMD;

use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_SimpleFilter;

/**
 * This class takes the output from 'parser', and generate the markdown
 * templates.
 *
 * @copyright Copyright (C) Evert Pot. All rights reserved.
 * @author    Evert Pot (https://evertpot.coom/)
 * @license   MIT
 */
class Generator
{
    /**
     * Output directory.
     *
     * @var string
     */
    protected $outputDir;

    /**
     * The list of classes and interfaces.
     *
     * @var array
     */
    protected $classDefinitions;

    /**
     * Directory containing the twig templates.
     *
     * @var string
     */
    protected $templateDir;

    /**
     * A simple template for generating links.
     *
     * @var string
     */
    protected $linkTemplate;

    /**
     * Filename for API Index.
     *
     * @var string
     */
    protected $apiIndexFile;



    /**
     * if true, then the api index and the component indices are sorted. Defaults to false.
     *
     * @var bool
     * @author    Rainer Stötter
     *
     */

    protected $m_do_sort = false;

    /**
     * If set, then we operate on class level and generate one md file for each class
     *
     * @var int
     * @see m_level
     * @author    Rainer Stötter
     *
     */

    const LEVEL_CLASS = 0;

    /**
     * If set, then we operate on component level and generate one md file for each class component (const, method ..)
     *
     * @var int
     * @see m_level
     * @author    Rainer Stötter
     *
     */

    const LEVEL_COMPONENT = 1;

    /**
     *  Defines the level we are operating. Defaults to self::LEVEL_CLASS.
     *
     * @var int
     * @see LEVEL_CLASS
     * @see LEVEL_COMPONENT
     * @author    Rainer Stötter
     *
     */


    protected $m_level = self::LEVEL_CLASS;



    /**
     * @param array  $classDefinitions
     * @param string $outputDir
     * @param string $templateDir
     * @param string $linkTemplate
     * @param string $apiIndexFile
     */
    function __construct(
	  array $classDefinitions,
	  $outputDir,
	  $templateDir,
	  $linkTemplate = '%c.md',
	  $apiIndexFile = 'ApiIndex.md',
	  $do_sort = false,
	  $level = 'class'
	  )
    {
        $this->classDefinitions = $classDefinitions;
        $this->outputDir = $outputDir;
        $this->templateDir = $templateDir;
        $this->linkTemplate = $linkTemplate;
        $this->apiIndexFile = $apiIndexFile;

        $this->m_do_sort = $do_sort;

        $level = strtolower( $level );
        $this->m_level = ( $level == 'component' ? self::LEVEL_COMPONENT : self::LEVEL_CLASS );

        if ( $this->m_do_sort ) echo "\n sorting the output";
        if ( $this->m_level == self::LEVEL_CLASS ) echo "\n working on class level";
        if ( $this->m_level == self::LEVEL_COMPONENT ) echo "\n working on component level";
        echo "\n api index file is {$this->apiIndexFile}";

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
     * Starts the generator.
     */
    function run()
    {
    
/* Rainer Stötter: Fehler 2018-03: nimmt kein Array als zweiten Parameter mehr an
        $loader = new Twig_Loader_Filesystem($this->templateDir, [
            'cache' => false,
            'debug' => true,
        ]);
*/        

        $loader = new Twig_Loader_Filesystem($this->templateDir );  // Rainer Stötter

        $twig = new Twig_Environment($loader);

        $GLOBALS['PHPDocMD_classDefinitions'] = $this->classDefinitions;
        $GLOBALS['PHPDocMD_linkTemplate'] = $this->linkTemplate;

        $filter = new Twig_SimpleFilter('classLink', ['PHPDocMd\\Generator', 'classLink']);
        $twig->addFilter($filter);

        // Rainer Stötter
        $filter = new Twig_SimpleFilter('namespace2Link', ['PHPDocMd\\Generator', 'namespace2Link']);
        $twig->addFilter($filter);


        if ( $this->m_do_sort ) {
            asort( $this->classDefinitions );
        }

        if ( $this->m_level == self::LEVEL_CLASS ) {
        
            foreach ($this->classDefinitions as $className => $data) {
            
                $output = $twig->render('class.twig', $data);

                file_put_contents($this->outputDir . '/' . $data['fileName'], $output);

            }
            
            $index = $this->createIndex( );

            $index = $twig->render('index.twig',
                [
                'index'            => $index,
                'classDefinitions' => $this->classDefinitions,
                ]
            );

            file_put_contents($this->outputDir . '/' . $this->apiIndexFile, $index);	    

        } else {
        
            $this->SplitIntoComponents( $twig );


        }



    }

    // constants for the API type we are generating - added by Rainer Stötter
    const API_TYPE_NONE = 0;      // no api type defined
    const API_TYPE_USER = 1;      // users of the classes get merely public components
    const API_TYPE_DEVELOPER = 2;      // developers get merely public, protected and private components
    
    // member variables for the acces type of the components to render  - added by Rainer Stötter
    
    protected $m_render_public = true;
    protected $m_render_protected = true;
    protected $m_render_private = true;
    
    protected function RenderFilterFunction( $ary ) {
    
        if ( ( $this->m_render_public ) && ( $ary[ 'visibility' ] == 'public' ) ) {
            return true;
        } elseif ( ( $this->m_render_protected ) && ( $ary[ 'visibility' ] == 'protected' ) ) {
            return true;
        } elseif ( ( $this->m_render_private ) && ( $ary[ 'visibility' ] == 'private' ) ) {
            return true;
        }
        
        return false;
        
    
    }   // function RenderFilterFunction
    
    protected function _SplitIntoComponents( int $api_type, Twig_Environment $twig ) {
    
        // added by Rainer Stötter
        
        $prefix_api_file = '';
        
        if ( $api_type == self::API_TYPE_USER ) {
            $prefix_api_file = 'pub';
            $this->m_render_public = true;
            $this->m_render_protected = false;
            $this->m_render_private = false;
            
        } elseif ( $api_type == self::API_TYPE_DEVELOPER ) {
            $prefix_api_file = 'dev';
            $this->m_render_public = true;
            $this->m_render_protected = true;
            $this->m_render_private = true;

        }        
        
        // the following variables are used for twig - twig interprets empty string as false
        
        $str_render_public = ( $this->m_render_public ? 'true' : '' );
        $str_render_protected = ( $this->m_render_protected ? 'true' : '' );
        $str_render_private = ( $this->m_render_private ? 'true' : '' );
        
        $namespace_str = '-';

	    foreach ( $this->classDefinitions as $className => $data_class ) {
	    
            $namespace = $data_class[ 'namespace' ];
            $namespace_str = '-' . str_replace( '\\', '-', $namespace ) . '-';
	    
            $_class_name = $data_class[ 'shortClass' ];            
            // $_file_name = $prefix_api_file . $namespace_str . $_class_name . '.md';
            
            $shortClassPrefixed = "{$prefix_api_file}{$namespace_str}{$_class_name}";
            $file_name_class ="{$shortClassPrefixed}.md";
            
            $prefix_class_link = rawurlencode( $shortClassPrefixed . '::' );
            
            $data_class['shortClassPrefixed'] = $prefix_api_file . $_class_name;	    
            $data_class['prefix_api_file'] = $prefix_api_file;
            $data_class['shortClass'] = $_class_name;
            $data_class['render_public'] = $str_render_public;
            $data_class['render_protected'] = $str_render_protected;
            $data_class['render_private'] = $str_render_private;            	                
            $data_class['prefix_class_link'] = $prefix_class_link;
	    
            $output = $twig->render('component-class.twig', $data_class);

            // file_put_contents($this->outputDir . '/' . $data_class['fileName'], $output);
            file_put_contents($this->outputDir . '/' . $file_name_class, $output);                        
            
            
            $a_filtered = array_filter( $data_class['methods'], array( 'PHPDocMD\Generator', 'RenderFilterFunction' ) );

            foreach ( $a_filtered as $method => $data_method ) {
            
                
                /*
                var_dump( array_keys( $data_method ) );
                die( "\n Abbruch" );
                
                array(13) {
  [0] =>
  string(4) "name"
  [1] =>
  string(11) "description"
  [2] =>
  string(10) "visibility"
  [3] =>
  string(8) "abstract"
  [4] =>
  string(6) "static"
  [5] =>
  string(10) "deprecated"
  [6] =>
  string(9) "signature"
  [7] =>
  string(9) "arguments"
  [8] =>
  string(9) "definedBy"
  [9] =>
  string(3) "see"
  [10] =>
  string(10) "class_name"
  [11] =>
  string(9) "namespace"
  [12] =>
  string(7) "returns"
}

                */
            
                
            
                $class_name = $data_class[ 'shortClass' ];
                $method_name = $data_method['name'];
                $namespace = $data_class['namespace'];
                $namespace_str = '-' . str_replace( '\\', '-', $namespace ) . '-';

                $file_name = $prefix_api_file . $namespace_str . $class_name . '::' . $method_name . '().md';
                
                $data = $data_method;
                $data['shortClass'] = $class_name;
                $data['namespace'] = $namespace;
                $data['namespace_str'] = $namespace_str;
                $data['prefix_api_file'] = $prefix_api_file;
                $data['shortClassPrefixed'] = $shortClassPrefixed;
                $data['prefix_class_link'] = $prefix_class_link;
                $data['render_public'] = $str_render_public;
                $data['render_protected'] = $str_render_protected;
                $data['render_private'] = $str_render_private;                

                $output = $twig->render( 'component-method.twig', $data );

                file_put_contents($this->outputDir . '/' . $file_name, $output);

            }

            // constants need to be filtered, too!
            $a_filtered = array_filter( $data_class['constants'], array( 'PHPDocMD\Generator', 'RenderFilterFunction' ) );
            foreach ( $a_filtered as $constant => $data_constant ) {

                $class_name = $data_class[ 'shortClass' ];
                $constant_name = $data_constant['name'];
                $namespace = $data_class['namespace'];
                $namespace_str = '-' . str_replace( '\\', '-', $namespace ) . '-';

                $file_name = $prefix_api_file . $namespace_str . $class_name . '::' . $constant_name . '.md';

                $data = $data_constant;
                $data['shortClass'] = $class_name;                
                $data['namespace'] = $namespace;
                $data['namespace_str'] = $namespace_str;
                $data['prefix_api_file'] = $prefix_api_file;
                $data['shortClassPrefixed'] = $shortClassPrefixed;
                $data['prefix_class_link'] = $prefix_class_link;
                $data['render_public'] = $str_render_public;
                $data['render_protected'] = $str_render_protected;
                $data['render_private'] = $str_render_private;                
                
                $output = $twig->render( 'component-constant.twig', $data );

                file_put_contents($this->outputDir . '/' . $file_name, $output);

            }
            
            $a_filtered = array_filter( $data_class['properties'], array( 'PHPDocMD\Generator', 'RenderFilterFunction' ) );
            foreach ( $a_filtered as $property => $data_property ) {

                $class_name = $data_class[ 'shortClass' ];
                $property_name = $data_property['name'];
                $namespace = $data_class['namespace'];
                $namespace_str = '-' . str_replace( '\\', '-', $namespace ) . '-';
                
                $file_name = $prefix_api_file . $namespace_str . $class_name . '::' . $property_name . '.md';

                $data = $data_property;
                $data['shortClass'] = $class_name;
                $data['namespace'] = $namespace;
                $data['namespace_str'] = $namespace_str;
                $data['prefix_api_file'] = $prefix_api_file;
                $data['shortClassPrefixed'] = $shortClassPrefixed;
                $data['s'] = $prefix_class_link;
                $data['render_public'] = $str_render_public;
                $data['render_protected'] = $str_render_protected;
                $data['render_private'] = $str_render_private;                                                

                $output = $twig->render( 'component-property.twig', $data );
                

                file_put_contents($this->outputDir . '/' . $file_name, $output);

            }


	    }
	    
	    // create the index file
	    
        $index = $this->createComponentIndex( $prefix_api_file );

        $index = $twig->render('component-index.twig',
            [
            'index'            => $index,
            'classDefinitions' => $this->classDefinitions,
            'prefix_api_file'  => $prefix_api_file,
            'namespace_str'    => $namespace_str,
            'render_public'    => $str_render_public,
            'render_protected' => $str_render_protected,
            'render_private'   => $str_render_private
            ]
        );
        
        $name_index_file = $prefix_api_file . $namespace_str . '-index' . '.md'; // we want to have two minus signs before 'index'
        
        file_put_contents($this->outputDir . '/' . $name_index_file, $index);	    
        
    
    }   // function _SplitIntoComponents( )
    
    protected function SplitIntoComponents( Twig_Environment $twig ) {
    
        // added by Rainer Stötter
        
	    // Rainer Stötter : we are splitting into component files
	    
        $filter = new Twig_SimpleFilter('classLinkPrefixed', ['PHPDocMd\\Generator', 'classLinkPrefixed']);
        $twig->addFilter($filter);
	    
        $filter = new Twig_SimpleFilter('namespace2LinkPrefixed', ['PHPDocMd\\Generator', 'namespace2LinkPrefixed']);
        $twig->addFilter($filter);
        
        $filter = new Twig_SimpleFilter('rawurlencode', ['PHPDocMd\\Generator', '_rawurlencode']);
        $twig->addFilter($filter);
        
	    
	    // the md files refer to the api file's basename without the extension 
	    $md_api_file = basename( $this->apiIndexFile, '.md' );
	    $prefix_api_file = '';
	    
	    if ( true ) {
            $prefix_api_file = $md_api_file . '_';            
	    }
	    
	    self::$m_api_prefix = $prefix_api_file;
	    
	    $this->_SplitIntoComponents( self::API_TYPE_USER, $twig );
	    $this->_SplitIntoComponents( self::API_TYPE_DEVELOPER, $twig );
	    
	    /*
        $function = new Twig_SimpleFunction('classLinkExtended', function ($param1, $param2 = null) {
            return isset($param2) ? $param1 * $param2 : $param1;
        });	    
        */


	    // var_dump( array_keys( $this->classDefinitions[ 'rstoetter\libdatephp\cDate' ] ) );        
        
    
    
    }   // function SplitIntoComponents( )
    
    protected function createComponentIndex( string $prefix_api_file ) : string {
    
        // added by Rainer Stötter - basing on $this->createIndex( )
    
        $tree = [];

        foreach ($this->classDefinitions as $className => $classInfo) {
            $current = & $tree;

            foreach (explode('\\', $className) as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }

                $current = & $current[$part];
            }
        }

        /**
         * This will be a reference to the $treeOutput closure, so that it can be invoked
         * recursively. A string is used to trick static analysers into thinking this might be
         * callable.
         */
        $treeOutput = '';

        $treeOutput = function($item, $prefix_api_file, $fullString = '', $depth = 0 ) use (&$treeOutput) {
            $output = '';

            foreach ($item as $name => $subItems) {
                $fullName = $name;

                if ($fullString) {
                    $fullName = $fullString . '\\' . $name;
                }

                $output .= str_repeat(' ', $depth * 4) . '* ' . Generator::classLinkComponent($fullName, $prefix_api_file, $name ) . "\n";
                $output .= $treeOutput($subItems, $prefix_api_file, $fullName, $depth + 1);
            }

            return $output;
        };

        return $treeOutput($tree, $prefix_api_file );
        
    }   // function createComponentIndex( )
    
    static function classLinkComponent( string $className, string $prefix_api_file, $label = null ) : string {
    
        // added by Rainer Stötter - basing on self::classLink( )
        
        $classDefinitions = $GLOBALS['PHPDocMD_classDefinitions'];
        $linkTemplate = $GLOBALS['PHPDocMD_linkTemplate'];

        $returnedClasses = [];

        foreach (explode('|', $className) as $oneClass) {
            $oneClass = trim($oneClass, '\\ ');

            if (!$label) {
                $label = $oneClass;
            }

            if (!isset($classDefinitions[$oneClass])) {
                $returnedClasses[] = $oneClass;
            } else {
                $link = str_replace('\\', '-', $oneClass);
                $link = strtr($linkTemplate, ['%c' => $link]);

                $returnedClasses[] = sprintf("[%s](%s-%s)", $label, $prefix_api_file, $link );
            }
        }

        // remove the trailing '.md' from the link as with this suffix the content of the link is interpreted as
        // raw and not formatted in Markdown ( Rainer Stötter )

        foreach( $returnedClasses as & $item ) {

            $item = str_replace( '.md', '', $item );

        }



        return implode('|', $returnedClasses);
    }    
    
    

    /**
     * Creates an index of classes and namespaces.
     *
     * I'm generating the actual markdown output here, which isn't great...But it will have to do.
     * If I don't want to make things too complicated.
     *
     * @return array
     */
    protected function createIndex()
    {
        $tree = [];

        foreach ($this->classDefinitions as $className => $classInfo) {
            $current = & $tree;

            foreach (explode('\\', $className) as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }

                $current = & $current[$part];
            }
        }

        /**
         * This will be a reference to the $treeOutput closure, so that it can be invoked
         * recursively. A string is used to trick static analysers into thinking this might be
         * callable.
         */
        $treeOutput = '';

        $treeOutput = function($item, $fullString = '', $depth = 0) use (&$treeOutput) {
            $output = '';

            foreach ($item as $name => $subItems) {
                $fullName = $name;

                if ($fullString) {
                    $fullName = $fullString . '\\' . $name;
                }

                $output .= str_repeat(' ', $depth * 4) . '* ' . Generator::classLink($fullName, $name) . "\n";
                $output .= $treeOutput($subItems, $fullName, $depth + 1);
            }

            return $output;
        };

        return $treeOutput($tree);
    }

    /**
     * This is a twig template function.
     *
     * This function allows us to easily link classes to their existing pages.
     *
     * Due to the unfortunate way twig works, this must be static, and we must use a global to
     * achieve our goal.
     *
     * @param string      $className
     * @param null|string $label
     *
     * @return string
     */
    static function classLink($className, $label = null)
    {
        $classDefinitions = $GLOBALS['PHPDocMD_classDefinitions'];
        $linkTemplate = $GLOBALS['PHPDocMD_linkTemplate'];

        $returnedClasses = [];

        foreach (explode('|', $className) as $oneClass) {
            $oneClass = trim($oneClass, '\\ ');

            if (!$label) {
                $label = $oneClass;
            }

            if (!isset($classDefinitions[$oneClass])) {
                $returnedClasses[] = $oneClass;
            } else {
                $link = str_replace('\\', '-', $oneClass);
                $link = strtr($linkTemplate, ['%c' => $link]);

                $returnedClasses[] = sprintf("[%s](%s)", $label, $link);
            }
        }

        // remove the trailing '.md' from the link as with this suffix the content of the link is interpreted as
        // raw and not formatted in Markdown ( Rainer Stötter )

        foreach( $returnedClasses as & $item ) {

	    $item = str_replace( '.md', '', $item );

        }



        return implode('|', $returnedClasses);
    }


    static function namespace2Link($name_namespace, $label = null)
    {

	$link = trim( $name_namespace, '\\ ');
	$link = str_replace( '\\', '-', $link );

	return $link;
	
    }
    
    static $m_api_prefix = '';

    static function classLinkPrefixed($className, $label = null)
    {
        $classDefinitions = $GLOBALS['PHPDocMD_classDefinitions'];
        $linkTemplate = $GLOBALS['PHPDocMD_linkTemplate'];

        $returnedClasses = [];
        
        foreach (explode('|', $className) as $oneClass) {
            $oneClass = trim($oneClass, '\\ ');

            if (!$label) {
                $label = $oneClass;
            }

            if (!isset($classDefinitions[$oneClass])) {
                $returnedClasses[] = $oneClass;
            } else {
                $link = str_replace('\\', '-', $oneClass);
                $link = strtr($linkTemplate, ['%c' => $link]);

                $returnedClasses[] = sprintf("[%s](%s%s)", $label, self::$m_api_prefix, $link);
            }
        }

        // remove the trailing '.md' from the link as with this suffix the content of the link is interpreted as
        // raw and not formatted in Markdown ( Rainer Stötter )

        foreach( $returnedClasses as & $item ) {

            $item = str_replace( '.md', '', $item );

        }



        return implode('|', $returnedClasses);
    }
    
    static function _rawurlencode( $file_name, $label = null ) {
        // added by Rainer Stötter
        
        $file_name = rawurlencode( $file_name );

        return $file_name;
	
    }    
    
    static function namespace2LinkPrefixed($name_namespace, $label = null)
    {

	$link = trim( $name_namespace, '\\ ');
	$link = str_replace( '\\', '-', $link );
	
	$link = self::$m_api_prefix . $link;

	return $link;
	
    }

    
}
