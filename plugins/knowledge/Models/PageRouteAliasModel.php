<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\ValidationException;
use Gdn_Session;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;

/**
 * A model for managing page route aliases of certain type (for articles, discussions, etc)
 */
class PageRouteAliasModel extends \Vanilla\Models\PipelineModel {

    /** Default limit on the number of results returned. */
    const LIMIT_DEFAULT = 30;

    /** @var Gdn_Session */
    private $session;

    /**
     * ArticleModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("pageRouteAlias");
        $this->session = $session;

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
     * Get resource rows from a database table.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     * @throws ValidationException If a row fails to validate against the schema.
     */
    public function get(array $where = [], array $options = []): array {
        $options["limit"] = $options["limit"] ?? self::LIMIT_DEFAULT;
        return parent::get($where, $options);
    }

    /**
     * Select existing aliases for given recordID of recordType
     *
     * @param string $recordType
     * @param int $recordID
     * @param bool $aliasColumnOnly
     * @return array
     */
    public function getAliases(string $recordType, int $recordID, bool $aliasColumnOnly = false): array {
        $where = [
            'recordType' => $recordType,
            'recordID' => $recordID
        ];
        $options = [
            'select' => ['alias']
        ];
        if (!$aliasColumnOnly) {
            $options['select'][] = 'pageRouteAliasID';
        }
        $data = parent::get($where, $options);

        if ($aliasColumnOnly) {
            $res = array_column($data, 'alias');
        } elseif (empty($data)) {
            $res = [];
        } else {
            $res = array_combine(
                array_column($data, 'pageRouteAliasID'),
                array_column($data, 'alias')
            );
        }
        return $res;
    }

    /**
     * Add alias to recordType with particular recodrID
     *
     * @param string $recordType
     * @param int $recordID
     * @param string $alias
     * @return bool
     */
    public function addAlias(string $recordType, int $recordID, string $alias): bool {
        return parent::insert([
            'recordType' => $recordType,
            'recordID' => $recordID,
            'alias' => $alias
        ]);
    }

    /**
     * Drop aliases for particular recordID of recordType.
     * Note: when $aliases is empty delete all aliases for the record
     *
     * @param string $recordType
     * @param int $recordID
     * @param array $aliases
     * @return bool
     */
    public function dropAliases(string $recordType, int $recordID, array $aliases = []): bool {
        $where = [
            'recordType' => $recordType,
            'recordID' => $recordID,
        ];
        if (!empty($aliases)) {
            $where['alias'] = $aliases;
        }
        return parent::delete($where);
    }

    /**
     * Get recordID of particular recordType by alias.
     *
     * @param string $recordType
     * @param string $alias
     * @return int
     * @throws NoResultsException Exception is thrown if no records found.
     */
    public function getRecordID(string $recordType, string $alias): int {
        $where = [
            'recordType' => $recordType,
            'alias' => $alias
        ];
        $options = [
            'select' => ['recordID'],
            'limit' => 1
        ];

        $data = parent::get($where, $options);

        if (empty($data)) {
            throw new NoResultsException();
        } else {
            return $data[0]['recordID'];
        }
    }
}
