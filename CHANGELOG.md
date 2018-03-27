ChangeLog
=========
* Added: if level==component, then the program creates an user reference (showing just the public components) and a developer reference (including public, protected and private components) @rstoetter
* Added:--sort-index option (Sort the api index and the methods on class level) @rstoetter
* Added:--sort-see option (Sort the see also section) @rstoetter
* Added:--level option (Component level: Generate a md file for each class component ( method / const ..). Class level: generate a md file for each class) @rstoetter
* Added:--protected-off option (Disables the output of protected components) @rstoetter
* Added:--private-off option (Disables the output of private components) @rstoetter
* Added:--public-off option (Disables the output of public components) @rstoetter

0.2.0 (2016-02-11)
------------------

* Updated: Big CS cleanup thanks to @assertchris
* #87: Now supports Twig 2.0. (@ericdowell)
* #87: Added ability to override the 'ApiIndex.md' filename via the
  `--index` command line option. (@ericdowell)
* Using php-cs-fixer and travis.


0.1.1 (2015-03-26)
------------------

* Updated: Updated to the latest dependencies.
* Changed: Switched to PSR-4.
* Changed: Fixed documentation in bin/phpdocmd. See issue #5.


0.1.0 (2014-09-01)
------------------

* Added: phpdocumentor is now a dev-dependency.
* Fixed: Updated documentation so it's correct again for the latest
  phpdocumentor.
* Changed: Using the more universal "4 space" indentation syntax for code,
  instead of github's triple-backtick syntax.
* Fixed: Template variables about which class defined a property or
  method, is correct again.


0.0.7 (2013-02-11)
------------------

* Added: --lt option.
* Fixed: Relative links in github. Thnx @jelmersnoeck.
* Fixed: Lots of bugfixes before this point :)


0.0.1 (2012-11-02)
------------------
* First alpha release

Project started: 2012-11-02
