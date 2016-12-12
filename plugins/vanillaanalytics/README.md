# Overview

## Purpose

Facilitate the capture of user access and usage data, interpret it and display the trends in a way that is helpful and easy for a user to understand.

## Features

1. Data collection via an analytics backend, currently keen.io.
2. Dashboard widget collection pages for "Traffic" and "Posting" data widgets.
3. Bookmarking widgets for a personalized analytics dashboard.
4. Rationing widgets by cloud plan level.
5. Toolbar for drilling down by date range, interval (monthly/daily/hourly), and meta data (e.g. category or role).

# Glossary

1. **Page view**: Opening a single page / one click on a site.
1. **Visit**: Multiple page views and actions between periods of inactivity.
1. **Post**: A discussion or comment.
1. **User**: An individual signed in to their forum account.
1. **Active User**: A user who has visited & viewed posts (within the timeframe).
1. **Participant**: A user who has made a post (within the timeframe).
1. **Role Type**: Some roles are assigned default role types, including: Administrator, Moderator, Default, and others.


# Setup

You'll need to make sure your forum configuration has some values set before you enable the plug-in.  If you don't have them, you won't be able to track the data.

## Required settings

* `VanillaAnalytics.KeenIO.OrgKey` Organization-level API key. *Required for keen.io project provisioning.*
* `VanillaAnalytics.KeenIO.OrgID` Unique identifier for a keen.io organization. *Required for keen.io project provisioning.*
* `VanillaAnalytics.KeenIO.DefaultProjectUser` E-mail address associated with a keen.io user having access to the configured organization. *Required for keen.io project provisioning.*
* `VanillaAnalytics.KeenIO.ProjectID` Unique identifier for a keen.io project *Required for keen.io event tracking.*
* `VanillaAnalytics.KeenIO.ReadKey` Scoped read API key for the configured project. *Required for keen.io charts and reporting.*
* `VanillaAnalytics.KeenIO.WriteKey` Scoped write key for the configured project. *Required for keen.io event tracking.*

If `VanillaAnalytics.KeenIO.ProjectID` is not set when the plug-in is enabled, it will attempt to automatically provision one.
This will only be possible with the project provisioning requirements cited above.
If `VanillaAnalytics.KeenIO.ProjectID` is set, no attempt will be made to create a new project and the project provisioning requirements are not necessary for the site.

## Optional settings

* `VanillaAnalytics.DisableTracking` Stops tracking all events.
* `VanillaAnalytics.DisableDashboard` Don't display links to access analytics data in Vanilla's dashboard.
* `VanillaAnalytics.Widget.*` Enable an analytics widget.  The last portion of the config should be one of the widget slugs.  Some widgets are included by default (e.g. Discussions, Comments, Page Views and all metrics).
* `Garden.Analytics.AllowLocal` Off by default. Allows localhost running.
* `Garden.Analytics.Enabled` On by default. If this is set to false, analytics will be blocked.

# Widgets

When reading widgets descriptions you can always add "during/for the selected time range" at the end of the sentence.
Widgets hide themselves if they have no data to show.

There are 3 types of widgets:
- **Metric**
  - Metrics are a single information quantifying something for the selected time range.
- **Leaderboard**
  - Leaderboards are tables showing the ranking of specific items for the selected time range.
  The "previous" rankings are calculated using the rankings of the previous time range.
- **Graph**
  - Graphs can come in multiple forms! They are always information fetched using the selected time range.
    - Line, Area, Bar: Use the selected interval to group the fetched information.
    - Pie chart: Represent multiple values for the whole selected time range.

## Engagement

- Metrics
  - Posts Positivity Rate *(posts-positivity-rate)*
    - Number of posts having a positive reaction divided by the number of posts having a negative reaction.
  - Average Time to First Comment *(average-time-to-first-comment)*
    - Average amount of time it took for discussions to have their first comment.
- Leaderboards
  - Members by Accumulated Reputation *(top-member-by-accumulated-reputation)*
    - Users ordered by the highest sum of points accumulated.
  - Discussions with Most Comments *(top-commented-discussions)*
    - Discussions ordered by highest number of comments made in it.
  - Discussions with Most Positive Reactions *(top-positive-discussions)*
    - Discussions ordered by highest sum of positive reactions given to them.
  - Discussions with Most Negative Reactions *(top-negative-discussions)*
    - Discussions ordered by highest sum of negative reactions given to them.
- Graphs
  - Participation Rate *(participation-rate)*
    - Number of active (visiting) users compared to participating (posting) users. 
  - Sentiment Ratio *(sentiment-ratio)*
    - Number of posts having a positive reaction vs the number of posts having a negative reaction.
  - Visits per Active User *(visits-per-active-user)*
    - See "[visists](#visits)" divided by [active-users](#active-users).
  - Average Posts per Active User *(average-posts-per-active-user)*
    - Average number of new posts grouped by [active-users](#active-users).
  - Average Comments per Discussion *(average-comments-per-discussion)*
    - Average number of new comments grouped by [discussions](#discussions).

## Posting

- Metrics
  - Discussions *(total-discussions)*
    - Number of discussions created.
  - Comments *(total-comments)*
    - Number of comments created.
  - Contributors *(total-contributors)*
    - Number of distinct users who created a post.
- Leaderboards
  - Users with Most Posts *(top-posters)*
    - Users ordered by the highest number of post created.
  - Users with Most Discussions *(top-discussion-starters)*
    - Users ordered by the highest number of discussions created.
- Graphs
  - Discussions *(<a name="discussions">discussions</a>)*
    - Number of discussions created.
  - Comments *(comments)*
    - Number of comments created.
  - Posts *(posts)*
    - Number of posts created.
  - Posts by Type *(posts-by-type)*
    - Number of created posts grouped by posts type.
  - Posts by Category *(posts-by-category)*
    - Number of created posts grouped by categories.
  - Posts by Role Type *(posts-by-role-type)*
    - Number of created posts grouped by vanilla's predefined role types.
  - Contributors *(contributors)*
    - Number of distinct users having created a post.
  - Contributors by Category *(contributors-by-category)*
    - Number of distinct users, having created a post, grouped by categories.
  - Contributors by Role Type *(contributors-by-role-type)*
    - Number of distinct users, having created a post, grouped by vanilla's predefined role types.

## Q&A

- Metrics
  - Questions Asked *(total-asked)*
    - Number of questions asked.
  - Questions Answered *(total-answered)*
    - Number of questions having at least one answer.
  - Answers Accepted *(total-accepted)*
    - Number of answers accepted.
  - Average Time to Answer *(time-to-answer)*
    - Average amount of time it took for questions to have their first answer.
  - Average Time to Accept *(time-to-accept)*
    - Average amount of time it took for questions to have an accepted answer.
- Leaderboards
  - Questions with Most Views *(top-viewed-qna-discussions)*
    - Questions ordered by the highest number of accumulated views.
  - Users with Most Answers *(top-question-answerers)*
    - Users ordered by the highest number of answers created.
  - Users with Most Accepted Answers *(top-best-answerers)*
    - Users ordered by the highest number of answers created and then accepted as the best answer.
- Graphs
  - Questions Asked *(questions-asked)*
    - Number of questions created.
  - Questions Answered *(questions-answered)*
    - Number of questions having at least one answer.
  - Accepted Answers *(answers-accepted)*
    - Number of answers accepted.

## Traffic

- Metrics
  - Page views *(total-pageviews)*
    - Number of pages viewed.
  - Active Users *(total-active-users)*
    - See [active-users](#active-users).
  - Visits *(total-visits)*
    - See [visits](#visits).
- Leaderboards
  - Discussions with Most Views *(top-viewed-discussions)*
    - Discussions ordered by the highest number of accumulated views.
- Graphs
  - Active Users *(<a name="active-users">active-users</a>)*
    - Number of distinct users that viewed a page on the forum.
  - Visits *(<a name="visits">visits</a>)*
    - Number of distinct users that viewed a page on the forum.
  - Unique Visits by Role Type *(visits-by-role-type)*
    - Number of visits, from users, grouped by vanilla's predefined role types.
  - Page Views *(pageviews)*
    - Number of pages viewed.
  - New Users *(registrations)*
    - Number of new users.

# Notes

## Data collection mechanism

Pageviews rely on the `gdn.stats` JavaScript function being triggered and an [event listener](https://github.com/vanilla/vanilla/pull/3503) being utilized by a service tracker's JavaScript.
`gdn.stats` is fired if `gdn.meta.AnalyticsTask` is set to `tick`.  This should happen by default, unless you're testing from a local environment.

**If testing locally**, you'll need to enable `Garden.Analytics.AllowLocal` in your config.  If you have `Garden.Analytics.Enabled` disabled in your config, this will also block `gdn.stats` from firing.
