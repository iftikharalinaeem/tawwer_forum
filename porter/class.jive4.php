<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

$Supported['jive4'] = array('name' => 'Jive 4', 'prefix' => 'Jive');


class Jive4 extends ExportController {
    /**
     * @param ExportModel $Ex
     */
    protected function ForumExport($Ex) {
//        $this->Ex->TestMode = TRUE;

//        $Ex->BeginExport('', 'Jive4', array('HashMethod' => 'reset'));
        $Ex->BeginExport('', 'Jive4');

        $Ex->Query("drop table if exists vanilla_messageID_userID_map;");
        $Ex->Query("drop table if exists vanilla_user");
        $Ex->Query("drop table if exists vanilla_message;");

        // Create temporary tables.

        $Ex->Query("create table vanilla_messageID_userID_map (
            messageID int not null,
            userID int not null
            );
        ");
        $Ex->Query("create table vanilla_user like jiveuser;");
        $Ex->Query("create table vanilla_message like jivemessage;");

        // Prepare temporary tables.

        $Ex->Query("ALTER TABLE jiveuser ADD INDEX (email);");
        $Ex->Query("alter table vanilla_user modify column userID int auto_increment;");
        $Ex->Query("drop index username on vanilla_user;");


        // Populate vanilla_user with all existing users.

        $Ex->Query("insert into vanilla_user select * from jiveuser;");

        // Look up messages with no userID; If message has name/email in props then try and add that user.
        // Messages will be update below.

        $Ex->Query("insert ignore into vanilla_user (Email, Username)
            select p1.propValue as Email, p2.propValue as Name
            from jivemessage jm
            left join jiveMessageProp p1 on jm.messageID=p1.messageID and p1.name='email'
            left join jiveMessageProp p2 on jm.messageID=p2.messageID and p2.name='name'
            left join jiveuser ju on (p1.propValue = ju.email)
            where jm.userID is null and ju.userID is null and p1.propvalue != ''
            group by p1.propValue;");

        // Duplicate emails will be imported.
        $User_Map = array(
            'userID' => 'UserID',
            'email' => 'Email',
            'username' => 'Name',
            'creationDate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
            'modificationDate' => array('Column' => 'DateUpdated', 'Filter' => array($this, 'JiveDate')),
            'newPasswordHash' => 'Password',
            'emailVisible' => 'ShowEmail');
        // JiveGroupUser.groupID == 7 == Admin
        $Ex->ExportTable(
            'User',
            "select
              ju.*,
              p1.propValue as Location,
              p2.propValue as About,
              if (groupID = 7, 1, 0) as Admin,
              if(passwordHash='', 'reset', 'django') as HashMethod,
              if(passwordHash='', '', concat('md5$$', passwordHash)) as newPasswordHash
            from vanilla_user ju
            left join jiveUserProp p1 on p1.userID = ju.UserID and p1.name= 'JiveLocation'
            left join jiveUserProp p2 on p2.userID = ju.UserID and p2.name= 'JiveBiography'
			left join JiveGroupUser jgu on jgu.userID = ju.userID and jgu.groupID = 7;",
            $User_Map
        );

        // UserMeta.

        $UserMeta_Map = array(
            'userID' => 'UserID',
            'name' => 'Name',
            'propValue' => 'Value');
        $Ex->ExportTable(
            'UserMeta',
            "select
                userID,
                Replace(Concat('Profile.', name), 'jive', '') as name,
                propValue
                from jiveUserProp where name in ('jiveHomepage', 'jiveOccupation')
            union all
            select userID, 'Profile.Name', name from jiveuser where name != '';",
            $UserMeta_Map
        );


        // Populate vanilla_message with existing data.
        $Ex->Query("insert into vanilla_message select * from jivemessage;");

        // Map messageID to new UserID created above.

        $Ex->Query("insert into vanilla_messageID_userID_map (select vm.messageID, vu.userID
            from vanilla_message vm
            join jiveMessageProp jmp on jmp.messageID = vm.messageID and jmp.name='email'
            join vanilla_user vu on jmp.propvalue = vu.email
            where vm.userID is null);");
        $updates = array();
        $result = $Ex->Query('select * from vanilla_messageID_userID_map;');
        while ($row = mysql_fetch_assoc($result)) {
            $query = "update vanilla_message set userID = " . mysql_real_escape_string($row['userID'])
                . ' where messageID = ' . mysql_real_escape_string($row['messageID']);
            $updates[] = $query;
        }
        foreach ($updates as $update) {
            $Ex->Query($update);
        }
        unset($updates);

        // set missing userid to anonymous or zero if not found.
        (int)$AnonymousUserID = $Ex->GetValue("select userID from vanilla_user where username = 'Anonymous'", 0);
        $Ex->Query("update vanilla_message set userID = $AnonymousUserID where userID is null");

        // Remove temporary tables.

//        $Ex->Query("drop table if exists vanilla_messageID_userID_map;");
//        $Ex->Query("drop table if exists vanilla_user");
//        $Ex->Query("drop table if exists vanilla_message;");



        // Roles
        $Role_Map = array(
            'groupID' => 'RoleID',
            'name' => 'Name',
            'description' => 'Description'
        );

        $sql = "select groupID, name, description
        from jiveGroup

        union all
        select 1000, 'Guest', 'Guest role'

        union all
        select 1001, 'Member', 'Member role'

        union all
        select 1002, 'Applicant', 'Applicant role'";

        $Ex->ExportTable(
            'Role',
            $sql,
            $Role_Map
        );

        $sql = "select coalesce(gu.groupID, 1001) as groupID, u.userID
                from jiveuser u
                left join jiveGroupUser gu
                    on u.userID = gu.userID
                ;";
        // UserRole.
        $UserRole_Map = array(
            'userID' => 'UserID',
            'groupID' => 'RoleID');
        $Ex->ExportTable(
            'UserRole',
            $sql,
            $UserRole_Map
        );


        // Categories

        $Category_Map = array(
            'categoryID' => 'CategoryID',
            'name' => 'Name',
            'description' => 'Body',
            'lft' => 'TreeLeft',
            'rgt' => 'TreeRight'
        );
        $Ex->ExportTable('Category', "select * from JiveCategory where name !='root' ", $Category_Map);


        // Discussions
        $Discussion_Map = array(
            'threadID' => 'DiscussionID',
            'subject' => 'Name',
            'body' => 'Body',
            'newUserID' => 'InsertUserID',
            'creationDate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
            'modificationDate' => array('Column' => 'DateUpdated', 'Filter' => array($this, 'JiveDate')),
        );

        $Ex->ExportTable(
            'Discussion',
            "select
                jm.*,
                jf.CategoryID,
                if (jm.userID is null, 4, jm.userID) as newUserID,
                'Text' as Format
                from jivethread jt
                join vanilla_message jm on (rootMessageID=messageID)
                join jiveForum jf on (jf.forumID=jm.forumID)
                where parentMessageID IS NULL
            ",
            $Discussion_Map
        );

        // Comments
        // jivemessage.userID == 4 == Anonymous

        $Comment_Map = array(
            'messageID' => 'CommentID',
            'threadID' => 'DiscussionID',
            'body' => 'Body',
            'newUserID' => 'InsertUserID',
            'creationDate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
            'modificationDate' => array('Column' => 'DateUpdated', 'Filter' => array($this, 'JiveDate')),
        );

        $Ex->ExportTable(
            'Comment',
            "select
                jm.*,
                if (jm.userID is null, 4, jm.userID) as newUserID,
                'Text' as Format
                from vanilla_message jm
                where parentMessageID IS not null
            ",
            $Comment_Map
        );

        // Import forums as tags.
        $Tag_Map = array(
            'forumID' => 'TagID',
            'name' => array('Column' => 'Name', 'Filter' => array($this, 'formatSlug')),
            'name2' => 'FullName',
            'categoryID' => 'CategoryID',
            'creationDate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),

        );
        // forumname with , gets treated as tag separator.. change to space
        $Ex->ExportTable('Tag', "select jf.*, name as name, name as name2 from jiveForum jf", $Tag_Map);

        // TagDiscussion.
        $TagDiscussionMap = array(
            'forumID' => 'TagID',
            'threadID' => 'DiscussionID',
            'categoryID' => 'CategoryID'
        );
        $Ex->ExportTable(
            'TagDiscussion',
            "select forumID, threadID, categoryID, NOW() as DateInserted
             from vanilla_message join jiveforum using (forumID) where parentMessageID IS NULL;",
            $TagDiscussionMap
        );


        // Media.
        $cdn = $this->Param('cdn', '');
        $Media_Map = array(
            'attachmentID' => 'MediaID',
            'fileName' => 'Name',
            'contentType' => 'Type',
            'creationDate' => array('Column' => 'DateInserted', 'Filter' => array($this, 'JiveDate')),
            'fileSize' => 'Size',
            'userID' => 'InsertUserID'
        );
        $Ex->ExportTable(
            'Media',
            "select
                 case when vm.parentMessageID is NULL then 'discussion' else 'comment' end as ForeignTable,
                 case when vm.parentMessageID is NULL then vm.threadID  else vm.messageID end as ForeignID,
                 concat('$cdn', '/FileUpload/', vm.forumID, '-', vm.threadID, '-', ja.objectID, '-', ja.attachmentID ,'/', fileName) as Path,
                 ja.*, vu.userID
                from jiveAttachment ja
                 join vanilla_message vm on vm.messageID = ja.objectID and ja.objectType=2
                 join vanilla_user vu on vm.userID = vu.userID;",
            $Media_Map);

    }

    public function JiveDate($Value) {
        if (!$Value)
            return NULL;

        return date('Y-m-d H:i:s', $Value / 1000);
    }

    /**
     * Generate a url friendly slug from a string.
     *
     * @param string $str A string to be formatted.
     * @return string
     * @global array $transliterations An array of translations from other scripts into url friendly characters.
     */
    function formatSlug($str) {
        $transliterations = array('–' => '-', '—' => '-', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae',
            'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C',
            'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E',
            'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I',
            'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K', 'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N',
            'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe',
            'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O', 'Œ' => 'OE', 'Ŕ' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S',
            'Ŝ' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T', 'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue',
            'Ū' => 'U', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y',
            'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c',
            'ĉ' => 'c', 'ċ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g',
            'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i',
            'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l',
            'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o',
            'œ' => 'oe', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue',
            'ū' => 'u', 'ů' => 'u', 'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y',
            'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss', 'ſ' => 'ss', 'А' => 'A', 'Б' => 'B',
            'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'ș' => 's', 'ț' => 't',
            'Ț' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '',
            'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g',
            'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l',
            'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
            'ю' => 'yu', 'я' => 'ya');

        $str = trim($str);
        $str = strip_tags(html_entity_decode($str, ENT_COMPAT, 'UTF-8')); // remove html tags
        $str = strtr($str, $transliterations); // transliterate known characters
        $str = preg_replace('`([^\PP.\-_])`u', '', $str); // get rid of punctuation
        $str = preg_replace('`([^\PS+])`u', '', $str); // get rid of symbols
        $str = preg_replace('`[\s\-/+.]+`u', '-', $str); // replace certain characters with dashes
        $str = rawurlencode(strtolower($str));
        $str = trim($str, '.-');
        return $str;
    }


}

?>