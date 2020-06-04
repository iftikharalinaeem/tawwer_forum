# Private Discussions

This addon displays a restricted discussion view for guests.

The restricted view is only triggered if "Private Communities" is enabled and the "guest" role has discussion view permission.

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

**I enabled the addon but guests are redirected to the signin page when trying to access a discussion.**

- Make sure the roles "guest" has discussion view permission on that category.

**I enabled the addon but guests can see the whole discussion.**

- Make sure the setting "Private Communities" is enabled. You should find this setting under: Dashboard > Roles & Permissions.

