<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\Container\Container;
use Vanilla\Knowledge\Models\Breadcrumb;

/**
 * Knowledge base controller for article view.
 */
class KbRootController extends KnowledgeTwigPageController {

    /**
     * Gather the data array to render a page with.
     *
     * @return array
     */
    protected function getViewData(): array {
        $this->setSeoMetaData();
        $this->meta->setTag('og:site_name', ['property' => 'og:site_name', 'content' => 'Vanilla']);
        $data = $this->getWebViewResources();
        return $data;
    }

    /**
     * Render out the /kb page.
     */
    public function index() : string {
        $this->setPageTitle(\Gdn::translate('Home'));

        // We'll need to be able to set all of this dynamically in the future.
        $data = $this->getViewData();
        $data['page']['classes'][] = 'isLoading';
        $data['template'] = 'seo/pages/home.twig';

        return $this->twigInit()->render('default-master.twig', $data);
    }

    /**
     * Initialize page SEO meta data.
     *
     * (temporary solution, need to be extended and/or refactored later)
     *
     * @return $this
     */
    public function setSeoMetaData() {
        $this->meta
            ->setLink('canonical', ['rel' => 'canonical', 'href' => $this->getCanonicalLink()]);
        $this->meta
            ->setSeo('title', $this->data['title'] ?? 'Knowledge')
            ->setSeo('description', $this->data['description'] ?? 'Knowledge Base')
            ->setSeo('locale', \Gdn::locale()->current())
            ->setSeo('breadcrumb', Breadcrumb::crumbsAsJsonLD($this->getBreadcrumbs()));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalLink() : string {
        return \Gdn::request()->url('/kb/', true);
    }

    /**
     * Get Breadcrubs data array
     *
     * @return array
     */
    public function getBreadcrumbs(): array {
        return [
            new Breadcrumb('Home', \Gdn::request()->url('/', true)),
            new Breadcrumb('Knowledge', $this->getCanonicalLink()),
        ];
    }
}
