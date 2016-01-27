<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['CommentImporter'] = array(
    'Name' => 'Comment Importer',
    'Description' => 'Import Comments from Wordpress or Disqus into Vanilla.',
    'Version' => '1.0a',
    'MobileFriendly' => FALSE,
    'RequiredApplications' => array('Vanilla' => '2.1a'),
    'RequiredTheme' => FALSE,
    'RequiredPlugins' => FALSE,
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com'
);

class CommentImporterPlugin extends Gdn_Plugin {
    /**
     * Add menu item.
     */
    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Import', T('Import Comments'), '/import/comments', 'Garden.Settings.Manage');
    }

    /**
     *
     * @param Gdn_Controller $Sender
     * @param string $Type
     */
    public function ImportController_Comments_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');

        $AllowedTypes = array('wordpres' => T('Wordpress'), 'disqus' => T('Disqus'));
        $Form = new Gdn_Form();
        $Form->InputPrefix = '';

        $Sender->Form = $Form;

        if ($Sender->Form->IsPostBack()) {
            // Validate the form.
            $Type = $Form->GetFormValue('Type');
            if (isset($AllowedTypes[$Type])) {
                $Form->AddError(T("Please select an import type."));
            }

            $LocalPath = PATH_UPLOADS.'/commentimport/'.md5(microtime());
            if (!file_exists(dirname($LocalPath)))
            mkdir(dirname($LocalPath), 0777, TRUE);

            if ($Form->GetFormValue('IsUpload')) {
                // Save the uploaded file into the temporary folder.

            } else {
                // Grab a copy of the file.
                if (!($FileUrl = $Form->GetFormValue('FileUrl'))) {
                    $Form->AddError(T("Please specify the url of your comments file."));
                } else {
                    if (!preg_match('`^https?:///`', $FileUrl)) {
                        $Form->AddError(T('We only accept urls that begin with http:// or https://'));
                    } else {
                        copy($FileUrl, $LocalPath);
                    }
                }
            }

        } else {
            // Preserve plugin RequiredApplications version.
            $increaseMaxExecutionTime =
                function_exists('increaseMaxExecutionTime')
                    ? 'increaseMaxExecutionTime'
                    : function ($maxExecutionTime) {

                    $iniMaxExecutionTime = ini_get('max_execution_time');

                    // max_execution_time == 0 means no limit.
                    if ($iniMaxExecutionTime === '0') {
                        return true;
                    }

                    if (((string)$maxExecutionTime) === '0') {
                        return set_time_limit(0);
                    }

                    if (!ctype_digit($iniMaxExecutionTime) || $iniMaxExecutionTime < $maxExecutionTime) {
                        return set_time_limit($maxExecutionTime);
                    }

                    return true;
                };
            $increaseMaxExecutionTime(60 * 30);

            $Model = new WordpressImportModel();
            $Model->Path = '/www/techwhirl2/uploads/techwhirl.wordpress.xml';
            $Model->Import();
            die('Done');

            $Form->SetValue('Type', 'wordpress');
            $Sender->View = 'ImportForm';
        }

        $Sender->SetData('AllowedTypes', $AllowedTypes);
        $Sender->SetData('Title', T('Import Comments'));
        $Sender->AddSideMenu();
        $Sender->Render('', '', 'plugins/CommentImporter');
    }
}