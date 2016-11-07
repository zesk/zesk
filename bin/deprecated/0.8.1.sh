#!/bin/bash 
zesk cannon --verbose Response_HTML 'zesk\Response_Text_HTML'
zesk cannon --skip-when-matches 'use zesk\Request' --verbose '(Request ' '(zesk\Request '
zesk cannon --skip-when-matches 'use zesk\Router' --verbose '(Router ' '(zesk\Router '
zesk cannon --skip-when-matches 'use zesk\Route;' --skip-when-matches 'use zesk\Route ' --verbose '(Route ' '(zesk\Route '
zesk cannon --verbose ' Route $' ' zesk\Route $'
zesk cannon --verbose 'zesk\dir::' 'zesk\Directory::'
zesk cannon --verbose 'dir::' 'Directory::'
zesk cannon --verbose 'Database::' 'zesk\Database::'
zesk cannon --verbose 'Database_Query' 'zesk\Database_Query'
zesk cannon --verbose 'Database_MySQL' 'mysql\Database'
zesk cannon --verbose 'zesk\zesk' 'zesk'
zesk cannon --skip-when-matches 'namespace zesk' --verbose ' \dir::' ' zesk\Directory::'
zesk cannon --verbose 'Class_Zesk_User' 'zesk\Class_User'
zesk cannon --verbose 'Class_Zesk_User_Role' 'zesk\Class_User_Role'
zesk cannon --skip-when-matches 'namespace zesk' --verbose 'Zesk_User_Role' 'zesk\User_Role'
zesk cannon --skip-when-matches 'namespace zesk' --verbose 'Zesk_User' 'zesk\User'
zesk cannon --verbose 'tion->module->' 'tion->modules->'
zesk cannon --verbose 'app->module->' 'app->modules->'
zesk cannon --verbose '>hook_array(' '>call_hook_arguments('
zesk cannon --verbose '>hook(' '>call_hook('
zesk cannon --verbose url:: URL::
zesk cannon --verbose js:: JavaScript::
zesk cannon --verbose json:: JSON::
zesk cannon --verbose php:: PHP::
zesk cannon --verbose text:: Text::
zesk cannon --verbose html:: HTML::
zesk cannon --verbose css:: CSS::
zesk cannon --verbose mime:: MIME::
zesk cannon --verbose '>process->id' '>process->id()'
zesk cannon --verbose '>process->id()()' '>process->id()'
zesk cannon --verbose 'use zesk\text' 'use zesk\Text'
zesk cannon --verbose 'arr::path(' 'apath('
zesk cannon --verbose 'use zesk\file as file' 'use zesk\File'
zesk cannon --verbose 'use zesk\file;' 'use zesk\File;'
zesk cannon --verbose file:: File::
zesk cannon --verbose 'lang::' 'Locale::'
zesk cannon --verbose 'zesk::deprecated()' 'zesk()->deprecated()'
zesk cannon --skip-when-matches 'namespace zesk' --verbose 'Module_Interface_Foot' 'zesk\Interface_Module_Foot'
zesk cannon --skip-when-matches 'namespace zesk' --verbose 'Module_Interface_Routes' 'zesk\Interface_Module_Routes'
zesk cannon --skip-when-matches 'namespace zesk' --verbose 'Module_Interface_Head' 'zesk\Interface_Module_Head'
zesk cannon --verbose 'Module_Interface_Foot' '\zesk\Interface_Module_Foot'
zesk cannon --verbose 'Module_Interface_Routes' '\zesk\Interface_Module_Routes'
zesk cannon --verbose 'Module_Interface_Head' '\zesk\Interface_Module_Head'
zesk cannon --verbose 'use zesk\Locale as Locale' 'use zesk\Locale'
zesk cannon --verbose 'use zesk\Timestamp as Timestamp' 'use zesk\Timestamp'
zesk cannon --skip-when-matches 'use zesk\Module' --verbose ' extends Module ' ' extends zesk\Module '
zesk cannon --skip-when-matches 'namespace zesk' --verbose 'Template ' 'zesk\Template '
zesk cannon --verbose 'Controller_zesk\Template' 'Controller_Template'
zesk cannon 'Class_Zesk_Role' 'zesk\Class_Role'
zesk cannon 'Controller_zesk\Template' 'Controller_Template'

zesk cannon --skip-when-matches 'namespace zesk' --verbose 'extends Class_Object' 'extends zesk\Class_Object'
zesk cannon --also-match 'namespace zesk' --verbose 'extends \Class_Object' 'extends Class_Object'

echo "Fix issues with the following zesk::sort_weight_array"
php-find.sh ::sort_weight_array
