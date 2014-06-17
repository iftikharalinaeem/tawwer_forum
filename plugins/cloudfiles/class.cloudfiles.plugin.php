<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['cloudfiles'] = array(
    'Name' => 'File Uploads to CloudFiles',
    'Description' => "This plugin allows files to be uploaded to Rackspace Cloud Files.",
    'Version' => '2.0.1',
    'MobileFriendly' => true,
    'RequiredApplications' => array(
        'Vanilla' => '2.1a'
    ),
    'RequiredTheme' => false,
    'RequiredPlugins' => array(
        'cloudmonkey' => '2.0'
    ),
    'HasLocale' => false,
    'RegisterPermissions' => false,
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com',
    'Hidden' => false
);

/**
 * Rackspace Cloud Files Integration
 *
 * This plugin allows files to be uploaded to Rackspace Cloud Files.
 *
 * To use this plugin on localhost:
 * 1. This plugin relies on the config in rackmonkey/library/class.rackspaceapi.php.
 *      Just copy it into your config and you should be okay.
 * 2. You need to have CLIENT_NAME defined. It's a good idea to set up a conf/bootstrap.before.php locally
 *      and do it here.
 * 3. Add the following config:
 *    $Configuration['VanillaForums']['Rackspace']['Context'] = 'public';
 *
 * Changes:
 *
 *  1.0        Initial Release
 *  1.0.1      Auto switch to secure URLs during https
 *  2.0.1      Use new cloudmonkey
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @package infrastructure
 * @since 1.0
 */
class CloudFilesPlugin extends Gdn_Plugin {

    protected $containerURL;

    public function __construct() {
        $containerURL = C('VanillaForums.Rackspace.UploadContainerURL', 'http://cdn.vanillaforums.com');
        $secureContainerURL = C('VanillaForums.Rackspace.SecureContainerURL', 'https://c3409409.ssl.cf0.rackcdn.com');
        $this->containerURL = Gdn::request()->scheme() == 'https' ? $secureContainerURL : $containerURL;
    }

    /**
     * Get cloudfiles reference
     *
     * @return \FirstGen\CloudFiles
     */
    public function cloudFiles() {
        static $cloudFiles = null;

        if ($cloudFiles === null) {
            $rackspace = Cloud::service('cloudfiles');
            if ($rackspace instanceof FirstGenService) {
                $cloudFiles = $rackspace->files();
            }
        }

        return $cloudFiles;
    }

    /**
     * Prepend uploadfolder to a path
     *
     * @param array $parsed
     * @param string $returnContainer
     * @return array|string
     */
    public static function path($parsed, $returnContainer = true) {
        $folder = C('VanillaForums.Rackspace.UploadFolder', CLIENT_NAME);
        $path = paths($folder, $parsed['Name']);

        if ($returnContainer) {
            return array($path, C('VanillaForums.Rackspace.UploadContainer', 'cdn'));
        } else {
            return $path;
        }
    }

    /**
     *
     * @param string $path
     * @param string $originalName
     */
    public function moveFile($name, $originalName = false) {
        $currentLocalPath = PATH_UPLOADS . '/' . ltrim($name, '/');
        $currentLocalExists = file_exists($currentLocalPath);

        $localPath = $this->upload()->copyLocal($name);
        if (!$localPath) {
            return false;
        }

        if (!file_exists($localPath)) {
            return null;
        }

        $parsed = Gdn_upload::parse($name);
        list($path, $container) = self::path($parsed);

        $options = array();
        $meta = array();

        if ($originalName) {
            $options['Content-Type'] = 'application/force-download';
            $options['Content-Disposition'] = 'attachment; filename="' . $originalName . '"';
        }
        $this->cloudFiles()->putObject($container, $path, $localPath, $options, $meta);

        // Okay, the file has been uploaded so we can maybe delete the old one.
        if ($currentLocalExists) {
            $archivePath = paths(PATH_UPLOADS, 'Archive', $name);
            if (!file_exists(dirname($archivePath))) {
                mkdir(dirname($archivePath), 0777, true);
            }

            rename($currentLocalPath, $archivePath);
            if ($localPath != $currentLocalPath) {
                unlink($localPath);
            }
        } else {
            unlink($localPath);
        }
        return '~cf/' . ltrim($name, '/');
    }

    /**
     * Remove subfolders from upload
     *
     * @param type $path
     * @return type
     */
    public static function removeEmptySubFolders($path) {
        $path = dirname($path);

        $empty = true;
        foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
            $empty &= is_dir($file) && self::removeEmptySubFolders($file);
        }
        return $empty && rmdir($path);
    }

    /**
     * Get Gdn_Upload object
     *
     * @staticvar null $upload
     * @return Gdn_upload
     */
    public function upload() {
        static $upload = null;
        if (is_null($upload)) {
            $upload = new Gdn_upload();
        }

        return $upload;
    }

    /// Event Handlers ///

    public function Gdn_Dispatcher_AppStartup_Handler($sender) {
        // Preload identity
        $identity = C('Cloudfiles.Identity');
        if ($identity) {
            $identityKey = val('key', $identity);
            $identityValue = val('identity', $identity);
            Cloud::addIdentity($identityKey, $identityValue);
        }
    }

    public function Gdn_upload_CopyLocal_Handler($sender, $args) {
        $parsed = $args['Parsed'];
        if ($parsed['Type'] != 'cf') {
            return;
        }

        list($path, $container) = self::path($parsed);

        // Since this is just a temp file we don't want to nest it in a bunch of subfolders.
        $destPath = PATH_UPLOADS . "/cftemp/" . str_replace('/', '-', $path);

        if (!file_exists(dirname($destPath))) {
            mkdir(dirname($destPath), 0777, true);
        }

        $this->cloudFiles()->retrieveObject($container, $path, array('SaveAs' => $destPath));

        $args['Path'] = $destPath;
    }

    public function Gdn_upload_Delete_Handler($sender, $args) {
        $parsed = $args['Parsed'];
        if ($parsed['Type'] != 'cf') {
            return;
        }

        list($path, $container) = self::path($parsed);
        try {
            $this->cloudFiles()->deleteObject($container, $path);
        } catch (Exception $ex) {
            if ($ex->getCode() != 404) { // just ignore not found files.
                throw $ex;
            }
        }
        $args['Handled'] = true;
    }

    public function Gdn_upload_SaveAs_Handler($sender, $args) {
        $tempPath = $args['Path'];
        $parsed = $args['Parsed'];
        $originalFilename = val('OriginalFilename', $args);
        list($path, $container) = self::path($parsed);
        list($imageWidth, $imageHeight) = Gdn_uploadImage::imageSize($tempPath, $originalFilename);

        $options = array();
        $meta = array();
        if (!$imageWidth && $originalFilename) {
            $options['Content-Type'] = 'application/force-download';
            $options['Content-Disposition'] = 'attachment; filename="' . $originalFilename . '"';
        }

        $result = $this->cloudFiles()->putObject($container, $path, $tempPath, $options, $meta);
        if ($result) {
            $parsed = Gdn_upload::parse('~cf/' . $parsed['Name']);

            $args['Parsed'] = $parsed;
            @unlink($tempPath);
            $args['Handled'] = true;
        } else {
            throw new Exception('There was an error saving the file to cloud files.', 500);
        }
    }

    public function Gdn_upload_GetUrls_Handler($sender, $args) {
        $url = trim($this->containerURL, '/');
        $folder = trim(C('VanillaForums.Rackspace.UploadFolder', CLIENT_NAME), '/');
//      $F = substr($Folder, 0, 1);
        if ($url && $folder) {
            $args['Urls']['cf'] = "$url/$folder";
        }
    }

    /**
     *
     * @param SettingsController $sender
     */
    public function UtilityController_MoveFiles_Create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $urls = array(
            url('/utility/movemisc.json') => 'Miscellaneous',
            url('/utility/moveprofilephotos.json') => 'Profile Photos',
            url('/utility/movemedia.json') => 'Media'
        );
        $sender->setData('Urls', $urls);

        $sender->sddSideMenu();
        $sender->render('MoveFiles', '', 'plugins/cloudfiles');
    }

    public function UtilityController_MoveMedia_Create($sender, $limit = 10) {
        $sender->Permission('Garden.Settings.Manage');
        if (empty($_POST)) {
            throw PermissionException('PostBack');
        }

        $moved = array();
        $working = array();
        $errors = array();
        $count = 0;

        $data = Gdn::sql()
            ->select('*')
            ->from('Media')
            ->notLike('Path', '~cf/', 'right')
            ->notLike('Path', 'http', 'right')
            ->limit($limit)
            ->get()->resultArray();
        $complete = count($data) < $limit;

        foreach ($data as $row) {
            $mediaID = $row['MediaID'];
            $path = $row['Path'];
            $thumbPath = $row['ThumbPath'];
            $set = array();

            if ($path) {
                try {
                    $name = '';
                    if (!$row['ImageWidth']) {
                        $name = $row['Name'];
                    }
                    $pathMoved = $this->moveFile($path, $name);
                    $set['Path'] = $pathMoved;
                } catch (Exception $ex) {
                    $errors[$path] = $ex->getMessage();
                }
            }

            if ($thumbPath) {
                try {
                    $thumbPathMoved = $this->moveFile($thumbPath);
                    $set['ThumbPath'] = $thumbPathMoved;
                    if ($thumbPathMoved) {
                        $set['ThumbWidth'] = null;
                        $set['ThumbHeight'] = null;
                    }
                } catch (Exception $ex) {
                    $errors[$Avatar] = $ex->getMessage();
                    $set['ThumbPath'] = null;
                    $set['ThumbWidth'] = null;
                    $set['ThumbHeight'] = null;
                }
            }

            if (!empty($set)) {
                Gdn::sql()->put('Media', $set, array('MediaID' => $mediaID));
            }

            $count++;
        }

        $sender->setData('Complete', $complete);
        $sender->setData('Count', $count);
        $sender->setData('Moved', $moved);
        $sender->setData('Exceptions', $errors);
        $sender->setData('Working', $working);
        $sender->render('Blank');
    }

    public function UtilityController_MoveMisc_Create($sender) {
        $sender->permission('Garden.Settings.Manage');
        if (empty($_POST)) {
            throw PermissionException('PostBack');
        }

        $config = array('Garden.Logo', 'Garden.FavIcon');
        $save = array();
        $working = array();
        foreach ($config as $key) {
            $value = C($key);
            if (!$value || stringBeginsWith($value, '~cf')) {
                continue;
            }

            try {
                $working[] = $value;
                $newValue = $this->moveFile($value);
                $save[$key] = $newValue;
            } catch (Exception $ex) {

            }
        }
        if (!empty($save)) {
            saveToConfig($save, '', array('RemoveEmpty' => true));
        }

        $sender->setData('Complete', true);
        $sender->setData('Config', $save);
        $sender->setData('Working', $working);
        $sender->render('Blank');
    }

    public function UtilityController_MoveProfilePhotos_Create($sender, $limit = 10) {
        $sender->permission('Garden.Settings.Manage');
        if (empty($_POST)) {
            throw PermissionException('PostBack');
        }

        $data = Gdn::sql()
            ->select('UserID, Name, Photo')
            ->from('User')
            ->where('Photo <>', '')
            ->notLike('Photo', '~cf/', 'right')
            ->notLike('Photo', 'http', 'right')
            ->limit($limit)
            ->get()->resultArray();

        $upload = new Gdn_upload();
        $names = array();
        $working = array();
        $errors = array();
        $count = 0;
        $complete = count($data) < $limit;

        foreach ($data as $row) {
            $userID = $row['UserID'];
            $photo = $row['Photo'];
            $working[] = $photo;

            $profilePhoto = changeBasename($photo, 'p%s');
            $profileMoved = false;
            try {
                $profileMoved = $this->moveFile($profilePhoto);
            } catch (Exception $ex) {
                $errors[$profilePhoto] = $ex->getMessage();
            }

            $avatar = changeBasename($photo, 'n%s');
            $avatarMoved = false;
            try {
                $avatarMoved = $this->moveFile($avatar);
            } catch (Exception $ex) {
                $errors[$avatar] = $ex->getMessage();
            }

            if ($avatarMoved) {
                $parsed = Gdn_upload::parse($photo);
                $newPhoto = '~cf/' . $parsed['Name'];
                Gdn::userModel()->setField($userID, 'Photo', $newPhoto);

                $names[$row['Name']] = $newPhoto;
            } elseif ($profileMoved === null && $avatarMoved === null) {
                // The file doesn't exist so clear the avatar.
                Gdn::userModel()->setField($userID, 'Photo', null);
            }
            $count++;
        }

        $sender->setData('Complete', $complete);
        $sender->setData('Count', $count);
        $sender->setData('Moved', $names);
        $sender->setData('Exceptions', $errors);
        $sender->setData('Working', $working);
        $sender->render('Blank');
    }
}
