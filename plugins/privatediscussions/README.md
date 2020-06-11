# Private Discussions

Allows you to show a restricted version of discussions to guests with a call to action asking them to sign in or register.

The restricted view is only triggered if the "guest" role has discussion view permission.

The retricted view differs from the default view where:

- It strips all embeds from the discussion. (If the configuration is enabled on the settings addon page).
- It restringes the discussion to a specific word count.
- It strips all modules from the panel.



### Settings:

**Strip Embeds:**

- Description: Defines if embeded content should be striped from restricted view.
- Value: boolean
- Default: `true`

**Word Count:**

- Description: Defines how many workds the discussion should have on restricted view.
- Value: integer
- Default: `100`



### Notes:

- Settings can be modified on the community configuration.
- This addon enabled the feature flag `Feature.discussionSiteMaps.Enabled` by default.



### Troubleshooting:

**I enabled the addon but guests can see the whole discussion/are redirected to the signin page.**

- Getting redirected to the signin page mean "Private Communities" is enabled.
- Make sure the roles "guest" has discussion view permission on that category.
