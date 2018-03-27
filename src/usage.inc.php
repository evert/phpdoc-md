PHPDocumentor Markdown Generator

Usage:

    # First generate a structure.xml file with phpdocumentor.
    # This command will generate structure.xml in the docs directry
    phpdoc -d [project path] -t docs/ --template="xml"

    # Next, run phpdocmd:
    <?php echo $argv[0]; ?> docs/structure.xml [outputdir]

Options:

    --lt [template]
        This specifies the 'template' for links we're generating. By default
        this is "%c.md".
    --sort-index
	Sort the api index and the methods on class level
    --sort-see
	Sort the see also section
    --level [ component | class ]
        Component level: Generate a md file for each class component ( method / const ..).
        Class level: generate a md file for each class
        The default behaviour is the class level
    --protected-off
    --private-off
    --public-off
	Disables the output of protected / private /  public components
    --index [ name ]
	Sets the name of the index file. Defaults to ApiIndex.md
