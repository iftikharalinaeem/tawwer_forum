<?php
/**
 * KeenIOTracker class file.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */

/**
 * Responsible for managing communication with the keen.io service.
 */
class KeenIOTracker implements TrackerInterface {

    /**
     * Instance of KeenIOClient.
     * @var KeenIOClient
     */
    protected $client;

    /**
     * @var array The widgets and their settings.
     */
    protected $widgets = [
        'top-posters' => [
            'title' => 'Users with Most Posts',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Name',
                    'count' => 'Posts'
                ]
            ],
        ],
        'top-discussion-starters' => [
            'title' => 'Users with Most Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Name',
                    'count' => 'Discussions'
                ]
            ],
        ],
        'top-question-answerers' => [
            'title' => 'Users with Most Answers',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Name',
                    'count' => 'Questions Answered'
                ]
            ],
        ],
        'top-best-answerers' => [
            'title' => 'Users with Most Accepted Answers',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Name',
                    'count' => 'Best Answers Given'
                ]
            ],
        ],
        'top-viewed-discussions' => [
            'title' => 'Discussions with Most Views',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Discussion',
                    'count' => 'Views'
                ]
            ],
        ],
        'top-viewed-qna-discussions' => [
            'title' => 'Questions with Most Views',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Questions',
                    'count' => 'Views'
                ]
            ],
        ],
        'top-commented-discussions' => [
            'title' => 'Discussions with Most Comments',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Discussions',
                    'count' => 'Comments'
                ]
            ]
        ],
        'top-positive-discussions' => [
            'title' => 'Discussions with Most Positive Reactions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Discussions',
                    'count' => 'Positive Score'
                ]
            ]
        ],
        'top-negative-discussions' => [
            'title' => 'Discussions with Most Negative Reactions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Discussions',
                    'count' => 'Negative Score'
                ]
            ]
        ],
        'top-member-by-total-reputation' => [
            'title' => 'Members by Total Reputation',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Members',
                    'count' => 'Reputation Score'
                ]
            ]
        ],
        'top-member-by-accumulated-reputation' => [
            'title' => 'Members by Accumulated Reputation',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'leaderboard',
            'chart' => [
                'labels' => [
                    'record' => 'Members',
                    'count' => 'Reputation Score'
                ]
            ]
        ],
        'total-active-users' => [
            'title' => 'Active Users',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric'
        ],
        'total-pageviews' => [
            'title' => 'Page Views',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric'
        ],
        'total-visits' => [
            'title' => 'Visits',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric'
        ],
        'visits' => [
            'title' => 'Visits',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'chart',
            'chart' => [
                'labels' => ['Visits']
            ],
        ],
        'total-discussions' => [
            'title' => 'Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'supportCategoryFilter' => true,
        ],
        'total-comments' => [
            'title' => 'Comments',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'supportCategoryFilter' => true,
        ],
        'total-contributors' => [
            'title' => 'Contributors',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
        ],
        'total-asked' => [
            'title' => 'Questions Asked',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'supportCategoryFilter' => true,
        ],
        'total-answered' => [
            'title' => 'Questions Answered',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'supportCategoryFilter' => true,
        ],
        'total-accepted' => [
            'title' => 'Answers Accepted',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'supportCategoryFilter' => true,
        ],
        'time-to-answer' => [
            'title' => 'Average Time to Answer',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'callback' => 'formatSeconds',
            'supportCategoryFilter' => true,
        ],
        'time-to-accept' => [
            'title' => 'Average Time to Accept',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'callback' => 'formatSeconds',
            'supportCategoryFilter' => true,
        ],
        'pageviews' => [
            'title' => 'Page Views',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'chart'
        ],
        'total-resolved-discussions' => [
            'title' => 'Resolved Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'supportCategoryFilter' => true,
        ],
        'total-unresolved-discussions' => [
            'title' => 'Unresolved Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'supportCategoryFilter' => true,
        ],
        'average-time-to-resolve-discussion' => [
            'title' => 'Average Time to Resolve Discussion',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'metric',
            'callback' => 'formatSeconds',
            'supportCategoryFilter' => true,
        ],
        'resolved-discussion' => [
            'title' => 'Resolved Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'type' => 'chart',
            'supportCategoryFilter' => true,
        ],
        'active-users' => [
            'title' => 'Active Users',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'visits-by-role-type' => [
            'title' => 'Unique Visits by Role Type',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'discussions' => [
            'title' => 'Discussions',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'supportCategoryFilter' => true,
        ],
        'comments' => [
            'title' => 'Comments',
            'rank' => AnalyticsWidget::SMALL_WIDGET_RANK,
            'supportCategoryFilter' => true,
        ],
        'posts' => [
            'title' => 'Posts',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
        ],
        'posts-by-type' => [
            'title' => 'Posts by Type',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'chart' => [
                'labelMapping' => [
                    'discussion_add' => 'Discussions',
                    'comment_add' => 'Comments'
                ]
            ],
        ],
        'posts-by-category' => [
            'title' => 'Posts by Category',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
        ],
        'posts-by-role-type' => [
            'title' => 'Posts by Role Type',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
        ],
        'posts-per-user' => [
            'title' => 'Posts per User',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'contributors' => [
            'title' => 'Contributors',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
        ],
        'contributors-by-category' => [
            'title' => 'Contributors by Category',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
        ],
        'contributors-by-role-type' => [
            'title' => 'Contributors by Role Type',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
        ],
        'comments-per-discussion' => [
            'title' => 'Comments per Discussion',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'registrations' => [
            'title' => 'New Users',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'questions-asked' => [
            'title' => 'Questions Asked',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'supportCategoryFilter' => true,
        ],
        'questions-answered' => [
            'title' => 'Questions Answered',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'supportCategoryFilter' => true,
        ],
        'answers-accepted' => [
            'title' => 'Accepted Answers',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'supportCategoryFilter' => true,
        ],
        'visits-per-active-user' => [
            'title' => 'Visits per Active User',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'average-posts-per-active-user' => [
            'title' => 'Average Posts per Active User',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'average-comments-per-discussion' => [
            'title' => 'Average Comments per Discussion',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart'
        ],
        'participation-rate' => [
            'title' => 'Participation Rate',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'chart' => [
                'chartType' => 'pie'
            ]
        ],
        'sentiment-ratio' => [
            'title' => 'Sentiment Ratio',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'chart',
            'chart' => [
                'chartType' => 'bar'
            ],
        ],
        'posts-positivity-rate' => [
            'title' => 'Posts Positivity Rate',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'metric',
            'callback' => 'formatPercent',
        ],
        'average-time-to-first-comment' => [
            'title' => 'Average Time to First Comment',
            'rank' => AnalyticsWidget::MEDIUM_WIDGET_RANK,
            'type' => 'metric',
            'callback' => 'formatSeconds',
            'supportCategoryFilter' => true,
        ],

    ];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->client = new KeenIOClient(
            'https://api.keen.io/{version}/',
            [
                'orgID' => c('VanillaAnalytics.KeenIO.OrgID'),
                'orgKey' => c('VanillaAnalytics.KeenIO.OrgKey'),
                'projectID' => c('VanillaAnalytics.KeenIO.ProjectID'),
                'readKey' => c('VanillaAnalytics.KeenIO.ReadKey'),
                'writeKey' => c('VanillaAnalytics.KeenIO.WriteKey')
            ]
        );
    }

    /**
     * Builds objects from the widgets in the analytics array. Queries must be appended to the items in the widgets
     * array before objects can be made from them. Use registerQueries() for this.
     *
     * @param string $id The slug-type ID of the widget.
     * @return AnalyticsWidget|null The Analytics widget object.
     */
    protected function buildWidget($id) {
        $widget = val($id, $this->widgets, []);
        if (empty($widget) || !val('query', $widget)) {
            return null;
        }

        if (!$chart = val('chart', $widget, [])) {
            if (val('type', $widget) != 'metric') {
                $chart = ['labels' => val('title', $widget)];
            }
        }

        if (!val('title', $chart)) {
            $chart['title'] = val('title', $widget);
        }

        // Override default chart 'Result' label
        $chart['labelMapping']['Result'] = val('title', $widget);

        // TODO: refactor this to be closer to the definition
        // Lets declare what needs to be under data under data and what is a real attribute as such.
        // Let's not do this kind of gymnastic
        // We are also restraining ourselves from adding new data without modifying this function......
        $data = [
            'chart' => $chart,
            'query' => val('query', $widget)
        ];
        $queryProcessor = val('queryProcessor', $widget);
        if ($queryProcessor) {
            $data['queryProcessor'] = $queryProcessor;
        }
        $callback = val('callback', $widget);
        if ($callback) {
            $data['callback'] = $callback;
        }
        $size = val('size', $widget);
        if ($size) {
            $data['size'] = $size;
        }

        $widgetObj = new AnalyticsWidget();
        $widgetObj->setID($id)
            ->setTitle(t(val('title', $widget, '')))
            ->setHandler('KeenIOWidget')
            ->setRank(val('rank', $widget, 1))
            ->setData($data);

        if ($type = val('type', $widget)) {
            $widgetObj->setType(val('type', $widget));
        }

        if (val('supportCategoryFilter', $widget, false)) {
            $widgetObj->addSupport('cat01');
        }

        if ($callback = val('callback', $widget)) {
            $widgetObj->setCallback($callback);
        }

        return $widgetObj;

    }

    /**
     * Add widget object configurations to the ongoing list.
     *
     * @todo Add Visits
     * @todo Add Visits by Role Type
     * @param array $widgets Incoming array of charts to add to.
     */
    public function addWidgets(array &$widgets) {
        $this->registerQueries();
        foreach($this->widgets as $id => $widget) {
            if ($widgetObj = $this->buildWidget($id)) {
                $widgets[$id] = $widgetObj;
            }
        }
    }


    /**
     * Each valid widget in needs a query associated with it.
     * This builds the KeenIOQueries and adds them to the widgets array.
     */
    protected function registerQueries() {
        /*
         * Leaderboards
         */

        // Top Posters (leaderboard)
        $topPostersQuery = new KeenIOQuery();
        $topPostersQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('post')
            ->setGroupBy('user.userID');

        $this->widgets['top-posters']['query'] = $topPostersQuery;

        // Top Discussion Starters (leaderboard)
        $topDiscussionStartersQuery = new KeenIOQuery();
        $topDiscussionStartersQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'discussion_add'
            ])
            ->setGroupBy('user.userID');

        $this->widgets['top-discussion-starters']['query'] = $topDiscussionStartersQuery;

        // Top Question Answerers (leaderboard)
        $topQuestionAnswerers = new KeenIOQuery();
        $topQuestionAnswerers->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'discussion.discussionType',
                'property_value' => 'Question'
            ])
            ->addFilter([
                'operator' => 'gt',
                'property_name' => 'commentID',
                'property_value' => 0
            ])
            ->setGroupBy('user.userID');

        $this->widgets['top-question-answerers']['query'] = $topQuestionAnswerers;

        // Top Best Question Answerers (leaderboard)
        $topBestAnswerers = new KeenIOQuery();
        $topBestAnswerers->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('qna')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'answer_accepted'
            ])
            ->setGroupBy('insertUser.userID');

        $this->widgets['top-best-answerers']['query'] = $topBestAnswerers;

        // Top Viewed Discussions (leaderboard)
        $topViewedDiscussions = new KeenIOQuery();
        $topViewedDiscussions->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('page')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'discussion_view'
            ])
            ->setGroupBy('discussion.discussionID');

        $this->widgets['top-viewed-discussions']['query'] = $topViewedDiscussions;

        // Top Viewed QnA Discussions (leaderboard)
        $topViewedQnADiscussions = new KeenIOQuery();
        $topViewedQnADiscussions->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('page')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'discussion_view'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'discussion.discussionType',
                'property_value' => 'Question'
            ])
            ->setGroupBy('discussion.discussionID');

        $this->widgets['top-viewed-qna-discussions']['query'] = $topViewedQnADiscussions;

        // Top Commented Discussions (leaderboard)
        $topCommentedDiscussions = new KeenIOQuery();
        $topCommentedDiscussions->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'comment_add'
            ])
            ->setGroupBy('discussion.discussionID');

        $this->widgets['top-commented-discussions']['query'] = $topCommentedDiscussions;


        // Top Positive Discussions (leaderboard)
        $topPositiveDiscussions = new KeenIOQuery();
        $topPositiveDiscussions->setAnalysisType(KeenIOQuery::ANALYSIS_SUM)
            ->setEventCollection('reaction')
            ->setTargetProperty('reaction.total')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.reactionClass',
                'property_value' => 'Positive'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.recordType',
                'property_value' => 'discussion'
            ])
            // You need to group by reaction.recordType. This is a requirement of leaderboard
            ->setGroupBy(['reaction.recordID', 'reaction.recordType']);

        $this->widgets['top-positive-discussions']['query'] = $topPositiveDiscussions;

        // Top Negative Discussions (leaderboard)
        $topNegativeDiscussions = new KeenIOQuery();
        $topNegativeDiscussions->setAnalysisType(KeenIOQuery::ANALYSIS_SUM)
            ->setEventCollection('reaction')
            ->setTargetProperty('reaction.total')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.reactionClass',
                'property_value' => 'Negative'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.recordType',
                'property_value' => 'discussion'
            ])
            // You need to group by reaction.recordType. This is a requirement of leaderboard
            ->setGroupBy(['reaction.recordID', 'reaction.recordType']);

        $this->widgets['top-negative-discussions']['query'] = $topNegativeDiscussions;

        // Top Members by Accumulated Reputation (leaderboard)
        $topMembersByAccumulatedReputation = new KeenIOQuery();
        $topMembersByAccumulatedReputation->setAnalysisType(KeenIOQuery::ANALYSIS_SUM)
            ->setEventCollection('point')
            ->setTargetProperty('point.given.points')
            // This should not be required normally but it is because of a deploy bug
            ->addFilter([
                'operator' => 'ne',
                'property_name' => 'point.given.points',
                'property_value' => 0
            ])
            ->setGroupBy('point.user.userID');

        $this->widgets['top-member-by-accumulated-reputation']['query'] = $topMembersByAccumulatedReputation;

        // Top Members by Total Reputation (leaderboard)
        $topMembersByTotalReputation = new KeenIOQuery();
        $topMembersByTotalReputation->setAnalysisType(KeenIOQuery::ANALYSIS_MAXIMUM)
            ->setEventCollection('point')
            ->setTargetProperty('point.user.points')
            ->setGroupBy('point.user.userID');

        $this->widgets['top-member-by-total-reputation']['query'] = $topMembersByTotalReputation;

        /**
         * Metrics
         */

        // Active Users (metric)
        $totalActiveUsersQuery = new KeenIOQuery();
        $totalActiveUsersQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Active Users'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID')
            ->addFilter([
                'operator' => 'gt',
                'property_name' => 'user.userID',
                'property_value' => 0
            ]);

        $this->widgets['total-active-users']['query'] = $totalActiveUsersQuery;

        // Pageviews (metric)
        $totalPageViewQuery = new KeenIOQuery();
        $totalPageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Page Views'))
            ->setEventCollection('page');
        $this->widgets['total-pageviews']['query'] = $totalPageViewQuery;


        // Visits (metric)
        $totalVisitsQuery = new KeenIOQuery();
        $totalVisitsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Visits'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID');

        $this->widgets['total-visits']['query'] = $totalVisitsQuery;

        // Discussions (metric)
        $totalDiscussionsQuery = new KeenIOQuery();
        $totalDiscussionsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Discussions'))
            ->setEventCollection('post')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'type',
                'property_value' => 'discussion_add'
            ]);

        $this->widgets['total-discussions']['query'] = $totalDiscussionsQuery;

        // Comments (metric)
        $totalCommentsQuery = new KeenIOQuery();
        $totalCommentsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Comments'))
            ->setEventCollection('post')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'type',
                'property_value' => 'comment_add'
            ]);

        $this->widgets['total-comments']['query'] = $totalCommentsQuery;

        // Contributors (metric)
        $totalContributorsQuery = new KeenIOQuery();
        $totalContributorsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors'))
            ->setEventCollection('post')
            ->setTargetProperty('user.userID');

        $this->widgets['total-contributors']['query'] = $totalContributorsQuery;

        // Questions Asked (metric)
        $totalAskedQuery = new KeenIOQuery();
        $totalAskedQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Questions Asked'))
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'discussion_add'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'discussionType',
                'property_value' => 'Question'
            ]);

        $this->widgets['total-asked']['query'] = $totalAskedQuery;

        // Questions Answered (metric)
        $totalAnsweredQuery = new KeenIOQuery();
        $totalAnsweredQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Questions Answered'))
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'comment_add'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'discussion.discussionType',
                'property_value' => 'Question'
            ])
            ->setTargetProperty('discussionID');

        $this->widgets['total-answered']['query'] = $totalAnsweredQuery;

        // Answers Accepted (metric)
        $totalAcceptedQuery = new KeenIOQuery();
        $totalAcceptedQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Answers Accepted'))
            ->setEventCollection('qna')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'answer_accepted'
            ]);

        $this->widgets['total-accepted']['query'] = $totalAcceptedQuery;

        // Average Time to Answer (metric)
        $timeToAnswerQuery = new KeenIOQuery();
        $timeToAnswerQuery->setAnalysisType(KeenIOQuery::ANALYSIS_MEDIAN)
            ->setTitle(t('Average Time to Answer'))
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'comment_add'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'discussion.discussionType',
                'property_value' => 'Question'
            ])
            ->setTargetProperty('commentMetric.time');

        $this->widgets['time-to-answer']['query'] = $timeToAnswerQuery;

        // Average Time to Accept (metric)
        $timeToAcceptQuery = new KeenIOQuery();
        $timeToAcceptQuery->setAnalysisType(KeenIOQuery::ANALYSIS_MEDIAN)
            ->setTitle(t('Average Time to Accept'))
            ->setEventCollection('qna')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'answer_accepted'
            ])
            ->setTargetProperty('commentMetric.time');

        $this->widgets['time-to-accept']['query'] = $timeToAcceptQuery;

         // Posts Positivity Rate (metric)
        $reactedDiscussionsQuery = new KeenIOQuery();
        $reactedDiscussionsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Reacted Discussions'))
            ->setEventCollection('reaction')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.recordType',
                'property_value' => 'discussion'
            ])
            ->setTargetProperty('reaction.recordID');

        $reactedPositiveDiscussionsQuery = new KeenIOQuery();
        $reactedPositiveDiscussionsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Reacted Positive Discussions'))
            ->setEventCollection('reaction')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.recordType',
                'property_value' => 'discussion'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.reactionClass',
                'property_value' => 'Positive'
            ])
            ->setTargetProperty('reaction.recordID');

        $reactedCommentsQuery = new KeenIOQuery();
        $reactedCommentsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Reacted Comments'))
            ->setEventCollection('reaction')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.recordType',
                'property_value' => 'comment'
            ])
            ->setTargetProperty('reaction.recordID');

        $reactedPositiveCommentsQuery = new KeenIOQuery();
        $reactedPositiveCommentsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Reacted Positive Comments'))
            ->setEventCollection('reaction')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.recordType',
                'property_value' => 'comment'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'reaction.reactionClass',
                'property_value' => 'Positive'
            ])
            ->setTargetProperty('reaction.recordID');

        $validationReactedPostsQuery = new KeenIOQuery();
        $validationReactedPostsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Reacted Discussions'))
            ->setEventCollection('reaction')
            ->setGroupBy('reaction.reactionClass')
            ->addFilter([
                'operator' => 'in',
                'property_name' => 'reaction.reactionClass',
                'property_value' => ['Positive', 'Negative']
            ])
            ->setTargetProperty('reaction.reactionClass');

        $this->widgets['posts-positivity-rate']['query'] = [
            $reactedPositiveDiscussionsQuery,
            $reactedPositiveCommentsQuery,
            $reactedDiscussionsQuery,
            $reactedCommentsQuery,
            $validationReactedPostsQuery,
        ];
        $this->widgets['posts-positivity-rate']['queryProcessor'] = [
            'instructions' => [
                'validatedReactedPosts' => [
                    'analyses' => [4],
                    'processor' => 'noop',
                    'validators' => [
                        'validatePropertyValueExisting' => [
                            ['reaction.reactionClass', 'Positive'],
                            ['reaction.reactionClass', 'Negative'],
                        ],
                    ],
                ],
                'reacted-positive-posts' => [
                    'analyses' => [0, 1],
                    'processor' => 'addResults'
                ],
                'reacted-posts' => [
                    'analyses' => [2, 3],
                    'processor' => 'addResults'
                ],
                'positive-reacted-rate' => [
                    'analyses' => ['reacted-positive-posts', 'reacted-posts'],
                    'processor' => 'divideResults'
                ],
            ],
            'finalAnalysis' => 'positive-reacted-rate'
        ];

        // Average Time to First Comment (metric)
        $timeToFirstCommentQuery = new KeenIOQuery();
        $timeToFirstCommentQuery->setAnalysisType(KeenIOQuery::ANALYSIS_MEDIAN)
            ->setTitle(t('Average Time to First Comment'))
            ->setEventCollection('post')
            ->setTargetProperty('commentMetric.time')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'commentMetric.firstComment',
                'property_value' => true
            ])
            ->addFilter([
                'operator' => 'gte',
                'property_name' => 'discussion.dateInserted.timestamp',
                'property_callback' => 'timeframeStart',
            ])
        ;

        $this->widgets['average-time-to-first-comment']['query'] = $timeToFirstCommentQuery;

        // Total Resolved Discussion (metric)
        // Only count first resolution
        $totalDiscussionsResolvedOnCreationQuery = new KeenIOQuery();
        $totalDiscussionsResolvedOnCreationQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Total Resolved Discussions On Creation'))
            ->setEventCollection('post')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.resolved',
                'property_value' => 1
            ])
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.countResolved',
                'property_value' => 1
            ])
        ;
        $totalDiscussionsResolvedOnUpdateQuery = new KeenIOQuery();
        $totalDiscussionsResolvedOnUpdateQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Total Resolved Discussions On Update'))
            ->setEventCollection('post_modify')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.resolved',
                'property_value' => 1
            ])
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.countResolved',
                'property_value' => 1
            ])
        ;
        $this->widgets['total-resolved-discussions']['query'] = [
            $totalDiscussionsResolvedOnCreationQuery,
            $totalDiscussionsResolvedOnUpdateQuery
        ];
        $this->widgets['total-resolved-discussions']['queryProcessor'] = [
            'instructions' => [
                'merged-resolved-discussions' => [
                    'analyses' => [0, 1],
                    'processor' => 'addResults'
                ],
            ],
            'finalAnalysis' => 'merged-resolved-discussions',
        ];

        // Total Unresolved Discussion (metrics)
        $totalDiscussionsUnresolvedOnCreationQuery = new KeenIOQuery();
        $totalDiscussionsUnresolvedOnCreationQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Total Discussion Unresolved On Creation'))
            ->setEventCollection('post')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.resolved',
                'property_value' => 0
            ])
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.countResolved',
                'property_value' => 0
            ])
        ;

        $this->widgets['total-unresolved-discussions']['query'] = [
            $totalDiscussionsUnresolvedOnCreationQuery,
            $totalDiscussionsResolvedOnUpdateQuery
        ];
        $this->widgets['total-unresolved-discussions']['queryProcessor'] = [
            'instructions' => [
                'merged-unresolved-discussions' => [
                    'analyses' => [0, 1],
                    'processor' => 'substractResults'
                ],
            ],
            'finalAnalysis' => 'merged-unresolved-discussions',
        ];

        // Average Time to Resolve Discussion (metric)
        // Does not count discussion created as resolved.
        $averageTimeToResolveDiscussionQuery = new KeenIOQuery();
        $averageTimeToResolveDiscussionQuery->setAnalysisType(KeenIOQuery::ANALYSIS_AVERAGE)
            ->setTitle(t('Average Time to Resolve Discussion'))
            ->setEventCollection('post_modify')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.resolved',
                'property_value' => 1
            ])
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.countResolved',
                'property_value' => 1
            ])
            ->setTargetProperty('resolvedMetric.time')
        ;

        $this->widgets['average-time-to-resolve-discussion']['query'] = $averageTimeToResolveDiscussionQuery;

        /**
         * Pie Charts
         */
        $this->widgets['participation-rate']['query'] = [$totalActiveUsersQuery, $totalContributorsQuery];
        $this->widgets['participation-rate']['queryProcessor'] = [
            'instructions' => [
                'relative-active-users' => [
                    'analyses' => [0, 1],
                    'processor' => 'substractResults'
                ],
                'merged-participation-rate' => [
                    'analyses' => ['relative-active-users', 1],
                    'processor' => 'mergeResults'
                ],
            ],
            'finalAnalysis' => 'merged-participation-rate',
        ];

        /**
         * Bar Charts
         */
        $sentimentRatioQuery = new KeenIOQuery();
        $sentimentRatioQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setEventCollection('reaction')
            ->setGroupBy('reaction.reactionClass')
            ->addFilter([
                'operator' => 'in',
                'property_name' => 'reaction.reactionClass',
                'property_value' => ['Positive', 'Negative']
            ])
            ->setInterval('daily');

        $this->widgets['sentiment-ratio']['query'] = $sentimentRatioQuery;
        $this->widgets['sentiment-ratio']['queryProcessor'] = [
            'instructions' => [
                'validated-sentiment-ratio' => [
                    'analyses' => [0],
                    'processor' => 'noop',
                    'validators' => [
                        'validatePropertyValueExisting' => [
                            ['reaction.reactionClass', 'Positive'],
                            ['reaction.reactionClass', 'Negative'],
                        ],
                    ],
                ],
            ],
            'finalAnalysis' => 'validated-sentiment-ratio',
        ];

        /**
         * Timeframe Charts
         */
        // Pageviews (chart)
        $pageViewQuery = new KeenIOQuery();
        $pageViewQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Page Views'))
            ->setEventCollection('page')
            ->setInterval('daily');

        $this->widgets['pageviews']['query'] = $pageViewQuery;

        // Active Users (chart)
        $activeUsersQuery = new KeenIOQuery();
        $activeUsersQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Active Users'))
            ->setEventCollection('page')
            ->setTargetProperty('user.userID')
            ->setInterval('daily')
            ->addFilter([
                'operator' => 'gt',
                'property_name' => 'user.userID',
                'property_value' => 0
            ]);

        $this->widgets['active-users']['query'] = $activeUsersQuery;

        // Visits (chart)
        $visitsQuery = new KeenIOQuery();
        $visitsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Visits'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID')
            ->setInterval('daily');

        $widgets['visits']['query'] = $visitsQuery;

        // Visits by Role Type
        $visitsByRoleTypeQuery = new KeenIOQuery();
        $visitsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Visits by Role Type'))
            ->setEventCollection('page')
            ->setTargetProperty('user.sessionID')
            ->setInterval('daily')
            ->setGroupBy('user.roleType');

        $this->widgets['visits-by-role-type']['query'] = $visitsByRoleTypeQuery;

        // Discussions
        $discussionsQuery = new KeenIOQuery();
        $discussionsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Discussions'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'discussion_add'
            ]);

        $this->widgets['discussions']['query'] = $discussionsQuery;

        // Comments
        $commentsQuery = new KeenIOQuery();
        $commentsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Comments'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'comment_add'
            ]);

        $this->widgets['comments']['query'] = $commentsQuery;

        // Posts
        $postsQuery = new KeenIOQuery();
        $postsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts'))
            ->setEventCollection('post')
            ->setInterval('daily');

        $this->widgets['posts']['query'] = $postsQuery;

        // Posts by type
        $postsByTypeQuery = new KeenIOQuery();
        $postsByTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts by Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('type');

        $this->widgets['posts-by-type']['query'] = $postsByTypeQuery;

        // Posts by category
        $postsByCategoryQuery = new KeenIOQuery();
        $postsByCategoryQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts by Category'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('categoryAncestors.cat01.name');

        $this->widgets['posts-by-category']['chart']['labelMapping'] = AnalyticsData::getCategoryMap();
        $this->widgets['posts-by-category']['query'] = $postsByCategoryQuery;

        // Posts by role type
        $postsByRoleTypeQuery = new KeenIOQuery();
        $postsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Posts by Role Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setGroupBy('user.roleType');

        $this->widgets['posts-by-role-type']['query'] = $postsByRoleTypeQuery;

        // Contributors (chart)
        $contributorsQuery = new KeenIOQuery();
        $contributorsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID');
        $this->widgets['contributors']['query'] = $contributorsQuery;


        // Contributors by category (chart)
        $contributorsByCategoryQuery = new KeenIOQuery();
        $contributorsByCategoryQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors by Category'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID')
            ->setGroupBy('categoryAncestors.cat01.name');

        $this->widgets['contributors-by-category']['query'] = $contributorsByCategoryQuery;

        // Contributors by role type (chart)
        $contributorsByRoleTypeQuery = new KeenIOQuery();
        $contributorsByRoleTypeQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Contributors by Role Type'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->setTargetProperty('user.userID')
            ->setGroupBy('user.roleType');

        $this->widgets['contributors-by-role-type']['query'] = $contributorsByRoleTypeQuery;

        // Posts per user (chart)
        $this->widgets['posts-per-user']['query'] = [$postsQuery, $activeUsersQuery];
        $this->widgets['posts-per-user']['queryProcessor'] = [
            'instructions' => [
                'divided-posts-per-user' => [
                    'analyses' => [0, 1],
                    'processor' => 'divideResults'
                ],
            ],
            'finalAnalysis' => 'divided-posts-per-user'
        ];

        // Comments per discussion (chart)
        $this->widgets['comments-per-discussion']['query'] = [$commentsQuery, $discussionsQuery];
        $this->widgets['comments-per-discussion']['queryProcessor'] = [
            'instructions' => [
                'divided-comments-per-discussion' => [
                    'analyses' => [0, 1],
                    'processor' => 'divideResults'
                ],
            ],
            'finalAnalysis' => 'divided-comments-per-discussion'
        ];

        // Registrations
        $registrationsQuery = new KeenIOQuery();
        $registrationsQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Registrations'))
            ->setEventCollection('registration')
            ->setInterval('daily');

        $this->widgets['registrations']['query'] = $registrationsQuery;

        // Questions asked
        $questionsAskedQuery = new KeenIOQuery();
        $questionsAskedQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Questions Asked'))
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'discussion_add'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'discussionType',
                'property_value' => 'Question'
            ])
            ->setInterval('daily');

        $this->widgets['questions-asked']['query'] = $questionsAskedQuery;

        // Questions answered
        $questionsAnsweredQuery = new KeenIOQuery();
        $questionsAnsweredQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT_UNIQUE)
            ->setTitle(t('Questions Answered'))
            ->setEventCollection('post')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'comment_add'
            ])
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'discussion.discussionType',
                'property_value' => 'Question'
            ])
            ->setTargetProperty('discussionID')
            ->setInterval('daily');

        $this->widgets['questions-answered']['query'] = $questionsAnsweredQuery;

        // Answers accepted
        $answersAcceptedQuery = new KeenIOQuery();
        $answersAcceptedQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Answers Accepted'))
            ->setEventCollection('qna')
            ->addFilter([
                'operator' => 'eq',
                'property_name' => 'type',
                'property_value' => 'answer_accepted'
            ])
            ->setInterval('daily');

        $this->widgets['answers-accepted']['query'] = $answersAcceptedQuery;

        // Visits per Active User
        $this->widgets['visits-per-active-user']['query'] = [$visitsQuery, $activeUsersQuery];
        $this->widgets['visits-per-active-user']['queryProcessor'] = [
            'instructions' => [
                'divided-visits-per-active-user' => [
                    'analyses' => [0, 1],
                    'processor' => 'divideResults'
                ],
            ],
            'finalAnalysis' => 'divided-visits-per-active-user'
        ];

        // Average Posts per Active User
        $this->widgets['average-posts-per-active-user']['query'] = [$postsQuery, $activeUsersQuery];
        $this->widgets['average-posts-per-active-user']['queryProcessor'] = [
            'instructions' => [
                'divided-average-posts-per-active-user' => [
                    'analyses' => [0, 1],
                    'processor' => 'divideResults'
                ],
            ],
            'finalAnalysis' => 'divided-average-posts-per-active-user'
        ];

        // Average Comments per discussion
        $this->widgets['average-comments-per-discussion']['query'] = [$commentsQuery, $discussionsQuery];
        $this->widgets['average-comments-per-discussion']['queryProcessor'] = [
            'instructions' => [
                'divided-average-comments-per-discussion' => [
                    'analyses' => [0, 1],
                    'processor' => 'divideResults'
                ],
            ],
            'finalAnalysis' => 'divided-average-comments-per-discussion'
        ];

        // Discussion Resolved
        $discussionResolvedOnCreationQuery = new KeenIOQuery();
        $discussionResolvedOnCreationQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Discussion Resolved On Creation'))
            ->setEventCollection('post')
            ->setInterval('daily')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.resolved',
                'property_value' => 1
            ])
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.countResolved',
                'property_value' => 1
            ])
        ;

        $discussionResolvedOnUpdateQuery = new KeenIOQuery();
        $discussionResolvedOnUpdateQuery->setAnalysisType(KeenIOQuery::ANALYSIS_COUNT)
            ->setTitle(t('Discussion Resolved On Update'))
            ->setEventCollection('post_modify')
            ->setInterval('daily')
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.resolved',
                'property_value' => 1
            ])
            ->addFilter([
                'operator'       => 'eq',
                'property_name'  => 'resolvedMetric.countResolved',
                'property_value' => 1
            ])
        ;

        $this->widgets['resolved-discussion']['query'] = [
            $discussionResolvedOnCreationQuery,
            $discussionResolvedOnUpdateQuery
        ];
        $this->widgets['resolved-discussion']['queryProcessor'] = [
            'instructions' => [
                'merged-resolved-discussion' => [
                    'analyses' => [0, 1],
                    'processor' => 'addResults'
                ],
            ],
            'finalAnalysis' => 'merged-resolved-discussion'
        ];
    }

    /**
     * Add CSS files to the current page.
     *
     * @param Gdn_Controller $controller Instance of the current page's controller.
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addCssFiles(Gdn_Controller $controller, $inDashboard = false) {
    }

    /**
     * Add values to the gdn.meta JavaScript array on the page.
     *
     * @param Gdn_Controller $controller Instance of the current page's controller.
     * @param bool $inDashboard Is the current page a dashboard page?
     * @param array $eventData Data for the current event.
     */
    public function addDefinitions(Gdn_Controller $controller, $inDashboard = false, &$eventData = []) {
        $controller->addDefinition('keenio.projectID', $this->client->getProjectID());
        $controller->addDefinition('keenio.writeKey', $this->client->getWriteKey());

        // Make sure we have the structure we need.
        if (!array_key_exists('keen', $eventData)) {
            $eventData['keen'] = [
                'addons' => []
            ];
        } elseif (!array_key_exists('addons', $eventData['keen'])) {
            $eventData['keen']['addons'] = [];
        }

        if (!empty($eventData['referrer'])) {
            $eventData['keen']['addons'][] = [
                'name' => 'keen:referrer_parser',
                'input' => [
                    'referrer_url' => 'referrer',
                    'page_url' => 'url'
                ],
                'output' => 'referrerParsed'
            ];
        }

        if ($inDashboard) {
            $controller->addDefinition('keenio.readKey', $this->client->getReadKey());
        }
    }

    /**
     * Add JavaScript files to the current page.
     *
     * @param Gdn_Controller $controller Instance of the current page's controller.
     * @param bool $inDashboard Is the current page a dashboard page?
     */
    public function addJsFiles(Gdn_Controller $controller, $inDashboard = false) {
        if (!AnalyticsTracker::getInstance()->trackingDisabled() || $inDashboard) {
            $controller->addJsFile('vendors/keen.min.js', 'plugins/vanillaanalytics');
        }

        if (!AnalyticsTracker::getInstance()->trackingDisabled()) {
            $controller->addJsFile('keenio.min.js', 'plugins/vanillaanalytics');
        }

        if ($inDashboard) {
            $controller->addJsFile('keeniowidget.min.js', 'plugins/vanillaanalytics');
            $controller->addJsFile('keeniofiltercallback.min.js', 'plugins/vanillaanalytics');
            $controller->addJsFile('keenioanalysisprocessor.min.js', 'plugins/vanillaanalytics');
        }
    }

    /**
     * Overwrite and append default key/value pairs to incoming array.
     *
     * @link https://keen.io/docs/api/#data-enrichment
     * @param array $defaults List of default data pairs for all events.
     * @return array
     */
    public function addDefaults(array $defaults = []) {
        $additionalDefaults = [
            'keen' => [
                'addons' => [
                    [
                        'name' => 'keen:ip_to_geo',
                        'input' => [
                            'ip' => 'ip'
                        ],
                        'output' => 'ipGeo'
                    ],
                    [
                        'name' => 'keen:url_parser',
                        'input' => [
                            'url' => 'url'
                        ],
                        'output' => 'urlParsed'
                    ]
                ]
            ]
        ];

        $defaults = array_merge($defaults, $additionalDefaults);

        if (!empty($eventData['userAgent'])) {
            $eventData['keen']['addons'][] = [
                'name' => 'keen:ua_parser',
                'input' => [
                    'ua_string' => 'userAgent'
                ],
                'output' => 'userAgentParsed'
            ];
        }

        return $defaults;
    }

    /**
     * Record an event using the keen.io API.
     *
     * @param string $collection Grouping for the current event.
     * @param string $type Name/type of the event being tracked.
     * @param array $details A collection of details about the event.
     * @return array Body of response from keen.io
     */
    public function event($collection, $type, array $details = []) {
        $details['type'] = $type;

        return $this->client->addEvent($collection, $details);
    }


    /**
     * Detect if tracker is configured for use.
     *
     * @param bool $disableWrite Disable writing to the tracker?
     * @param bool $disableRead Disable reading from the tracker?
     * @return bool True on configured, false otherwise
     */
    public static function isConfigured($disableWrite = false, $disableRead = false) {
        $configured = false;

        // Do we at least have a project ID configured?
        if (c('VanillaAnalytics.KeenIO.ProjectID')) {
            // Do we need either a read or write key and have them configured?
            if (($disableWrite || c('VanillaAnalytics.KeenIO.WriteKey')) &&
                ($disableRead || c('VanillaAnalytics.KeenIO.ReadKey'))) {
                $configured = true;
            }
        }

        return $configured;
    }

    /**
     * Setup routine, called when plug-in is enabled.
     */
    public function setup()
    {
        if (!c('VanillaAnalytics.KeenIO.ProjectID')) {
            // Attempt to grab all the necessary data for creating a project with keen.io
            $defaultProjectUser = c('VanillaAnalytics.KeenIO.DefaultProjectUser');
            $site = class_exists('Infrastructure') ? Infrastructure::site('name') : c('Garden.Domain', null);
            $orgID = c('VanillaAnalytics.KeenIO.OrgID');
            $orgKey = c('VanillaAnalytics.KeenIO.OrgKey');

            // All of these pieces are essential for creating a project.  Fail without them.
            if (!$orgID) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgID');
            }
            if (!$orgKey) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgKey');
            }
            if (!$defaultProjectUser) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.DefaultProjectUser');
            }

            // Build the keen.io client and attempt to create a new project
            $keenIOConfig = [
                'orgID' => $orgID,
                'orgKey' => $orgKey
            ];
            $keenIOClient = new KeenIOClient(null, $keenIOConfig);

            $project = $keenIOClient->addProject(
                $site,
                [
                    ['email' => $defaultProjectUser]
                ]
            );

            // If we were successful, save the details.  If not, trigger an error.
            if ($project) {
                saveToConfig('VanillaAnalytics.KeenIO.ProjectID', $project['id']);
                saveToConfig('VanillaAnalytics.KeenIO.ReadKey', $project['apiKeys']['readKey']);
                saveToConfig('VanillaAnalytics.KeenIO.WriteKey', $project['apiKeys']['writeKey']);
            } else {
                throw new Gdn_UserException('Unable to create project on keen.io');
            }
        }
    }
}
