<?php if (!defined('APPLICATION')) exit();
echo "<div class='_container'>";
echo wrap("Knowledge base styles", 'h1', ['class' => 'pageTitle']);
echo "</div>";

echo "<style>.knowledgeStylesHome {width: 500px; margin: auto; padding: 20px;} .styleGuideNav-link { font-size: 20px; line-height: 2; }</style>";

echo "<div class='knowledgeStylesHome'>";
include "styleGuideNav.php";
echo "</div>";

echo "<style>.Trace { display: none; }</style>";
