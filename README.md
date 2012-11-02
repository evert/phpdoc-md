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

**Usage:**

You must have phpdocumentor2 installed.

First generate a `structure.xml` file as such:

```
phpdoc parse -t . -d [project path]
```

This will create the xml file in the current directory.
Next, call the phpdocmd script: 

```
phpdocmd structure.xml [outputdir]
```

This should generate all the .md files. I'm excited to hear your feedback.

Cheers,
[Evert](https://twitter.com/evertp)
