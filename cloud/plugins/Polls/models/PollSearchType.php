<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Polls\Models;

use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Navigation\BreadcrumbModel;

/**
 * Class PollSearchType
 *
 * @package Vanilla\Polls\Models
 */
class PollSearchType extends DiscussionSearchType {

    /** @var \DiscussionsApiController */
    protected $discussionsApi;

    /** @var BreadcrumbModel */
    protected $breadcrumbModel;

    /**
     * DI.
     *
     * @param \DiscussionsApiController $discussionsApi
     * @param \CategoryModel $categoryModel
     * @param \UserModel $userModel
     * @param \TagModel $tagModel
     * @param BreadcrumbModel $breadcrumbModel
     */
    public function __construct(
        \DiscussionsApiController $discussionsApi,
        \CategoryModel $categoryModel,
        \UserModel $userModel,
        \TagModel $tagModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        parent::__construct(
            $discussionsApi,
            $categoryModel,
            $userModel,
            $tagModel,
            $breadcrumbModel
        );
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'poll';
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'discussion';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'poll';
    }
}
