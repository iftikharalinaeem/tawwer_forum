<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class CommentImporterPlugin extends Gdn_Plugin {
    /**
     * Add menu item.
     */
    public function Base_GetAppSettingsMenuItems_Handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddLink('Import', T('Import Comments'), '/import/comments', 'Garden.Settings.Manage');
    }

    /**
     *
     * @param Gdn_Controller $sender
     * @param string $type
     */
    public function ImportController_Comments_Create($sender) {
        $sender->Permission('Garden.Settings.Manage');

        $allowedTypes = ['wordpres' => T('Wordpress'), 'disqus' => T('Disqus')];
        $form = new Gdn_Form();
        $form->InputPrefix = '';

        $sender->Form = $form;

        if ($sender->Form->IsPostBack()) {
            // Validate the form.
            $type = $form->GetFormValue('Type');
            if (isset($allowedTypes[$type])) {
                $form->AddError(T("Please select an import type."));
            }

            $localPath = PATH_UPLOADS.'/commentimport/'.md5(microtime());
            if (!file_exists(dirname($localPath)))
            mkdir(dirname($localPath), 0777, TRUE);

            if ($form->GetFormValue('IsUpload')) {
                // Save the uploaded file into the temporary folder.

            } else {
                // Grab a copy of the file.
                if (!($fileUrl = $form->GetFormValue('FileUrl'))) {
                    $form->AddError(T("Please specify the url of your comments file."));
                } else {
                    if (!preg_match('`^https?:///`', $fileUrl)) {
                        $form->AddError(T('We only accept urls that begin with http:// or https://'));
                    } else {
                        copy($fileUrl, $localPath);
                    }
                }
            }

        } else {
            // Preserve plugin RequiredApplications version.
            $increaseMaxExecutionTime =
                function_exists('increaseMaxExecutionTime') // Exists in Vanilla 2.3
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

            $model = new WordpressImportModel();
            $model->Path = '/www/techwhirl2/uploads/techwhirl.wordpress.xml';
            $model->Import();
            die('Done');

            $form->SetValue('Type', 'wordpress');
            $sender->View = 'ImportForm';
        }

        $sender->SetData('AllowedTypes', $allowedTypes);
        $sender->SetData('Title', T('Import Comments'));
        $sender->AddSideMenu();
        $sender->Render('', '', 'plugins/CommentImporter');
    }
}
