<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Formatting\Quill\BlotGroup;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingTerminatorBlot;
use Vanilla\Formatting\Quill\Parser;

/**
 * A class for dealing with article draft.
 */
class ArticleDraft {
    // Maximum length before article excerpts are truncated.
    const EXCERPT_MAX_LENGTH = 325;

    const BODY_TYPE_RICH = 'rich';
    const BODY_TYPE_HTML = 'html';
    const BODY_TYPE_TEXT = 'text';
    const BODY_TYPE_MD = 'markdown';

    /* @var Parser */
    protected $parser;

    /**
     * ArticleDraft constructor.
     *
     * @param Parser $parser Blot parser
     */
    public function __construct(Parser $parser) {
        $this->parser = $parser;
    }

    /**
     * Generate outline array from article body
     *
     * @param string $body
     * @return array
     */
    public static function getOutline(string $body): array {
        $outline = [];
        $body = json_decode($body, true);
        if (is_array($body) && count($body) > 0) {
            $parser = (new Parser())
                ->addBlot(HeadingTerminatorBlot::class);
            $blotGroups = $parser->parse($body);

            /** @var BlotGroup $blotGroup */
            foreach ($blotGroups as $blotGroup) {
                $blot = $blotGroup->getPrimaryBlot();
                if ($blot instanceof HeadingTerminatorBlot && $blot->getReference()) {
                    $outline[] = [
                        'ref' => $blot->getReference(),
                        'level' => $blot->getHeadingLevel(),
                        'text' => $blotGroup->getUnsafeText(),
                    ];
                }
            }
        }
        return $outline;
    }

    /**
     * Generate plain text from article body in rich format
     *
     * @param string $body
     * @param int $maxLength Max text length required for excerpt.
     *     It does not limit the exact $maxLength number of characters,
     *     but prevent extra calculations and break the loop when enough text generated.
     *
     * @return string
     */
    public function getPlainText(string $body, int $maxLength = 0): string {
        $text = '';
        $body = json_decode($body, true);
        if (is_array($body) && count($body) > 0) {
            $parser = $this->parser;
            $blotGroups = $parser->parse($body);

            /** @var BlotGroup $blotGroup */
            foreach ($blotGroups as $blotGroup) {
                $text .= $blotGroup->getUnsafeText();
                if ($maxLength > 0 && mb_strlen($text) > self::EXCERPT_MAX_LENGTH) {
                    break;
                }
            }
        }
        return $text;
    }

    /**
     * Cut plain text string to excerpt
     *
     * @param string $body Plaintext body string.
     *
     * @return string
     */
    public static function getExcerpt(string $body): string {

        $str = mbereg_replace("\n", ' ', $body);
        $str = mbereg_replace("\s{2,}", ' ', $str);
        if (mb_strlen($str) > self::EXCERPT_MAX_LENGTH) {
            $str = mb_substr($str, 0, self::EXCERPT_MAX_LENGTH);
            if (mb_strlen($str) === self::EXCERPT_MAX_LENGTH) {
                if ($lastSpace = mb_strrpos($str, ' ')) {
                    $str = mb_substr($str, 0, $lastSpace);
                }
            }
            $str .= '…';
        }
        return $str;
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
            $body['attributes']['format'] = $body['format'] ?? self::BODY_TYPE_RICH;
            if ($body['format'] === self::BODY_TYPE_RICH) {
                $bodyContent = $this->getPlainText($bodyContent, self::EXCERPT_MAX_LENGTH);
            }
            $body['attributes']['excerpt'] = self::getExcerpt($bodyContent);
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
            $draftRow['excerpt'] = $draftRow['attributes']['excerpt'] ?? '';
            $draftRow['format'] = $draftRow['attributes']['format'] ?? self::BODY_TYPE_RICH;
            unset($draftRow['attributes']['body']);
            unset($draftRow['attributes']['format']);
            unset($draftRow['attributes']['excerpt']);
            $res[] = $draftRow;
        }
        return $singleMode ? $res[0] : $res;
    }
}
