# Category Roles
This plug-in allows users to be granted the default category permissions of a role on a per-category basis.  For example, a user in the Member role might be given the default category permissions of the Moderator role, but only in the General Discussions category.

These additional permissions are assigned per-user and per-category.

## Syncing with SSO

Category Role permissions can be synced with SSO connections.  This is done by providing properly-structured data under a "CategoryRoles" key in the authentication provider's response.  The values under this key are expected to be an array of elements.  Each element should have two keys: RoleID and CategoryID.  RoleID should be a single value representing a role.  CategoryID can be several category IDs.  The specified role's permissions will be given to the authenticated user in the specified categories.
 
```json
{
  ...
  "CategoryRoles": [
    { "RoleID": 16, "CategoryID": [5,8,12] },
    { "RoleID": 32, "CategoryID": [6,7,9] }
  ],
  ...
}
```

Smart IDs may also be used, instead of numeric IDs.

```json
{
  ...
  "CategoryRoles": [
    { "Role.Name": "Administrator", "Category.UrlCode": ["special-admins"] },
    { "Role.Name": "Moderator", "Category.UrlCode": ["special-moderators"] }
  ],
  ...
}
```

_Important: Because of the way Vanilla builds permissions, the event handler the plug-in uses may not be triggered if the user already has their permissions cached.  This includes caching with a service like memcached or in the Permissions column of the user record.  If permissions aren't applying, make sure all cached permissions for the user are cleared._

_Important: In order for this plugin to work for a particular category, that category must have its PermissionCategoryID field in the database set to its own category ID. If you set a child categories PermissionCategoryID to its parent's CategoryID it will receive the same roles as its parent._

_Please Note: Only a role's default category permissions are granted.  __A role's global and per-category permissions will not be granted by this plug-in.___ This plugin also can interact weirdly with certain modules such as the CategoriesModule and the CategoryModeratorModule. If you are doing any estimate with this plugin __budget extra time for it, and verify any expectations you have about its interactions with other plugin.__ For an example of implementations of these modules that work with this plugin please view the IearnCustomizations plugin at [`vanillaforums/iearn`](http://github.com/vanillaforums/iearn).

## How to set up this plugin for local development
1. Enable the plugin.
2. Verify that every category you wish to assign a role has its PermissionCategoryID set to its own PermissionCategoryID or one of its parents PermissionCategoryID. If you have subcommunities enabled verify that this is not root the root categoryID (-1). Otherwise this plugin may not work properly.
3. Insert necessary rows into the Gdn_CategoryRole table. 
4. Clear cached permissions.
    * Clear the Permissions column for User records associated with entries in your Gdn_CategoryRole table.
    * Restart or flush cache of memcached if you are running it locally.
