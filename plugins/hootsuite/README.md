# Hootsuite Plug-in

Hootsuite is a platform for managing social media. It allows its users to include apps in their dashboards. These apps, iframe-based panels, allow users to monitor several of their online services in a centralized location. Vanilla needs a plug-in built to act as an interface for such a widget.

## Requirements

### Discussion Stream

* Allow Hootsuite users to view recent discussions on a forum.
* The results should be paginated with infinite scroll. As the user moves down the list, more discussions are loaded in.
* Show recent discussions, ordered by latest activity.
* Display discussion title and user picture.

### Add Discussions

* Allow Hootsuite users to create new discussions.
* Title, category and body should be available form controls.

### View Discussion

* Allow Hootsuite users to view a specific discussion and all its comments.
* Full discussion body and title should be at the top of the single discussion view.
* Comments should be paginated with infinite scroll. As the user moves down the list, more records are loaded in.
* Display user photos alongside usernames for posts.

### Add Comments

* Allow Hootsuite users to add comments to a discussion when viewing a single thread.
* Comment body text field and submit button should be in the available form controls.
* Comments will be posted to the currently-viewed conversation.

### Conversations stream

* Allow Hootsuite users to view a list of their conversations.
* Clicking on a conversation will take the user to a page that displays all messages associated with that conversation.

### Reply to conversations

* Allow Hootsuite users to respond to their conversations when viewing a single message thread.
* A message body text field and a submit button should be in the available form controls.
* Messages will be posted to the currently-viewed conversation.

### Search

* Allow Hootsuite users to search discussions and comments for the Vanilla site.
* Results should be paginated with infinite scroll. As the user moves down the list, more results are loaded in.

### Assign a Post to a Hootsuite User

* Allow Hootsuite users to assign posts to other Hootsuite users. This is part of Hootsuite's Assignments functionality.
* Assigned posts will show up in the target user's Hootsuite dashboard.

### Resolve an Assigned Post

* Allow Hootsuite users to resolve Vanilla posts assigned to them in Hootsuite's Assignments from Vanilla.

### Share to Social Media

* Allow Hootsuite users to share posts on social media with the Share to Social Networks dialog in the Hootsuite dashboard.

## Solution

### ReactJS

React (ReactJS) is a JavaScript library for building user interfaces. It will be used as the foundation for UI of this application.

Read more: https://facebook.github.io/react/docs

### Vanilla API v2

The second version of Vanilla's API will provide all data necessary to drive the application. Endpoints for viewing discussions, comments, users, conversations and conversation messages will be required. In addition, endpoints for adding discussions, comments and conversation messages will be required.

### Hootsuite JS API v2

Hootsuite provides a JavaScript-driven API to facilitate communication between the app panel iframe and Hootsuite dashboard window. All communication between Vanilla and Hootsuite should be performed through this SDK.

Read more: https://hootsuite.com/developers/app-directory/docs/api

## Authentication

Hootsuite provides basic URL parameters as a means to aid verification the user viewing the panel is a valid Hootsuite user. Associating the Hootsuite user ID received during this process with a Vanilla account will require additional connectivity logic. Proposed options:

1. Prompt new users to connect to an existing Vanilla account or create a new one.
1. Implement per-user API keys. Users will enter these when connecting to establish their identity.

## Design

The app panel should run as a single-page application. Vanilla theming should be excluded from the contents of this panel. The aesthetic of the app panel should standard across all Vanilla sites.

UI and design asset requirements and recommendations for Hootsuite apps are available here: https://hootsuite.com/developers/app-directory/best-practices
