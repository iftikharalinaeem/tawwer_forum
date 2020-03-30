<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Formatting\ExtendedContentFormatService;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;

/**
 * A class for dealing with article draft.
 */
class ArticleDraft {
    /* @var FormatService */
    protected $formatService;

    /**
     * ArticleDraft constructor.
     *
     * @param FormatService $formatService Blot formatService
     */
    public function __construct(ExtendedContentFormatService $formatService) {
        $this->formatService = $formatService;
    }

    /**
     * Prepare article data to ba saved as a draft
     *
     * @param array $body Incoming request validated arguments array.
     *
     * @return array
     */
    public function prepareDraftFields(array $body): array {
        if ($bodyContent = ($body['body'] ?? false)) {
            $body['attributes']['body'] = $bodyContent;
            $body['attributes']['format'] = $body['format'] ?? RichFormat::FORMAT_KEY;
            $body['attributes']['excerpt'] = $this->formatService->renderExcerpt($bodyContent, $body['attributes']['format']);
        }

        return $body;
    }

    /**
     * Prepare article draft data to be output according to article draft schema
     *
     * @param array $drafts Data from contentDraft records.
     * @param bool $singleMode Single or multi line mode
     *
     * @return array
     */
    public function normalizeDraftFields(array $drafts, bool $singleMode = true): array {
        $res = [];
        if ($singleMode) {
            $drafts = [$drafts];
        }
        foreach ($drafts as $draftRow) {
            $draftRow['body'] = $draftRow['attributes']['body'] ?? '[]';
            if (is_array($draftRow['body'])) {
                $draftRow['body'] = json_encode($draftRow['body']);
            }
            $draftRow['excerpt'] = $draftRow['attributes']['excerpt'] ?? '';
            $draftRow['format'] = $draftRow['attributes']['format'] ?? RichFormat::FORMAT_KEY;
            unset($draftRow['attributes']['body']);
            unset($draftRow['attributes']['format']);
            unset($draftRow['attributes']['excerpt']);
            $res[] = $draftRow;
        }
        return $singleMode ? $res[0] : $res;
    }
}
