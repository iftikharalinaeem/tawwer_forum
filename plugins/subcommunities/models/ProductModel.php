<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Schema\Schema;
use Gdn_Router as Router;
use Vanilla\Contracts\ConfigurationInterface;
use Gdn_Session;
use Vanilla\Models\FullRecordCacheModel;

/**
 * A model for managing products.
 */
class ProductModel extends FullRecordCacheModel {

    const FEATURE_FLAG = 'SubcommunityProducts';

    /** @var Gdn_Session */
    private $session;

    /** @var Router $router */
    private $router;

    /** @var ConfigurationInterface $config */
    private $config;

    /** @var Schema */
    public $productSchema;

    /**
     * ProductModel constructor.
     *
     * @param Gdn_Session $session
     * @param Router $router
     * @param ConfigurationInterface $config
     */
    public function __construct(
        Gdn_Session $session,
        Router $router,
        ConfigurationInterface $config
    ) {
        parent::__construct("product");
        $this->session = $session;
        $this->config = $config;
        $this->router = $router;
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Make a site section group key from a site section ID.
     *
     * @param int|null $productID
     * @return string
     */
    public static function makeSiteSectionGroupKey(?int $productID): string {
        if ($productID === null) {
            $siteGroup = SubcommunitySiteSection::SUBCOMMUNITY_NO_PRODUCT;
        } else {
            $siteGroup = $productID;
        }
        return SubcommunitySiteSection::SUBCOMMUNITY_GROUP_PREFIX . $siteGroup;
    }

    /**
     * Add multi-dimensional category data to an array.
     *
     * @param array $rows Results we need to associate category data with.
     */
    public function expandProduct(array &$rows) {
        if (count($rows) === 0) {
            // Nothing to do here.
            return;
        }
        reset($rows);
        $single = is_string(key($rows));

        $populate = function (array &$row) {
            if (array_key_exists('ProductID', $row) && !is_null($row['ProductID'])) {
                try {
                    $where = ['productID' => $row['ProductID']];
                    $product = $this->selectSingle($where);
                    if ($product) {
                        $row['product'] = $product;
                    }
                } catch (NoResultsException $e) {
                    logException($e);
                }
            }
            if (empty($row['defaultController'] ?? '')) {
                $configDefaultController = $this->config->get('Routes.DefaultController');
                $row['defaultController'] = sprintf(t('Default (%s)'), $this->router->parseRoute($configDefaultController)['Destination']);
            }
        };

        if ($single) {
            $populate($rows);
        } else {
            foreach ($rows as &$row) {
                $populate($row);
            }
        }
    }

    /**
     * Simplified product schema.
     *
     * @param string $type
     * @return Schema
     */
    public function productFragmentSchema(string $type = ""): Schema {
        if ($this->productSchema === null) {
            $this->productSchema = Schema::parse([
                "productID",
                "name",
                "body?",
            ], $type);
        }
        return $this->productSchema;
    }
}
