# Localligator

This plugin eases adding strings to locales by finding missing strings and providing an interface to add strings to
one of the source files. In order for this plugin to work properly, you must have the entire locales repo symlinked
into your web root, or at very least the tx-source directory should be symlinked into your locales directory in your
web root.

Works with four different configuration-type files to manage translations.

In the locales repo's tx-source:

`dash_core`: Contains translations for the Dashboard application.
`site_core`: Contains translations for everything else.

In the `conf/localligator` directory:

`ignore`: Contains the translations marked as ignore.
`untranslated`: Contains a list of translation codes we've encountered that are not in any of the above files.

Using the settings page (/settings/localligator), you can choose from any of the unstranslated strings to
automatically add to either Vanilla (`site_core`) or Dashboard (`dash_core`) or Ignore (`ignore`).

It's up to the developer to make a PR to add their strings to the locale repo tx-source files.

Future improvements to this plugin could include:
* Giving the dev the ability to manually add a definition.
* Giving the dev the ability to change a definition/default translation before adding.

---
Copyright &copy; 2016 Vanilla Forums. Licensed under the terms of the [MIT License](LICENSE.md).
