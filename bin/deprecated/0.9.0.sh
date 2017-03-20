#!/bin/bash 
cannon_opts="--verbose"
zesk cannon $cannon_opts --also-match 'extends Object'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts --also-match 'extends Controller'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts --also-match 'extends Widget'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts --also-match 'extends View'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts --also-match 'extends Control'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts --dry-run 'log::send' 'zesk()->logger->info'
zesk cannon $cannon_opts 'log::' 'zesk()->logger->'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Lock' 'Lock'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'Lock' 'zesk\Lock'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\base26::' 'Base26::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'base26::' 'zesk\Base26::'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Image_Library::' 'Image_Library::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'Image_Library::' 'zesk\Image_Library::'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Model_List' 'Model_List'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'Model_List' 'zesk\Model_List'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Model_Login' 'Model_Login'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'Model_Login' 'zesk\Model_Login'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Model_Settings' 'Model_Settings'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'Model_Settings' 'zesk\Model_Settings'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Model_URL' 'Model_URL'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'Model_URL' 'zesk\Model_URL'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\ulong::' 'ulong::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'ulong::' 'zesk\ulong::'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Deploy::' 'Deploy::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'Deploy::' 'zesk\Deploy::'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\debug::' 'Debug::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'debug::' 'zesk\Debug::'
zesk cannon $cannon_opts 'debug::' 'Debug::'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\FIFO::' 'FIFO::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'FIFO::' 'zesk\FIFO::'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\Module_JSLib' 'Module_JSLib'
zesk cannon $cannon_opts --skip-when-matches 'zesk\Module_JSLib'  'Module_JSLib' 'zesk\Module_JSLib'

zesk cannon $cannon_opts --also-match 'namespace zesk'  'extends \\Module' 'extends Module'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'extends Module' 'extends zesk\Module'
zesk cannon $cannon_opts --skip-when-matches 'namespace '  'extends Module' 'extends \zesk\Module'
zesk cannon $cannon_opts --also-match 'namespace '  'extends \Module' 'extends \zesk\Module'
zesk cannon $cannon_opts --also-match 'namespace '  'extends Module' 'extends \zesk\Module'

zesk cannon $cannon_opts --also-match 'namespace zesk'  '\system::' 'System::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  '\system::' 'zesk\System::'
zesk cannon $cannon_opts --skip-when-matches 'namespace zesk'  'System::' 'zesk\System::'
zesk cannon $cannon_opts 'system::'  'System::'

zesk cannon 'extends Controller_Template_Login' 'extends \zesk\Controller_Template_Login'
for w in Control_Select Control_List Control_Hidden; do
	zesk cannon "extends \\$w" "extends \\zesk\\$w"
	zesk cannon "extends $w" "extends \\zesk\\$w"
done
zesk cannon 'extends Control_List' 'extends \zesk\Control_List'
zesk cannon 'extends \Control_List' 'extends \zesk\Control_List'

zesk cannon 'extends Control_Hidden' 'extends \zesk\Control_Hidden'
zesk cannon 'extends \Control_Hidden' 'extends \zesk\Control_Hidden'


zesk cannon 'extends Control_Edit' 'extends \zesk\Control_Edit'
zesk cannon 'extends \Control_Edit' 'extends \zesk\Control_Edit'

zesk cannon 'extends Controller_Object' 'extends \zesk\Controller_Object'
zesk cannon 'extends Controller_Template_Login' 'extends \zesk\Controller_Template_Login'

zesk cannon 'Deploy' 'zesk\Deploy'

echo "NOT CHANGED, please change manually: "
php-find.sh DirectoryIterator
php-find.sh SplFileInfo


