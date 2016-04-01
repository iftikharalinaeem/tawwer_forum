# Purpose
This add-on attempts to facilitate the capture of user access and usage data, interpret it and display the trends in a way that is helpful and easy for a user to understand.

# How To Use
The plug-in currently only captures data, so there's not a lot of setup involved.  You'll need to make sure your forum configuration has some values set before you enable the plug-in.  If you don't have them, you won't be able to track the data.

* `VanillaAnalytics.DisableTracking` Stops tracking all events.
* `VanillaAnalytics.KeenIO.OrgKey` Organization-level API key. *Required for keen.io project provisioning.*
* `VanillaAnalytics.KeenIO.OrgID` Unique identifier for a keen.io organization. *Required for keen.io project provisioning.*
* `VanillaAnalytics.KeenIO.DefaultProjectUser` E-mail address associated with a keen.io user having access to the configured organization. *Required for keen.io project provisioning.*
* `VanillaAnalytics.KeenIO.ProjectID` Unique identifier for a keen.io project *Required for keen.io event tracking.*
* `VanillaAnalytics.KeenIO.ReadKey` Scoped read API key for the configured project. *Required for keen.io charts and reporting.*
* `VanillaAnalytics.KeenIO.WriteKey` Scoped write key for the configured project. *Required for keen.io event tracking.*

If `VanillaAnalytics.KeenIO.ProjectID` is not set when the plug-in is enabled, it will attempt to automatically provision one.  This will only be possible with the project provisioning requirements cited above.  If `VanillaAnalytics.KeenIO.ProjectID` is set, no attempt will be made to create a new project and the project provisioning requirements are not necessary for the site.

# Available Widgets
* total-active-users
* total-pageviews
* total-unique-pageviews
* total-discussions
* total-comments
* total-contributors
* pageviews
* active-users
* unique-pageviews
* unique-visits-by-role-type
* discussions
* comments
* posts
* posts-by-type
* posts-by-category
* posts-by-role-type
* contributors
* contributors-by-category
* contributors-by-role-type
* posts-per-user

# Additional Config Settings
* `VanillaAnalytics.DisableDashboard` Don't display links to access analytics data in Vanilla's dashboard.
* `VanillaAnalytics.Widget.*` Enable an analytics widget.  The last portion of the config should be one of the widget slugs.  Some widgets are included by default (e.g. Discussions, Comments, Page Views and all metrics).

# Notes
* Pageviews rely on the `gdn.stats` JavaScript function being triggered and an [event listener](https://github.com/vanilla/vanilla/pull/3503) being utilized by a service tracker's JavaScript. `gdn.stats` is fired if `gdn.meta.AnalyticsTask` is set to `tick`.  This should happen by default, unless you're testing from a local environment.  If testing locally, you'll need to enable `Garden.Analytics.AllowLocal` in your config.  If you have `Garden.Analytics.Enabled` disabled in your config, this will also block `gdn.stats` from firing.
