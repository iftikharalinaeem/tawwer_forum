<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;
use Vanilla\Web\Controller;
use Vanilla\Swagger\Models\SwaggerModel;

/**
 * Returns the swagger spec for the APIv2.
 */
class SwaggerApiController extends Controller {
    private $swaggerModel;

    /**
     * Construct a {@link SwaggerApiController}.
     *
     * @param SwaggerModel $swaggerModel The swagger model dependency.
     */
    public function __construct(SwaggerModel $swaggerModel) {
        $this->swaggerModel = $swaggerModel;
    }

    /**
     * Get the root swagger object.
     *
     * @return array Returns the swagger object as an array.
     */
    public function index() {
        $this->permission(); //'Garden.Settings.Manage');

        $this->schema(
            new Schema(['$ref' => 'https://raw.githubusercontent.com/OAI/OpenAPI-Specification/master/schemas/v2.0/schema.json']),
            'out'
        );

        $this->getSession()->getPermissions()->setAdmin(true);
        return $this->swaggerModel->getSwaggerObject();
    }

    public function get_foo() {
        return null;
    }
}
