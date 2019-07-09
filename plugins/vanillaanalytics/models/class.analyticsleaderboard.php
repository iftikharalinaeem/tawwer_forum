<?php
/**
 * AnalyticsLeaderboard class file.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * A representation of posts or users, ranked by activity or popularity.
 */
class AnalyticsLeaderboard {

    /** Number of days for the default timespan. */
    const DEFAULT_SPAN = 30;

    /** Default size for a leaderboard. */
    const DEFAULT_SIZE = 10;

    /** Maximum size for a leaderboard. */
    const MAX_SIZE = 100;

    /** @var $query KeenIOQuery */
    private $previousQuery;

    /** @var $query KeenIOQuery */
    private $query;

    /**
     * @var int Number of records to limit the leaderboard to.
     */
    private $size = self::DEFAULT_SIZE;

    private $timeframe;

    /**
     * Execute the current- and previous-range queries.
     *
     * @param int|bool $start Range start, represented as a timestamp.
     * @param int|bool $end Range end, represented as a timestamp.
     * @return array
     * @throws Gdn_UserException
     */
    public function lookupData($start, $end) {
        // Determine the timeframe for the leaderboard, as well as previous user standings.
        if (is_int($start) && is_int($end)) {
            $duration = $end - $start;

            $this->query->setTimeframeAbsolute(
                date('c', $start),
                date('c', $end)
            );
            $this->previousQuery->setTimeframeAbsolute(
                date('c', strtotime('-'.$duration.' seconds', $start)),
                date('c', $start)
            );
        } else {
            $this->query->setTimeframeAbsolute(
                date('c', strtotime('now')),
                date('c', strtotime('-'.self::DEFAULT_SPAN.' days'))
            );
            $this->previousQuery->setTimeframeAbsolute(
                date('c', strtotime('-'.(self::DEFAULT_SPAN*2).' days')),
                date('c', strtotime('-'.self::DEFAULT_SPAN.' days'))
            );
        }

        $this->query
            ->setLimit($this->size)
            ->addOrderBy('result', 'DESC');
        $this->previousQuery
            ->setLimit($this->size)
            ->addOrderBy('result', 'DESC');

        $response = $this->query->exec();
        $responsePrevious = $this->previousQuery->exec();

        if (empty($response) || empty($responsePrevious)) {
            throw new Gdn_UserException('An error was encountered while querying data.');
        }

        $processedResult = $this->processResponse(
            $response['result'],
            $responsePrevious['result']
        );

        if (empty($processedResult)) {
            throw new Gdn_UserException('No data was returned.');
        }

        return $processedResult;
    }

    /**
     * @param array $result
     * @param array $ptfResult Previous Time Frame result
     * @return array
     * @throws Gdn_UserException
     */
    private function processResponse($result, $ptfResult) {
        $resultIndexed = [];

        // Have results? Process them.
        if ((is_array($result) && !empty($result)) && is_array($ptfResult)) {
            $detectTypes = [
                'user.userID',
                'point.user.userID',
                'insertUser.userID',
                'discussion.discussionID',
                'reaction.recordID',
                "article.articleID",
                "article.insertUserID",
            ];
            $typeID = false;

            // Attempt to determine the type based on the first row's attributes.
            $firstResult = current($result);
            $emulatedTypeID = null;
            foreach ($detectTypes as $currentType) {
                if (array_key_exists($currentType, $firstResult)) {
                    $typeID = $currentType;
                    if ($typeID === 'reaction.recordID') {
                        if (!isset($firstResult['reaction.recordType'])) {
                            throw new Gdn_UserException('You need to group by that query with reaction.recordType');
                        }
                        if ($firstResult['reaction.recordType'] === 'discussion') {
                            $emulatedTypeID = 'discussion.discussionID';
                        } else {
                            throw new Gdn_UserException('Comments are not supported yet!');
                        }
                    } elseif (substr($currentType, -strlen('.userID')) === '.userID') {
                        $emulatedTypeID = 'user.userID';
                    } else {
                        $emulatedTypeID = $typeID;
                    }
                    break;
                }
            }
            if (!$typeID) {
                throw new Gdn_UserException('Unable to determine result type of query.');
            }

            // Prepare to build out the values we need for the leaderboard.
            switch ($emulatedTypeID) {
                case 'discussion.discussionID':
                    $discussionModel = Gdn::getContainer()->get("DiscussionModel");
                    $lookup = [$discussionModel, "getID"];
                    $recordUrl = '/discussion/%d';
                    $titleAttribute = 'Name';
                    break;
                case "article.insertUserID":
                case 'user.userID':
                    $userModel = Gdn::getContainer()->get("UserModel");
                    $lookup = [$userModel, "getID"];
                    $recordUrl = '/profile?UserID=%d';
                    $titleAttribute = 'Name';
                    break;
                case "article.articleID":
                    $articleModel = Gdn::getContainer()->get("Vanilla\Knowledge\Models\ArticleModel");
                    $lookup = [$articleModel, "getIDWithRevision"];
                    $recordUrl = "/kb/articles/%d";
                    $titleAttribute = "name";
                    break;
                default:
                    throw new Gdn_UserException('Invalid type ID.');
            }

            // Previous time frame results
            $ptfPositionByResult = [];
            $ptfResultIndexed = [];

            $position = 1;
            foreach ($ptfResult as $index => $ptfStanding) {
                $ptfResultIndexed[$ptfStanding[$typeID]] = $ptfStanding['result'];

                if (!isset($ptfPositionByResult[$ptfStanding['result']])) {
                    $ptfPositionByResult[$ptfStanding['result']] = $position++;
                }
            }

            $i = 0;
            $position = 1;
            $previousRecordCount = null;
            $previousCount = null;
            foreach ($result as $currentResult) {
                $i++;
                $recordID = $currentResult[$typeID];
                if ($recordID === null) {
                    // Probably bad data. Skip this row.
                    continue;
                }
                $count = $currentResult['result'];
                $record = (array)$lookup($recordID);
                $previousPosition = val($ptfResultIndexed[$recordID], $ptfPositionByResult);

                if ($previousRecordCount && $previousRecordCount != $count) {
                    $position = $i;
                }

                if ($previousPosition === false) {
                    $positionChange = "New";
                } elseif ($position === $previousPosition) {
                    $positionChange = "Same";
                } elseif ($position < $previousPosition) {
                    $positionChange = "Rise";
                } else {
                    $positionChange = "Fall";
                }

                $record['LeaderRecord'] = [
                    'ID' => $recordID,
                    'Position' => $position,
                    'PositionChange' => $positionChange,
                    'Previous' => $previousPosition ? intval($previousPosition) : null,
                    'Url' => sprintf($recordUrl, $recordID),
                    'Title' => $record[$titleAttribute],
                    'Count' => $count
                ];
                $resultIndexed[$recordID] = $record;

                $previousRecordCount = $count;
            }
        }

        return $resultIndexed;
    }

    public function setPreviousQuery($previousQuery) {
        $this->previousQuery = $previousQuery;
    }

    public function setQuery($query) {
        $this->query = $query;
    }

    /**
     * Set a limit on the number of records to be displayed.
     *
     * @param int $size
     * @return $this
     */
    public function setSize($size) {
        if ($size < 1) {
            $size = 10;
        }

        $this->size = max(min(self::MAX_SIZE, $size), 1);

        return $this;
    }

    /**
     * @param array $r1
     * @param array $r2
     * @return int
     */
    public function sortResults($r1, $r2) {
        if ($r1['result'] === $r2['result']) {
            $sortOrder = 0;
        } else {
            $sortOrder = $r1['result'] > $r2['result'] ? -1 : 1;
        }

        return $sortOrder;
    }
}
