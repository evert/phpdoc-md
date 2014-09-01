PHPDocumentor MarkDown export
=============================

This is a script that can generate markdown (.md) files for your API
documentation.

It is tailored for projects using PSR-0, PSR-1, PSR-2 and PHP 5.3 namespaces.
The project was primarily developed for [SabreDAV](https://github.com/evert/sabreDAV),
but it should work for other codebases as well.

It only documents classes and interfaces.

The code is ugly, it was intended as a one-off, and I was in a hurry.. so the
codebase may not be up to your standards. (it certainly isn't up to mine).

Installation
------------

This project assumes you have composer installed.
Simply add:

    "require-dev" : {

        "evert/phpdoc-md" : "~0.0.7"

    }

To your composer.json, and then you can simply install with:

    composer install


Usage
-----

First ensure that phpdocumentor 2 is installed somewhere, after, you must
generate a file called `structure.xml`.

The easiest is to create a temporary directory, for example named `docs/` as
phpDocumentor2 creates a lot of 'cache' directories.

    # phpdoc command
    mkdir docs
    cd docs
    phpdoc  -d [project path] -t . --template="xml"
    rm -r phpdoc-cache-*

    # Next, run phpdocmd:
    phpdocmd structure.xml [outputdir]

Options
-------

    --lt [template]
        This specifies the 'template' for links we're generating. By default
        this is "%c.md".

This should generate all the .md files. I'm excited to hear your feedback.

Cheers,
[Evert](https://twitter.com/evertp)
