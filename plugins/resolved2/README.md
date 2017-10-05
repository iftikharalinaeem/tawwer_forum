Requirements:

- User must have a role that includes the staff (`Garden.Staff.Allow`) permission in Roles & Permissions.

Features:

- Add discussion option (in menu) to "Resolve" a discussion.
- New comment by staff automatically marks discussion as resolved.
- New comments by non-staff will make the discussion not resolved.
- Create a list of discussions on `/discussion/unresolved` that are not resolved.
- Display an indicator that state is a discussion is Resolved or Not.
- There is a configuration `Resolved2.DiscussionTitle.DisplayResolved` to prevent `[RESOLVED]` from being prepended to the discussion title when viewing the title from inside the discussion.

Interactions with Analytics:

- Only first "resolved" action will be tracked.
- Discussions created while the plugin was not active will not be counted in analytics.
