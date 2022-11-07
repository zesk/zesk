<?php declare(strict_types=1);
/**
 * @copyright Copyright 2006 ThoughtWorks, Inc
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * -----------------
 * This file has been automatically generated via XSL
 * -----------------
 *
 *
 *
 * @category   Testing
 * @package    Selenium
 * @author     Shin Ohno <ganchiku at gmail dot com>
 * @author     Bjoern Schotte <schotte at mayflower dot de>
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License, Version 2.0
 * @see        http://www.openqa.org/selenium-rc/
 * @since      0.1
 */
namespace zesk;

/**
 * Selenium exception class
 */

/**
 * Defines an object that runs Selenium commands.
 *
 *
 * <p>
 * <b>Element Locators</b>
 * </p><p>
 *
 * Element Locators tell Selenium which HTML element a command refers to.
 * The format of a locator is:
 * </p><p>
 *
 * <i>locatorType</i><b>=</b><i>argument</i>
 * </p>
 * <p>
 *
 * We support the following strategies for locating elements:
 *
 * </p>
 * <ul>
 *
 * <li>
 * <b>identifier</b>=<i>id</i>:
 * Select the element with the specified @id attribute. If no match is
 * found, select the first element whose @name attribute is <i>id</i>.
 * (This is normally the default; see below.)
 * </li>
 * <li>
 * <b>id</b>=<i>id</i>:
 * Select the element with the specified @id attribute.
 * </li>
 * <li>
 * <b>name</b>=<i>name</i>:
 * Select the first element with the specified @name attribute.
 *
 * <ul>
 *
 * <li>
 * username
 * </li>
 * <li>
 * name=username
 * </li>
 * </ul>
 * <p>
 * The name may optionally be followed by one or more <i>element-filters</i>, separated from the name by whitespace.  If the <i>filterType</i> is not specified, <b>value</b> is assumed.
 * </p>
 * <ul>
 *
 * <li>
 * name=flavour value=chocolate
 * </li>
 * </ul>
 * </li>
 * <li>
 * <b>dom</b>=<i>javascriptExpression</i>:
 *
 * Find an element by evaluating the specified string.  This allows you to traverse the HTML Document Object
 * Model using JavaScript.  Note that you must not return a value in this string; simply make it the last expression in the block.
 *
 * <ul>
 *
 * <li>
 * dom=document.forms['myForm'].myDropdown
 * </li>
 * <li>
 * dom=document.images[56]
 * </li>
 * <li>
 * dom=function foo() { return document.links[1]; }; foo();
 * </li>
 * </ul>
 * </li>
 * <li>
 * <b>xpath</b>=<i>xpathExpression</i>:
 * Locate an element using an XPath expression.
 *
 * <ul>
 *
 * <li>
 * xpath=//img[@alt='The image alt text']
 * </li>
 * <li>
 * xpath=//table[@id='table1']//tr[4]/td[2]
 * </li>
 * <li>
 * xpath=//a[contains(@href,'#id1')]
 * </li>
 * <li>
 * xpath=//a[contains(@href,'#id1')]/@class
 * </li>
 * <li>
 * xpath=(//table[@class='stylee'])//th[text()='theHeaderText']/../td
 * </li>
 * <li>
 * xpath=//input[@name='name2' and @value='yes']
 * </li>
 * <li>
 * xpath=//*[text()="right"]
 * </li>
 * </ul>
 * </li>
 * <li>
 * <b>link</b>=<i>textPattern</i>:
 * Select the link (anchor) element which contains text matching the
 * specified <i>pattern</i>.
 *
 * <ul>
 *
 * <li>
 * link=The link text
 * </li>
 * </ul>
 * </li>
 * <li>
 * <b>css</b>=<i>cssSelectorSyntax</i>:
 * Select the element using css selectors. Please refer to CSS2 selectors, CSS3 selectors for more information. You can also check the TestCssLocators test in the selenium test suite for an example of usage, which is included in the downloaded selenium core package.
 *
 * <ul>
 *
 * <li>
 * css=a[href="#id3"]
 * </li>
 * <li>
 * css=span#firstChild + span
 * </li>
 * </ul>
 * <p>
 * Currently the css selector locator supports all css1, css2 and css3 selectors except namespace in css3, some pseudo classes(:nth-of-type, :nth-last-of-type, :first-of-type, :last-of-type, :only-of-type, :visited, :hover, :active, :focus, :indeterminate) and pseudo elements(::first-line, ::first-letter, ::selection, ::before, ::after).
 * </p>
 * </li>
 * </ul><p>
 *
 * Without an explicit locator prefix, Selenium uses the following default
 * strategies:
 *
 * </p>
 * <ul>
 *
 * <li>
 * <b>dom</b>, for locators starting with "document."
 * </li>
 * <li>
 * <b>xpath</b>, for locators starting with "//"
 * </li>
 * <li>
 * <b>identifier</b>, otherwise
 * </li>
 * </ul>
 * <p>
 * <b>Element Filters</b>
 * </p><p>
 *
 * <p>
 * Element filters can be used with a locator to refine a list of candidate elements.  They are currently used only in the 'name' element-locator.
 * </p>
 * <p>
 * Filters look much like locators, ie.
 * </p>
 * <p>
 *
 * <i>filterType</i><b>=</b><i>argument</i>
 * </p>
 * <p>
 * Supported element-filters are:
 * </p>
 * <p>
 * <b>value=</b><i>valuePattern</i>
 * </p>
 * <p>
 *
 *
 * Matches elements based on their values.  This is particularly useful for refining a list of similarly-named toggle-buttons.
 * </p>
 * <p>
 * <b>index=</b><i>index</i>
 * </p>
 * <p>
 *
 *
 * Selects a single element based on its position in the list (offset from zero).
 * </p>
 *
 * </p>
 *
 * <p>
 * <b>String-match Patterns</b>
 * </p><p>
 *
 * Various Pattern syntaxes are available for matching string values:
 *
 * </p>
 * <ul>
 *
 * <li>
 * <b>glob:</b><i>pattern</i>:
 * Match a string against a "glob" (aka "wildmat") pattern. "Glob" is a
 * kind of limited regular-expression syntax typically used in command-line
 * shells. In a glob pattern, "*" represents any sequence of characters, and "?"
 * represents any single character. Glob patterns match against the entire
 * string.
 * </li>
 * <li>
 * <b>regexp:</b><i>regexp</i>:
 * Match a string using a regular-expression. The full power of JavaScript
 * regular-expressions is available.
 * </li>
 * <li>
 * <b>exact:</b><i>string</i>:
 *
 * Match a string exactly, verbatim, without any of that fancy wildcard
 * stuff.
 * </li>
 * </ul><p>
 *
 * If no pattern prefix is specified, Selenium assumes that it's a "glob"
 * pattern.
 *
 * </p>
 *
 * @package Selenium
 * @author Shin Ohno <ganchiku at gmail dot com>
 * @author Bjoern Schotte <schotte at mayflower dot de>
 */
class Test_Selenium_Legacy extends UnitTest {
	/**
	 *
	 * @var string
	 * @access private
	 */
	protected $browser;

	/**
	 *
	 * @var string
	 * @access private
	 */
	protected $browserUrl;

	/**
	 *
	 * @var string
	 * @access private
	 */
	protected $host;

	/**
	 *
	 * @var int
	 * @access private
	 */
	protected $port;

	/**
	 *
	 * @var string
	 * @access private
	 */
	protected $sessionId;

	/**
	 *
	 * @var string
	 * @access private
	 */
	protected $timeout;

	/**
	 * Most recent page title
	 * @var string
	 */
	protected $page_title;

	/**
	 * Most recent page body
	 * @var string
	 */
	protected $page_body;

	/**
	 * Most recent page text
	 * @var array Keys are locators
	 */
	protected $page_text;

	/**
	 * Most recent page html
	 * @var string
	 */
	protected $page_html;

	/**
	 * Constructor
	 * @param string $browser
	 * @param string $browserUrl
	 * @param string $host
	 * @param int $port
	 * @param int $timeout
	 * @access public
	 * @throws Exception_Selenium
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$browser = '*chrome';
		$url = 'localhost';
		$host = 'localhost';
		$port = 4444;
		$timeout = 30000;
		extract($this->options, EXTR_IF_EXISTS);
		$this->browser = $browser;
		$this->browserUrl = $url;
		if (empty($host)) {
			throw new Exception_Configuration(__CLASS__ . '::host', 'No host found');
		}
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		$this->page_text = [];
	}

	public function setBrowser($set = null) {
		$old_browser = $this->browser;
		$this->browser = $set;
		return $old_browser;
	}

	public function setBrowserIE() {
		return $this->setBrowser('*iehta');
	}

	public function setBrowserFirefox() {
		return $this->setBrowser('*firefox');
	}

	public function setBrowserChrome() {
		return $this->setBrowser('*chrome');
	}

	/**
	 * Run the browser and set session id.
	 * @access public
	 * @return void
	 */
	public function start() {
		$this->sessionId = $this->getString('getNewBrowserSession', [
			$this->browser,
			$this->browserUrl,
		]);
		return $this->sessionId;
	}

	/**
	 * Close the browser and set session id null
	 * @access public
	 * @return void
	 */
	public function stop(): void {
		$this->doCommand('testComplete');
		$this->sessionId = null;
	}

	public function restart(): void {
		$this->stop();
		$this->start();
	}

	/**
	 * Clicks on a link, button, checkbox or radio button.
	 * If the click action
	 * causes a new page to load (like a link usually does), call
	 * waitForPageToLoad.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function click($locator): void {
		$this->doCommand('click', [
			$locator,
		]);
	}

	/**
	 * Double clicks on a link, button, checkbox or radio button.
	 * If the double click action
	 * causes a new page to load (like a link usually does), call
	 * waitForPageToLoad.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function doubleClick($locator): void {
		$this->doCommand('doubleClick', [
			$locator,
		]);
	}

	/**
	 * Clicks on a link, button, checkbox or radio button.
	 * If the click action
	 * causes a new page to load (like a link usually does), call
	 * waitForPageToLoad.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $coordString specifies the x,y position (i.e. - 10,20) of the mouse event relative to the element
	 *        	returned by the locator.
	 */
	public function clickAt($locator, $coordString): void {
		$this->doCommand('clickAt', [
			$locator,
			$coordString,
		]);
	}

	/**
	 * Doubleclicks on a link, button, checkbox or radio button.
	 * If the action
	 * causes a new page to load (like a link usually does), call
	 * waitForPageToLoad.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $coordString specifies the x,y position (i.e. - 10,20) of the mouse event relative to the element
	 *        	returned by the locator.
	 */
	public function doubleClickAt($locator, $coordString): void {
		$this->doCommand('doubleClickAt', [
			$locator,
			$coordString,
		]);
	}

	/**
	 * Explicitly simulate an event, to trigger the corresponding "on<i>event</i>"
	 * handler.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $eventName the event name, e.g. "focus" or "blur"
	 */
	public function fireEvent($locator, $eventName): void {
		$this->doCommand('fireEvent', [
			$locator,
			$eventName,
		]);
	}

	/**
	 * Simulates a user pressing and releasing a key.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $keySequence Either be a string("\" followed by the numeric keycode of the key to be pressed,
	 *        	normally the ASCII value of that key), or a single character. For example: "w", "\119".
	 */
	public function keyPress($locator, $keySequence): void {
		$this->doCommand('keyPress', [
			$locator,
			$keySequence,
		]);
	}

	/**
	 * Press the shift key and hold it down until doShiftUp() is called or a new page is loaded.
	 * @access public
	 */
	public function shiftKeyDown(): void {
		$this->doCommand('shiftKeyDown', []);
	}

	/**
	 * Release the shift key.
	 * @access public
	 */
	public function shiftKeyUp(): void {
		$this->doCommand('shiftKeyUp', []);
	}

	/**
	 * Press the meta key and hold it down until doMetaUp() is called or a new page is loaded.
	 * @access public
	 */
	public function metaKeyDown(): void {
		$this->doCommand('metaKeyDown', []);
	}

	/**
	 * Release the meta key.
	 * @access public
	 */
	public function metaKeyUp(): void {
		$this->doCommand('metaKeyUp', []);
	}

	/**
	 * Press the alt key and hold it down until doAltUp() is called or a new page is loaded.
	 * @access public
	 */
	public function altKeyDown(): void {
		$this->doCommand('altKeyDown', []);
	}

	/**
	 * Release the alt key.
	 * @access public
	 */
	public function altKeyUp(): void {
		$this->doCommand('altKeyUp', []);
	}

	/**
	 * Press the control key and hold it down until doControlUp() is called or a new page is loaded.
	 * @access public
	 */
	public function controlKeyDown(): void {
		$this->doCommand('controlKeyDown', []);
	}

	/**
	 * Release the control key.
	 * @access public
	 */
	public function controlKeyUp(): void {
		$this->doCommand('controlKeyUp', []);
	}

	/**
	 * Simulates a user pressing a key (without releasing it yet).
	 * @access public
	 * @param string $locator an element locator
	 * @param string $keySequence Either be a string("\" followed by the numeric keycode of the key to be pressed,
	 *        	normally the ASCII value of that key), or a single character. For example: "w", "\119".
	 */
	public function keyDown($locator, $keySequence): void {
		$this->doCommand('keyDown', [
			$locator,
			$keySequence,
		]);
	}

	/**
	 * Simulates a user releasing a key.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $keySequence Either be a string("\" followed by the numeric keycode of the key to be pressed,
	 *        	normally the ASCII value of that key), or a single character. For example: "w", "\119".
	 */
	public function keyUp($locator, $keySequence): void {
		$this->doCommand('keyUp', [
			$locator,
			$keySequence,
		]);
	}

	/**
	 * Simulates a user hovering a mouse over the specified element.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function mouseOver($locator): void {
		$this->doCommand('mouseOver', [
			$locator,
		]);
	}

	/**
	 * Simulates a user moving the mouse pointer away from the specified element.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function mouseOut($locator): void {
		$this->doCommand('mouseOut', [
			$locator,
		]);
	}

	/**
	 * Simulates a user pressing the mouse button (without releasing it yet) on
	 * the specified element.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function mouseDown($locator): void {
		$this->doCommand('mouseDown', [
			$locator,
		]);
	}

	/**
	 * Simulates a user pressing the mouse button (without releasing it yet) at
	 * the specified location.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $coordString specifies the x,y position (i.e. - 10,20) of the mouse event relative to the element
	 *        	returned by the locator.
	 */
	public function mouseDownAt($locator, $coordString): void {
		$this->doCommand('mouseDownAt', [
			$locator,
			$coordString,
		]);
	}

	/**
	 * Simulates the event that occurs when the user releases the mouse button (i.e., stops
	 * holding the button down) on the specified element.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function mouseUp($locator): void {
		$this->doCommand('mouseUp', [
			$locator,
		]);
	}

	/**
	 * Simulates the event that occurs when the user releases the mouse button (i.e., stops
	 * holding the button down) at the specified location.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $coordString specifies the x,y position (i.e. - 10,20) of the mouse event relative to the element
	 *        	returned by the locator.
	 */
	public function mouseUpAt($locator, $coordString): void {
		$this->doCommand('mouseUpAt', [
			$locator,
			$coordString,
		]);
	}

	/**
	 * Simulates a user pressing the mouse button (without releasing it yet) on
	 * the specified element.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function mouseMove($locator): void {
		$this->doCommand('mouseMove', [
			$locator,
		]);
	}

	/**
	 * Simulates a user pressing the mouse button (without releasing it yet) on
	 * the specified element.
	 * @access public
	 * @param string $locator an element locator
	 * @param string $coordString specifies the x,y position (i.e. - 10,20) of the mouse event relative to the element
	 *        	returned by the locator.
	 */
	public function mouseMoveAt($locator, $coordString): void {
		$this->doCommand('mouseMoveAt', [
			$locator,
			$coordString,
		]);
	}

	/**
	 * Sets the value of an input field, as though you typed it in.
	 * <p>
	 * Can also be used to set the value of combo boxes, check boxes, etc. In these cases,
	 * value should be the value of the option selected, not the visible text.
	 * </p>
	 * @access public
	 * @param string $locator an element locator
	 * @param string $value the value to type
	 */
	public function type($locator, $value): void {
		$this->doCommand('type', [
			$locator,
			$value,
		]);
	}

	/**
	 * Simulates keystroke events on the specified element, as though you typed the value key-by-key.
	 * <p>
	 * This is a convenience method for calling keyDown, keyUp, keyPress for every character in the specified string;
	 * this is useful for dynamic UI widgets (like auto-completing combo boxes) that require explicit key events.
	 * </p><p>
	 * Unlike the simple "type" command, which forces the specified value into the page directly, this command
	 * may or may not have any visible effect, even in cases where typing keys would normally have a visible effect.
	 * For example, if you use "typeKeys" on a form element, you may or may not see the results of what you typed in
	 * the field.
	 * </p><p>
	 * In some cases, you may need to use the simple "type" command to set the value of the field and then the
	 * "typeKeys" command to
	 * send the keystroke events corresponding to what you just typed.
	 * </p>
	 * @access public
	 * @param string $locator an element locator
	 * @param string $value the value to type
	 */
	public function typeKeys($locator, $value): void {
		$this->doCommand('typeKeys', [
			$locator,
			$value,
		]);
	}

	/**
	 * Set execution speed (i.e., set the millisecond length of a delay which will follow each selenium operation).
	 * By default, there is no such delay, i.e.,
	 * the delay is 0 milliseconds.
	 * @access public
	 * @param string $value the number of milliseconds to pause after operation
	 */
	public function setSpeed($value): void {
		$this->doCommand('setSpeed', [
			$value,
		]);
	}

	/**
	 * Get execution speed (i.e., get the millisecond length of the delay following each selenium operation).
	 * By default, there is no such delay, i.e.,
	 * the delay is 0 milliseconds.
	 * See also setSpeed.
	 * @access public
	 */
	public function getSpeed(): void {
		$this->doCommand('getSpeed', []);
	}

	public function checkbox($locator, $checked = true): void {
		$this->doCommand($checked ? 'check' : 'uncheck', [
			$locator,
		]);
	}

	/**
	 * Check a toggle-button (checkbox/radio)
	 * @access public
	 * @param string $locator an element locator
	 */
	public function check($locator): void {
		$this->doCommand('check', [
			$locator,
		]);
	}

	/**
	 * Uncheck a toggle-button (checkbox/radio)
	 * @access public
	 * @param string $locator an element locator
	 */
	public function uncheck($locator): void {
		$this->doCommand('uncheck', [
			$locator,
		]);
	}

	/**
	 * Select an option from a drop-down using an option locator.
	 * <p>
	 * Option locators provide different ways of specifying options of an HTML
	 * Select element (e.g. for selecting a specific option, or for asserting
	 * that the selected option satisfies a specification). There are several
	 * forms of Select Option Locator.
	 * </p>
	 * <ul>
	 * <li>
	 * <b>label</b>=<i>labelPattern</i>:
	 * matches options based on their labels, i.e. the visible text. (This
	 * is the default.)
	 * <ul>
	 * <li>
	 * label=regexp:^[Oo]ther
	 * </li>
	 * </ul>
	 * </li>
	 * <li>
	 * <b>value</b>=<i>valuePattern</i>:
	 * matches options based on their values.
	 * <ul>
	 * <li>
	 * value=other
	 * </li>
	 * </ul>
	 * </li>
	 * <li>
	 * <b>id</b>=<i>id</i>:
	 * matches options based on their ids.
	 * <ul>
	 * <li>
	 * id=option1
	 * </li>
	 * </ul>
	 * </li>
	 * <li>
	 * <b>index</b>=<i>index</i>:
	 * matches an option based on its index (offset from zero).
	 * <ul>
	 * <li>
	 * index=2
	 * </li>
	 * </ul>
	 * </li>
	 * </ul><p>
	 * If no option locator prefix is provided, the default behaviour is to match on <b>label</b>.
	 * </p>
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @param string $optionLocator an option locator (a label by default)
	 */
	public function select($selectLocator, $optionLocator): void {
		$this->doCommand('select', [
			$selectLocator,
			$optionLocator,
		]);
	}

	/**
	 * Add a selection to the set of selected options in a multi-select element using an option locator.
	 * @see #doSelect for details of option locators
	 * @access public
	 * @param string $locator an element locator identifying a multi-select box
	 * @param string $optionLocator an option locator (a label by default)
	 */
	public function addSelection($locator, $optionLocator): void {
		$this->doCommand('addSelection', [
			$locator,
			$optionLocator,
		]);
	}

	/**
	 * Remove a selection from the set of selected options in a multi-select element using an option locator.
	 * @see #doSelect for details of option locators
	 * @access public
	 * @param string $locator an element locator identifying a multi-select box
	 * @param string $optionLocator an option locator (a label by default)
	 */
	public function removeSelection($locator, $optionLocator): void {
		$this->doCommand('removeSelection', [
			$locator,
			$optionLocator,
		]);
	}

	/**
	 * Unselects all of the selected options in a multi-select element.
	 * @access public
	 * @param string $locator an element locator identifying a multi-select box
	 */
	public function removeAllSelections($locator): void {
		$this->doCommand('removeAllSelections', [
			$locator,
		]);
	}

	/**
	 * Submit the specified form.
	 * This is particularly useful for forms without
	 * submit buttons, e.g. single-input "Search" forms.
	 * @access public
	 * @param string $formLocator an element locator for the form you want to submit
	 */
	public function submit($formLocator): void {
		$this->doCommand('submit', [
			$formLocator,
		]);
	}

	/**
	 * Opens an URL in the test frame.
	 * This accepts both relative and absolute
	 * URLs.
	 * The "open" command waits for the page to load before proceeding,
	 * ie. the "AndWait" suffix is implicit.
	 * <i>Note</i>: The URL must be on the same domain as the runner HTML
	 * due to security restrictions in the browser (Same Origin Policy). If you
	 * need to open an URL on another domain, use the Selenium Server to start a
	 * new browser session on that domain.
	 * @access public
	 * @param string $url the URL to open; may be relative or absolute
	 */
	public function open($url): void {
		$this->doCommand('open', [
			$url,
		]);
	}

	/**
	 * Opens a popup window (if a window with that ID isn't already open).
	 * After opening the window, you'll need to select it using the selectWindow
	 * command.
	 * <p>
	 * This command can also be a useful workaround for bug SEL-339. In some cases, Selenium will be unable to intercept
	 * a call to window.open (if the call occurs during or before the "onLoad" event, for example).
	 * In those cases, you can force Selenium to notice the open window's name by using the Selenium openWindow command,
	 * using
	 * an empty (blank) url, like this: openWindow("", "myFunnyWindow").
	 * </p>
	 * @access public
	 * @param string $url the URL to open, which can be blank
	 * @param string $windowID the JavaScript window ID of the window to select
	 */
	public function openWindow($url, $windowID): void {
		$this->doCommand('openWindow', [
			$url,
			$windowID,
		]);
	}

	/**
	 * Selects a popup window; once a popup window has been selected, all
	 * commands go to that window.
	 * To select the main window again, use null
	 * as the target.
	 * <p>
	 * Note that there is a big difference between a window's internal JavaScript "name" property
	 * and the "title" of a given window's document (which is normally what you actually see, as an end user,
	 * in the title bar of the window). The "name" is normally invisible to the end-user; it's the second
	 * parameter "windowName" passed to the JavaScript method window.open(url, windowName, windowFeatures, replaceFlag)
	 * (which selenium intercepts).
	 * </p><p>
	 * Selenium has several strategies for finding the window object referred to by the "windowID" parameter.
	 * </p><p>
	 * 1.) if windowID is null, (or the string "null") then it is assumed the user is referring to the original window
	 * instantiated by the browser).
	 * </p><p>
	 * 2.) if the value of the "windowID" parameter is a JavaScript variable name in the current application window,
	 * then it is assumed
	 * that this variable contains the return value from a call to the JavaScript window.open() method.
	 * </p><p>
	 * 3.) Otherwise, selenium looks in a hash it maintains that maps string names to window "names".
	 * </p><p>
	 * 4.) If <i>that</i> fails, we'll try looping over all of the known windows to try to find the appropriate "title".
	 * Since "title" is not necessarily unique, this may have unexpected behavior.
	 * </p><p>
	 * If you're having trouble figuring out what is the name of a window that you want to manipulate, look at the
	 * selenium log messages
	 * which identify the names of windows created via window.open (and therefore intercepted by selenium). You will see
	 * messages
	 * like the following for each window as it is opened:
	 * </p><p>
	 * <code>debug: window.open call intercepted; window ID (which you can use with selectWindow()) is
	 * "myNewWindow"</code>
	 * </p><p>
	 * In some cases, Selenium will be unable to intercept a call to window.open (if the call occurs during or before
	 * the "onLoad" event, for example).
	 * (This is bug SEL-339.) In those cases, you can force Selenium to notice the open window's name by using the
	 * Selenium openWindow command, using
	 * an empty (blank) url, like this: openWindow("", "myFunnyWindow").
	 * </p>
	 * @access public
	 * @param string $windowID the JavaScript window ID of the window to select
	 */
	public function selectWindow($windowID): void {
		$this->doCommand('selectWindow', [
			$windowID,
		]);
	}

	/**
	 * Selects a frame within the current window.
	 * (You may invoke this command
	 * multiple times to select nested frames.) To select the parent frame, use
	 * "relative=parent" as a locator; to select the top frame, use "relative=top".
	 * You can also select a frame by its 0-based index number; select the first frame with
	 * "index=0", or the third frame with "index=2".
	 * <p>
	 * You may also use a DOM expression to identify the frame you want directly,
	 * like this: <code>dom=frames["main"].frames["subframe"]</code>
	 * </p>
	 * @access public
	 * @param string $locator an element locator identifying a frame or iframe
	 */
	public function selectFrame($locator): void {
		$this->doCommand('selectFrame', [
			$locator,
		]);
	}

	/**
	 * Determine whether current/locator identify the frame containing this running code.
	 * <p>
	 * This is useful in proxy injection mode, where this code runs in every
	 * browser frame and window, and sometimes the selenium server needs to identify
	 * the "current" frame. In this case, when the test calls selectFrame, this
	 * routine is called for each frame to figure out which one has been selected.
	 * The selected frame will return true, while all others will return false.
	 * </p>
	 * @access public
	 * @param string $currentFrameString starting frame
	 * @param string $target new frame (which might be relative to the current one)
	 * @return boolean true if the new frame is this code's window
	 */
	public function getWhetherThisFrameMatchFrameExpression($currentFrameString, $target) {
		return $this->getBoolean('getWhetherThisFrameMatchFrameExpression', [
			$currentFrameString,
			$target,
		]);
	}

	/**
	 * Determine whether currentWindowString plus target identify the window containing this running code.
	 * <p>
	 * This is useful in proxy injection mode, where this code runs in every
	 * browser frame and window, and sometimes the selenium server needs to identify
	 * the "current" window. In this case, when the test calls selectWindow, this
	 * routine is called for each window to figure out which one has been selected.
	 * The selected window will return true, while all others will return false.
	 * </p>
	 * @access public
	 * @param string $currentWindowString starting window
	 * @param string $target new window (which might be relative to the current one, e.g., "_parent")
	 * @return boolean true if the new window is this code's window
	 */
	public function getWhetherThisWindowMatchWindowExpression($currentWindowString, $target) {
		return $this->getBoolean('getWhetherThisWindowMatchWindowExpression', [
			$currentWindowString,
			$target,
		]);
	}

	/**
	 * Waits for a popup window to appear and load up.
	 * @access public
	 * @param string $windowID the JavaScript window ID of the window that will appear
	 * @param string $timeout a timeout in milliseconds, after which the action will return with an error
	 */
	public function waitForPopUp($windowID, $timeout): void {
		$this->doCommand('waitForPopUp', [
			$windowID,
			$timeout,
		]);
	}

	/**
	 * By default, Selenium's overridden window.confirm() function will
	 * return true, as if the user had manually clicked OK; after running
	 * this command, the next call to confirm() will return false, as if
	 * the user had clicked Cancel.
	 * Selenium will then resume using the
	 * default behavior for future confirmations, automatically returning
	 * true (OK) unless/until you explicitly call this command for each
	 * confirmation.
	 * @access public
	 */
	public function chooseCancelOnNextConfirmation(): void {
		$this->doCommand('chooseCancelOnNextConfirmation', []);
	}

	/**
	 * Undo the effect of calling chooseCancelOnNextConfirmation.
	 * Note
	 * that Selenium's overridden window.confirm() function will normally automatically
	 * return true, as if the user had manually clicked OK, so you shouldn't
	 * need to use this command unless for some reason you need to change
	 * your mind prior to the next confirmation. After any confirmation, Selenium will resume using the
	 * default behavior for future confirmations, automatically returning
	 * true (OK) unless/until you explicitly call chooseCancelOnNextConfirmation for each
	 * confirmation.
	 * @access public
	 */
	public function chooseOkOnNextConfirmation(): void {
		$this->doCommand('chooseOkOnNextConfirmation', []);
	}

	/**
	 * Instructs Selenium to return the specified answer string in response to
	 * the next JavaScript prompt [window.prompt()].
	 * @access public
	 * @param string $answer the answer to give in response to the prompt pop-up
	 */
	public function answerOnNextPrompt($answer): void {
		$this->doCommand('answerOnNextPrompt', [
			$answer,
		]);
	}

	/**
	 * Simulates the user clicking the "back" button on their browser.
	 * @access public
	 */
	public function goBack(): void {
		$this->doCommand('goBack', []);
	}

	/**
	 * Simulates the user clicking the "Refresh" button on their browser.
	 * @access public
	 */
	public function refresh(): void {
		$this->doCommand('refresh', []);
	}

	/**
	 * Simulates the user clicking the "close" button in the titlebar of a popup
	 * window or tab.
	 * @access public
	 */
	public function close(): void {
		$this->doCommand('close', []);
	}

	/**
	 * Has an alert occurred?
	 * <p>
	 * This function never throws an exception
	 * </p>
	 * @access public
	 * @return boolean true if there is an alert
	 */
	public function isAlertPresent() {
		return $this->getBoolean('isAlertPresent', []);
	}

	/**
	 * Has a prompt occurred?
	 * <p>
	 * This function never throws an exception
	 * </p>
	 * @access public
	 * @return boolean true if there is a pending prompt
	 */
	public function isPromptPresent() {
		return $this->getBoolean('isPromptPresent', []);
	}

	/**
	 * Has confirm() been called?
	 * <p>
	 * This function never throws an exception
	 * </p>
	 * @access public
	 * @return boolean true if there is a pending confirmation
	 */
	public function isConfirmationPresent() {
		return $this->getBoolean('isConfirmationPresent', []);
	}

	/**
	 * Retrieves the message of a JavaScript alert generated during the previous action, or fail if there were no
	 * alerts.
	 * <p>
	 * Getting an alert has the same effect as manually clicking OK. If an
	 * alert is generated but you do not get/verify it, the next Selenium action
	 * will fail.
	 * </p><p>
	 * NOTE: under Selenium, JavaScript alerts will NOT pop up a visible alert
	 * dialog.
	 * </p><p>
	 * NOTE: Selenium does NOT support JavaScript alerts that are generated in a
	 * page's onload() event handler. In this case a visible dialog WILL be
	 * generated and Selenium will hang until someone manually clicks OK.
	 * </p>
	 * @access public
	 * @return string The message of the most recent JavaScript alert
	 */
	public function getAlert() {
		return $this->getString('getAlert', []);
	}

	/**
	 * Retrieves the message of a JavaScript confirmation dialog generated during
	 * the previous action.
	 * <p>
	 * By default, the confirm function will return true, having the same effect
	 * as manually clicking OK. This can be changed by prior execution of the
	 * chooseCancelOnNextConfirmation command. If an confirmation is generated
	 * but you do not get/verify it, the next Selenium action will fail.
	 * </p><p>
	 * NOTE: under Selenium, JavaScript confirmations will NOT pop up a visible
	 * dialog.
	 * </p><p>
	 * NOTE: Selenium does NOT support JavaScript confirmations that are
	 * generated in a page's onload() event handler. In this case a visible
	 * dialog WILL be generated and Selenium will hang until you manually click
	 * OK.
	 * </p>
	 * @access public
	 * @return string the message of the most recent JavaScript confirmation dialog
	 */
	public function getConfirmation() {
		return $this->getString('getConfirmation', []);
	}

	/**
	 * Retrieves the message of a JavaScript question prompt dialog generated during
	 * the previous action.
	 * <p>
	 * Successful handling of the prompt requires prior execution of the
	 * answerOnNextPrompt command. If a prompt is generated but you
	 * do not get/verify it, the next Selenium action will fail.
	 * </p><p>
	 * NOTE: under Selenium, JavaScript prompts will NOT pop up a visible
	 * dialog.
	 * </p><p>
	 * NOTE: Selenium does NOT support JavaScript prompts that are generated in a
	 * page's onload() event handler. In this case a visible dialog WILL be
	 * generated and Selenium will hang until someone manually clicks OK.
	 * </p>
	 * @access public
	 * @return string the message of the most recent JavaScript question prompt
	 */
	public function getPrompt() {
		return $this->getString('getPrompt', []);
	}

	/**
	 * Gets the absolute URL of the current page.
	 * @access public
	 * @return string the absolute URL of the current page
	 */
	public function getLocation() {
		return $this->getString('getLocation', []);
	}

	/**
	 * Gets the title of the current page.
	 * @access public
	 * @return string the title of the current page
	 */
	public function getTitle() {
		return $this->getString('getTitle', []);
	}

	/**
	 * Gets the entire text of the page.
	 * @access public
	 * @return string the entire text of the page
	 */
	public function getBodyText($force = false) {
		if ($this->page_body === null || $force) {
			$this->page_body = $this->getString('getBodyText', []);
		}
		return $this->page_body;
	}

	/**
	 * Gets the (whitespace-trimmed) value of an input field (or anything else with a value parameter).
	 * For checkbox/radio elements, the value will be "on" or "off" depending on
	 * whether the element is checked or not.
	 * @access public
	 * @param string $locator an element locator
	 * @return string the element value, or "on/off" for checkbox/radio elements
	 */
	public function getValue($locator) {
		return $this->getString('getValue', [
			$locator,
		]);
	}

	/**
	 * Gets the text of an element.
	 * This works for any element that contains
	 * text. This command uses either the textContent (Mozilla-like browsers) or
	 * the innerText (IE-like browsers) of the element, which is the rendered
	 * text shown to the user.
	 * @access public
	 * @param string $locator an element locator
	 * @return string the text of the element
	 */
	public function getText($locator) {
		return $this->getString('getText', [
			$locator,
		]);
	}

	/**
	 * Briefly changes the backgroundColor of the specified element yellow.
	 * Useful for debugging.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function highlight($locator): void {
		$this->doCommand('highlight', [
			$locator,
		]);
	}

	/**
	 * Gets the result of evaluating the specified JavaScript snippet.
	 * The snippet may
	 * have multiple lines, but only the result of the last line will be returned.
	 * <p>
	 * Note that, by default, the snippet will run in the context of the "selenium"
	 * object itself, so <code>this</code> will refer to the Selenium object. Use <code>window</code> to
	 * refer to the window of your application, e.g. <code>window.document.getElementById('foo')</code>
	 * </p><p>
	 * If you need to use
	 * a locator to refer to a single element in your application page, you can
	 * use <code>this.browserbot.findElement("id=foo")</code> where "id=foo" is your locator.
	 * </p>
	 * @access public
	 * @param string $script the JavaScript snippet to run
	 * @return string the results of evaluating the snippet
	 */
	public function getEval($script) {
		return $this->getString('getEval', [
			$script,
		]);
	}

	/**
	 * Gets whether a toggle-button (checkbox/radio) is checked.
	 * Fails if the specified element doesn't exist or isn't a toggle-button.
	 * @access public
	 * @param string $locator an element locator pointing to a checkbox or radio button
	 * @return boolean true if the checkbox is checked, false otherwise
	 */
	public function isChecked($locator) {
		return $this->getBoolean('isChecked', [
			$locator,
		]);
	}

	/**
	 * Gets the text from a cell of a table.
	 * The cellAddress syntax
	 * tableLocator.row.column, where row and column start at 0.
	 * @access public
	 * @param string $tableCellAddress a cell address, e.g. "foo.1.4"
	 * @return string the text from the specified cell
	 */
	public function getTable($tableCellAddress) {
		return $this->getString('getTable', [
			$tableCellAddress,
		]);
	}

	/**
	 * Gets all option labels (visible text) for selected options in the specified select or multi-select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return array an array of all selected option labels in the specified select drop-down
	 */
	public function getSelectedLabels($selectLocator) {
		return $this->getStringArray('getSelectedLabels', [
			$selectLocator,
		]);
	}

	/**
	 * Gets option label (visible text) for selected option in the specified select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return string the selected option label in the specified select drop-down
	 */
	public function getSelectedLabel($selectLocator) {
		return $this->getString('getSelectedLabel', [
			$selectLocator,
		]);
	}

	/**
	 * Gets all option values (value attributes) for selected options in the specified select or multi-select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return array an array of all selected option values in the specified select drop-down
	 */
	public function getSelectedValues($selectLocator) {
		return $this->getStringArray('getSelectedValues', [
			$selectLocator,
		]);
	}

	/**
	 * Gets option value (value attribute) for selected option in the specified select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return string the selected option value in the specified select drop-down
	 */
	public function getSelectedValue($selectLocator) {
		return $this->getString('getSelectedValue', [
			$selectLocator,
		]);
	}

	/**
	 * Gets all option indexes (option number, starting at 0) for selected options in the specified select or
	 * multi-select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return array an array of all selected option indexes in the specified select drop-down
	 */
	public function getSelectedIndexes($selectLocator) {
		return $this->getStringArray('getSelectedIndexes', [
			$selectLocator,
		]);
	}

	/**
	 * Gets option index (option number, starting at 0) for selected option in the specified select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return string the selected option index in the specified select drop-down
	 */
	public function getSelectedIndex($selectLocator) {
		return $this->getString('getSelectedIndex', [
			$selectLocator,
		]);
	}

	/**
	 * Gets all option element IDs for selected options in the specified select or multi-select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return array an array of all selected option IDs in the specified select drop-down
	 */
	public function getSelectedIds($selectLocator) {
		return $this->getStringArray('getSelectedIds', [
			$selectLocator,
		]);
	}

	/**
	 * Gets option element ID for selected option in the specified select element.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return string the selected option ID in the specified select drop-down
	 */
	public function getSelectedId($selectLocator) {
		return $this->getString('getSelectedId', [
			$selectLocator,
		]);
	}

	/**
	 * Determines whether some option in a drop-down menu is selected.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return boolean true if some option has been selected, false otherwise
	 */
	public function isSomethingSelected($selectLocator) {
		return $this->getBoolean('isSomethingSelected', [
			$selectLocator,
		]);
	}

	/**
	 * Gets all option labels in the specified select drop-down.
	 * @access public
	 * @param string $selectLocator an element locator identifying a drop-down menu
	 * @return array an array of all option labels in the specified select drop-down
	 */
	public function getSelectOptions($selectLocator) {
		return $this->getStringArray('getSelectOptions', [
			$selectLocator,
		]);
	}

	/**
	 * Gets the value of an element attribute.
	 * @access public
	 * @param string $attributeLocator an element locator followed by an @ sign and then the name of the attribute, e.g.
	 *        	"foo@bar"
	 * @return string the value of the specified attribute
	 */
	public function getAttribute($attributeLocator) {
		return $this->getString('getAttribute', [
			$attributeLocator,
		]);
	}

	/**
	 * Verifies that the specified text pattern appears somewhere on the rendered page shown to the user.
	 * @access public
	 * @param string $pattern a pattern to match with the text of the page
	 * @return boolean true if the pattern matches the text, false otherwise
	 */
	public function isTextPresent($pattern) {
		return $this->getBoolean('isTextPresent', [
			$pattern,
		]);
	}

	/**
	 * Verifies that the specified element is somewhere on the page.
	 * @access public
	 * @param string $locator an element locator
	 * @return boolean true if the element is present, false otherwise
	 */
	public function isElementPresent($locator) {
		return $this->getBoolean('isElementPresent', [
			$locator,
		]);
	}

	/**
	 * Determines if the specified element is visible.
	 * An
	 * element can be rendered invisible by setting the CSS "visibility"
	 * property to "hidden", or the "display" property to "none", either for the
	 * element itself or one if its ancestors. This method will fail if
	 * the element is not present.
	 * @access public
	 * @param string $locator an element locator
	 * @return boolean true if the specified element is visible, false otherwise
	 */
	public function is_visible($locator) {
		return $this->getBoolean('isVisible', [
			$locator,
		]);
	}

	/**
	 * Determines whether the specified input element is editable, ie hasn't been disabled.
	 * This method will fail if the specified element isn't an input element.
	 * @access public
	 * @param string $locator an element locator
	 * @return boolean true if the input element is editable, false otherwise
	 */
	public function isEditable($locator) {
		return $this->getBoolean('isEditable', [
			$locator,
		]);
	}

	/**
	 * Returns the IDs of all buttons on the page.
	 * <p>
	 * If a given button has no ID, it will appear as "" in this array.
	 * </p>
	 * @access public
	 * @return array the IDs of all buttons on the page
	 */
	public function getAllButtons() {
		return $this->getStringArray('getAllButtons', []);
	}

	/**
	 * Returns the IDs of all links on the page.
	 * <p>
	 * If a given link has no ID, it will appear as "" in this array.
	 * </p>
	 * @access public
	 * @return array the IDs of all links on the page
	 */
	public function getAllLinks() {
		return $this->getStringArray('getAllLinks', []);
	}

	/**
	 * Returns the IDs of all input fields on the page.
	 * <p>
	 * If a given field has no ID, it will appear as "" in this array.
	 * </p>
	 * @access public
	 * @return array the IDs of all field on the page
	 */
	public function getAllFields() {
		return $this->getStringArray('getAllFields', []);
	}

	/**
	 * Returns every instance of some attribute from all known windows.
	 * @access public
	 * @param string $attributeName name of an attribute on the windows
	 * @return array the set of values of this attribute from all known windows.
	 */
	public function getAttributeFromAllWindows($attributeName) {
		return $this->getStringArray('getAttributeFromAllWindows', [
			$attributeName,
		]);
	}

	/**
	 * deprecated - use dragAndDrop instead
	 * @access public
	 * @param string $locator an element locator
	 * @param string $movementsString offset in pixels from the current location to which the element should be moved,
	 *        	e.g., "+70,-300"
	 */
	public function dragdrop($locator, $movementsString): void {
		$this->doCommand('dragdrop', [
			$locator,
			$movementsString,
		]);
	}

	/**
	 * Configure the number of pixels between "mousemove" events during dragAndDrop commands (default=10).
	 * <p>
	 * Setting this value to 0 means that we'll send a "mousemove" event to every single pixel
	 * in between the start location and the end location; that can be very slow, and may
	 * cause some browsers to force the JavaScript to timeout.
	 * </p><p>
	 * If the mouse speed is greater than the distance between the two dragged objects, we'll
	 * just send one "mousemove" at the start location and then one final one at the end location.
	 * </p>
	 * @access public
	 * @param string $pixels the number of pixels between "mousemove" events
	 */
	public function setMouseSpeed($pixels): void {
		$this->doCommand('setMouseSpeed', [
			$pixels,
		]);
	}

	/**
	 * Returns the number of pixels between "mousemove" events during dragAndDrop commands (default=10).
	 * @access public
	 * @return number the number of pixels between "mousemove" events during dragAndDrop commands (default=10)
	 */
	public function getMouseSpeed() {
		return $this->getNumber('getMouseSpeed', []);
	}

	/**
	 * Drags an element a certain distance and then drops it
	 * @access public
	 * @param string $locator an element locator
	 * @param string $movementsString offset in pixels from the current location to which the element should be moved,
	 *        	e.g., "+70,-300"
	 */
	public function dragAndDrop($locator, $movementsString): void {
		$this->doCommand('dragAndDrop', [
			$locator,
			$movementsString,
		]);
	}

	/**
	 * Drags an element and drops it on another element
	 * @access public
	 * @param string $locatorOfObjectToBeDragged an element to be dragged
	 * @param string $locatorOfDragDestinationObject an element whose location (i.e., whose center-most pixel) will be
	 *        	the point where locatorOfObjectToBeDragged is dropped
	 */
	public function dragAndDropToObject($locatorOfObjectToBeDragged, $locatorOfDragDestinationObject): void {
		$this->doCommand('dragAndDropToObject', [
			$locatorOfObjectToBeDragged,
			$locatorOfDragDestinationObject,
		]);
	}

	/**
	 * Gives focus to the currently selected window
	 * @access public
	 */
	public function windowFocus(): void {
		$this->doCommand('windowFocus', []);
	}

	/**
	 * Resize currently selected window to take up the entire screen
	 * @access public
	 */
	public function windowMaximize(): void {
		$this->doCommand('windowMaximize', []);
	}

	/**
	 * Returns the IDs of all windows that the browser knows about.
	 * @access public
	 * @return array the IDs of all windows that the browser knows about.
	 */
	public function getAllWindowIds() {
		return $this->getStringArray('getAllWindowIds', []);
	}

	/**
	 * Returns the names of all windows that the browser knows about.
	 * @access public
	 * @return array the names of all windows that the browser knows about.
	 */
	public function getAllWindowNames() {
		return $this->getStringArray('getAllWindowNames', []);
	}

	/**
	 * Returns the titles of all windows that the browser knows about.
	 * @access public
	 * @return array the titles of all windows that the browser knows about.
	 */
	public function getAllWindowTitles() {
		return $this->getStringArray('getAllWindowTitles', []);
	}

	/**
	 * Returns the entire HTML source between the opening and
	 * closing "html" tags.
	 * @access public
	 * @return string the entire HTML source
	 */
	public function getHtmlSource() {
		return $this->getString('getHtmlSource', []);
	}

	/**
	 * Moves the text cursor to the specified position in the given input element or textarea.
	 * This method will fail if the specified element isn't an input element or textarea.
	 * @access public
	 * @param string $locator an element locator pointing to an input element or textarea
	 * @param string $position the numerical position of the cursor in the field; position should be 0 to move the
	 *        	position to the beginning of the field. You can also set the cursor to -1 to move it to the end of the
	 *        	field.
	 */
	public function setCursorPosition($locator, $position): void {
		$this->doCommand('setCursorPosition', [
			$locator,
			$position,
		]);
	}

	/**
	 * Get the relative index of an element to its parent (starting from 0).
	 * The comment node and empty text node
	 * will be ignored.
	 * @access public
	 * @param string $locator an element locator pointing to an element
	 * @return number of relative index of the element to its parent (starting from 0)
	 */
	public function getElementIndex($locator) {
		return $this->getNumber('getElementIndex', [
			$locator,
		]);
	}

	/**
	 * Check if these two elements have same parent and are ordered siblings in the DOM.
	 * Two same elements will
	 * not be considered ordered.
	 * @access public
	 * @param string $locator1 an element locator pointing to the first element
	 * @param string $locator2 an element locator pointing to the second element
	 * @return boolean true if element1 is the previous sibling of element2, false otherwise
	 */
	public function isOrdered($locator1, $locator2) {
		return $this->getBoolean('isOrdered', [
			$locator1,
			$locator2,
		]);
	}

	/**
	 * Retrieves the horizontal position of an element
	 * @access public
	 * @param string $locator an element locator pointing to an element OR an element itself
	 * @return number of pixels from the edge of the frame.
	 */
	public function getElementPositionLeft($locator) {
		return $this->getNumber('getElementPositionLeft', [
			$locator,
		]);
	}

	/**
	 * Retrieves the vertical position of an element
	 * @access public
	 * @param string $locator an element locator pointing to an element OR an element itself
	 * @return number of pixels from the edge of the frame.
	 */
	public function getElementPositionTop($locator) {
		return $this->getNumber('getElementPositionTop', [
			$locator,
		]);
	}

	/**
	 * Retrieves the width of an element
	 * @access public
	 * @param string $locator an element locator pointing to an element
	 * @return number width of an element in pixels
	 */
	public function getElementWidth($locator) {
		return $this->getNumber('getElementWidth', [
			$locator,
		]);
	}

	/**
	 * Retrieves the height of an element
	 * @access public
	 * @param string $locator an element locator pointing to an element
	 * @return number height of an element in pixels
	 */
	public function getElementHeight($locator) {
		return $this->getNumber('getElementHeight', [
			$locator,
		]);
	}

	/**
	 * Retrieves the text cursor position in the given input element or textarea; beware, this may not work perfectly on
	 * all browsers.
	 * <p>
	 * Specifically, if the cursor/selection has been cleared by JavaScript, this command will tend to
	 * return the position of the last location of the cursor, even though the cursor is now gone from the page. This is
	 * filed as SEL-243.
	 * </p>
	 * This method will fail if the specified element isn't an input element or textarea, or there is no cursor in the
	 * element.
	 * @access public
	 * @param string $locator an element locator pointing to an input element or textarea
	 * @return number the numerical position of the cursor in the field
	 */
	public function getCursorPosition($locator) {
		return $this->getNumber('getCursorPosition', [
			$locator,
		]);
	}

	/**
	 * Returns the specified expression.
	 * <p>
	 * This is useful because of JavaScript preprocessing.
	 * It is used to generate commands like assertExpression and waitForExpression.
	 * </p>
	 * @access public
	 * @param string $expression the value to return
	 * @return string the value passed in
	 */
	public function getExpression($expression) {
		return $this->getString('getExpression', [
			$expression,
		]);
	}

	/**
	 * Returns the number of nodes that match the specified xpath, eg.
	 * "//table" would give
	 * the number of tables.
	 * @access public
	 * @param string $xpath the xpath expression to evaluate. do NOT wrap this expression in a 'count()' function; we
	 *        	will do that for you.
	 * @return number the number of nodes that match the specified xpath
	 */
	public function getXpathCount($xpath) {
		return $this->getNumber('getXpathCount', [
			$xpath,
		]);
	}

	/**
	 * Temporarily sets the "id" attribute of the specified element, so you can locate it in the future
	 * using its ID rather than a slow/complicated XPath.
	 * This ID will disappear once the page is
	 * reloaded.
	 * @access public
	 * @param string $locator an element locator pointing to an element
	 * @param string $identifier a string to be used as the ID of the specified element
	 */
	public function assignId($locator, $identifier): void {
		$this->doCommand('assignId', [
			$locator,
			$identifier,
		]);
	}

	/**
	 * Specifies whether Selenium should use the native in-browser implementation
	 * of XPath (if any native version is available); if you pass "false" to
	 * this function, we will always use our pure-JavaScript xpath library.
	 * Using the pure-JS xpath library can improve the consistency of xpath
	 * element locators between different browser vendors, but the pure-JS
	 * version is much slower than the native implementations.
	 * @access public
	 * @param string $allow boolean, true means we'll prefer to use native XPath; false means we'll only use JS XPath
	 */
	public function allowNativeXpath($allow): void {
		$this->doCommand('allowNativeXpath', [
			$allow,
		]);
	}

	/**
	 * Runs the specified JavaScript snippet repeatedly until it evaluates to "true".
	 * The snippet may have multiple lines, but only the result of the last line
	 * will be considered.
	 * <p>
	 * Note that, by default, the snippet will be run in the runner's test window, not in the window
	 * of your application. To get the window of your application, you can use
	 * the JavaScript snippet <code>selenium.browserbot.getCurrentWindow()</code>, and then
	 * run your JavaScript in there
	 * </p>
	 * @access public
	 * @param string $script the JavaScript snippet to run
	 * @param string $timeout a timeout in milliseconds, after which this command will return with an error
	 */
	public function waitForCondition($script, $timeout): void {
		$this->doCommand('waitForCondition', [
			$script,
			$timeout,
		]);
	}

	/**
	 * Specifies the amount of time that Selenium will wait for actions to complete.
	 * <p>
	 * Actions that require waiting include "open" and the "waitFor*" actions.
	 * </p>
	 * The default timeout is 30 seconds.
	 * @access public
	 * @param string $timeout a timeout in milliseconds, after which the action will return with an error
	 */
	public function setTimeout($timeout): void {
		$this->doCommand('setTimeout', [
			$timeout,
		]);
	}

	/**
	 * Waits for a new page to load.
	 * <p>
	 * You can use this command instead of the "AndWait" suffixes, "clickAndWait", "selectAndWait", "typeAndWait" etc.
	 * (which are only available in the JS API).
	 * </p><p>
	 * Selenium constantly keeps track of new pages loading, and sets a "newPageLoaded"
	 * flag when it first notices a page load. Running any other Selenium command after
	 * turns the flag to false. Hence, if you want to wait for a page to load, you must
	 * wait immediately after a Selenium command that caused a page-load.
	 * </p>
	 * @access public
	 * @param string $timeout a timeout in milliseconds, after which this command will return with an error
	 */
	public function waitForPageToLoad($timeout = '30000'): void {
		$this->doCommand('waitForPageToLoad', [
			$timeout,
		]);
	}

	public function waitForAjax($pattern, $timeout = 30): void {
		$timer = new Timer();
		$delay = 0;
		$html = null;
		do {
			if ($timer->elapsed() > $timeout) {
				throw new Exception_Selenium("Timed out waiting for \"$pattern\" to appear via Ajax");
			}
			$html = $this->getHTMLSource();
			if ($delay) {
				sleep($delay);
			}
			$delay = min($delay + 1, 3);
		} while (!str_contains($html, $pattern));
	}

	/**
	 * Waits for a new frame to load.
	 * <p>
	 * Selenium constantly keeps track of new pages and frames loading,
	 * and sets a "newPageLoaded" flag when it first notices a page load.
	 * </p>
	 * See waitForPageToLoad for more information.
	 * @access public
	 * @param string $frameAddress FrameAddress from the server side
	 * @param string $timeout a timeout in milliseconds, after which this command will return with an error
	 */
	public function waitForFrameToLoad($frameAddress, $timeout): void {
		$this->doCommand('waitForFrameToLoad', [
			$frameAddress,
			$timeout,
		]);
	}

	/**
	 * Return all cookies of the current page under test.
	 * @access public
	 * @return string all cookies of the current page under test
	 */
	public function getCookie() {
		return $this->getString('getCookie', []);
	}

	/**
	 * Create a new cookie whose path and domain are same with those of current page
	 * under test, unless you specified a path for this cookie explicitly.
	 * @access public
	 * @param string $nameValuePair name and value of the cookie in a format "name=value"
	 * @param string $optionsString options for the cookie. Currently supported options include 'path' and 'max_age'.
	 *        	the optionsString's format is "path=/path/, max_age=60". The order of options are irrelevant, the unit
	 *        	of the value of 'max_age' is second.
	 */
	public function createCookie($nameValuePair, $optionsString): void {
		$this->doCommand('createCookie', [
			$nameValuePair,
			$optionsString,
		]);
	}

	/**
	 * Delete a named cookie with specified path.
	 * @access public
	 * @param string $name the name of the cookie to be deleted
	 * @param string $path the path property of the cookie to be deleted
	 */
	public function deleteCookie($name, $path): void {
		$this->doCommand('deleteCookie', [
			$name,
			$path,
		]);
	}

	/**
	 * Sets the threshold for browser-side logging messages; log messages beneath this threshold will be discarded.
	 * Valid logLevel strings are: "debug", "info", "warn", "error" or "off".
	 * To see the browser logs, you need to
	 * either show the log window in GUI mode, or enable browser-side logging in Selenium RC.
	 * @access public
	 * @param string $logLevel one of the following: "debug", "info", "warn", "error" or "off"
	 */
	public function setBrowserLogLevel($logLevel): void {
		$this->doCommand('setBrowserLogLevel', [
			$logLevel,
		]);
	}

	/**
	 * Creates a new "script" tag in the body of the current test window, and
	 * adds the specified text into the body of the command.
	 * Scripts run in
	 * this way can often be debugged more easily than scripts executed using
	 * Selenium's "getEval" command. Beware that JS exceptions thrown in these script
	 * tags aren't managed by Selenium, so you should probably wrap your script
	 * in try/catch blocks if there is any chance that the script will throw
	 * an exception.
	 * @access public
	 * @param string $script the JavaScript snippet to run
	 */
	public function runScript($script): void {
		$this->doCommand('runScript', [
			$script,
		]);
	}

	/**
	 * Defines a new function for Selenium to locate elements on the page.
	 * For example,
	 * if you define the strategy "foo", and someone runs click("foo=blah"), we'll
	 * run your function, passing you the string "blah", and click on the element
	 * that your function
	 * returns, or throw an "Element not found" error if your function returns null.
	 * We'll pass three arguments to your function:
	 * <ul>
	 * <li>
	 * locator: the string the user passed in
	 * </li>
	 * <li>
	 * inWindow: the currently selected window
	 * </li>
	 * <li>
	 * inDocument: the currently selected document
	 * </li>
	 * </ul>
	 * The function must return null if the element can't be found.
	 * @access public
	 * @param string $strategyName the name of the strategy to define; this should use only letters [a-zA-Z] with no
	 *        	spaces or other punctuation.
	 * @param string $functionDefinition a string defining the body of a function in JavaScript. For example:
	 *        	<code>return inDocument.getElementById(locator);</code>
	 */
	public function addLocationStrategy($strategyName, $functionDefinition): void {
		$this->doCommand('addLocationStrategy', [
			$strategyName,
			$functionDefinition,
		]);
	}

	/**
	 * Writes a message to the status bar and adds a note to the browser-side
	 * log.
	 * @access public
	 * @param string $context the message to be sent to the browser
	 */
	public function setContext($context): void {
		$this->doCommand('setContext', [
			$context,
		]);
	}

	/**
	 * Captures a PNG screenshot to the specified file.
	 * @access public
	 * @param string $filename the absolute path to the file to be written, e.g. "c:\blah\screenshot.png"
	 */
	public function captureScreenshot($filename): void {
		$this->doCommand('captureScreenshot', [
			$filename,
		]);
	}

	protected function http_get_fopen($url) {
		if (!$handle = fopen($url, 'rb')) {
			throw new Exception_Selenium('Cannot connected to Selenium RC Server');
		}
		stream_set_blocking($handle, false);
		$response = stream_get_contents($handle);
		fclose($handle);
		return $response;
	}

	protected function http_get_HTTP_Client($url) {
		$x = new Net_HTTP_Client($this->application, $url);
		$x->timeout(60000);

		try {
			$data = $x->go();
		} catch (Exception $e) {
			throw new Exception_Selenium('Can not connect to server: ' . $e->getMessage());
		}
		return $data;
	}

	protected function doCommand($verb, $args = []) {
		$this->clearCaches();
		$this->application->logger->debug('{verb}({args_string})', [
			'verb' => $verb,
			'args_string' => implode(',', $args),
			'args',
		]);
		$url = sprintf('http://%s:%s/selenium-server/driver/?cmd=%s', $this->host, $this->port, urlencode($verb));
		for ($i = 0; $i < count($args); $i++) {
			$argNum = strval($i + 1);
			$url .= sprintf('&%s=%s', $argNum, urlencode(trim($args[$i])));
		}
		if (isset($this->sessionId)) {
			$url .= sprintf('&%s=%s', 'sessionId', $this->sessionId);
		}
		$response = $this->http_get_HTTP_Client($url);

		if (!preg_match('/^OK/', $response)) {
			throw new Exception_Selenium('The Response of the Selenium RC is invalid: ' . $response);
		}
		return $response;
	}

	private function getString($verb, $args = []) {
		$result = $this->doCommand($verb, $args);
		if (strlen($result) === 3) {
			return '';
		}
		return substr($result, 3);
	}

	private function getStringArray($verb, $args = []) {
		$csv = $this->getString($verb, $args);
		$token = '';
		$tokens = [];
		$letters = preg_split('//', $csv, -1, PREG_SPLIT_NO_EMPTY);
		for ($i = 0; $i < count($letters); $i++) {
			$letter = $letters[$i];
			switch ($letter) {
				case '\\':
					$i++;
					$letter = $letters[$i];
					$token = $token . $letter;
					break;
				case ',':
					array_push($tokens, $token);
					$token = '';
					break;
				default:
					$token = $token . $letter;
					break;
			}
		}
		array_push($tokens, $token);
		return $tokens;
	}

	private function getBoolean($verb, $args = []) {
		$result = $this->getString($verb, $args);
		switch ($result) {
			case 'true':
				return true;
			case 'false':
				return false;
			default:
				throw new Exception_Selenium('result is neither "true" or "false": ' . $result);
		}
	}

	public function clearCaches(): void {
		$this->page_title = null;
		$this->page_body = null;
		$this->page_html = null;
		$this->text = [];
	}

	public function assertTitleContains($text) {
		if ($this->page_title === null) {
			$this->page_title = $this->getTitle();
		}
		if (str_contains($this->page_title, $text)) {
			return true;
		}
		$this->failed("Title \"$this->page_title\" doesn't contain string \"$text\"");
		return false;
	}

	public function pageContains($text) {
		$content = $this->getBodyText();
		if (is_array($text)) {
			foreach ($text as $text_item) {
				if (!$this->pageContains($text_item)) {
					return false;
				}
			}
			return true;
		}
		return (str_contains($content, $text));
	}

	public function assertPageContains($text) {
		$content = $this->getBodyText();
		if (is_array($text)) {
			foreach ($text as $text_item) {
				if (!$this->assertPageContains($text_item)) {
					return false;
				}
			}
			return true;
		} else {
			if (str_contains($content, $text)) {
				return true;
			}
			$this->failed("Body*****\n$content\n*****\ndoesn't contain string \"$text\"");
			return false;
		}
	}

	public function assertHTMLContains($text, $count = null, $case_insensitive = false) {
		if ($this->page_html === null) {
			$this->page_html = $this->getHtmlSource();
		}
		if (is_array($text)) {
			foreach ($text as $text_item) {
				if (!$this->assertHTMLContains($text_item, $count, $case_insensitive)) {
					return false;
				}
			}
			return true;
		} else {
			$test_result = $case_insensitive ? stripos($this->page_html, $text) : strpos($this->page_html, $text);
			if ($test_result !== false) {
				if ($count > 0) {
					$num = count(explode($text, $this->page_html));
					if (($num - 1) !== $count) {
						$this->failed(($num - 1) . " found, $count expected\nHTML *****\n$this->page_html\n*****\ncontain string \"$text\"");
					}
				}
				return true;
			}
			$this->failed("HTML *****\n$this->page_html\n*****\ndoesn't contain string \"$text\"");
			return false;
		}
	}

	public function assertHTMLDoesNotContain($text) {
		if ($this->page_html === null) {
			$this->page_html = $this->getHtmlSource();
		}
		if (is_array($text)) {
			foreach ($text as $text_item) {
				if (!$this->assertHTMLDoesNotContain($text_item)) {
					return false;
				}
			}
			return true;
		} else {
			if (!str_contains($this->page_html, $text)) {
				return true;
			}
			$this->failed("HTML *****\n$this->page_html\n*****\ncontains string \"$text\"");
			return false;
		}
	}

	public function assertPageDoesNotContain($text) {
		$content = $this->getBodyText();
		if (is_array($text)) {
			foreach ($text as $text_item) {
				if (!$this->assertPageDoesNotContain($text_item)) {
					return false;
				}
			}
			return true;
		} else {
			if (!str_contains($content, $text)) {
				return true;
			}
			$this->failed("Body\n\n$content\nEOF contains contain string \"$text\" but shouldn't");
			return false;
		}
	}

	public function assertSelectedlabel($locator, $value, $partial = false) {
		$site_value = $this->getSelectedLabel($locator);
		if ($partial) {
			if (!str_contains($site_value, $value)) {
				$this->failed("Select $locator does not match expected value \"$value\": \"$site_value\"");
				return false;
			}
		} else {
			if ($site_value !== $value) {
				$this->failed("Select $locator does not match expected value \"$value\": \"$site_value\"");
				return false;
			}
		}
		return true;
	}

	public function assertEquals($a, $b, $message = ''): void {
		if ($a !== $b) {
			$this->failed("\"$a\" !== \"$b\": $message");
		}
	}

	public function assertValue($locator, $value): void {
		$site_value = $this->getValue($locator);
		if ($site_value !== $value) {
			$this->failed("Element $locator does not match expected value \"$value\" (" . gettype($value) . "): \"$site_value\" (" . gettype($site_value) . ')');
		}
	}

	public function assertConfirmationContains($pattern): void {
		$confirmation = $this->getConfirmation();
		if (!str_contains($confirmation, $pattern)) {
			$this->failed("Confirmation \"$confirmation\" does not contain pattern \"$pattern\"");
		}
	}

	public function assertTrue($boolean, $message = ''): void {
		if (!$boolean) {
			$this->failed($message);
		}
	}

	public function assertFalse($boolean, $message = ''): void {
		if ($boolean) {
			$this->failed($message);
		}
	}

	/**
	 * Clicks on a link, button, checkbox or radio button.
	 * If the click action
	 * causes a new page to load (like a link usually does), call
	 * waitForPageToLoad.
	 * @access public
	 * @param string $locator an element locator
	 */
	public function clickAndWait($locator, $timeout = '30000'): void {
		$this->click($locator);
		$this->waitForPageToLoad($timeout);
	}
}
