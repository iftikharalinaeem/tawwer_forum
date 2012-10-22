<?php if (!defined('APPLICATION')) exit();

/**
 * API Maper Interface
 * 
 * API Mappers should implement this to ensure compatibility with 
 * Simple API.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 */

interface IApiMapper {
   
   /**
    * API Mapping method
    * 
    * Takes the passed API request URI and maps it to an internal URI, allowing 
    * different versions of the API to expose different URLs that point to the 
    * same internals
    * 
    * @param string $APIRequest 
    */
   public function Map($APIRequest);
   
}