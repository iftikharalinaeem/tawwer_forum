<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use Garden\EventManager;
use Garden\Events\ResourceEvent;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\ExtendedContentFormatService;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\UpdateMediaTrait;
use Vanilla\Knowledge\Events\ArticleEvent;
use Vanilla\Knowledge\Models\DefaultArticleModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\KnowledgeNavigationModel;
use Vanilla\Models\DraftModel;
use Vanilla\Models\Model;
use Vanilla\UploadedFile;

/**
 * API controller helper functions.
 */
class ArticlesApiHelper {

    use UpdateMediaTrait;
    use ArticlesApiSchemes;

    const REHOST_SUCCESS_HEADER = 'x-file-rehosted-success-count';
    const REHOST_FAILED_HEADER = 'x-file-rehosted-failed-count';

    /** @var ArticleModel */
    private $articleModel;

    /** @var ArticleRevisionModel */
    private $articleRevisionModel;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var KnowledgeNavigationModel */
    private $knowledgeNavigationModel;

    /** @var DefaultArticleModel */
    private $defaultArticleModel;

    /** @var \DiscussionsApiController */
    private $discussionApi;

    /** @var DraftModel */
    private $draftModel;

    /** @var \Gdn_Session */
    private $session;

    /** @var FormatService */
    private $formatService;

    /** @var \Gdn_Upload */
    private $upload;

    /** @var EventManager */
    private $eventManager;

    /**
     * DI.
     *
     * @inheritdoc
     */
    public function __construct(
        ArticleModel $articleModel,
        ArticleRevisionModel $articleRevisionModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        KnowledgeNavigationModel $knowledgeNavigationModel,
        DefaultArticleModel $defaultArticleModel,
        \DiscussionsApiController $discussionApi,
        DraftModel $draftModel,
        \Gdn_Session $session,
        \MediaModel $mediaModel,
        ExtendedContentFormatService $formatService,
        \Gdn_Upload $upload,
        EventManager $eventManager
    ) {
        $this->articleModel = $articleModel;
        $this->articleRevisionModel = $articleRevisionModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->knowledgeNavigationModel = $knowledgeNavigationModel;
        $this->defaultArticleModel = $defaultArticleModel;
        $this->discussionApi = $discussionApi;
        $this->draftModel = $draftModel;
        $this->session = $session;
        $this->formatService = $formatService;
        $this->upload = $upload;
        $this->eventManager = $eventManager;

        $this->setMediaForeignTable("article");
        $this->setMediaModel($mediaModel);
        $this->setFormatterService($formatService);
        $this->setSessionInterface($session);
    }


    /**
     * Massage article row data for useful API output.
     *
     * @param array $row
     * @return array
     * @throws ServerException If no article ID was found in the row.
     */
    public function normalizeOutput(array $row): array {
        $articleID = $row["articleID"] ?? null;
        if (!$articleID) {
            throw new ServerException("No ID in article row.");
        }
        if (!isset($row['url'])) {
            $row["url"] = $this->articleModel->url($row);
        }

        if (isset($row["queryLocale"])) {
            $row["translationStatus"] = ($row["locale"] === $row["queryLocale"]) ?
                ArticleRevisionModel::STATUS_TRANSLATION_UP_TO_DATE :
                ArticleRevisionModel::STATUS_TRANSLATION_NOT_TRANSLATED;
        }

        if (isset($row['bodyRendered']) ?? !isset($row['body'])) {
            $bodyRendered = $row["bodyRendered"] ?? null;
            $row["body"] = $bodyRendered;
        }
        $row["outline"] = isset($row["outline"]) && is_string($row['outline']) ? json_decode($row["outline"], true) : [];
        // Placeholder data.
        $row["seoName"] = null;
        $row["seoDescription"] = null;
        $row["slug"] = $this->articleModel->getSlug($row);
        $row["featured"] = (bool)$row['featured'];
        return $row;
    }

    /**
     * Get an knowledge category by its numeric ID.
     *
     * @param int $id Knowledge category ID.
     * @param bool $includeDeleted Include knowledge category which belongs to knowledge base "deleted"
     * @return array
     * @throws NotFoundException If the knowledge category could not be found.
     * @throws ValidationException If the result fails schema validation.
     */
    public function knowledgeCategoryByID(int $id, bool $includeDeleted = false): array {
        try {
            $knowledgeCategory = $this->knowledgeCategoryModel->selectSingle(["knowledgeCategoryID" => $id]);
            if (!$includeDeleted) {
                $this->knowledgeBaseModel->checkKnowledgeBasePublished($knowledgeCategory['knowledgeBaseID']);
            }
        } catch (NoResultsException $e) {
            throw new NotFoundException("Knowledge Category");
        }
        return $knowledgeCategory;
    }

    /**
     * Get the article translation data for the api.
     *
     * @param array $article
     * @return array
     */
    public function getArticleTranslationData(array $article): array {
        $result = [];
        $firstRevision = reset($article);

        $knowledgeBase = $this->getKnowledgeBaseFromCategoryID($firstRevision["knowledgeCategoryID"]);
        $allLocales = $this->knowledgeBaseModel->getLocales($knowledgeBase["siteSectionGroup"]);

        foreach ($allLocales as $locale) {
            $matchingArticleTranslation = null;
            foreach ($article as $translation) {
                if ($translation['locale'] === $locale['locale']) {
                    $matchingArticleTranslation = $translation;
                    break;
                }
            }

            if ($matchingArticleTranslation) {
                $translation['queryLocale'] = $locale['locale'];
                $url = $this->articleModel->url($translation);

                $result[] = [
                    "articleRevisionID" => $translation["articleRevisionID"],
                    "name" => $translation["name"],
                    "url" => $url,
                    "locale" => $translation["locale"],
                    "sourceLocale" => $knowledgeBase["sourceLocale"],
                    "translationStatus" => $translation["translationStatus"],
                    "dateUpdated" => $translation["dateUpdated"],
                ];
            } else {
                $articleToGenerateFrom = $firstRevision + ['queryLocale' => $locale['locale']];
                $notFound = [
                    "articleRevisionID" => -1,
                    "name" => '',
                    "url" => $this->articleModel->url($articleToGenerateFrom),
                    "locale" => $locale["locale"],
                    "sourceLocale" => $knowledgeBase["sourceLocale"],
                    "translationStatus" => ArticleRevisionModel::STATUS_TRANSLATION_NOT_TRANSLATED,
                    "dateUpdated" => $firstRevision["dateUpdated"],
                ];
                $result[] = $notFound;
            }
        }
        return $result;
    }

    /**
     * Get a knowledge-base by a category ID.
     *
     * @param int $id
     * @return array
     */
    public function getKnowledgeBaseFromCategoryID(int $id): array {
        $knowledgeBaseCategoryFragement = $this->knowledgeCategoryModel->selectSingleFragment($id);
        $knowledgeBaseID = $knowledgeBaseCategoryFragement->getKnowledgeBaseID();
        $knowledgeBase = $this->knowledgeBaseModel->get(["knowledgeBaseID" => $knowledgeBaseID]);
        $knowledgeBase = reset($knowledgeBase);
        return $knowledgeBase;
    }

    /**
     * Get an article row by it's id and locale.
     *
     * @param int $id
     * @param array $query
     * @return array
     * @throws ClientException When no record is found.
     */
    public function retrieveRow(int $id, array $query = []): array {
        $article = $this->articleModel->selectSingle(["articleID" => $id]);
        $knowledgeBase = $this->getKnowledgeBaseFromCategoryID($article["knowledgeCategoryID"]);
        $this->knowledgeBaseModel->checkViewPermission($knowledgeBase['knowledgeBaseID']);

        $options = [];
        $where = [];

        $options['only-translated'] = (isset($query['only-translated'])) ? $query['only-translated'] : false;

        if ($options['only-translated']) {
            $where['ar.locale'] = $query['locale'] ?? $knowledgeBase['sourceLocale'];
        } else {
            $where['ar.locale'] = $knowledgeBase['sourceLocale'];
            if (!empty($query['locale']) && $query['locale'] !== $where['ar.locale']) {
                $options['arl.locale'] = $query['locale'];
            }
        }

        $where["a.articleID"] = $id;
        $where["kb.status"] = KnowledgeBaseModel::STATUS_PUBLISHED;
        $options["limit"] = ArticleRevisionModel::DEFAULT_LIMIT;

        $record = $this->articleModel->getWithRevision($where, $options);
        $record = reset($record);

        if (!$record) {
            throw new NotFoundException("Article");
        }

        if ($article['status'] !== ArticleModel::STATUS_PUBLISHED) {
            try {
                // Deleted articles have a special permission check.
                $this->knowledgeBaseModel->checkEditPermission($record['knowledgeBaseID']);
            } catch (PermissionException $e) {
                throw new ClientException('This article has been deleted.', 410);
            }
        }

        $record = $this->normalizeOutput($record);
        $record = $this->articleSchema()->validate($record);
        return $record;
    }


    /**
     * Check if an locale is supported by a knowledge-base.
     *
     * @param string $locale
     * @param array $knowledgeBase
     * @throws ClientException If locale is not supported.
     */
    public function checkKbSupportsLocale(string $locale, array $knowledgeBase) {
        $allLocales = $this->knowledgeBaseModel->getLocales($knowledgeBase["siteSectionGroup"]);
        $allLocales = array_column($allLocales, "locale");
        $allLocales[] = $knowledgeBase["sourceLocale"];
        $supportedLocale = in_array($locale, $allLocales);
        if (!$supportedLocale) {
            throw new ClientException("Locale {$locale} not supported in this Knowledge-Base");
        }
    }

    /**
     * Update translation status to out-of-date.
     *
     * @param int $id
     */
    public function updateInvalidateArticleTranslations(int $id) {
        $articles = $this->articleModel->getIDWithRevision($id, true);
        $firstArticle = reset($articles);
        $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $firstArticle["knowledgeBaseID"]]);
        $supportedLocales = $this->knowledgeBaseModel->getSupportedLocalesByKnowledgeBase($knowledgeBase);
        $locales = array_diff($supportedLocales, [$knowledgeBase["sourceLocale"]]);

        $this->articleRevisionModel->update(
            [
                "translationStatus" => ArticleRevisionModel::STATUS_TRANSLATION_OUT_TO_DATE
            ],
            [
                "articleID" => $id,
                "locale" => $locales,
                "status" => ArticleModel::STATUS_PUBLISHED,
            ]
        );
    }

    /**
     * Get db operation mode for pipeline model (force || default).
     *
     * @return string
     */
    public function getOperationMode(): string {
        return $this->session->checkPermission('knowledge.articles.manage')
            ? Operation::MODE_IMPORT
            : Operation::MODE_DEFAULT;
    }

    /**
     * Get filed name list for article revision record
     *
     * @return array
     */
    private function getRevisionFields(): array {
        $fields = ["body" => true, "format" => true, "locale" => true, "name" => true];
        if ($this->getOperationMode() === Operation::MODE_IMPORT) {
            $fields['dateInserted'] = true;
        }
        return $fields;
    }

    /**
     * Fire an update resource event for all locale variants.
     *
     * @param int $articleID
     */
    public function fireUpdateEventForAllLocales(int $articleID) {
        // Fire resource events for every updated record.
        $allLocaleVariants = $this->articleModel->getIDWithRevision($articleID, true);
        $articleSchema = $this->articleSchema();
        foreach ($allLocaleVariants as $localeVariant) {
            $localeVariant = $this->normalizeOutput($localeVariant);
            $localeVariant = $articleSchema->validate($localeVariant);
            $event = new ArticleEvent(ResourceEvent::ACTION_UPDATE, ['article' => $localeVariant]);
            $this->eventManager->dispatch($event);
        }
    }

    /**
     * Separate article and revision fields from request input and save to the proper resources.
     *
     * @param array $fields
     * @param int|null $articleID
     * @return int
     * @throws \Exception If an error is encountered while performing underlying database operations.
     * @throws NoResultsException If the article could not be found.
     */
    public function save(array $fields, int $articleID = null): int {
        $revisionFields = $this->getRevisionFields();

        $article = array_diff_key($fields, $revisionFields);
        $revision = array_intersect_key($fields, $revisionFields);
        if (isset($article['dateUpdated'])) {
            // The dateUpdated should be used for the revision.
            $revision['dateInserted'] = $article['dateUpdated'];
        }

        $locale = $fields["locale"] ?? null;

        if ($articleID !== null) {
            // this means we patch existing Article
            if ($previousRevisionID = $fields['previousRevisionID'] ?? false) {
                $prevState = $this->articleModel->getIDWithRevision($articleID);
                if ($prevState['articleRevisionID'] !== $previousRevisionID) {
                    throw new ClientException("Article revision ID is outdated. Current revision ID is: ".$prevState['articleRevisionID'], 409);
                }
            } else {
                $prevState = $this->articleModel->getID($articleID);
            }

            $knowledgeCategory = $this->knowledgeCategoryByID($article['knowledgeCategoryID'] ?? $prevState['knowledgeCategoryID']);
            $knowledgeBase = $this->getKnowledgeBaseFromCategoryID($knowledgeCategory["knowledgeCategoryID"]);

            // If the locale is passed check if it is supported.
            $locale = $locale ?? $knowledgeBase["sourceLocale"];
            if ($fields['validateLocale'] ?? true) {
                $this->checkKbSupportsLocale($locale, $knowledgeBase);
            }

            //check if knowledge category exists and knowledge base is "published"
            $this->knowledgeCategoryByID($article['knowledgeCategoryID'] ?? $prevState['knowledgeCategoryID']);

            $moveToAnotherCategory = (isset($article['knowledgeCategoryID'])
                && $prevState['knowledgeCategoryID'] !== $article['knowledgeCategoryID']);

            if (!is_int($fields['sort'] ?? false)) {
                if ($moveToAnotherCategory) {
                    $sortInfo = $this->knowledgeCategoryModel->getMaxSortIdx($article['knowledgeCategoryID']);
                    $maxSortIndex = $sortInfo['maxSort'];
                    $article['sort'] = $maxSortIndex + 1;
                    $updateSorts = false;
                } else {
                    // if we don't change the categoryID and there is no $fields['sort']
                    // then we don't need to update sorting
                    $updateSorts = false;
                }
            } else {
                //update sorts for other records only if 'sort' changed
                $updateSorts = ($article['sort'] != $prevState['sort']);
            }

            if (isset($article['sort'])
                && isset($prevState['knowledgeCategoryID'])
                && isset($prevState['sort'])
                && $article['sort'] != $prevState['sort']) {
                $this->knowledgeCategoryModel->updateCounts($prevState['knowledgeCategoryID']);
                //shift sorts down for source category when move one article to another category
                $this->knowledgeCategoryModel->shiftSorts(
                    $prevState['knowledgeCategoryID'],
                    $prevState['sort'],
                    $prevState['articleID'],
                    KnowledgeCategoryModel::SORT_TYPE_ARTICLE,
                    KnowledgeCategoryModel::SORT_DECREMENT
                );
            }

            $this->articleModel->update($article, ["articleID" => $articleID], [Model::OPT_MODE => $this->getOperationMode()]);

            if ($moveToAnotherCategory) {
                if (!empty($prevState['knowledgeCategoryID'])) {
                    $this->knowledgeCategoryModel->updateCounts($prevState['knowledgeCategoryID']);
                }
            }

            if ($updateSorts) {
                $this->knowledgeCategoryModel->shiftSorts(
                    $article['knowledgeCategoryID'] ?? $prevState['knowledgeCategoryID'],
                    $article['sort'],
                    $articleID,
                    KnowledgeCategoryModel::SORT_TYPE_ARTICLE
                );
                if ($prevState['knowledgeCategoryID'] !== $knowledgeCategory["knowledgeCategoryID"]) {
                    $this->updateDefaultArticleID($prevState['knowledgeCategoryID']);
                }
                $this->updateDefaultArticleID($knowledgeCategory["knowledgeCategoryID"]);
            }
        } else {
            //check if knowledge category exists and knowledge base is "published"
            $this->knowledgeCategoryByID($fields['knowledgeCategoryID']);
            // this means we insert a new Article
            $sortInfo = $this->knowledgeCategoryModel->getMaxSortIdx($fields['knowledgeCategoryID']);
            $maxSortIndex = $sortInfo['maxSort'];
            if (!is_int($fields['sort'] ?? false)) {
                if ($sortInfo['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
                    $fields['sort'] = $maxSortIndex + 1;
                }
                $updateSorts = false;
            } else {
                if ($sortInfo['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
                    $updateSorts = ($fields['sort'] <= $maxSortIndex);
                } else {
                    // when KB is in Help center mode or KB is not in Manual sorting mode
                    // we don't need to update sorting from Articles API
                    $updateSorts = false;
                }
            }
            if (!empty($fields["insertUserID"] ?? null)) {
                $fields["updateUserID"] = $fields["insertUserID"];
            }
            $articleID = $this->articleModel->insert($fields, [Model::OPT_MODE => $this->getOperationMode()]);
            if ($updateSorts) {
                $this->knowledgeCategoryModel->shiftSorts(
                    $fields['knowledgeCategoryID'],
                    $fields['sort'],
                    $articleID,
                    KnowledgeCategoryModel::SORT_TYPE_ARTICLE
                );
            }
            $this->updateDefaultArticleID($fields['knowledgeCategoryID']);
        }
        if (!empty($article['knowledgeCategoryID'])) {
            $this->knowledgeCategoryModel->updateCounts($article['knowledgeCategoryID']);
        }

        $isUpdate = false;
        if (!empty($revision)) {
            // Grab the current published revisions from each locale, if available, to load as initial defaults.
            $articles = $this->articleModel->getIDWithRevision($articleID, true);

            $currentRevision =[];
            foreach ($articles as $article) {
                // find the unique published revision in the give locale.
                if ($article["locale"] === $locale) {
                    $currentRevision = array_intersect_key($article, $revisionFields);
                    break;
                }
            }

            if (!empty($currentRevision)) {
                // We have an existing revision for this locale. This means it's an update.
                // This means it's an update.
                $isUpdate = true;
            }

            $revision = array_merge($currentRevision, $revision);
            $revision["articleID"] = $articleID;
            $revision["locale"] = $locale;

            // Temporary defaults until drafts are implemented, at which point these fields will be required.
            $revision["name"] = $revision["name"] ?? "";
            $revision["body"] = $revision["body"] ?? "";
            $revision["format"] = $revision["format"] ?? HtmlFormat::FORMAT_KEY;

            // Temporary hack to avoid a Rich format error if we have no body.
            if ($revision["body"] === "" && $revision["format"] === RichFormat::FORMAT_KEY) {
                $revision["body"] = "[]";
            }

            $images = $this->formatterService->parseImageUrls($revision['body'], $revision['format']);
            $revision['seoImage'] = count($images) > 0 ? $images[0] : null;
            $revision["bodyRendered"] = $this->formatterService->renderHTML($revision['body'], $revision['format']);
            $revision["plainText"] = $this->formatterService->renderPlainText($revision['body'], $revision['format']);
            $revision["excerpt"] =  $this->formatterService->renderExcerpt($revision['body'], $revision['format']);
            $revision["outline"] =  json_encode($this->formatterService->parseHeadings($revision['body'], $revision['format']));
            $revision["translationStatus"] = ArticleRevisionModel::STATUS_TRANSLATION_UP_TO_DATE;
            $revision["insertUserID"] = $fields["insertUserID"] ?? null;
            $revision["updateUserID"] =  $fields["updateUserID"] ?? $revision["insertUserID"] ?? null;

            if (!$currentRevision) {
                $revision["status"] = "published";
                $this->articleRevisionModel->insert($revision, [Model::OPT_MODE => $this->getOperationMode()]);
            } else {
                $articleRevisionID = $this->articleRevisionModel->insert($revision, [Model::OPT_MODE => $this->getOperationMode()]);
                $this->articleRevisionModel->publish($articleRevisionID, $revision["insertUserID"] ?? null, $this->getOperationMode());
            }

            if (isset($fields['knowledgeCategoryID'])) {
                $this->updateDefaultArticleID($fields['knowledgeCategoryID']);
            }
            $this->flagInactiveMedia($articleID, $revision["body"], $revision["format"]);
            $this->refreshMediaAttachments($articleID, $revision["body"], $revision["format"]);
        }

        // The article has been updated or saved. Make sure we dispatch a resource event.
        $article = $this->retrieveRow($articleID, ['locale' => $locale]);
        $event = new ArticleEvent($isUpdate ? ResourceEvent::ACTION_UPDATE : ResourceEvent::ACTION_INSERT, ['article' => $article]);
        $this->eventManager->dispatch($event);

        if ($fields['discussionID'] ?? false) {
            // canonicalize discussion
            $article = $this->articleModel->getIDWithRevision($articleID);
            $articleUrl = $this->articleModel->url($article);
            $this->discussionApi->put_canonicalUrl($fields['discussionID'], ['canonicalUrl' => $articleUrl]);
        }

        if (array_key_exists("draftID", $fields)) {
            $this->draftModel->delete([
                "draftID" => $fields["draftID"],
                "recordType" => "article",
            ]);
        }

        return $articleID;
    }

    /**
     * Update parent KB default article ID when needed
     *
     * @param int $knowledgeCategoryID
     *
     * @return int|null
     */
    public function updateDefaultArticleID(int $knowledgeCategoryID): ?int {
        $knowledgeBase = $this->getKnowledgeBaseFromCategoryID($knowledgeCategoryID);
        $defaultArticleID = null;
        if ($knowledgeBase['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
            $kbID = $knowledgeBase['knowledgeBaseID'];
            $defaultArticleID = $this->knowledgeNavigationModel->getDefaultArticleID($kbID);
            $this->defaultArticleModel->update(['defaultArticleID' => $defaultArticleID], ['knowledgeBaseID' => $kbID]);
        }
        return $defaultArticleID;
    }

    /**
     * Rehost the images in an article.
     *
     * @param array $requestBody The request body of a POST/PATCH request.
     * @return array A tuple of [$modifiedRow, $replaceCount, $failedCount].
     */
    public function rehostArticleImages(array $requestBody): array {
        $shouldRehost = $requestBody['fileRehosting']['enabled'] ?? false;
        $body = $requestBody['body'] ?? null;
        $format = $requestBody['format'] ?? null;

        if (!$shouldRehost || !$body || !$format) {
            return [$requestBody, []];
        }

        $rehostRequestHeaders = $requestBody['fileRehosting']['requestHeaders'] ?? [];

        $allUrls = $this->formatService->parseImageUrls($body, $format);
        $attachments = $this->formatService->parseAttachments($body, $format);
        foreach ($attachments as $attachment) {
            $allUrls[] = $attachment->url;
        }
        $failedCount = 0;
        $successCount = 0;

        $oldUrlToNewUrlMap = [];
        foreach ($allUrls as $url) {
            if ($this->upload->isOwnWebPath($url)) {
                continue;
            }

            // Check if we've already migrated it.
            try {
                $result = $this->mediaModel->findUploadedMediaByForeignUrl($url);
            } catch (NotFoundException $e) {
                $result = null;
            }

            if ($result) {
                $oldUrlToNewUrlMap[$url] = $result['url'];
                $body = str_replace($url, $result['url'], $body);
                $successCount++;
            } else {
                // We need to clone the file.
                try {
                    $upload = UploadedFile::fromRemoteResourceUrl($url, $rehostRequestHeaders);
                    $upload->persistUpload();
                    $result = $this->mediaModel->saveUploadedFile($upload);
                    $body = str_replace($url, $result['url'], $body);
                    $successCount++;
                } catch (\Exception $e) {
                    trigger_error($e, E_USER_NOTICE);
                    $failedCount++;
                }
            }
        }
        $requestBody['body'] = $body;
        $rehostResponseHeaders = [
            self::REHOST_SUCCESS_HEADER => $successCount,
            self::REHOST_FAILED_HEADER => $failedCount,
        ];
        return [$requestBody, $rehostResponseHeaders];
    }
}
