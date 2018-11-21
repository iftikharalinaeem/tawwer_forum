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
     * @return string
     */
    public static function getPlainText(string $body): string {
        $text = '';
        $body = json_decode($body, true);
        if (is_array($body) && count($body) > 0) {
            $parser = (new Parser());
            $blotGroups = $parser->parse($body);

            /** @var BlotGroup $blotGroup */
            foreach ($blotGroups as $blotGroup) {
                $text .= $blotGroup->getUnsafeText();
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
        $str = mb_substr($body, 0, self::EXCERPT_MAX_LENGTH);
        $str = mbereg_replace("\n", ' ', $str);
        $str = mbereg_replace("\s{2,}", ' ', $str);
        if (mb_strlen($str) === self::EXCERPT_MAX_LENGTH) {
            if ($lastSpace = mb_strrpos($str, ' ')) {
                $str = mb_substr($str, 0, $lastSpace);
            }
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
    public static function prepareDraftFields(array $body): array {
        if ($bodyContent = ($body['body']['bodyContent'] ?? false)) {
            if ($body['body']['bodyFormat'] === self::BODY_TYPE_RICH) {
                $bodyContent = self::getPlainText($bodyContent);
            }
            $body['attributes']['excerpt'] = self::getExcerpt($bodyContent);
        }
        return $body;
    }
}
