/**
 * This file contains a dummy post HTML taken from rendered rich editor posts before javascript ran.
 * To use these just import the one you want. These should be removed once we have the API hooked up.
 *
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

export const dummyTestPost1 = `
<p>I guess it's worth pointing out as well that there were numerous backwards incompatible changes in PHP 7.1 and PHP 7.2. We even ran into some ourselves.</p><h3><em>But...</em></h3><p>PHP 7.0 will no longer receive security patches after December 2018 <a href="http://php.net/supported-versions.php" target="_blank">http://php.net/supported-versions.php</a>.</p><p>So I think come December there is no good reason for us to offer any support for PHP 7.0. Since that is not an option we have 3 choices:</p><h2>Upgrade to PHP 7.1</h2><p>We pretty much have to do this one and it seems to have the biggest improvements and the biggest breaking changes.</p><h3>Major Features Gained</h3><ul><li>Type safety improvements (<em>This seems to be the biggest improvement</em>). Nullable types, void functions, iterable pseudo type (This is huge in us trying to move it data fragments that are iterable classes. Improves type safety, helps us remove val in more places, improve memory usage).</li><li>Class constant visibility.</li><li>Syntax sugar for array destructuring.</li></ul><h3>Major breaking changes</h3><ul><li>Throw on passing too few function arguments. <strong><em>This was significant</em></strong>. It required a good amount of effort on our part just to resolve issues with a single function call.</li><li>The empty index operator is not supported for strings anymore. We also ran into this but its scope wasn't as large.</ li > </ul><h2>Upgrade to PHP 7.2</h2 > <p>Whether we want this to me really depends on if we have any intention of using LibSodium or Argon2 any time soon.The rest of the stuff doesn't seem to be of significant utility.</p><p>Personally I feel like we could skip this one.</p><h3>Major Features Gained</h3><ul><li>LibSodium as a core library. Argon2 for password hashing. <em>Are we actually intending to use this any time soon?</em></li><li><code class="codeInline code isInline" spellcheck="false">object</code> type</li><li>Abstract method overriding and Parameter type widening. <em>Does anyone else see a major benefit here? I almost view this as an anti-pattern because doing so breaks a contract of the base class.</em></li></ul><h3>Major breaking changes</h3><p>I don't feel like listing all of these out.We didn't run into any of these issues however.</p><ul><li><a href="http://php.net/manual/en/migration72.incompatible.php" target="_blank">http://php.net/manual/en/migration72.incompatible.php</a></li></ul><h2>What about PHP 7.3</h2><ul><li>heredoc improvements. These seem of limited utility as we are trying to <strong>never right templates in PHP going forward.</strong></li><li>Allow a trailing comma in function calls. <em>This is a nicety.</em></li><li>array_key_first(), array_key_last() <em>I wasn't even aware that this was such a PITA in PHP, but I guess it is if want any accurate last / first index without affecting the internal array pointer.< /em></li > <li>Enhancements to Argon2 password hashing.< /li></ul > <h2>Summary < /h2><p><em>If we want to support as many hosts for open source as possible:</em > </p><p>Unless we want to use LibSodium or the more limited implementation of Argon2 password hashing in PHP 7.2 I think upping the requirement to PHP 7.1 and then jumping directly to PHP 7.3 next year.</p > <p><em>If we don't care about the 20% gap in hosting that supports 7.1 and not 7.2</em></p><p>We jump to 7.2 and reconsider when 7.4/8.0 is out.</p>
`;

export const dummyTestPost2 = `
<p>Our current&nbsp;<a href="https://github.com/vanilla/standards" rel="nofollow">coding standard</a>&nbsp;is largely unused.</p><p>It seems that:</p><ul><li>No one was running it locally (in PHPStorm or from the command line)</li><li>Our installation docs don't mention the years old version of CodeSniffer that is actually required to run our Sniffs and say to install it with PEAR.&nbsp;<a href="https://docs.vanillaforums.com/developer/contributing/coding-standard-php/#validating-your-php-code" rel="nofollow"><em>I've updated the docs for this</em></a></li><li>We don't run it CI&nbsp;<a href="https://github.com/vanilla/vanilla/issues/7393" rel="nofollow">Issue here</a></li></ul><p>All of these things in mind there was nothing actually validating our new code. This isn't exactly the path towards enforcing a coding standard.</p><p>Part of the reason no one was running it locally besides the installation instructions was that the standards were way to strict in certain areas. Old code had so many violations that it seemed impossible to even work with it on.</p><h2>Analysis</h2><p>I've including a log of the phpcs issues with our DiscussionController:</p><div class="spoiler"><div contenteditable="false" class="spoiler-buttonContainer">
<button class="iconButton button-spoiler js-toggleSpoiler">
<span class="spoiler-warning">
<span class="spoiler-warningMain">
<svg class="icon spoiler-icon" viewBox="0 0 24 24">
<title>Spoiler</title>
<path d="M8.138,16.569l.606-.606a6.677,6.677,0,0,0,1.108.562,5.952,5.952,0,0,0,2.674.393,7.935,7.935,0,0,0,1.008-.2,11.556,11.556,0,0,0,5.7-4.641.286.286,0,0,0-.02-.345c-.039-.05-.077-.123-.116-.173a14.572,14.572,0,0,0-2.917-3.035l.6-.6a15.062,15.062,0,0,1,2.857,3.028,1.62,1.62,0,0,0,.154.245,1.518,1.518,0,0,1,.02,1.5,12.245,12.245,0,0,1-6.065,4.911,6.307,6.307,0,0,1-1.106.22,4.518,4.518,0,0,1-.581.025,6.655,6.655,0,0,1-2.383-.466A8.023,8.023,0,0,1,8.138,16.569Zm-.824-.59a14.661,14.661,0,0,1-2.965-3.112,1.424,1.424,0,0,1,0-1.867A13.69,13.69,0,0,1,8.863,6.851a6.31,6.31,0,0,1,6.532.123c.191.112.381.231.568.356l-.621.621c-.092-.058-.184-.114-.277-.168a5.945,5.945,0,0,0-3.081-.909,6.007,6.007,0,0,0-2.868.786,13.127,13.127,0,0,0-4.263,3.929c-.214.271-.214.343,0,.639a13.845,13.845,0,0,0,3.059,3.153ZM13.9,9.4l-.618.618a2.542,2.542,0,0,0-3.475,3.475l-.61.61A3.381,3.381,0,0,1,12,8.822,3.4,3.4,0,0,1,13.9,9.4Zm.74.674a3.3,3.3,0,0,1,.748,2.138,3.382,3.382,0,0,1-5.515,2.629l.6-.6a2.542,2.542,0,0,0,3.559-3.559Zm-3.146,3.146L13.008,11.7a1.129,1.129,0,0,1-1.516,1.516Zm-.6-.811a1.061,1.061,0,0,1-.018-.2A1.129,1.129,0,0,1,12,11.079a1.164,1.164,0,0,1,.2.017Z" style="currentColor"></path>
<polygon points="19.146 4.146 19.854 4.854 4.854 19.854 4.146 19.146 19.146 4.146" style="currentColor"></polygon>
</svg>
<strong class="spoiler-warningBefore">
Spoiler Warning
</strong>
</span>
<span class="spoiler-chevron">
<svg class="icon spoiler-chevronUp" viewBox="0 0 20 20">
<title>▲</title>
<path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(-90 9.857 10.429)"></path>
</svg>
<svg class="icon spoiler-chevronDown" viewBox="0 0 20 20">
<title>▼</title>
<path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(90 9.857 10.429)"></path>
</svg>
</span>
</span>
</button></div><div class="spoiler-content"><p class="spoiler-line">1 | ERROR | There must be a single blank line after the last</p><p class="spoiler-line"> | | param tag</p><p class="spoiler-line">17 | WARNING | Property name "$Uses" should be camelCase</p><p class="spoiler-line">20 | WARNING | Property name "$CategoryID" should be camelCase</p><p class="spoiler-line">23 | WARNING | Property name "$CommentModel" should be camelCase</p><p class="spoiler-line">26 | WARNING | Property name "$DiscussionModel" should be</p><p class="spoiler-line">| | camelCase</p><p class="spoiler-line">28 | ERROR | Doc comment for parameter "$name" missing</p><p class="spoiler-line">28 | ERROR | Doc comment short description must be on the first</p><p class="spoiler-line">| | line</p><p class="spoiler-line">31 | ERROR | There must be exactly one blank line before the</p><p class="spoiler-line">| | first tag in a doc comment</p><p class="spoiler-line">31 | ERROR | There must be a single blank line after the last</p><p class="spoiler-line">| | param tag</p><p class="spoiler-line">31 | ERROR | Missing parameter name</p><p class="spoiler-line">33 | ERROR | Comment missing for&nbsp;<a href="https://staff.vanillaforums.com/profile/throws" rel="nofollow">@throws</a>&nbsp;tag in function comment</p><p class="spoiler-line">54 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">261 | WARNING | Line exceeds 150 characters; contains 151</p><p class="spoiler-line">| | characters</p><p class="spoiler-line">294 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">312 | WARNING | Line exceeds 150 characters; contains 163</p><p class="spoiler-line">| | characters</p><p class="spoiler-line">333 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">348 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">379 | ERROR | Superfluous parameter comment</p><p class="spoiler-line">380 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">420 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">476 | WARNING | Line exceeds 150 characters; contains 152</p><p class="spoiler-line">| | characters</p><p class="spoiler-line">512 | ERROR | Doc comment for parameter "$target" missing</p><p class="spoiler-line">524 | ERROR | Doc comment for parameter $TransientKey does not</p><p class="spoiler-line">| | match actual variable name $target</p><p class="spoiler-line">525 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">564 | ERROR | Doc comment for parameter "$discussion" missing</p><p class="spoiler-line">564 | ERROR | Doc comment short description must be on the first</p><p class="spoiler-line">| | line</p><p class="spoiler-line">567 | ERROR | There must be exactly one blank line before the</p><p class="spoiler-line">| | first tag in a doc comment</p><p class="spoiler-line">567 | ERROR | There must be a single blank line after the last</p><p class="spoiler-line">| | param tag</p><p class="spoiler-line">567 | ERROR | Missing parameter name</p><p class="spoiler-line">568 | ERROR | Comment missing for&nbsp;<a href="https://staff.vanillaforums.com/profile/throws" rel="nofollow">@throws</a>&nbsp;tag in function comment</p><p class="spoiler-line">569 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">572 | ERROR | Line exceeds maximum limit of 180 characters;</p><p class="spoiler-line">| | contains 197 characters</p><p class="spoiler-line">575 | ERROR | Doc comment for parameter "$from" missing</p><p class="spoiler-line">587 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">620 | ERROR | Doc comment for parameter "$From" missing</p><p class="spoiler-line">632 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">674 | ERROR | Doc comment for parameter "$target" missing</p><p class="spoiler-line">683 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">728 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">786 | ERROR | Missing parameter comment</p><p class="spoiler-line">787 | ERROR | Missing parameter comment</p><p class="spoiler-line">788 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">924 | WARNING | Line exceeds 150 characters; contains 176</p><p class="spoiler-line">| | characters</p><p class="spoiler-line">925 | WARNING | Line exceeds 150 characters; contains 173</p><p class="spoiler-line">| | characters</p><p class="spoiler-line">987 | ERROR | Class comment short description must be on a single</p><p class="spoiler-line">| | line</p><p class="spoiler-line">989 | ERROR | Missing parameter comment</p><p class="spoiler-line">990 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">999 | ERROR | Class comment short description must be on a single</p><p class="spoiler-line">| | line</p><p class="spoiler-line">1001 | ERROR | Missing parameter comment</p><p class="spoiler-line">1002 | ERROR | Missing&nbsp;<a href="https://staff.vanillaforums.com/profile/return" rel="nofollow">@return</a>&nbsp;tag in function comment</p><p class="spoiler-line">1042 | WARNING | Method name "_setOpenGraph" should not be prefixed</p><p class="spoiler-line">| | with an underscore to indicate visibility</p><p class="spoiler-line">1042 | ERROR | Missing function doc comment</p></div></div><p>Spoiler</p><ul><li>90% of these have to do with comment validation</li><li>A couple are for variable naming</li><li>A couple are for line length (More than 150 characters?)</li><li>There are none for the various unused variables</li><li>There are none for dynamically created class properties</li></ul><p>In summary all of our warnings here are for cosmetic fixes (that aren't actually inline with our coding standard), but there is nothing picking up the potential bugs.</p><h2>Fixes</h2><p>I'm proposing a few changes the standard to relax this.</p><h3>Comments</h3><p>Our standard says:</p><div class="blockquote"><div class="blockquote-content"><p class="blockquote-line">Descriptions MUST BE a full sentence with a capital to start and period to end</p><p class="blockquote-line">Short, return and parameter descriptions MUST be included if present</p><p class="blockquote-line">There MUST be one space before and after parameters</p></div></div><p>Currently our CodeSniffer setup requires that in addition to this:</p><ul><li>The first line of the description can only be 1 line.</li><li>A long description MAY come after</li><li>Param annotations are REQUIRED for every parameter</li><li>A return annotation is REQUIRED no matter what.</li><li>There must be empty comment lines between [short description, long description, params, return].</li><li>You are not allowed to use the&nbsp;<a href="https://staff.vanillaforums.com/profile/see" rel="nofollow">@see</a>&nbsp;tag in a class comment. What is the purpose of this?</li></ul><p>Removing validation of all of these things that are not outlined in our standard would remove a lot of our CodeSniffer validation errors, because almost none of our code does these things. We do generate documentation from our comments, so param annotations are sometimes helpful but not always necessary, and info about returns is generally available in the method name.</p><p>Additionally:</p><ul><li>An&nbsp;<code class="code codeInline" spellcheck="false">@inheritdoc</code>&nbsp;statement should void all other validation of the comment.</li></ul><h3>Line length</h3><p>From our standard</p><div class="blockquote"><div class="blockquote-content"><p class="blockquote-line">There MUST NOT be a hard limit on line length; the soft limit MUST be 120 characters; lines SHOULD be 80 characters or less.</p><p class="blockquote-line">I actually am having trouble parsing this, but I guess this means we shouldn't actually validate line length?</p><p class="blockquote-line">Either way what we currently enforce (150 characters) in code sniffer is ridiculous.</p></div></div><h3>Missing validation</h3><p>A bit of missing validation that would go a long way:</p><ul><li>Warnings for high cyclomatic complexity in a function</li><li>Warnings for excessive method length</li><li>Warnings for excessive amounts of parameters (more than 5 is lot!)</li><li>Unused private fields, local variables, and private methods.</li><li>Trailing commas in multiline arrays!!!</li></ul><p>There are built in code sniffer sniffs for all of these.</p><code class="code codeBlock" spellcheck="false">&lt;rule ref="rulesets/codesize.xml/CyclomaticComplexity"/&gt;
&lt;rule ref="rulesets/codesize.xml/ExcessiveMethodLength"/&gt;
&lt;rule ref="rulesets/codesize.xml/ExcessiveParameterList"/&gt;
&lt;rule ref="rulesets/unusedcode.xml/UnusedPrivateField"/&gt;
&lt;rule ref="rulesets/unusedcode.xml/UnusedLocalVariable"/&gt;
&lt;rule ref="rulesets/unusedcode.xml/UnusedPrivateMethod"/&gt;
</code>
`;

export const dummyPostEverything = `
<h1>Inline operations</h1><p>Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind's eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there's little good evidence Orion's sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxyrise the sky calls to us! Descended from astronomers?</p><p><code class="code codeInline" spellcheck="false">Code Inline</code></p><p><strong>Bold</strong></p><p><em>italic</em></p><p><strong><em>bold italic</em></strong></p><p><strong><em><s>bold italic strike</s></em></strong></p><p><a href="http://test.com" rel="nofollow"><strong><em><s>bold italic strike link</s></em></strong></a></p><p>Some text with a mention in it <a class="atMention" data-username="Alex Other Name" data-userid="23" href="http://vanillafactory.spawn/profile/Alex%20Other%20Name">@Alex Other Name</a> Another mention <a class="atMention" data-username="System" data-userid="1" href="http://vanillafactory.spawn/profile/System">@System</a>.</p><p>Some text with emojis<span class="safeEmoji nativeEmoji">🤗</span><span class="safeEmoji nativeEmoji">🤔</span><span class="safeEmoji nativeEmoji">🤣</span>.</p><hr><h1>Block operations</h1><p>Block operations H1 Title here. Code Block next.<br></p><code class="code codeBlock" spellcheck="false">/**
 *adds locale data to the view, and adds a respond button to the discussion page.
 */
class MyThemeNameThemeHooks extends Gdn_Plugin {

    /**
     * Fetches the current locale and sets the data for the theme view.
     * Render the locale in a smarty template using {$locale}
     *
     * @param  Controller $sender The sending controller object.
     */
    public function base_render_beforebase_render_beforebase_render_beforebase_render_beforebase_render_before($sender) {
        // Bail out if we're in the dashboard
        if (inSection('Dashboard')) {
            return;
        }

        // Fetch the currently enabled locale (en by default)
        $locale = Gdn::locale()-&gt;current();
        $sender-&gt;setData('locale', $locale);
    }
}
</code><p><br></p><h2>H2 Here. Spoiler next</h2><div class="spoiler"><div contenteditable="false" class="spoiler-buttonContainer">
<button class="iconButton button-spoiler js-toggleSpoiler">
    <span class="spoiler-warning">
        <span class="spoiler-warningMain">
            <svg class="icon spoiler-icon" viewBox="0 0 24 24">
                <title>Spoiler</title>
                <path d="M8.138,16.569l.606-.606a6.677,6.677,0,0,0,1.108.562,5.952,5.952,0,0,0,2.674.393,7.935,7.935,0,0,0,1.008-.2,11.556,11.556,0,0,0,5.7-4.641.286.286,0,0,0-.02-.345c-.039-.05-.077-.123-.116-.173a14.572,14.572,0,0,0-2.917-3.035l.6-.6a15.062,15.062,0,0,1,2.857,3.028,1.62,1.62,0,0,0,.154.245,1.518,1.518,0,0,1,.02,1.5,12.245,12.245,0,0,1-6.065,4.911,6.307,6.307,0,0,1-1.106.22,4.518,4.518,0,0,1-.581.025,6.655,6.655,0,0,1-2.383-.466A8.023,8.023,0,0,1,8.138,16.569Zm-.824-.59a14.661,14.661,0,0,1-2.965-3.112,1.424,1.424,0,0,1,0-1.867A13.69,13.69,0,0,1,8.863,6.851a6.31,6.31,0,0,1,6.532.123c.191.112.381.231.568.356l-.621.621c-.092-.058-.184-.114-.277-.168a5.945,5.945,0,0,0-3.081-.909,6.007,6.007,0,0,0-2.868.786,13.127,13.127,0,0,0-4.263,3.929c-.214.271-.214.343,0,.639a13.845,13.845,0,0,0,3.059,3.153ZM13.9,9.4l-.618.618a2.542,2.542,0,0,0-3.475,3.475l-.61.61A3.381,3.381,0,0,1,12,8.822,3.4,3.4,0,0,1,13.9,9.4Zm.74.674a3.3,3.3,0,0,1,.748,2.138,3.382,3.382,0,0,1-5.515,2.629l.6-.6a2.542,2.542,0,0,0,3.559-3.559Zm-3.146,3.146L13.008,11.7a1.129,1.129,0,0,1-1.516,1.516Zm-.6-.811a1.061,1.061,0,0,1-.018-.2A1.129,1.129,0,0,1,12,11.079a1.164,1.164,0,0,1,.2.017Z" style="currentColor"></path>
                <polygon points="19.146 4.146 19.854 4.854 4.854 19.854 4.146 19.146 19.146 4.146" style="currentColor"></polygon>
            </svg>
            <strong class="spoiler-warningBefore">
                Spoiler Warning
            </strong>
        </span>
        <span class="spoiler-chevron">
    <svg class="icon spoiler-chevronUp" viewBox="0 0 20 20">
        <title>▲</title>
        <path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(-90 9.857 10.429)"></path>
    </svg>
    <svg class="icon spoiler-chevronDown" viewBox="0 0 20 20">
        <title>▼</title>
        <path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(90 9.857 10.429)"></path>
    </svg>
</span>
    </span>
</button></div><div class="spoiler-content"><p class="spoiler-line">Some Spoiler content with formatting <strong>bold</strong> <em>italic </em><s>strike</s></p><p class="spoiler-line"><br></p><p class="spoiler-line"><br></p><p></p><p class="spoiler-line">Newlines above <a href="unsafe:test link" rel="nofollow">Link</a></p><p class="spoiler-line">Another line</p></div></div><p><br></p><p>A blockquote will be next.</p><p><br></p><p></p><div class="blockquote"><div class="blockquote-content"><p class="blockquote-line">Some Block quote content<strong>bold</strong> <em>italic </em><s>strike</s></p><p class="blockquote-line"><s>More blockquote content</s></p></div></div><p></p><p><br></p><p></p><p>Unordered List</p><ul><li>Line 1</li><li>Line 2 (2 empty list items after this)</li><li><br></li><li><br></li><li>Line 5 item with <strong>bold and a </strong><a href="https://vanillaforums.com" rel="nofollow"><strong>link</strong></a><strong>.</strong></li><li>Line 6 item with an emoji<span class="safeEmoji nativeEmoji">😉</span>.</li></ul><p>Ordered List</p><ol><li>Number 1</li><li>Number 2</li><li>Number 3 (Empty line below)</li><li><br></li><li>Number 5 with <strong>bold and a </strong><a href="https://vanillaforums.com/" rel="nofollow"><strong>link</strong></a><strong>.</strong></li></ol><p><br></p><hr><h1>Embed operations</h1><h2>Imgur:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedImgur">
    <div class="embedExternal-content">
        <blockquote class="imgur-embed-pub" lang="en" data-id="arP2Otg"><a href="https://imgur.com/arP2Otg"></a></blockquote>
    </div>
</div></div><h2>Image:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedImage">
    <div class="embedExternal-content">
        <a class="embedImage-link" href="http://www.worldoceansday.org/_assets/css/images/events/8075118_2_IMG_1262CoastOceanSky.jpg" rel="nofollow noopener" target="_blank">
            <img class="embedImage-img" src="http://www.worldoceansday.org/_assets/css/images/events/8075118_2_IMG_1262CoastOceanSky.jpg">
        </a>
    </div>
</div></div><h2>Twitter:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedTwitter">
    <div class="embedExternal-content js-twitterCard" data-tweeturl="https://twitter.com/hootsuite/status/1009883861617135617" data-tweetid="1009883861617135617">
        <a href="https://twitter.com/hootsuite/status/1009883861617135617" class="tweet-url" rel="nofollow">https://twitter.com/hootsuite/status/1009883861617135617</a>
    </div>
</div></div><h2>Getty:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedGetty">
    <div class="embedExternal-content">
        <a class="embedExternal-content gie-single js-gettyEmbed" href="//www.gettyimages.com/detail/810147408" id="VPkxdgtCQFx-rEo96WtR_g" data-height="345" data-width="498" data-sig="Mb27fqjaYbaPPFANi1BffcYTEvCcNHg0My7qzCNDSHo=" data-items="810147408" data-capt="false" data-tld="com" data-i360="false">
            https://www.gettyimages.ca/detail/photo/explosion-of-a-cloud-of-powder-of-particles-of-royalty-free-image/810147408
        </a>
    </div>
</div></div><h2>Vimeo:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio" style="padding-top: 42.5%;">
            <button type="button" data-url="https://player.vimeo.com/video/264197456?autoplay=1" aria-label="Vimeo" class="embedVideo-playButton iconButton js-playVideo" title="Vimeo">
                <img class="embedVideo-thumbnail" src="https://i.vimeocdn.com/video/694532899_640.jpg" role="presentation" alt="A thumnail preview of a video">
                <span class="videoEmbed-scrim">
                <svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
    <title>Play Video</title>
    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"></path>
    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"></polygon>
</svg>
            </span></button>
        </div>
    </div>
</div></div><h2>Youtube:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio" style="padding-top: 74.945533769063%;">
            <button type="button" data-url="https://www.youtube.com/embed/fy0fTFpqT48?feature=oembed&amp;autoplay=1&amp;start=2" aria-label="Attack of the Killer Tomatoes - Trailer" class="embedVideo-playButton iconButton js-playVideo" title="Attack of the Killer Tomatoes - Trailer">
                <img class="embedVideo-thumbnail" src="https://img.youtube.com/vi/fy0fTFpqT48/0.jpg" role="presentation" alt="A thumnail preview of a video">
                <span class="videoEmbed-scrim">
                <svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
    <title>Play Video</title>
    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"></path>
    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"></polygon>
</svg>
            </span></button>
        </div>
    </div>
</div></div><h2>Instagram:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedInstagram">
    <div class="embedExternal-content">
        <blockquote class="instagram-media" data-instgrm-captioned="" data-instgrm-permalink="https://www.instagram.com/p/BTjnolqg4po" data-instgrm-version="8">
            <a href="https://www.instagram.com/p/BTjnolqg4po/?taken-by=vanillaforums">https://www.instagram.com/p/BTjnolqg4po/?taken-by=vanillaforums</a>
        </blockquote>
    </div>
</div></div><h2>Soundcloud:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedSoundCloud">
    <div class="embedExternal-content">
        <iframe width="100%" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/330864225&amp;show_artwork=true&amp;visual=true">
        </iframe>
    </div>
</div></div><h2>Giphy:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedGiphy">
    <div class="embedExternal-content" style="width: 720px">
        <div class="embedExternal-ratio" style="padding-bottom: 100%">
            <iframe class="giphy-embed embedGiphy-iframe" src="https://giphy.com/embed/JIX9t2j0ZTN9S"></iframe>
        </div>
    </div>
</div></div><h2>Twitch:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio" style="padding-top: 56.2%;">
            <button type="button" data-url="https://player.twitch.tv/?video=v277077149" aria-label="SamedWii Zeldaérobic" class="embedVideo-playButton iconButton js-playVideo" title="SamedWii Zeldaérobic">
                <img class="embedVideo-thumbnail" src="https://static-cdn.jtvnw.net/s3_vods/9e05228597e840e180f3_hoopyjv_29218011904_894795907/thumb/thumb0-640x360.jpg" role="presentation" alt="A thumnail preview of a video">
                <span class="videoEmbed-scrim">
                <svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
    <title>Play Video</title>
    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"></path>
    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"></polygon>
</svg>
            </span></button>
        </div>
    </div>
</div></div><h2>External No Image</h2><div class="js-embed embedResponsive"><div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://www.google.ca/search?q=typing+google+in+google" rel="noopener noreferrer">
            <article class="embedText-body">

                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">typing google in google - Google Search</h3>

                        <span class="embedLink-source meta">https://www.google.ca/search?q=typing+google+in+google</span>
                    </div>
                    <div class="embedLink-excerpt">typing google into google meme</div>
                </div>
            </article>
        </a>
    </div>
</div></div><h2>Exernal With Image</h2><div class="js-embed embedResponsive"><div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://vanillaforums.com/en/" rel="noopener noreferrer">
            <article class="embedText-body">
                <img src="https://vanillaforums.com/images/metaIcons/vanillaForums.png" class="embedLink-image" aria-hidden="true">
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">Online Community Software and Customer Forum Software by Vanilla Forums</h3>

                        <span class="embedLink-source meta">https://vanillaforums.com/en/</span>
                    </div>
                    <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
                </div>
            </article>
        </a>
    </div>
</div></div><h2>Wistia:</h2><div class="js-embed embedResponsive"><div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio is16by9" style="">
            <button type="button" data-url="https://fast.wistia.net/embed/iframe/0k5h1g1chs" aria-label="Lenny Delivers a Video - oEmbed glitch" class="embedVideo-playButton iconButton js-playVideo" title="Lenny Delivers a Video - oEmbed glitch">
                <img class="embedVideo-thumbnail" src="https://embed-ssl.wistia.com/deliveries/99f3aefb8d55eef2d16583886f610ebedd1c6734.jpg?image_crop_resized=960x540" role="presentation" alt="A thumnail preview of a video">
                <span class="videoEmbed-scrim">
                <svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
    <title>Play Video</title>
    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"></path>
    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"></polygon>
</svg>
            </span></button>
        </div>
    </div>
</div></div><p></p><div class="js-embed embedResponsive"><div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio is16by9" style="">
            <button type="button" data-url="https://fast.wistia.net/embed/iframe/vjidqnyg0a" aria-label="Borrowed video: Welcome to Wistia!" class="embedVideo-playButton iconButton js-playVideo" title="Borrowed video: Welcome to Wistia!">
                <img class="embedVideo-thumbnail" src="https://embed-ssl.wistia.com/deliveries/1e7b480521adb0d8cc29dbd388faa14eb7c99d21.jpg?image_crop_resized=960x540" role="presentation" alt="A thumnail preview of a video">
                <span class="videoEmbed-scrim">
                <svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
    <title>Play Video</title>
    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"></path>
    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"></polygon>
</svg>
            </span></button>
        </div>
    </div>
</div></div><p></p><p><br></p>`;
