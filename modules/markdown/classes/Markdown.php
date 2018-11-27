<?php
/**
 * Ported from Markdown -- A text-to-HTML conversion tool for web writers
 *
 * Copyright (c) 2004 John Gruber
 *
 * http://daringfireball.net/projects/markdown/
 *
 * Port by Kent Davidson, kent@marketacumen.com
 *
 * Copyright (c) 2014, Market Acumen, Inc.
 *
 * http://marketacumen.com/
 *
 * Yes, I'm aware of another PHP port.
 *
 * $Revision: 4430 $
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Markdown extends Options {
    const version = '1.0.1';

    const default_tab_size = 4;

    //
    // Global default settings:
    //
    private $g_empty_element_suffix = " />"; // Change to ">" for HTML output

    private $g_tab_width = self::default_tab_size;

    //
    // Globals:
    //

    // Regex to match balanced [brackets]. See Friedl's
    // "Mastering Regular Expressions", 2nd Ed., pp. 328-331.
    const g_nested_brackets = '
	(?> 								# Atomic matching
	   [^\[\]]+							# Anything other than brackets
	 |
	   \[
		 (?R)							# Recursive set of nested brackets
	   \]
	)*';

    // Table of hash values for escaped characters:
    private static $g_escape_chars = '\\`*_{}[]()>#+-.!';

    public static $g_escape_table = null;

    private static $g_unspecial = null;

    public $text = null;

    // Global hashes, used by various utility routines
    private $g_urls = array();

    private $g_titles = array();

    private $g_html_blocks = array();

    // Used to track when we're inside an ordered or unordered list
    // (see _ProcessListItems() for details):
    private $g_list_level = 0;

    public function __construct($text = null, array $options = array()) {
        parent::__construct($options);
        $this->text = $text;
        $this->_initialize();
    }

    public function tab_size($set = null) {
        if ($set !== null) {
            $this->tab_size = $set;
            return $this;
        }
        return $this->option_integer('tab_size', self::default_tab_size);
    }

    private function _initialize() {
        if (self::$g_escape_table === null) {
            $g_escape_table = array();
            foreach (str_split(self::$g_escape_chars) as $i => $c) {
                self::$g_escape_table[$c] = "^@^$i@^@";
            }
            self::$g_unspecial = array(
                '*' => self::$g_escape_table['*'],
                '_' => self::$g_escape_table['_'],
            );
        }
    }

    public static function filter($text, array $options = array()) {
        $x = new self($text, $options);
        return $x->_filter();
    }

    private function _filter($text = null) {
        if ($text === null) {
            $text = $this->text;
        }

        //
        // Main function. The order in which other subs are called here is
        // essential. Link and image substitutions need to happen before
        // _EscapeSpecialChars(), so that any *'s or _'s in the <a>
        // and <img> tags get encoded.
        //

        // Clear the global hashes. If we don't clear these, you get conflicts
        // from other articles when generating a page which contains more than
        // one article (e.g. an index page that shows the N most recent
        // articles):
        $this->g_urls = array();
        $this->g_html_blocks = array();

        // Standardize line endings:
        $text = Text::set_line_breaks($text, "\n");

        // Make sure $text ends with a couple of newlines:
        $text .= "\n\n";

        // Convert all tabs to spaces.
        $text = StringTools::detab($text, $this->g_tab_width);

        // Strip any lines consisting only of spaces and tabs.
        // This makes subsequent regexen easier to write, because we can
        // match consecutive blank lines with /\n+/ instead of something
        // contorted like /[ \t]*\n+/ .
        $text = preg_replace('/^[ \t]+$/m', '', $text);

        // Turn block-level HTML blocks into hash entries
        $text = $this->_HashHTMLBlocks($text);

        // Strip link definitions, store in hashes.
        $text = $this->_StripLinkDefinitions($text);

        $text = $this->_RunBlockGamut($text);

        $text = $this->_UnescapeSpecialChars($text);

        return $text . "\n";
    }

    private function _StripLinkDefinitions($text) {
        $less_than_tab = $this->g_tab_width - 1;

        $pattern = '@^[ ]{0,' . $less_than_tab . '}\[(.+)\]:	# id = $1
						  [ \t]*
						  \n?				# maybe *one* newline
						  [ \t]*
						<?(\S+?)>?			# url = $2
						  [ \t]*
						  \n?				# maybe one newline
						  [ \t]*
						(?:
							(?<=\s)			# lookbehind for whitespace
							[\"(]
							(.+?)			# title = $3
							[\")]
							[ \t]*
						)?	# title is optional
						(?:\n+|\Z)
					@mx';

        foreach (preg::matches($pattern, $text) as $match) {
            list($match, $id, $url, $title) = $match + array(
                null,
                null,
                null,
                null,
            );
            $id = strtolower($id);
            $this->g_urls[$id] = $url;
            if ($title) {
                $this->g_titles[$id] = $title;
            }
            $text = str_replace($match, '', $text);
        }
        return $text;
    }

    private function _HashHTMLBlocks($text) {
        $less_than_tab = $this->g_tab_width - 1;

        // Hashify HTML blocks:
        // We only want to do this for block-level HTML tags, such as headers,
        // lists, and tables. That's because we still want to wrap <p>s around
        // "paragraphs" that are wrapped in non-block-level tags, such as anchors,
        // phrase emphasis, and spans. The list of tags we're looking for is
        // hard-coded:
        $block_tags_b = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math';
        $block_tags_a = $block_tags_b . '|ins|del';

        // First, look for nested blocks, e.g.:
        // 	<div>
        // 		<div>
        // 		tags for inner block must be indented.
        // 		</div>
        // 	</div>
        //
        // The outermost tags must start at the left margin for this to match, and
        // the inner nested divs must be indented.
        // We need to do this before the next, more liberal match, because the next
        // match will start at the first `<div>` and stop at the first `</div>`.
        $patterns[] = '(				# save in $1
					^					# start of line  (with /m)
					<(' . $block_tags_a . ')	# start tag = $2
					\b					# word break
					(.*\n)*?			# any number of lines, minimally matching
					</\2>				# the matching end tag
					[ \t]*				# trailing spaces/tabs
					(?=\n+|\Z)			# followed by a newline or end of document
				)';

        //
        // Now match more liberally, simply from `\n<tag>` to `</tag>\n`
        //
        $patterns[] = '(						# save in $1
					^					# start of line  (with /m)
					<(' . $block_tags_b . ')	# start tag = $2
					\b					# word break
					(.*\n)*?			# any number of lines, minimally matching
					.*</\2>				# the matching end tag
					[ \t]*				# trailing spaces/tabs
					(?=\n+|\Z)	# followed by a newline or end of document
				)';

        // Special case just for <hr />. It was easier to make a special case than
        // to make the other regex more complicated.
        $patterns[] = '(?:
					(?<=\n\n)		# Starting after a blank line
					|				# or
					\A\n?			# the beginning of the doc
				)
				(						# save in $1
					[ ]{0,' . $less_than_tab . '}
					<(hr)				# start tag = $2
					\b					# word break
					([^<>])*?			#
					/?>					# the matching end tag
					[ \t]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
				)';

        // Special case for standalone HTML comments:
        $patterns[] = '(?:
					(?<=\n\n)		# Starting after a blank line
					|				# or
					\A\n?			# the beginning of the doc
				)
				(						# save in $1
					[ ]{0,' . $less_than_tab . '}
					(?s:
						<!
						(--.*?--\s*)+
						>
					)
					[ \t]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
				)';

        foreach ($patterns as $index => $pattern) {
            foreach (preg::matches("@$pattern@mx", $text) as $match) {
                list($html) = $match;
                $key = md5($html);
                $this->g_html_blocks[$key] = $html;
                $text = str_replace($html, "\n\n" . $key . "\n\n", $text);
            }
        }
        return $text;
    }

    private function _RunBlockGamut($text) {
        //
        // These are all the transformations that form block-level
        // tags like paragraphs, headers, and list items.
        //
        $text = $this->_DoHeaders($text);

        // Do Horizontal Rules:
        $replace = "\n<hr" . $this->g_empty_element_suffix . "\n";
        $text = preg_replace('/^[ ]{0,2}([ ]?\*[ ]?){3,}[ \t]*$/xm', $replace, $text);
        $text = preg_replace('/^[ ]{0,2}([ ]? -[ ]?){3,}[ \t]*$/xm', $replace, $text);
        $text = preg_replace('/^[ ]{0,2}([ ]? _[ ]?){3,}[ \t]*$/xm', $replace, $text);

        $text = $this->_DoLists($text);

        $text = $this->_DoCodeBlocks($text);

        $text = $this->_DoBlockQuotes($text);

        // We already ran _HashHTMLBlocks() before, in Markdown(), but that
        // was to escape raw HTML in the original Markdown source. This time,
        // we're escaping the markup we've just created, so that we don't wrap
        // <p> tags around block-level tags.
        $text = $this->_HashHTMLBlocks($text);

        $text = $this->_FormParagraphs($text);

        return $text;
    }

    private function _RunSpanGamut($text) {
        //
        // These are all the transformations that occur *within* block-level
        // tags like paragraphs, headers, and list items.
        //
        $text = $this->_DoCodeSpans($text);

        $text = $this->_EscapeSpecialChars($text);

        // Process anchor and image tags. Images must come first,
        // because ![foo][f] looks like an anchor.
        $text = $this->_DoImages($text);
        $text = $this->_DoAnchors($text);

        // Make links out of things like `<http://example.com/>`
        // Must come after _DoAnchors(), because you can use < and >
        // delimiters in inline links like [this](<url>).
        $text = $this->_DoAutoLinks($text);

        $text = $this->_EncodeAmpsAndAngles($text);

        $text = $this->_DoItalicsAndBold($text);

        // Do hard breaks:
        $replace = " <br" . $this->g_empty_element_suffix . "\n";
        $text = preg_replace("/ {2,}\n/", $replace, $text);

        return $text;
    }

    private function _EscapeSpecialChars($text) {
        $tokens = $this->_TokenizeHTML($text);

        $text = ''; // rebuild $text from the tokens
        // 	$in_pre = 0;	 # Keep track of when we're inside <pre> or <code> tags.
        // 	$tags_to_skip = qr!<(/?)(?:pre|code|kbd|script|math)[\s>]!;

        foreach ($tokens as $cur_token) {
            list($type, $content) = $cur_token;
            if ($type === "tag") {
                // Within tags, encode * and _ so they don't conflict
                // with their use in Markdown for italics and strong.
                // We're replacing each such character with its
                // corresponding MD5 checksum value; this is likely
                // overkill, but it should prevent us from colliding
                // with the escape values by accident.
                $content = strtr($content, self::$g_unspecial);
                $text .= $content;
            } else {
                $content = $this->_EncodeBackslashEscapes($content);
                $text .= $content;
            }
        }
        return $text;
    }

    private function _DoAnchors($text) {
        //
        // Turn Markdown link shortcuts into XHTML <a> tags.
        //
        $g_nested_brackets = self::g_nested_brackets;

        //
        // First, handle reference-style links: [link text] [id]
        //
        $pattern = '@
		(					# wrap whole match in $1
		  \[
		    (' . $g_nested_brackets . ')	# link text = $2
		  \]

		  [ ]?				# one optional space
		  (?:\n[ ]*)?		# one optional newline followed by spaces

		  \[
		    (.*?)		# id = $3
		  \]
		)
		@xsmi';
        foreach (preg::matches($pattern, $text) as $match) {
            list($whole_match, $IGNORED, $link_text, $link_id) = $match + array_fill(0, 4, null);

            $link_id = strtolower($link_id);
            if (empty($link_id)) {
                $link_id = strtolower($link_text); // for shortcut links like [this][].
            }

            if (array_key_exists($link_id, $this->g_urls)) {
                $url = $this->g_urls[$link_id];
                $url = strtr($url, self::$g_unspecial);
                $result = "<a href=\"$url\"";
                if (array_key_exists($link_id, $this->g_titles)) {
                    $title = $this->g_titles[$link_id];
                    $title = strtr($title, $this->g_titles);
                    $result .= " title=\"$title\"";
                }
                $result .= ">$link_text</a>";
                $text = str_replace($whole_match, $result, $text);
            }
        }

        //
        // Next, inline-style links: [link text](url "optional title")
        //
        $pattern = '@
		(				# wrap whole match in $1
		  \[
		    (' . $g_nested_brackets . ')	# link text = $2
		  \]
		  \(			# literal paren
		  	[ \t]*
			<?(.*?)>?	# href = $3
		  	[ \t]*
			(			# $4
			  ([\'"])	# quote char = $5
			  (.*?)		# Title = $6
			  \5		# matching quote
			)?			# title is optional
		  \)
		)
		@xs';

        foreach (preg::matches($pattern, $text) as $match) {
            $whole_match = $match[0];
            $link_text = $match[2];
            $url = $match[3];
            $title = avalue($match, 6);

            $url = strtr($url, self::$g_unspecial);
            $result = "<a href=\"$url\"";
            if ($title) {
                $title = str_replace('"', '&quot;', $title);
                $title = strtr($title, self::$g_unspecial);
                $result .= " title=\"$title\"";
            }
            $result .= ">$link_text</a>";
            $text = str_replace($whole_match, $result, $text);
        }
        return $text;
    }

    private function _DoImages($text) {
        //
        // Turn Markdown image shortcuts into <img> tags.
        //

        //
        // First, handle reference-style labeled images: ![alt text][id]
        //
        $pattern = '@
		(				# wrap whole match in $1
		  !\[
		    (.*?)		# alt text = $2
		  \]

		  [ ]?				# one optional space
		  (?:\n[ ]*)?		# one optional newline followed by spaces

		  \[
		    (.*?)		# id = $3
		  \]

		)
		@xs';

        foreach (preg::matches($pattern, $text) as $match) {
            list($whole_match, $IGNORED, $alt_text, $link_id) = $match;
            $link_id = strtolower($link_id);

            if ($link_id === "") {
                $link_id = strtolower($alt_text); // for shortcut links like ![this][].
            }

            $alt_text = str_replace('"', '&quot;', $alt_text);
            if (array_key_exists($link_id, $this->g_urls)) {
                $url = $this->g_urls[$link_id];
                $url = strtr($url, self::$g_unspecial);

                $result = "<img src=\"$url\" alt=\"$alt_text\"";
                if (array_key_exists($link_id, $this->g_titles)) {
                    $title = $this->g_titles[$link_id];
                    $title = strtr($title, self::$g_unspecial);
                    $result .= " title=\"$title\"";
                }
                $result .= $this->g_empty_element_suffix;
                $text = str_replace($match[0], $result, $text);
            } else {
                // If there's no such link ID, leave intact:
            }
        }

        //
        // Next, handle inline images:  ![alt text](url "optional title")
        // Don't forget: encode * and _

        $pattern = '@
		(				# wrap whole match in $1
		  !\[
		    (.*?)		# alt text = $2
		  \]
		  \(			# literal paren
		  	[ \t]*
			<?(\S+?)>?	# src url = $3
		  	[ \t]*
			(			# $4
			  ([\'"])	# quote char = $5
			  (.*?)		# title = $6
			  \5		# matching quote
			  [ \t]*
			)?			# title is optional
		  \)
		)
		@xs';

        foreach (preg::matches($pattern, $text) as $match) {
            $whole_match = $match[0];
            $alt_text = $match[2];
            $url = $match[3];
            $title = avalue($match, 6, null);

            $alt_text = strtr($alt_text, '"', '&quot;'); // KMD htmlspecialchars?
            $title = strtr($title, '"', '&quot;'); // KMD htmlspecialchars?

            $url = strtr($url, self::$g_unspecial);
            $result = "<img src=\"$url\" alt=\"$alt_text\"";
            if ($title) {
                $title = strtr($title, self::$g_unspecial);
                $result .= " title=\"$title\"";
            }
            $result .= $this->g_empty_element_suffix;

            $text = str_replace($whole_match, $result, $text);
        }

        return $text;
    }

    private function id_from_title($title) {
        return trim(preg_replace('/_+/', '_', preg_replace('/[^a-z0-9_]/', '_', strtolower($title))), '_');
    }

    private function _DoHeaders($text) {
        // Setext-style headers:
        //	  Header 1
        //	  ========
        //
        //	  Header 2
        //	  --------
        //
        foreach (preg::matches('@ ^(.+)[ \t]*\n=+[ \t]*\n+ @mx', $text) as $match) {
            $title = $this->_RunSpanGamut($match[1]);
            $id = $this->id_from_title($title);
            $text = str_replace($match[0], "<h1 id=\"$id\">" . $title . "</h1>\n\n", $text);
        }
        foreach (preg::matches('@ ^(.+)[ \t]*\n-+[ \t]*\n+ @mx', $text) as $match) {
            $title = $this->_RunSpanGamut($match[1]);
            $id = $this->id_from_title($title);
            $text = str_replace($match[0], "<h2 id=\"$id\">" . $title . "</h2>\n\n", $text);
        }

        // atx-style headers:
        //	# Header 1
        //	## Header 2
        //	## Header 2 with closing hashes ##
        //	...
        //	###### Header 6
        //
        $pattern = '@
			^(\#{1,6})	# $1 = string of #s
			[ \t]*
			(.+?)		# $2 = Header text
			[ \t]*
			\#*			# optional closing #s (not counted)
			\n+
		@mx';
        foreach (($preg = preg::matches($pattern, $text)) as $match) {
            $h_level = strlen($match[1]);
            $title = $this->_RunSpanGamut($match[2]);
            $id = $this->id_from_title($title);
            $text = $preg->replace_current("<h$h_level id=\"$id\">" . $title . "</h$h_level>\n\n");
        }

        return $text;
    }

    private function _DoLists($text) {
        //
        // Form HTML ordered (numbered) and unordered (bulleted) lists.
        //
        $less_than_tab = $this->g_tab_width - 1;

        // Re-usable patterns to match list item bullets and number markers:
        $marker_ul = '[*+-]';
        $marker_ol = '\d+[.]';
        $marker_any = "(?:$marker_ul|$marker_ol)";

        // Re-usable pattern to match any entirel ul or ol list:
        $whole_list = '
		(								# 1 = whole list
		  (								# 2
			[ ]{0,' . $less_than_tab . '}
			(' . $marker_any . ')			# 3 = first list item marker
			[ \t]+
		  )
		  (?s:.+?)
		  (								# 4
			  \z
			|
			  \n{2,}
			  (?=\S)
			  (?!						# Negative lookahead for another list item marker
				[ \t]*
				' . $marker_any . '[ \t]+
			  )
		  )
		)';

        // We use a different prefix before nested lists than top-level lists.
        // See extended comment in _ProcessListItems().
        //
        // Note: There's a bit of duplication here. original implementation
        // created a scalar regex pattern as the conditional result of the test on
        // $g_list_level, and then only ran the $text =~ s{...}{...}egmx
        // substitution once, using the scalar as the pattern. This worked,
        // everywhere except when running under MT on hosting account at Pair
        // Networks. There, this caused all rebuilds to be killed by the reaper (or
        // perhaps they crashed, but that seems incredibly unlikely given that the
        // same script on the same server ran fine *except* under MT. I've spent
        // more time trying to figure out why this is happening than I'd like to
        // admit. only guess, backed up by the fact that this workaround works,
        // is that Perl optimizes the substition when it can figure out that the
        // pattern will never change, and when this optimization isn't on, we run
        // afoul of the reaper. Thus, the slightly redundant code to that uses two
        // static s/// patterns rather than one conditional pattern.

        $whole_list = $this->g_list_level ? '@^' . $whole_list . '@mx' : '@(?:(?<=\n\n)|\A\n?)' . $whole_list . '@mx';
        foreach (preg::matches($whole_list, $text) as $match) {
            list($full_match, $list, $IGNORED, $list_type) = $match;
            $list_type = preg_match("/$marker_ul/", $list_type) ? "ul" : "ol";
            // Turn double returns into triple returns, so that we can make a
            // paragraph for the last item in a list, if necessary:
            $list = preg_replace('/\n{2,}/', "\n\n\n", $list);
            $result = $this->_ProcessListItems($list, $marker_any);
            $result = "<$list_type>\n" . $result . "</$list_type>\n";
            $text = StringTools::replace_first($full_match, $result, $text);
        }

        return $text;
    }

    private function _ProcessListItems($list_str, $marker_any) {
        //
        //	Process the contents of a single ordered or unordered list, splitting it
        //	into individual list items.
        //

        // The $g_list_level global keeps track of when we're inside a list.
        // Each time we enter a list, we increment it; when we leave a list,
        // we decrement. If it's zero, we're not in a list anymore.
        //
        // We do this because when we're not inside a list, we want to treat
        // something like this:
        //
        //		I recommend upgrading to version
        //		8. Oops, now this line is treated
        //		as a sub-list.
        //
        // As a single paragraph, despite the fact that the second line starts
        // with a digit-period-space sequence.
        //
        // Whereas when we're inside a list (or sub-list), that line will be
        // treated as the start of a sub-list. What a kludge, huh? This is
        // an aspect of Markdown's syntax that's hard to parse perfectly
        // without resorting to mind-reading. Perhaps the solution is to
        // change the syntax rules such that sub-lists must start with a
        // starting cardinal number; e.g. "1." or "a.".
        $this->g_list_level++;

        // trim trailing blank lines:
        $list_str = preg_replace('/\n{2,}\z/', "\n", $list_str);

        $pattern = '@
			(\n)?							# leading line = $1
			(^[ \t]*)						# leading whitespace = $2
			(' . $marker_any . ') [ \t]+		# list marker = $3
			((?s:.+?)						# list item text   = $4
			(\n{1,2}))
			(?= \n* (\z | \2 (' . $marker_any . ') [ \t]+))
		@mx';

        foreach (preg::matches($pattern, $list_str) as $match) {
            list($whole_string, $leading_line, $leading_space, $IGNORED, $item) = $match;

            if ($leading_line || preg_match('/\n{2,}/', $item) !== 0) {
                $item = $this->_RunBlockGamut($this->_Outdent($item));
            } else {
                // Recursion for sub-lists:
                $item = $this->_DoLists($this->_Outdent($item));
                $item = rtrim($item);
                $item = $this->_RunSpanGamut($item);
            }

            $list_str = StringTools::replace_first($whole_string, "<li>" . $item . "</li>\n", $list_str);
        }

        $this->g_list_level--;

        return $list_str;
    }

    private function _DoCodeBlocks($text) {
        //
        //	Process Markdown `<pre><code>` blocks.
        //
        $g_tab_width = $this->g_tab_width;
        $pattern = '@
			(?:\n\n|\A)
			(	            # $1 = the code block -- one or more lines, starting with a space/tab
			  (?:
			    (?:[ ]{' . $g_tab_width . '} | \t)  # Lines must start with a tab or a tab-width of spaces
			    .*\n+
			  )+
			)
			((?=^[ ]{0,' . $g_tab_width . '}\S)|\Z)	# Lookahead for non-space at line-start, or end of doc
		@mx';

        foreach (preg::matches($pattern, $text) as $match) {
            $codeblock = $match[1];

            $codeblock = $this->_EncodeCode($this->_Outdent($codeblock));
            $codeblock = StringTools::detab($codeblock, $this->g_tab_width);
            $codeblock = preg_replace('/\A\n+/', '', $codeblock); // trim leading newlines
            $codeblock = preg_replace('/\s+\z/', '', $codeblock); // trim trailing whitespace

            $result = "\n\n<pre><code>" . $codeblock . "\n</code></pre>\n\n";

            $text = StringTools::replace_first($match[0], $result, $text);
        }

        return $text;
    }

    private function _DoCodeSpans($text) {
        //
        // 	*	Backtick quotes are used for <code></code> spans.
        //
        // 	*	You can use multiple backticks as the delimiters if you want to
        // 		include literal backticks in the code span. So, this input:
        //
        //         Just type ``foo `bar` baz`` at the prompt.
        //
        //     	Will translate to:
        //
        //         <p>Just type <code>foo `bar` baz</code> at the prompt.</p>
        //
        //		There's no arbitrary limit to the number of backticks you
        //		can use as delimters. If you need three consecutive backticks
        //		in your code, use four for delimiters, etc.
        //
        //	*	You can use spaces to get literal backticks at the edges:
        //
        //         ... type `` `bar` `` ...
        //
        //     	Turns to:
        //
        //         ... type <code>`bar`</code> ...
        //
        $pattern = '@
			(`+)		# $1 = Opening run of `
			(.+?)		# $2 = The code block
			(?<!`)
			\1			# Matching closer
			(?!`)
		@sx';

        foreach (preg::matches($pattern, $text) as $match) {
            $c = trim($match[2]);
            $c = $this->_EncodeCode($c);
            $text = str_replace($match[0], "<code>$c</code>", $text);
        }

        return $text;
    }

    private function _EncodeCode($text) {
        //
        // Encode/escape certain characters inside Markdown code runs.
        // The point is that in code, these characters are literals,
        // and lose their special Markdown meanings.
        //

        // Encode all ampersands; HTML entities are not
        // entities within a Markdown code span.
        $text = strtr($text, '&', '&amp;');

        // Encode $'s, but only if we're running under Blosxom.
        // (Blosxom interpolates Perl variables in article bodies.)
        //	{
        //		no warnings 'once';
        //    	if (defined($blosxom::version)) {
        //    		s/\$/&#036;/g;
        //    	}
        //    }

        // Do the angle bracket song and dance:
        $text = strtr($text, array(
            '<' => "&lt;",
            '>' => '&gt;',
        ));

        // Now, escape characters that are magic in Markdown:
        $magicals = array();
        foreach (str_split('*_{}[]\\') as $char) {
            $magicals[$char] = self::$g_escape_table[$char];
        }
        $text = strtr($text, $magicals);

        return $text;
    }

    private function _DoItalicsAndBold($text) {
        // <strong> must go first:
        $text = preg_replace('/ (\*\*|__) (?=\S) (.+?[*_]*) (?<=\S) \1 /sx', '<strong>$2</strong>', $text);

        $text = preg_replace('/ (\*|_) (?=\S) (.+?) (?<=\S) \1 /sx', '<em>$2</em>', $text);

        return $text;
    }

    private function _DoBlockQuotes($text) {
        $pattern = '@
		  (								# Wrap whole match in $1
			(
			  ^[ \t]*>[ \t]?			# " > " at the start of a line
			    .+\n					# rest of the first line
			  (.+\n)*					# subsequent consecutive lines
			  \n*						# blanks
			)+
		  )
		@mx';

        foreach (preg::matches($pattern, $text) as $match) {
            list($bq) = $match;
            $bq = preg_replace('/^[ \t]*>[ \t]?/m', '', $bq); // trim one level of quoting
            $bq = preg_replace('/^[ \t]+$/m', '', $bq); // trim whitespace-only lines
            $bq = $this->_RunBlockGamut($bq); // recurse

            $bq = preg_replace('/^/m', '  ', $bq);
            // These leading spaces screw with <pre> content, so we need to fix that:
            $pattern = '@(\s*<pre>.+?</pre>)@sx';
            foreach (preg::matches($pattern, $bq) as $bqmatch) {
                $pre = $bqmatch[1];
                $pre = preg_replace('/^  /', '', $pre);
                $bq = str_replace($bqmatch[0], $pre, $bq);
            }
            $text = str_replace($match[0], "<blockquote>\n$bq\n</blockquote>\n\n", $text);
        }
        return $text;
    }

    private function _FormParagraphs($text) {

        // Strip leading and trailing lines:
        $text = trim($text, "\n");

        $grafs = preg_split('/\n{2,}/m', $text);

        //
        // Wrap <p> tags, and Unhashify HTML blocks
        //
        foreach ($grafs as $index => $graf) {
            if (array_key_exists($graf, $this->g_html_blocks)) {
                $grafs[$index] = $this->g_html_blocks[$graf];
            } else {
                $graf = $this->_RunSpanGamut($graf);
                $graf = '<p>' . ltrim($graf) . '</p>';
                $grafs[$index] = $graf;
            }
        }

        return implode("\n\n", $grafs);
    }

    private function _EncodeAmpsAndAngles($text) {
        // Smart processing for ampersands and angle brackets that need to be encoded.

        // Ampersand-encoding based entirely on Nat Irons's Amputator MT plugin:
        //   http://bumppo.net/projects/amputator/
        $text = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/i', '&amp;', $text);

        // Encode naked <'s
        $text = preg_replace('@<(?![a-z/?\$!])@i', '&lt;', $text);

        return $text;
    }

    private function _EncodeBackslashEscapes($text) {
        //
        //   Parameter:  String.
        //   Returns:    The string, with after processing the following backslash
        //               escape sequences.
        //
        return strtr($text, ArrayTools::kprefix(self::$g_escape_table, '\\'));
    }

    private function _DoAutoLinks($text) {
        $text = preg_replace('@<((https?|ftp):[^\'">\s]+)>@i', '<a href="$1">$1</a>', $text);

        // Email addresses: <address@domain.foo>

        $pattern = '/<
        (?:mailto:)?
		(
			[-.\w]+
			\@
			[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]+
		)
		>/ix';

        foreach (preg::matches($pattern, $text) as $match) {
            $replace = $this->_EncodeEmailAddress($this->_UnescapeSpecialChars($match[1]));
            $text = str_replace($match[0], $replace, $text);
        }

        return $text;
    }

    private function _EncodeEmailAddress($addr) {
        //
        //	Input: an email address, e.g. "foo@example.com"
        //
        //	Output: the email address as a mailto link, with each character
        //		of the address encoded as either a decimal or hex entity, in
        //		the hopes of foiling most address harvesting spam bots. E.g.:
        //
        //	  <a href="&#x6D;&#97;&#105;&#108;&#x74;&#111;:&#102;&#111;&#111;&#64;&#101;
        //       x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;">&#102;&#111;&#111;
        //       &#64;&#101;x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;</a>
        //
        //	Based on a filter by Matthew Wickline, posted to the BBEdit-Talk
        //	mailing list: <http://tinyurl.com/yu7ue>
        //
        $addr = "mailto:" . $addr;
        $eaddr = "";
        foreach (str_split($addr) as $c) {
            if ($c !== ':') {
                $top = $c === '@' ? 0.9 : 1;
                $rand = mt_rand(0, 1);
                if ($rand < 0.45) {
                    $c = '&#x' . sprintf('%X', ord($c)) . ";";
                } elseif ($rand <= $top) {
                    $c = '&#' . ord($c) . ';';
                }
            }
            $eaddr .= $c;
        }

        $addr = "<a href=\"$eaddr\">" . StringTools::right($eaddr, ":") . "</a>";

        return $addr;
    }

    private function _UnescapeSpecialChars($text) {
        //
        // Swap back in all the special characters we've hidden.
        //
        return strtr($text, array_flip(self::$g_escape_table));
    }

    private function _TokenizeHTML($text) {
        //
        //   Parameter:  String containing HTML markup.
        //   Returns:    Reference to an array of the tokens comprising the input
        //               string. Each token is either a tag (possibly with nested,
        //               tags contained therein, such as <a href="<MTFoo>">, or a
        //               run of text between tags. Each element of the array is a
        //               two-element array; the first is either 'tag' or 'text';
        //               the second is the actual value.
        //
        //
        //   Derived from the _tokenize() subroutine from Brad Choate's MTRegex plugin.
        //       <http://www.bradchoate.com/past/mtregex.php>
        //
        $len = strlen($text);
        $tokens = array();

        $depth = 6;
        $nested_tags = implode('|', array_fill(0, $depth, '(?:<[a-z/!$](?:[^<>]')) . str_repeat(')*>)', $depth);

        $pattern = '(?s: <! ( -- .*? -- \s* )+ > ) |  # comment
	               (?s: <\? .*? \?> ) |               # processing instruction
	               ' . $nested_tags; // nested tags

        $matches = null;
        $pos = 0;
        while (preg_match_all('@' . $pattern . '@ix', $text, $matches, PREG_OFFSET_CAPTURE, $pos) !== 0) {
            list($open_tag, $close_tag) = $matches[0] + array(
                null,
                null,
            );
            $tag_start = $open_tag[1];
            if ($close_tag) {
                $whole_tag_length = strlen($close_tag[0]) + $close_tag[1] - $tag_start;
            } else {
                $whole_tag_length = strlen($open_tag[0]);
            }
            if ($pos < $tag_start) {
                $tokens[] = array(
                    'text',
                    substr($text, $pos, $tag_start - $pos),
                );
            }
            $tokens[] = array(
                'tag',
                substr($text, $tag_start, $whole_tag_length),
            );
            $pos = $tag_start + $whole_tag_length;
        }
        if ($pos < $len) {
            $tokens[] = array(
                'text',
                substr($text, $pos, $len - $pos),
            );
        }
        return $tokens;
    }

    private function _Outdent($text) {
        //
        // Remove one level of line-leading tabs or spaces
        //
        $text = preg_replace('/^(\t|[ ]{1,' . $this->g_tab_width . '})/m', "", $text);
        return $text;
    }
}
