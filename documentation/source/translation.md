# Translation

Zesk uses the fairly standard short-form for string translation:

	__($message, ...)
	
With an optional longer form for those who like clarity:

    zesk\Locale::translate($message, ...)
	
Both are identical, and both return a translated string to the current locale. You can format translations like this:

	__("My {0} ate my {1}", $dog, $cat);
	
Or like this:

	__("My {dog} ate my {cat}", array("dog" => $dog, "cat" => $cat));
	
Or use no parameters at all:

	__("Organization")
	
If you want a label to be more context-sensitive, use `:=` to assign a more specific translation label to it. For example, is the term "Submit" is used in many buttons, but a certain one should be translated differently, you would do:

	__("Login_Submit:=Submit")

Which would return:

	"Submit"

The left-hand portion is always stripped off, but you can specify the entire phrase in your language file to get that translation first, e.g.

	$translation['Login_Submit:=Submit'] = "Enter the Thunderdome";
	$translation['Submit'] = "Submit";
	
As a general rule, it's best to avoid putting markup in translation files.

# Translation files

Translation are PHP files which simply populate an array and return them. The file itself can do whatever you want, including load the translations from a database. The simplest file would be:

	$translate = array();
	$translate['User'] = "Honorable Visitor";
	return $translate;
	
Or you can call any function available and loaded into the system. (Although loading language translations should be really, really fast.)

	return JSON::decode(File::contents($application->path("etc/language/en_GB.json")));

There is currently no mechanism to segment language files into distinct sections of the site, so it's all or nothing.

Translations are done for a **Language** first, then for a specific **Dialect** which is (typically) a country-code. This is represented by the universal 2-letter codes for a languages and then specific countries.

So, you can have:

	"en_US": "Organization", "Ms.", "Mr."
	"en_GB": "Organisation", "Ms", "Mr"
	"fr_FR": "L'Organisation", "Mr", "Mme"

The first part is the language (`en` = *English*), the 2nd part is the country, `US` = *United states*.

The locale names are used when loading files. All language files are (typically) located in one or more directories, and generally are loaded:

	${language}.inc
	${language}_${dialect}.inc

Use the call `zesk\Locale::localePath($path_to_add)` to add a directory to search for locale files.

This allows modules and applications to add their own translations for strings within their modules.

This way the majority of translated terms can be in a single, common file for a language, with specific country changes then in the dialect files.

Finally, files are loaded from zesk's root directory first, then from the application's directory.

# Auto-localization

While writing an application, it's nice to get a sense of what words are being translated. You can turn on `zesk\Locale::auto` and `zesk\Locale::auto_path` as [global configuration options](./globals.md) in order to have all translated words written to an ever-growing list in 

	${zesk\Locale::auto_path}/${locale}-auto.inc
	
You can then copy this file to your locale path.

	${ZESK_SITE_ROOT}/etc/language/${locale}.inc
	
And edit with the new language features.
