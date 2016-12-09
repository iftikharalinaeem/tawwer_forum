# Category Roles
This plug-in allows users to be granted the default category permissions of a role on a per-category basis.  For example, a user in the Member role might be given the default category permissions of the Moderator role, but only in the General Discussions category.

These additional permissions are assigned per-user and per-category.

_Important: Because of the way Vanilla builds permissions, the event handler the plug-in uses may not be triggered if the user already has their permissions cached.  This includes caching with a service like memcached or in the Permissions column of the user record.  If permissions aren't applying, make sure all cached permissions for the user are cleared._

_Important: This plug-in currently only works for categories where the row's PermissionCategoryID is its CategoryID.  Categories with a different PermissionCategoryID may not have the custom application of permissions._

_Please Note: Only a role's default category permissions are granted.  __A role's global and per-category permissions will not be granted by this plug-in.___
