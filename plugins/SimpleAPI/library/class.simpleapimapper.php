<?php if (!defined('APPLICATION')) exit();

/**
 * API Maper Abstraction
 *
 * API Mappers should extend this to ensure compatibility with
 * Simple API.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 */
abstract class SimpleApiMapper {

    protected $URIMap;
    protected $Mapping;
    protected $Filter;

    /**
     * API Mapping method
     *
     * Takes the passed API request URI and maps it to an internal URI, allowing
     * different versions of the API to expose different URLs that point to the
     * same internals
     *
     * @param string $aPIRequest
     */
    abstract public function map($aPIRequest);

    /**
     * Output Filtering method
     *
     * Takes the final controller output and performs any applicable filtering
     * on it.
     *
     * @param array $Data
     */
    abstract public function filter(&$data);

    public function addMap($map, $to = NULL, $filter = NULL) {
        if (!is_array($map)) {
            $map = [$map => $to];
            if (!is_null($filter) && !is_array($filter))
                $filter = [$map => $filter];
        }

        $this->URIMap = array_merge($this->URIMap, $map);
        if (is_array($filter))
            $this->Filter = array_merge($this->Filter, $filter);
    }

}