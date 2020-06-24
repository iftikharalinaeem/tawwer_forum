<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

class Infrastructure {
    /// Properties ///

    protected static $clusterConfig;

    /// Methods ///

    /**
     * Get a value from the cluster config.
     *
     * @param string $key The dot-separated key to get.
     * @param mixed $default The default value if {@link $key} is not found.
     * @return mixed Returns the value at {@link $key} or {$link $default} if there is no value set.
     */
    public static function clusterConfig($key, $default = null) {
        if (self::$clusterConfig === null) {
            $c = @json_decode(file_get_contents(PATH_CONF.'/config-cluster.json'), true);
            if (!is_array($c)) {
                self::$clusterConfig = [];
            } else {
                self::$clusterConfig = $c;
            }
        }

        $result = valr($key, self::$clusterConfig, $default);
        return $result;
    }

    public static function getMulti($name) {
        return null;
    }

    public static function site($key) {
        switch (strtolower($key)) {
            case 'accountid':
                return 8080;
            case 'siteid':
                return 123;
            case 'name':
                return 'localhost.vanillaforums.com';
        }
        return null;
    }

    public static function serverApiKey() {
        return static::clusterConfig('apiKey');
    }

    public static function siteID() {
        return static::site('siteid');
    }
}