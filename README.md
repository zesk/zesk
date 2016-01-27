# zesk

**Zesk Application Framework**: All the good stuff, none of the bad.

Zesk was written primarily by Kent Davidson, around 2002. It evolved and changed a lot in PHP 4 and was more object-oriented than PHP 4 could handle. It also turned into massive bloatware and was subsequently scrapped.

It was re-written to be leaner and meaner and the good ideas were taken out of the original (Database schema, MVC) and rewritten. In the meantime CodeIgniter, Kohana, Zend Framework, Drupal, and Wordpress evolved.

We like to think that we took the good parts of all of these technologies and left out most of the bad parts. From Kohana, some of its elegance, object-oriented approach, and strict PHP 5 support. From Zend Framework, the autoloader and class naming. From Drupal the method space hook system, and seamless module extension interface. From Wordpress, the hook registration system.

Zesk is a toolkit and a platform, and attempts to adhere to the DRY (Don't Repeat Yourself) principle. In addition, it generates no errors, warnings, or notices within the code, and encourages applications to be written using `E_ALL | E_STRICT` error reporting.

Module integration is meant to be straightforward, seamless, and easy to manage.

For more information, read the [docs](./docs).

Visit [Zesk.com](http://zesk.com) for up-to-date information.
