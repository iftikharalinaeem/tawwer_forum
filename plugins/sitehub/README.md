# Multisite Hub

Main Documentation: http://docs.vanillaforums.com/help/multisite/#the-hub

## Syncing configurations to nodes

- `NodeConfig.Addons.{AddonName}` will sync `EnabledPlugins.{AddonName}`.
- `NodeConfig.Theme.{ThemeName}` will sync `Garden.Theme.{ThemeName}`.
- `NodeConfig.MobileTheme.{ThemeName}` will sync `Garden.MobileTheme.{ThemeName}`.
- `NodeConfig.Config.{Config.To.Sync}` will sync `{Config.To.Sync}`.

### Example

This configuration:
```
    "NodeConfig": {
        "Addons": {
            "CustomTheme": true,
            "vanillicon": true,
            "Facebook": false
        },
        "Theme": "MyCustomTheme",
        "MobileTheme": "MyCustomMobileTheme",
        "Config": {
            "Garden.Registration.Method": "Connect",
            "Garden.ForceSSL": true,
        }
    }
```

would give the following on the nodes:
```
    "EnabledPlugins": {
        "CustomTheme": true,
        "vanillicon": true,
        "Facebook": false
    },
    "Garden": {
        "Theme": "MyCustomTheme",
        "MobileTheme": "MyCustomMobileTheme",
        "Registration": {
            "Method": "Connect",
        },
        "ForceSSL": true
    },
}
```

Both theme would also be enabled during the sync process.
