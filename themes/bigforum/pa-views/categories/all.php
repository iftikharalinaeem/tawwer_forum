<?php if (!defined('APPLICATION')) exit();
include dirname(__FILE__).'/helper_functions.php';
$CatList = '';
$DoHeadings = C('Vanilla.Categories.DoHeadings');
$MaxDisplayDepth = C('Vanilla.Categories.MaxDisplayDepth');
$ChildCategories = '';
$this->EventArguments['NumRows'] = $this->CategoryData->NumRows();
// CategoryModel::JoinModerators($this->CategoryData);

?>
<div class="Tabs Headings CategoryHeadings">
   <table class="CategoryHeading">
      <tr>
         <td class="CategoryName">Forum</td>
         <td class="LatestPost">Latest Post</td>
         <td class="CountHeading CountDiscussions">Threads</td>
         <td class="CountHeading CountComments">Posts</td>
      </tr>
   </table>
</div>
<?php
echo '<ul class="DataList CategoryList'.($DoHeadings ? ' CategoryListWithHeadings' : '').'">';
   foreach ($this->CategoryData->Result() as $Category) {
      $this->EventArguments['CatList'] = &$CatList;
      $this->EventArguments['ChildCategories'] = &$ChildCategories;
      $this->EventArguments['Category'] = &$Category;
      $this->FireEvent('BeforeCategoryItem');
      $ReadClass = GetValue('Read', $Category) ? 'Read' : 'Unread';

      if ($Category->CategoryID > 0) {
         // If we are below the max depth
         if ($Category->Depth < $MaxDisplayDepth) {
            // Replace childcategories placeholder with the collected categories if there are any
            if ($ChildCategories != '')
               $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(T('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);
            else 
               $CatList = str_replace('{ChildCategories}', '', $CatList);

            $ChildCategories = '';
         }

         if ($Category->Depth >= $MaxDisplayDepth && $MaxDisplayDepth > 0) {
            if ($ChildCategories != '')
               $ChildCategories .= ', ';
            $ChildCategories .= Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode);
         } else if ($Category->Depth == 1) {
            $CatList .= '<li class="Item CategoryHeading Depth1 Category-'.$Category->UrlCode.'">
               <div class="Category '.$ReadClass.'">'.Gdn_Format::Text($Category->Name).'</div>
            </li>';
         } else {
            $LastComment = UserBuilder($Category, 'LastComment');
            $CatList .= '<li class="Item Depth'.$Category->Depth.' Category-'.$Category->UrlCode.' '.$ReadClass.'">
               '.GetOptions($Category, $this).'
               <table>
                  <tr>
                     <td class="CategoryName">'.
                        Anchor(Gdn_Format::Text($Category->Name), '/categories/'.$Category->UrlCode, 'Title')
                        .Wrap($Category->Description, 'div', array('class' => 'CategoryDescription'));

                     // If this category is one level above the max display depth, and it
                     // has children, add a replacement string for them.
                     if ($MaxDisplayDepth > 0 && $Category->Depth == $MaxDisplayDepth - 1 && $Category->TreeRight - $Category->TreeLeft > 1)
                        $CatList .= '{ChildCategories}';
                     
                     $CatList .= '</td>
                     <td class="LatestPost">
                        <div class="Wrap">';
                        if ($LastComment && $Category->LastDiscussionTitle != '') {
                           $CountCommentsPerPage = GetValue('CountCommentsPerPage', $Sender);
                           if (!$CountCommentsPerPage) {
                              $CountCommentsPerPage = C('Vanilla.Comments.PerPage', 30);
                              $Sender->CountCommentsPerPage = $CountCommentsPerPage;
                           }
                           $CountPages = ceil(GetValue('LastDiscussionCountComments', $Category, 1) / $CountCommentsPerPage);
                           $FirstPageUrl = '/discussion/'.$Category->LastDiscussionID.'/'.Gdn_Format::Url($Category->LastDiscussionTitle);
                           $LastPageUrl = $FirstPageUrl . '/p'.$CountPages.'/#Comment_'.$Category->LastCommentID;
                           $CatList .= UserPhoto($LastComment, 'PhotoLink');
                           $CatList .= Anchor(
                              SliceString(Gdn_Format::Text($Category->LastDiscussionTitle), 100),
                              $FirstPageUrl,
                              'LatestPostTitle'
                           );
                           $CatList .= UserAnchor($LastComment, 'UserLink');
                           $CatList .= Anchor(
                              Gdn_Format::Date($Category->DateLastComment),
                              $LastPageUrl,
                              'CommentDate'
                           );
                        } else {
                           $CatList .= '&nbsp;';
                        }
                        $CatList .= '</div>
                     </td>
                     <td class="BigCount CountDiscussions"><div class="Wrap">'.Gdn_Format::BigNumber($Category->CountAllDiscussions).'</div></td>
                     <td class="BigCount CountComments">'.Gdn_Format::BigNumber($Category->CountAllComments).'</td>
                  </tr>
               </table>
            </li>';
         }
      }
   }
   // If there are any remaining child categories that have been collected, do
   // the replacement one last time.
   if ($ChildCategories != '')
      $CatList = str_replace('{ChildCategories}', '<span class="ChildCategories">'.Wrap(T('Child Categories:'), 'b').' '.$ChildCategories.'</span>', $CatList);
   
   echo $CatList;
?>
</ul>