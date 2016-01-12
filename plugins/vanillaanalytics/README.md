## Notes

In it's current state, the plug-in will require a keen.io project be configured.  If one is not configured at the time the plug-in is enabled, it will attempt to create one with the project provisioning API.  This request requires the following elements in the site's config:

```php
// API key with organization-level access
$Configuration['VanillaAnalytics']['KeenIO']['OrgKey'] = 'ABC123';
// ID of the organization that the project will be associated with
$Configuration['VanillaAnalytics']['KeenIO']['OrgID'] = '123abc';
// All keen.io projects require at least one user to be assigned to them. This is the e-mail address of that user.
$Configuration['VanillaAnalytics']['KeenIO']['DefaultProjectUser'] = 'user@host.net';
```
