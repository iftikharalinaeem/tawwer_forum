# Knowledge Porter Runner

The KnowledgePorterRunner Addon integrates [vanilla/vanilla](https://github.com/vanilla/vanilla) with [vanilla/hosted-queue](https://github.com/vanilla/hosted-queue) and [vanilla/knowledge-porter](https://github.com/vanilla/knowledge-porter)

The knowledge-porter is a command-line tool that imports and/or synchronizes content from different sources into Vanilla using Vanilla's APIv2.
Vanilla's Hosted Queue can run the knowledge-porter as an unattended and asynchronous task.
The KnowledgePorterRunner enables that functionality inside vanilla/vanilla.

## Main considerations

The KnowledgePorterRunner adds a new APIv2 endpoint (`api/v2/kbporter`) that will take the knowledge-porter's configuration from the Site config and it will schedule Job(s) into the Hosted Queue to run the knowledge-porter.

The main use case for the endpoint is to use any regular CRON utility to hit the endpoint at regular intervals and trigger imports and/or synchronizations.

The endpoint is by default secured using a special token. (HTTP-Header `Authorization: Bearer {TOKEN}`)
The token is auto-generated/regenerated when the Plugin is enabled/re-enabled.
The security can be disabled if it is needed, however it is highly discouraged to do so.
A special `null` token allows disabling security on the endpoint.

To avoid race conditions on the use of the endpoint (and potentially triggering several times the same job) a locking mechanism based on Memcached is used.
The endpoint will allow one execution at the time, creating and removing the locking key. The key has a TTL of 31 seconds and will be auto-removed if the regular flow of execution is not met.

The state of Jobs is persisted using UserMeta functionality. The state is needed to avoid triggering the same Job while there is one already running on the Hosted Queue.
Notifications about the result of the Hosted Queue execution is push-based and it relies on the Hosted Queue feedback loop functionality.
As a failsafe mechanism, the state of the Job will become stale after 1 hour of the execution schedule. That means that after 1 hour the Job would be considered lost and a new Job can be scheduled even though the response never reached back.

KnowledgePorterRunner will schedule all the Job sequentially and simultaneously. Multiple configurations and multiple domains are supported.

## Configuration

`Plugins.KnowledgePorterRunner` is where the configuration lives.
`Plugins.KnowledgePorterRunner.token` holds the auto-generated token. A value of `null` removes the authentication.
`Plugins.KnowledgePorterRunner.config` should be an associative array containing the configuration in the way the knowledge-porter requires it.
The key of the associate array is used for the state of the Job. Using an associative array instead of a regular array allows rearranging the configuration without impacting the state of it.
Each item in the array is considered as a knowledge-porter configuration. The values in the configuration will be forwarded to the knowledge-porter running on the Hosted-Queue.
Although, the KnowledgePorterRunner handles 2 additional cases:
+ If `['source']['domain']` is an array, it would create a Job for each of those domains.
+ If the item on `['source']['domain']` includes an `=`, the value after the `=` would be considered as a replacement of the `{prefix}` for `['source']['foreignIDPrefix']`
+ This behaviour was included to keep the configuration as close as possible with the knowledge-porter's `multi-site.sh` implementation.


Configuration example:
```
$Configuration['Plugins']['KnowledgePorterRunner']['token'] = '2Og0XDAY8vSmp0kWyAaNAoObTqZOcOBu';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['type'] = 'zendesk';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['foreignIDPrefix'] = '{prefix}';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['domain'] = array (
    0 => 'www.help.queen.com=queen-general',
    1 => 'sugarcrushsoda.help.queen.com=sugarcrushsoda',
);
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['token'] = 'abcd12345677890';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['sourceLocale'] = 'en-us';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['pageLimit'] = 100;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['pageFrom'] = 1;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['pageTo'] = 1;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['import']['categories'] = true;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['import']['sections'] = true;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['import']['articles'] = true;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['import']['authors'] = false;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['import']['translations'] = false;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['import']['helpful'] = false;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['api']['cache'] = false;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['source']['api']['log'] = true;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['type'] = 'vanilla';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['protocol'] = 'https';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['domain'] = 'kbporter.vanillawip.com';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['token'] = '0987654321dcba';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['update'] = 'always';
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['api']['cache'] = false;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['api']['log'] = true;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['patchKnowledgeBase'] = false;
$Configuration['Plugins']['KnowledgePorterRunner']['config']['queen-1']['destination']['syncUserByEmailOnly'] = false;
```
