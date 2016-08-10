## MailChimp Push Plugin

This plugin allows forum admins to connect their forum to MailChimp.

### Features

 * Send user information to MailChimp when a user signs up or updates his/her user information.
 * Allow admins to designate a list configured on MailChimp to be assigned to the user when sent to MailChimp.
 * Allow admins to assign "interests" (a form of tagging on MailChimp) to users when storing on MailChimp.
 * Allow admins to synchronize all their users to MailChimp in one mass migration, assigning them to a list and assigning insterests.
 
### How it works

There are two controllers in the plugin settings: `controller_index` and `controller_sync`. 

Controller_index is used to save the MailChimp API key and to designate the default lists and to the UserAuthenticationProvider table.
