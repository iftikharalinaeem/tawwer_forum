A Terrible Login System
=======================

This is a dummy, non-Vanilla login system written in PHP. It should never be used for anything because it is _terrible_. In fact, it is an encyclopedia of worst practices.

The only thing it is good for is __building SSO connections__! Symlink this bad boy into a web directory, then grab the [jsConnectPHP library](https://github.com/vanilla/jsConnectPHP) and roll your own SSO connection to Vanilla for practice, for debugging, or for testing pull requests.

Using this mess
---------------

1. Make a new terrible database.
1. Copy `config.default.php` as `config.php` and fill in the terrible database info.
1. Run `setup.php` to build your terrible content.
1. Try `login.php` to see it works terribly (users below).
1. Use `register.php` to make more terrible users.


### Default Users / Passwords

* `venkman` / `cats&dogs`
* `ray` / `staypuft`
* `egon` / `crossthestreams`
* `winston` / `yousayYES!`
* `janine` / `wegotalive1`

### // YOUR CODE HERE.

Copy the jsConnectPHP `index.php` file as `sso.php` into this folder (that name is gitignore'd). Copy over its functions file as-is. Then in your new `sso.php`, you could then use something like this:

	<?php
	include_once('config.php');
	include_once('functions.php');
	
	$Name = GetLogin();
	if ($Name) {
	   $StoredUser = GetUser($Name); // array to map to jsConnect $user array below
	}
	// the rest is up to you, grasshopper.

Need help? [Try the docs](http://blog.vanillaforums.com/jsconnect-technical-documentation/).

### Next step: Embed!

Try embedding a forum with [automatic SSO](http://blog.vanillaforums.com/jsconnect-technical-documentation-for-embedded-sso/). Copy `embed.default.html` as `embed.html` to get started (it's in gitignore so you don't accidentally overwrite the template).	

Requirements
------------

1. Uh, mysqli and PHP 5.3 or something.
2. A suppressed urge to puke at terrible code.

That's pretty much it. _So_ terribly sorry.
