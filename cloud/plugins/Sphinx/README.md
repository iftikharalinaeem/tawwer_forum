### Sphinx

Steps to configure Sphinx for a Developer attempting to run sphinx with the integration tests using the vanilla-docker container.

#### Installing Sphinx

Before enabling make sure that:
- Your database is named vanilla_dev
- You have set `Plugins.Sphinx.Server = sphinx` in your config
- You have set `Plugins.Sphinx.SphinxAPIDir = /sphinx/` in your config
- You need a copy of the sphinxapi.php in the root of your Vanilla project.  It can be found in the vanilla-docker repo (/vanilla-docker/resources/sphinx/sphinxapi.php)
- You have enabled the sphinx plugin

### Vanilla-Docker Setup

- Navigate to the vanilla-docker repository
- You symlinked one of the config in `resources/usr/local/etc/sphinx/configs-available` as sphinx.conf in `resources/usr/local/etc/sphinx/conf.d`
- Example from conf.d/: `ln -s configs-available/standard.sphinx.conf sphinx.conf`
- For unit tests use the everything.sphinx.conf

### Sphinx unit testing config
- Ensure you have a test database `vanilla_test`.
- Ensure you are using the `everything.sphinx.conf` config for sphinx.
- Ensure that your phpunit.xml and phpunit.dist.xml have the following environmental value:
`<env name="TEST_SPHINX_HOST" value="sphinx" />`

### Final Steps
- Restart your docker container
- Next time you run docker-compose ensure that you have `service-sphinx.yml -f` in the command.
ex. `docker-compose -f docker-compose.yml -f docker-compose.override.yml -f service-sphinx.yml -f --build`
