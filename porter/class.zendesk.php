<?php
$Supported['zendesk'] = array('name'=>'ZenDesk API', 'prefix'=>'zendesk');

class Zendesk extends ExportController {

    /**
     * @param ExportModel $Ex
     */
    protected function ForumExport($Ex) {

        $cdn = $this->Param('cdn', '');
//        $this->Ex->TestMode = TRUE;

        $Ex->BeginExport('', 'zendesk');

        $CharacterSet = $Ex->GetCharacterSet('zendesk_articles');
        if ($CharacterSet)
            $Ex->CharacterSet = $CharacterSet;

        // Users
        $Users_Map = array(
            'id' => 'UserID',
            'name' => 'Name',
            'email' => 'Email',
            'created_at' => 'DateInserted',
            'updated_at' => 'DateUpdated',
            'updated_at' => 'DateLastActive',
            'verified' => 'Verified',
        );
        $Ex->ExportTable(
            'User',
            "select u.* from zendesk_users u",
            $Users_Map
        );

        // Articles => Discussions
        $Discussion_Map = array(
            'id' => 'DiscussionID',
            'title' => 'Name',
            'body' => 'Body',
            'author_id' => 'InsertUserID',
            'created_at' => 'DateInserted',
            'update_at' =>  'DataUpdated',
            'section_id' => 'CategoryID',
            'promoted' => 'Announce'
        );

        $Ex->ExportTable(
            'Discussion',
            "select a.*, 'Html' as Format,
            if (comments_disabled = 1 or outdated=1, 1, 0) as Closed
             from zendesk_articles a",
            $Discussion_Map
        );

        // Comments
        // Articles => Discussions
        $Comment_Map = array(
            'id' => 'CommentID',
            'source_id' => 'DiscussionID',
            'body' => 'Body',
            'author_id' => 'InsertUserID',
            'created_at' => 'DateInserted',
            'update_at' =>  'DataUpdated',
            'Format' => 'Format'
        );

        $Ex->ExportTable(
            'Comment',
            "select ac.*, 'Markdown' as Format from zendesk_article_comments ac",
            $Comment_Map
        );

        // User Roles

        // Roles
        $Role_Map = array(
            'groupID' => 'RoleID',
            'name' => 'Name',
            'description' => 'Description'
        );

        $sql = "
        select 2 as groupID , 'Guest' as name, 'Guests can only view content. Anyone browsing the site who is not signed in is considered to be a \"Guest\".' as description

        union all
        select 3, 'Unconfirmed', 'Users must confirm their emails before becoming full members. They get assigned to this role.'

        union all
        select 8, 'Member', 'Members can participate in discussions.'

        union all
        select 4, 'Applicant', 'Users who have applied for membership, but have not yet been accepted. They have the same permissions as guests.'

        union all
        select 16, 'Administrator', 'Administrators have permission to do anything.'

        union all
        select 32, 'Moderator', 'Moderators have permission to edit most content.'

        ";

        $Ex->ExportTable(
            'Role',
            $sql,
            $Role_Map
        );

        // agents as moderators
        $UserRole_Map = array(
            'id' => 'UserID',
        );

        $Ex->ExportTable(
            'UserRole',
            "select u.id,
            case
             when role = 'admin'
              then 16
             when role = 'agent'
              then 32
             else
                8
            end as RoleID
            from zendesk_users u;
            ",
            $UserRole_Map
        );

        // Categories as Root Categories
        // Sections as 2nd Level

        $Category_Map = array(
            'id' => 'CategoryID',
            'name' => 'Name',
            'description' => 'Description',
            'position' => 'Sort',
            'created_at' => 'DateInserted',
            'updated_at' => 'DateUpdated',
        );
        $Ex->ExportTable(
            'Category',
            "select
                    id, name, description, position, created_at, updated_at, 0 as ParentCategoryID, outdated as Archived
                from zendesk_categories c
                union
                select
                    id, name, description, position, created_at, updated_at, category_id, outdated as Archived
                from zendesk_sections;

            ",
            $Category_Map
        );

        // End
        $Ex->EndExport();

    }
}

?>