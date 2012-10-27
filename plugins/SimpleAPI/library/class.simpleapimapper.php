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
    * @param string $APIRequest 
    */
   abstract public function Map($APIRequest);
   
   /**
    * Output Filtering method
    * 
    * Takes the final controller output and performs any applicable filtering
    * on it.
    * 
    * @param array $Data
    */
   abstract public function Filter(&$Data);
   
   public function AddMap($Map, $To = NULL, $Filter = NULL) {
      if (!is_array($Map)) {
         $Map = array($Map => $To);
         if (!is_null($Filter) && !is_array($Filter))
            $Filter = array($Map => $Filter);
      }
      
      $this->URIMap = array_merge($this->URIMap, $Map);
      if (is_array($Filter))
         $this->Filter = array_merge($this->Filter, $Filter);
   }
   
}