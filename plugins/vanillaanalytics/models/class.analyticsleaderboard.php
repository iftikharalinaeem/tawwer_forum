<?php
/**
 * AnalyticsLeaderboard class file.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * A representation of posts or users, ranked by activity or popularity.
 */
class AnalyticsLeaderboard {

    /**
     * Number of days for the default timespan.
     */
    const DEFAULT_SPAN = 30;

    /**
     * Maximum size for a leaderboard.
     */
    const MAX_SIZE = 100;

    private $previousQuery;

    private $query;

    /**
     * @var int Number of records to limit the leaderboard to.
     */
    private $size = 10;

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
                date('c', strtotime()),
                date('c', strtotime('-'.self::DEFAULT_SPAN.' days'))
            );
            $this->previousQuery->setTimeframeAbsolute(
                date('c', strtotime('-'.(self::DEFAULT_SPAN*2).' days')),
                date('c', strtotime('-'.self::DEFAULT_SPAN.' days'))
            );
        }

        $response = $this->query->exec();
        $responsePrevious = $this->previousQuery->exec();

        if (empty($response) || empty($responsePrevious)) {
            throw new Gdn_UserException('An error was encountered while querying data.');
        }

        return $this->processResponse(
            $response->result,
            $responsePrevious->result
        );
    }

    /**
     * @param array $result
     * @param array $previousResult
     * @return array
     * @throws Gdn_UserException
     */
    private function processResponse($result, $previousResult) {
        $resultIndexed = [];

        // Have results? Process them.
        if ((is_array($result) && !empty($result)) && is_array($previousResult)) {
            $detectTypes = [
                'user.userID',
                'discussion.discussionID',
                'reaction.recordID',
            ];
            $typeID = false;

            // Attempt to determine the type based on the first row's attributes.
            $firstResult = current($result);
            foreach ($detectTypes as $currentType) {
                if ($firstResult->$currentType) {
                    $typeID = $currentType;
                    $emulatedTypeID = $typeID;
                    if ($typeID === 'reaction.recordID') {
                        if (!isset($firstResult->{'reaction.recordType'})) {
                            throw new Gdn_UserException('You need to group by that query with reaction.recordType');
                        }
                        if ($firstResult->{'reaction.recordType'} === 'discussion') {
                            $emulatedTypeID = 'discussion.discussionID';
                        } else {
                            throw new Gdn_UserException('Comments are not supported yet!');
                        }
                    }
                    break;
                }
            }
            if (!$typeID) {
                throw new Gdn_UserException('Unable to determine result type of query.');
            }

            // Sort results based on their count total, descending.
            usort($result, [$this, 'sortResults']);

            // Cut to the desired number of results
            $result = array_slice($result, 0, $this->size);

            // Prepare to build out the values we need for the leaderboard.
            switch ($emulatedTypeID) {
                case 'discussion.discussionID':
                    $recordModel = new DiscussionModel();
                    $recordUrl = '/discussion/%d';
                    $titleAttribute = 'Name';
                    break;
                case 'user.userID':
                    $recordModel = Gdn::userModel();
                    $recordUrl = '/profile?UserID=%d';
                    $titleAttribute = 'Name';
                    break;
                default:
                    throw new Gdn_UserException('Invalid type ID.');
            }

            $previousPositions = [];
            usort($previousResult, [$this, 'sortResults']);
            foreach ($previousResult as $index => $previousStanding) {
                $previousPositions[$previousStanding->$typeID] = $index;
            }


            $position = 0;
            foreach ($result as $currentResult) {
                $recordID = $currentResult->$typeID;
                $count = $currentResult->result;
                $record = $recordModel->getID($recordID, DATASET_TYPE_ARRAY);
                $previous = $previousPositions[$recordID];

                if ($previous === false) {
                    $positionChange = "New";
                } elseif ($position === $previous) {
                    $positionChange = "Same";
                } elseif ($position < $previous) {
                    $positionChange = "Rise";
                } else {
                    $positionChange = "Fall";
                }

                $record['LeaderRecord'] = [
                    'ID' => $recordID,
                    'Position' => ($position + 1),
                    'PositionChange' => $positionChange,
                    'Previous' => $previous !== false ? ($previous + 1) : false,
                    'Url' => sprintf($recordUrl, $recordID),
                    'Title' => $record[$titleAttribute],
                    'Count' => $count
                ];
                $resultIndexed[$recordID] = $record;
                $position++;
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

    public function sortResults($r1, $r2) {
        if ($r1->result === $r2->result) {
            return 0;
        } else {
            return $r1->result > $r2->result ? -1 : 1;
        }
    }
}
