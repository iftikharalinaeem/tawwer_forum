<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Badges\Models;

use VanillaTests\SiteTestTrait;

/**
 * Tests for BadgeModel class
 * @package VanillaTests\Badges\Models
 */
class BadgeModelTest extends \PHPUnit\Framework\TestCase {
    use SiteTestTrait;

    /**
     * @var \BadgeModel
     */
    private $model;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array {
        return ['vanilla', 'badges'];
    }

    /**
     * Set up BadgeModel for testing.
     */
    public function setUp(): void {
        $this->model = $this->container()->get(\BadgeModel::class);
    }

    /**
     * Test changing 'AwardManually' attribute from true to false.
     */
    public function testChangeAwardManuallyAttribute() {
        $originalBadge = [
            'Name' => '50 Answers',
            'Slug' => 'answer-50',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '5',
            'Class' => 'Answerer',
            'Level' => '3',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['AwardManually' => true],
        ];
        $updatedBadge = [
            'Name' => '50 Answers',
            'Slug' => 'answer-50',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '5',
            'Class' => 'Answerer',
            'Level' => '3',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['AwardManually' => false],
        ];
        $this->model->save($originalBadge);
        $this->model->save($updatedBadge, ['AwardManually' => false]);
        $expected = ['AwardManually' => false];
        $actual = $this->model->getID($updatedBadge['Slug'])['Attributes'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test save() with new Attributes where there is no BadgeID.
     */
    public function testUpdateAttributesField() {
        $originalBadge = [
            'Name' => '50 Answers',
            'Slug' => 'answer-50',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '10',
            'Class' => 'Answerer',
            'Level' => '4',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['Column' => 'CountComments'],
        ];
        $updatedBadge = [
            'Name' => '50 Answers',
            'Slug' => 'answer-50',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '10',
            'Class' => 'Answerer',
            'Level' => '4',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['AwardManually' => true],
        ];
        $this->model->save($originalBadge);
        $this->model->save($updatedBadge);
        $expected = ['AwardManually' => true, 'Column' => 'CountComments'];
        $actual = $this->model->getID($updatedBadge['Slug'])['Attributes'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test save() with new Attributes where there is no Slug.
     */
    public function testUpdateAttributesFieldWithBadgeID() {
        $originalBadge = [
            'Name' => '50 Answers',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '10',
            'Class' => 'Answerer',
            'Level' => '4',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['Column' => 'CountComments'],
        ];
        $updatedBadge = [
            'Name' => '50 Answers',
            'BadgeID' => '27',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '10',
            'Class' => 'Answerer',
            'Level' => '4',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['AwardManually' => true],
        ];
        $this->model->save($originalBadge);
        $this->model->save($updatedBadge);
        $expected = ['AwardManually' => true, 'Column' => 'CountComments'];
        $actual = $this->model->getID((int)$updatedBadge['BadgeID'])['Attributes'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test where the settings parameter is also passed.
     */
    public function testUpdateSettingsParamIsIncluded() {
        $originalBadge = [
            'Name' => '50 Answers',
            'Slug' => 'answer-50',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '5',
            'Class' => 'Answerer',
            'Level' => '4',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['Column' => 'CountComments'],
        ];
        $updatedBadge = [
            'Name' => '50 Answers',
            'Slug' => 'answer-50',
            'Body' => 'Why use Google when we could just ask you?',
            'Points' => '5',
            'Class' => 'Answerer',
            'Level' => '4',
            'Threshold' => '50',
            'Save' => 'Save',
            'Attributes' => ['AwardManually' => true],
        ];
        $this->model->save($originalBadge);
        $this->model->save($updatedBadge, ['foo' => 'bar']);
        $expected = ['AwardManually' => true, 'Column' => 'CountComments'];
        $actual = $this->model->getID($updatedBadge['Slug'])['Attributes'];
        $this->assertSame($expected, $actual);
    }
}
