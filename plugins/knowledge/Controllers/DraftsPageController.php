<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Web\Data;

/**
 * Knowledge base Drafts controller.
 */
class DraftsPageController extends KnowledgeTwigPageController {

    /**
     * Render out the /kb/dsrafts :path page.
     *
     * @return string Returns HTML page content
     */
    public function index() {

        $status = 200;
        $this->setPageTitle("My Drafts");

        $data = $this->getViewData();
        $data['template'] = 'seo/pages/draft.twig';

        return new Data($this->twigInit()->render('default-master.twig', $data), $status);
    }


    /**
     * Gather the data array to render a page with.
     *
     * @return array
     */
    protected function getViewData(): array {
        $this->setSeoMetaData();
        $this->meta->setTag('og:site_name', ['property' => 'og:site_name', 'content' => 'Vanilla']);
        $data = $this->getWebViewResources();
        $data['page']['classes'][] = 'isLoading';
        $data['page']['userSignedIn'] = $this->session->isValid();
        $data['page']['classes'][] = $data['page']['userSignedIn'] ? 'isSignedIn' : 'isSignedOut';

        return $data;
    }

    /**
     * Initialize page SEO meta data.
     *
     * @return $this
     */
    public function setSeoMetaData() {
        $this->meta
            ->setLink('canonical', ['rel' => 'canonical', 'href' => $this->getCanonicalLink()]);

        $this->meta
                ->setTag('robots', ['name' => 'robots', 'content' => 'noindex'])
                ->setSeo('description', $this->getApiPageData('seoDescription'));

        $this->meta
            ->setSeo('locale', \Gdn::locale()->current());

        return $this;
    }


    /**
     * @inheritdoc
     */
    public function getCanonicalLink(): string {
        if ($this->canonicalUrl === null) {
            $this->canonicalUrl = \Gdn::request()->url('/kb/drafts', true);
        }
        return $this->canonicalUrl;
    }

    /**
     * Get the page data from api response array
     *
     * @param string $key Data key to get
     *
     * @return string
     */
    public function getApiPageData(string $key) {
        return $this->data[self::API_PAGE_KEY][$key] ?? '';
    }
}
