# Testing MailChimp Push

Things you should know when testing this plugin:

* If you have an email address you use for most of your Vanilla users, using the '+' to create a unique email address (i.e., user+variant@example.com), you may encounter "Invalid Resource" errors when batching users fairly quickly. There's a shim in the plugin where you can generate fake emails for any user with a base email when mass syncing. To do so, set `Plugins.MailChimp.TestEmail` in your config to your base email without the '+variant'.
* MailChimp ignores emails with domains such as `example.com` or `noreply.com`.
* The plugin does not provide updates on the batch process. You can download the full response by looking into the EventLog table in the database and looking for the batch response. In the Attributes column, you'll find the batch status url. It will look something like this: `https://us15.api.mailchimp.com/3.0/batches/{BatchID}`. You can use a REST client to retrieve updates on the batch. You'll need to provide the API key as a basic authorization header. The json response for the batch request will return a link to download the full response body for the batch.

## Logging in to MailChimp

We have an account with the username "VanillaDev" you can test with following these instructions. Speak with ops to get the credentials.

Login to MailChimp using these credentials. Update the API key in the MailChimp Push settings page `/plugin/mailchimp` in your dashboard on your test forum to be the one set here: `https://us15.admin.mailchimp.com/account/api/`

## Tests

### Lists and Groups

The lists and groups on the MailChimp account should appear as options on the MailChimp settings page.

#### Mailing Lists are Synced

1. Ensure that the lists dropdown is populated on the MailChimp Push settings page with the options 'My NewsLetter' and 'Promotions'

#### Groups are Synced

##### With Groups

1. Select the "My Newsletter" mailing list
2. Ensure the "Groups" section is populated with "Kittens", "Puppies", "My Forum" and "My Online Store"

##### Without Groups

1. Select the "Promotions" mailing list
2. Ensure the "Groups" section does not appear, as the "Promotions" list has no groups

### Mass Sync

Mass Syncing will never delete users already existing on MailChimp. It will only add new email addresses that do not already exist in the list. If you change the group, it will add user to the new group if they're not already in it, but it won't remove them from the old group.

#### Mass Sync

1. Ensure you have at least one banned user and one user with the Deleted flag set in your database. Ensure both of these users also have the Confirmed flag set.
2. Delete all users from the MailChimp "Promotions" and "My NewsLetter" lists.
3. On the Mass Synchronization tab `/plugin/mailchimp/masssync`, select the "My NewsLetter" list. Do not select a group. Ensure all checkboxes are disabled and click "Synchronize".
4. Wait a while (up to 15 mins) and check that all non-banned, non-deleted users have been imported into the "My NewsLetter" list on MailChimp.

#### Mass Sync with Deleted and Banned Users

1. On the Mass Synchronization tab `/plugin/mailchimp/masssync`, select the "Promotions" list. Enable "Sync banned users" and "Sync deleted users" and click "Synchronize".
2. Wait a while (up to 15 mins) and check that your confirmed users, including the banned and deleted users have been imported into the "Promotions" list on MailChimp.

### Updating and Adding users

Once confirmed, new registrants' emails should be sent to the mailing list set on the MailChimp settings page (`plugin/mailchimp`). If a user's email is edited, their old email should be set to "unregistered" in the MailChimp list and their new email should be registered. Your test user in this section cannot be a user whose email is a variant of `Plugins.MailChimp.TestEmail`.

#### Register User

1. Update the settings on the MailChimp Settings tag `/plugin/mailchimp`. Change the Mailing List to "My Newsletter" and the group to "Puppies"
2. Register a new user and confirm their email.
3. Wait a while (up to 15 mins) and check that the user appears in the MailChimp "My Newsletter" list with the group "Puppies".

#### Edit User

1. Update the settings on the MailChimp Settings tag `/plugin/mailchimp`. Change the Mailing List to "My Newsletter" and the group to "Kittens"
2. Edit the email of a user from the Users page in the dashboard.
3. Wait a while (up to 15 mins) and check that the user's old email has be "unregistered" and their new email appears in the MailChimp "My Newsletter" list with the group "Kittens".