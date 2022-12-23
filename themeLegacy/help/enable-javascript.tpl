<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage help
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Wed Jan 30 21:07:21 EST 2008
 */

/* @var $request Request */
$request = $this->request;

$is_mac = $request->user_agent_is('mac');
if ($request->user_agent_is('iphone')) {
	?><ul>
	<li>Go to the home screen</li>
	<li>Tap <strong>Settings</strong></li>
	<li>Tap <strong>Safari</strong></li>
	<li>Under <strong>Security</strong> make sure <strong>JavaScript</strong>
		is turned <strong>On</strong></li>
</ul><?php
} elseif ($request->user_agent_is('firefox')) {
	?><ul>
	<li>Under the <strong><?php echo $is_mac ? 'FireFox' : 'Tools' ?> Menu</strong>,
		choose <strong><?php echo $is_mac ? 'Preferences ...' : 'Options...' ?></strong></li>
	<li>Click the <strong>Content</strong> icon
	</li>
	<li>Ensure <strong>Enable JavaScript</strong> is checked
	</li>
</ul><?php
} elseif ($request->user_agent_is('ie')) {
	?><ul>
	<li>Under the <strong>Tools Menu</strong>, choose <strong>Internet
			Options...</strong></li>
	<li>Click the <strong>Security</strong> tab
	</li>
	<li>Click the <strong>Internet</strong> icon
	</li>
	<li>Click <strong>Custom Level...</strong></li>
	<li>Scroll down until you find <strong>Scripting</strong> with <strong>Active
			Scripting</strong> on the next line, and click <strong>Enable</strong></li>
</ul><?php
} else {
	?><ul>
	<li>Check your browser's settings to ensure JavaScript is enabled for
		this site</li>
</ul><?php
}
