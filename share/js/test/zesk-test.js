/**
* $URL$
* @package zesk
* @subpackage test
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2009, Market Acumen, Inc.
*/

function test_assert(x) {
	var result, sresult, message = arguments[1] || "", verbose = _Gb('verbose');
	if (is_string(x)) {
		result = eval(x);
		if (!message) {
			message = x;
		} else {
			message = x + "<br />" + message;
		}
	} else {
		result = to_bool(result);
	}
	if (!result) {
		verbose = verbose;
	}
	if (verbose) {
		document.write('<hr />');
	}
	sresult = (result) ? "<strong>SUCCESS</strong>" : "<em style=\"color: red\">FAILED</em>";
	document.write(message + ' => ' + result.toString() + ': ' + sresult + '<br />');
}

(function() {
	test_assert("_G('undefined') === null");
	_S('test', 'value')
	test_assert("_G('test') === 'value'");
	test_assert("gettype('test') === 'string'");
	test_assert("gettype([]) === 'array'");
	test_assert("gettype({}) === 'object'");
	test_assert("gettype(new Date) === 'date'");
	test_assert("gettype(false) === 'boolean'");
	test_assert("gettype(true) === 'boolean'");
	test_assert("gettype(0) === 'number'");
	test_assert("gettype(-124123120) === 'number'");
	test_assert("gettype(-12412312.0) === 'number'");
	test_assert("gettype(-12412312.0) === 'number'");
	test_assert("case_match_simple('dig','Dog') === 'Dig'");
	test_assert("case_match_simple('dig','DOG') === 'DIG'");
	test_assert("case_match_simple('DIG','doG') === 'dig'");
	translation('de', {'these::this {0}': 'diesem {0}'});
	locale('de_DE');
	test_assert("these('buchen', 1) === 'diesem buchen'");
	locale('en_US');
	test_assert("these('book', 1) === 'this book'");
	test_assert("these('book', 2) === 'these 2 books'");
	document.write('Done.');
})();
