## MailChimp Push Plugin

This plugin allows forum admins to connect their forum to MailChimp.

### Features

 * Send user information to MailChimp when a user signs up or updates his/her user information.
 * Allow admins to designate a list configured on MailChimp to be assigned to the user when sent to MailChimp.
 * Allow admins to assign "interests" (a form of tagging on MailChimp) to users when storing on MailChimp.
 * Allow admins to synchronize all their users to MailChimp in one mass migration, assigning them to a list and assigning insterests.
 
### How it works

MailChimp allows its users to create lists to store users in. They also allow you to "tag" your users with "intetersts". An "interest" has to be in category.

This plugin queries the admin's MailChimp account to get lists, categories and interests. Only the interests are displayed since you cannot tag a user with a category, only an interest. 

There are two controllers in the plugin settings: `controller_index` and `controller_sync`. 

Controller_index is used to save the MailChimp API key and to designate the default lists and interests to the UserAuthenticationProvider table.

Controller_sync is used to push bulk lists of users from the User table to MailChimp


### Gotchas and Pitfalls

When adding users with the opt-in ("Send confirmation email?" checked in the dashboard) the user will not show up at all in the MailChimp dashboard until the user has confirmed.

When uploading users to MailChimp, a list is sent that then sits in a queue on MailChimp servers as a batch to be processed. Especially when there is a big list, there could be some latency. I created methods (`controller_trackbatches` and `getBatchStatus`) for tracking the progress of batches but elected not to implement them.

Turn on db tracking to debug. Bulk lists are truncated to 10 users when stored in the debugger to save space. 
