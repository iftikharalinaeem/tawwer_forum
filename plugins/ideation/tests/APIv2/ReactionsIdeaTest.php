<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use CategoryModel;
use DiscussionModel;
use ReactionModel;
use StatusModel;
use Vanilla\Utility\CamelCaseScheme;

/**
 * Test voting on an idea.
 */
class ReactionsIdeaTest extends AbstractAPIv2Test {

    /** @var int Category ID associated with an idea category. */
    private static $categoryID;

    /** @var int Category ID associated with an idea category allowing down votes. */
    private static $downVoteCategoryID;

    /** @var DiscussionModel */
    private static $discussionModel;

    /** @var CamelCaseScheme */
    private static $camelCaseSchema;

    /** @var StatusModel */
    private static $statusModel;

    /**
     * Get an idea record.
     *
     * @param int $id
     * @return array|mixed
     */
    private function getIdea($id) {
        $row = self::$discussionModel->getID($id, DATASET_TYPE_ARRAY);

        $category = CategoryModel::categories($row['CategoryID']);
        $ideationType = $category['IdeationType'];
        $status = self::$statusModel->getStatusByDiscussion($id);
        $statusNotes = valr('Attributes.StatusNotes', $row, null);

        $row = self::$camelCaseSchema->convertArrayKeys($row);

        $row['attributes'] = [
            'idea' => [
                'score' => $row['score'] ?: 0,
                'statusID' => $status['StatusID'],
                'status' => [
                    'name' => $status['Name'],
                    'state' => $status['State']
                ],
                'statusNotes' => $statusNotes,
                'type' => $ideationType
            ]
        ];

        return $row;
    }

    /**
     * Create a new idea.
     *
     * @param string $type Type of idea: up or up-down.
     * @return array
     */
    private function postIdea($type) {
        switch ($type) {
            case 'up-down':
                $categoryID = self::$downVoteCategoryID;
                break;
            default:
                $categoryID = self::$categoryID;
        }

        $fields = [
            'CategoryID' => $categoryID,
            'Name' => 'Test Idea',
            'Body' => 'Hello world!',
            'Format' => 'markdown',
            'Type' => 'Idea'
        ];
        $defaultStatus = self::$statusModel->getDefaultStatus();
        $fields['Tags'] = $defaultStatus['TagID'];
        $id = self::$discussionModel->save($fields);

        $row = self::$discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $row = self::$camelCaseSchema->convertArrayKeys($row);

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        self::$addons = ['vanilla', 'ideation'];
        parent::setupBeforeClass();

        ReactionModel::$ReactionTypes = null;
        self::$camelCaseSchema = self::container()->get('Vanilla\Utility\CamelCaseScheme');
        self::$discussionModel = self::container()->get('DiscussionModel');
        self::$statusModel = self::container()->get('StatusModel');

        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get('CategoryModel');
        static::$categoryID = $categoryModel->save([
            'Name' => 'Test Vote Category',
            'UrlCode' => 'test-vote-category',
            'InsertUserID' => self::$siteInfo['adminUserID'],
            'IdeationType' => 'up'
        ]);
        static::$downVoteCategoryID = $categoryModel->save([
            'Name' => 'Test Down Vote Category',
            'UrlCode' => 'test-down-vote-category',
            'InsertUserID' => self::$siteInfo['adminUserID'],
            'IdeationType' => 'up-down'
        ]);
    }

    /**
     * Verify ability to down-vote an idea.
     */
    public function testDownVote() {
        $idea = $this->postIdea('up-down');
        $id = $idea['discussionID'];

        $row = $this->getIdea($id);
        $this->assertEquals(0, $row['attributes']['idea']['score']);

        $this->api()->post("discussions/{$id}/reactions", ['reactionType' => 'Down']);
        $updated = $this->getIdea($id);
        $this->assertEquals(-1, $updated['attributes']['idea']['score']);
    }

    /**
     * Verify ability to up-vote an idea.
     */
    public function testUpVote() {
        $idea = $this->postIdea('up');
        $id = $idea['discussionID'];

        $row = $this->getIdea($id);
        $this->assertEquals(0, $row['attributes']['idea']['score']);

        $this->api()->post("discussions/{$id}/reactions", ['reactionType' => 'Up']);
        $updated = $this->getIdea($id);
        $this->assertEquals(1, $updated['attributes']['idea']['score']);
    }
}
