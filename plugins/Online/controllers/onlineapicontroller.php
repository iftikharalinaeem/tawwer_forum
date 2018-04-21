<?php
/**
 * @copyright 2010-2017 Vanilla Forums, Inc
 * @license Proprietary
 */

use Vanilla\Exception\PermissionException;
use Vanilla\Web\Controller;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;

/**
 * Controller handling the /online API endpoint.
 */
class OnlineApiController extends Controller {

    /** @var OnlinePlugin */
    private $onlinePlugin;

    /** @var UserModel */
    private $userModel;

    /**
     * OnlineApiController constructor.
     *
     * @param OnlinePlugin $onlinePlugin
     * @param UserModel $userModel
     */
    public function __construct(OnlinePlugin $onlinePlugin, UserModel $userModel) {
        $this->onlinePlugin = $onlinePlugin;
        $this->userModel = $userModel;
    }

    /**
     * List all online users.
     *
     * @return array
     */
    public function index(array $query): array {
        $this->permission('');

        $in = $this->schema([
            'filter:s' => [
                'default' => 'all',
                'description' => 'Filter',
                'enum' => ['all', 'category', 'discussion']
            ],
            'filterID:i?' => 'Discussion or category ID to filter by. Required if filter is set to category or discussion.'
        ], 'in')
            ->setDescription('List all online users.')
            ->addValidator('', function($value, \Garden\Schema\ValidationField $field) {
                $filter = $value['filter'] ?? null;
                $filterID = $value['filterID'] ?? null;
                if (in_array($filter, ['category', 'discussion']) && $filterID === null) {
                    $field->addError('missingField', [
                        'messageCode' => 'You must specify a filterID',
                        'required' => true
                    ]);
                }
            });
        $out = $this->schema([], 'out');

        $query = $in->validate($query);
        $filterID = $query['filterID'] ?? null;

        $rows = $this->onlinePlugin->onlineUsers($query['filter'], $filterID);
        $rows = array_values($rows);
        $this->userModel->joinUsers($rows, ['UserID']);

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a counts of users currently online.
     *
     * @return array
     * @throws ValidationException if input or output fails schema validation.
     * @throws HttpException
     * @throws PermissionException if the permission check fails.
     */
    public function get_counts(): array {
        $this->permission('Garden.Settings.View');

        $in = $this->schema([], 'in')->setDescription('Get a count of users currently online.');
        $out = $this->schema([
            'users:i' => 'Number of signed-in users on the site.',
            'guests:i' => 'Number of guests on the site.',
            'total:i' => 'Total number of all guests and signed-in users.'
        ], 'out');

        $users = (int)$this->onlinePlugin->onlineCount();
        $guests = (int)$this->onlinePlugin->guestCount();

        $result = [
            'users' => $users,
            'guests' => $guests,
            'total' => ($users + $guests)
        ];
        $result = $out->validate($result);
        return $result;
    }
}
