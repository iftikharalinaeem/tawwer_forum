<?php
$Supported['zendesk'] = array('name'=>'ZenDesk API', 'prefix'=>'zendesk');
$Supported['zendesk']['CommandLine'] = array(
    'apiuser' => array('API user (usually email).', 'Sx' => '::', 'Short' => 'au'),
    'apipass' => array('API authentication password.', 'Sx' => '::', 'Short' => 'ap', 'Default' => ''),
    'apisite' => array('Subdomain of the ZenDesk.com site.', 'Sx' => '::', 'Short' => 'site'),
    'noexport' => array('Whether or not to skip the export.', 'Sx' => '::'),
    'attachurl' => array('URL of where current attachments are.', 'Sx' => '::'),
    'attachpath' => array('Path to download attachments too.', 'Sx' => '::'),
    'attachcdn' => array('URL of the new CDN.', 'Sx' => '::'),
);

class Zendesk extends ExportController {

    /**
     * @param ExportModel $Ex
     */
    protected function ForumExport($Ex) {

        $cdn = $this->Param('cdn', '');
        $noexport = $this->Param('noexport', '');
        if (empty($noexport)) {
            $apiuser = $this->Param('apiuser', '');
            $apipass = $this->Param('apipass', '');
            $apisite = $this->Param('apisite', '');
            $dbname = $this->Param('dbname');
            $dbuser = $this->Param('dbuser');
            $dbpass = $this->Param('dbpass');

            if (empty($apiuser) || empty($apisite) || empty($apipass)) {
                echo "You have asked for an API export but not provided all the required args." . PHP_EOL;
                echo "Reminder: To view help  $ php index.php --help". PHP_EOL;
                exit;
            }

            echo "Starting API export." . PHP_EOL;
            passthru("php zendesk.php $apisite $apiuser $apipass $dbname $dbuser $dbpass");
        } else {
            echo "Skipping API export." . PHP_EOL;
        }

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
            'body' => array('Column' => 'Body', 'Filter' => array($this, 'BodyFilter')),
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

    public function BodyFilter($body) {

        if (!$this->Param('attachurl', false) || !$this->Param('attachpath', false) || !$this->Param('attachcdn', false)) {
            return $body;
        }

        $dom = pQuery::parseStr($body);
        foreach ($dom->query('img') as $img) {
            $newSrc = false;
            $src = $img->attr('src');
            $p = parse_url($src);

            if (empty($p['host']) && empty($p['schema'])) {
                // local image. relative link
                $newSrc = $this->DownloadFile($src);
            } elseif($this->Param('attachurl', false) && $p['host'] == $this->Param('attachurl')) {
                // Local image. // absoule link
                $newSrc = $this->DownloadFile($src);
            }

            if ($newSrc) {
                $img->attr('src', $newSrc);
            }


        }
        return $dom->html();
    }

    public function DownloadFile($src) {

        $pathToDownloadImages = $this->Param('attachpath');
        $p = parse_url($src);

        if (empty($p['host']) && empty($p['schema'])) {
            // local image. relative link
            $downloadUrl = 'http://' . $this->Param('attachurl') . $src;
        } elseif($this->Param('attachurl', false) && $p['host'] == $this->Param('attachurl')) {
            // Local image. // absolute link
            $downloadUrl = $src;
        }
        $fileName = $p['path'];
        $p = parse_url($downloadUrl);

        if (stristr($fileName, '.') === false) {
            if (!empty($p['query'])) {
                parse_str($p['query'], $output);
                if (!empty($output['name'])) {
                    $fileName = $p['path'] . $output['name'];
                } else {
                    // not able to determine filename
                    var_dump('fail: ' . $src);
                    return $src;
                }
            }
        }

        $newSrc = $this->Param('attachcdn'. '')
            . ltrim($fileName, '/');

        // Check if already downloaded.
        $newFile = $pathToDownloadImages . $fileName;
        if (!file_exists($newFile)) {
            // mkdir if needed
            $dirName = pathinfo($newFile, PATHINFO_DIRNAME);
            if (!file_exists($dirName)) {
                mkdir($dirName, 0755, true);
            }

            $fp = fopen($newFile, 'w+');
            $ch = curl_init(str_replace(" ","%20",$downloadUrl));//Here is the file we are downloading, replace spaces with %20
            curl_setopt($ch, CURLOPT_USERPWD, $this->Param('apiuser') . ':' . $this->Param('apipass'));
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp); // write curl response to file
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch); // get curl response
            curl_close($ch);
            fclose($fp);
            // @todo check response code / better error handling.
        }

        return $newSrc;

    }
}

?>