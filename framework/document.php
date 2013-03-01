<?php

use Zend\Loader\StandardAutoloader;
use Zend\Code\Reflection\FileReflection;

define('ZF2_PATH', '/www/zend/library/Zend');

//require_once '/www/zend/library/Zend/Loader/Autoloader.php';
//$autoloader = Zend_Loader_Autoloader::getInstance();

require_once ZF2_PATH . '/Loader/StandardAutoloader.php';
$autoLoader = new StandardAutoloader(array(
      'namespaces' => array('Zend' => ZF2_PATH),
      'fallback_autoloader' => true,
   ));
$autoLoader->register();

require_once __DIR__ . '/boostrap.php';
require_once '/www/vanilla/library/vendors/markdown/markdown.php';

//require_once '/www/zend/library/Zend/Loader/ClassMapAutoloader.php';
//$loader = new Zend\Loader\ClassMapAutoloader();
//$loader->registerAutoloadMap('/www/zend/library/Zend/.classmap.php');
//$loader->register();
// parses a Zend_Reflection_Class object
function parseClass($zr, $class) {

   // read class docs
   $fileName = $zr->getFileName();

   $docBlock = $class->getDocblock();
   if (is_object($docBlock)) {
      $classShortComment = $docBlock->getShortDescription();
      $classLongComment = $docBlock->getLongDescription();
   } else {
      $classShortComment = null;
      $classLongComment = null;
   }

   // load class info
   $classDoc = array(
      'fileName' => $fileName,
      'className' => $class->getName(),
      'shortComment' => $classShortComment,
      'longComment' => $classLongComment,
   );

   // Read methods
   $methods = array();
   foreach ($class->getMethods() as $methodObj) {
      $methods[] = parseMethod($methodObj);
   }

   $classDoc['methods'] = $methods;

   return $classDoc;
}

// parses a Zend_Reflection_Method object
function parseMethod($methodObj) {
   $return = 'void';
   $method = array();
   $method['name'] = $methodObj->getName();

   $docBlock = $methodObj->getDocblock();
   if ($docBlock) {
      $shortDesc = $docBlock->getShortDescription();
      $longDesc = $docBlock->getLongDescription();
      $method['shortDesc'] = $shortDesc;
      $method['longDesc'] = $longDesc;

      // read method tags
      $tags = $docBlock->getTags();

      // parse them
      $paramDoc = array();
      foreach ($tags as $tag) {

         if ($tag->getName() == 'return') {
            $return = $tag->getType();
            $returnDesc = $tag->getDescription();
            $method['returnDesc'] = Markdown($returnDesc);
         }
         if ($tag->getName() == 'param') {
            $paramDoc[] = array(
               'name' => $tag->getVariableName(),
               'type' => $tag->getType(),
               'desc' => Markdown(trim($tag->getDescription())).'"'.str_replace(' ', '+', trim($tag->getDescription())).'"',
            );
         }
      }
      
      $method['params'] = $paramDoc;
   }
   
   $method['returnType'] = $return;

   return $method;
}

$file = __DIR__ . '/class.mysqldb.php';
require_once $file;
$zr = new FileReflection($file);
$classDoc = array();
foreach ($zr->getClasses() as $class) {
   $classDoc[] = parseClass($zr, $class);
}
print_r($classDoc);
