## MailChimp Push Plugin

This plugin allows forum admins to connect their forum to MailChimp.

### Features

 * Send user information to MailChimp when a user signs up or updates his/her user information.
 * Allow admins to designate a list configured on MailChimp to be assigned to the user when sent to MailChimp.
 * Allow admins to assign users to groups (a form of tagging on MailChimp) when storing on MailChimp.
 * Allow admins to synchronize all their users to MailChimp in one mass migration, assigning them to a list and group.

### How it works

MailChimp allows its users to create lists to store users in. They also allow you to add your users to a group within that list. [Learn more about creating groups on MailChimp](http://kb.mailchimp.com/lists/groups/create-a-new-list-group)

This plugin queries the MailChimp account associated with the provided API key to get lists and corresponding groups associated with that list. Note that MailChimp's API calls groups "Interests".

Currently, you can only choose one group to associate Vanilla imports with. For example, let's say I have a list on MailChimp: "My Newsletter" and add two group categories: "User Interests" with the groups "Puppies", and "Kittens", and "User Origin" with the groups "My Forum" and "My Store". On Vanilla, you can choose at most one of "Puppies", "Kittens", "My Forum" or "My Online Store" to add your users to.

There are two controllers in the plugin settings: `controller_index` and `controller_sync`. 

Controller_index is used to save the MailChimp API key and to designate the default lists and interests to the UserAuthenticationProvider table.

Controller_sync is used to push bulk lists of users from the User table to MailChimp

### Gotchas and Pitfalls

When adding users with the opt-in ("Send confirmation email?" checked in the dashboard) the user will not show up at all in the MailChimp dashboard until the user has confirmed.

When uploading users to MailChimp, a list is sent that then sits in a queue on MailChimp servers as a batch to be processed. Especially when there is a big list, there could be some latency. I created methods (`controller_trackbatches` and `getBatchStatus`) for tracking the progress of batches but elected not to implement them.

Turn on db tracking to debug. Bulk lists are truncated to 10 users when stored in the debugger to save space.
