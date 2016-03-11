# zesk

**Zesk Application Framework**: All the good stuff, none of the bad.

**Zesk is a suite of tools which make writing web applications easy and fast.** A robust and feature-rich **Object Relational Mapping** interface, **database schema synchronization**, easy command line tool coding, and tons of tools to allow you to write web applications quickly. 

It also has a modular interface to all extension via modules, a powerful hook system to allow integration and notifications for events, and an object-oriented architecture to allow for fast development using subclasses.

It also has a Model-View-Controller architecture, and a form and object editing system using Controls and Views (for form elements and UI generation).

Zesk is a toolkit and a platform, and attempts to adhere to the DRY (Don't Repeat Yourself) principle. In addition, it generates no errors, warnings, or notices within the code, and encourages applications to be written using `E_ALL | E_STRICT` error reporting.

Module integration is meant to be straightforward, seamless, and easy to manage.

For more information, read the [docs](./docs).

## History 

Zesk was written primarily by Kent Davidson, around 2002. It evolved and changed a lot in PHP 4 and was more object-oriented than PHP 4 could handle. It also turned into massive bloatware and was subsequently scrapped. PHP evolved by adding autoloading, PHP 5 added true object-orientedness and PHP 5 became much easier to write modular, object-oriented code.

Zesk was re-written to be leaner and meaner and the good ideas were taken out of the original (Database schema, MVC) and rewritten. In the meantime CodeIgniter, Kohana, Zend Framework, Drupal, and Wordpress evolved.

We like to think that we took the good parts of all of these technologies and left out most of the bad parts. From Kohana, some of its elegance, object-oriented approach, and strict PHP 5 support. From Zend Framework, the autoloader and class naming. From Drupal the method space hook system, and seamless module extension interface. From Wordpress, the hook registration system.

In short, this software was heavily influenced by much of the PHP frameworks out there, and hopefully presents a robust and powerful tool for building your web applications in PHP 5 or greater.

Visit [Zesk.com](http://zesk.com) for up-to-date information.
