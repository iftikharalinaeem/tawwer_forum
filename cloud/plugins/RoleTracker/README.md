Role Tracker
----

Highlight and tag posts made by users in selected roles.

- **Tag:**
  - In a discussion, tag(s) appears next to comments by a users that belongs to one or more tracked role(s).
  - Every time there is a new post in a discussion, tracked roles tags are added to that discussion. Those tags show up in the discussion list.
  - RoleTracker tags will take priority over user defined tag of the same name. ie: if you have a RoleTracker tag Moderator users won't be able to tag a discussion with a tag named Moderator.

- **Tagged list:** Every tracked discussion can be viewed using the `/discussions/tagged/[RELEVANT_TAG]` endpoint.

- **Jump to post:**
  - This functionality may **bug** if there are more than **50 tracked users**.
    - *There is a config `RoleTracker.MaxTrackedUser` that can be changed to allow more than 50 tracked users to be selected but be aware that
    increasing this setting __may__ cause some scalability issues.*
  - The `/roletracker/jump` endpoint can be used to jump to tracked posts
    - `/roletracker/jump/{DiscussionID}` will jump to the first tracked post of a discussion
    - `/roletracker/jump/{DiscussionID}/{Y-M-D G:i:s}` will jump to the next tracked post from the provided date.
      - If there is no next tracked post it will jump back to the first tracked post.
