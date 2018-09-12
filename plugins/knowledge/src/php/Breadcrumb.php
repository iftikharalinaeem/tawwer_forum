<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge;

/**
 * A class for dealing with Breadcrumb data.
 */
class Breadcrumb {
    /** @var string */
    private $name;

    /** @var string */
    private $url;

    /**
     * Breadcrumb constructor.
     *
     * @param string $name
     * @param string $url
     */
    public function __construct(string $name, string $url) {
        $this->name = $name;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * Convert an array of breadcrumbs into
     *
     * @param Breadcrumb[] $crumbs The array of breadcrumbs to convert to JSON-LD.
     *
     * @return string Breadcrumb data serialized into the JSON-LD breadcrumb micro-data format.
     */
    public static function crumbsAsJsonLD(array $crumbs): string {
        $crumbList = [];
        foreach ($crumbs as $index => $crumb) {
            $crumbList[] = [
                '@type' => 'ListItem',
                'position' => $index,
                'name' => $crumb->getName(),
                'item' => $crumb->getUrl(),
            ];
        }

        $data = [
            '@context' => 'http://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $crumbList,
        ];

        return json_encode($data);
    }
}
