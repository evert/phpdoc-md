PHPDocumentor MarkDown export
=============================
[![Build Status](https://travis-ci.org/evert/phpdoc-md.svg)](https://travis-ci.org/evert/phpdoc-md)
[![Total Downloads](https://poser.pugx.org/evert/phpdoc-md/d/total.svg)](https://packagist.org/packages/evert/phpdoc-md)
[![Latest Stable Version](https://poser.pugx.org/evert/phpdoc-md/v/stable.svg)](https://packagist.org/packages/evert/phpdoc-md)
[![Latest Unstable Version](https://poser.pugx.org/evert/phpdoc-md/v/unstable.svg)](https://packagist.org/packages/evert/phpdoc-md)
[![License](https://poser.pugx.org/evert/phpdoc-md/license.svg)](https://packagist.org/packages/evert/phpdoc-md)

This is a script that can generate markdown (.md) files for your API
documentation.

It is tailored for projects using PSR-0, PSR-1, PSR-2, PSR-4 and namespaces.
The project was primarily developed for [sabre/dav](https://sabre.io/),
but it should work for other codebases as well.

It only documents classes and interfaces.

The code is ugly, it was intended as a one-off, and I was in a hurry.. so the
codebase may not be up to your standards. (it certainly isn't up to mine).

Installation
------------

This project assumes you have composer installed.

Run composer command:

    composer require evert/phpdoc-md ~0.2.0 --dev

Or simply add:

    "require-dev" : {

        "evert/phpdoc-md" : "~0.2.0"

    }

To your composer.json, and then you can simply install with:

    composer install


Usage
-----

First ensure that phpdocumentor 2 is installed somewhere, after, you must
generate a file called `structure.xml`.

The easiest is to create a temporary directory, for example named `docs/`.

    # phpdoc command
    phpdoc  -d [project path] -t docs/ --template="xml"

    # Next, run phpdocmd:
    phpdocmd docs/structure.xml [outputdir]

Options
-------

    --lt [template]
        This specifies the 'template' for links we're generating. By default
        this is "%c.md".

    --index [filename]
        This specifies the 'filename' for API Index markdown file we're generating.
        By default this is "ApiIndex.md".

This should generate all the .md files. I'm excited to hear your feedback.

Cheers,
[Evert](https://twitter.com/evertp)
