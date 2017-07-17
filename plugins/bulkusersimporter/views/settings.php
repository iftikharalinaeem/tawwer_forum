<?php if (!defined('APPLICATION')) exit(); ?>

<?php
   $username_limits = $this->Data('username_limits');
?>

<div id="bulk-importer">
   <h1>
      <?php echo $this->Data('Title'); ?>
   </h1>

   <div class="Info">
      Use this tool to bulk import users from standardized CSV files.
   </div>

   <div class="Info">The format of a CSV file must be
      <code>Email,Username,Role</code> per line, in that order. There must be exactly one comma
      between each, no trailing comma, with a maximum of three values. Each
      grouping of three are on their own line. If the CSV file has a header
      line, check the option below to let the importer skip it. <strong>All
      three parameters are required</strong>, unless indicated otherwise.
   </div>

   <div class="Info userin-options-list">
      There are two options to import users into your community:
      <ul>
         <li>
            Send an <strong>email invitation</strong> to all users. This allows them to follow
            the provided URL and choose their own username and password. Of the
            three parameters, <strong>username is optional for invitation mode</strong>.
            An example row for this would look like:
            <code>johndoes@example.com,,member:administrator</code>. Notice
            the double comma, indicating that the username has been left out.
            If a username is provided, it will be used to pre-populate the
            username field in the registration form, but can be easily changed.
         </li>
         <li>
            <strong>Directly insert</strong> users into the community. Users
            who already exist in the community will have their information
            updated, based on the username, while new users will be created
            and emailed. <strong>All three parameters are required</strong>. An
            example of this: <code>jd@anon.com,anon44,member</code>.
         </li>
         <li>
            <strong>Update users</strong> in the community, based on their
            email. Users who already exist in the community will have
            their role(s) updated, and their username will also be updated if
            a new one is provided. <strong>The email and role(s) are
            required</strong>, but the username is optional. New roles specified
            for an email will replace the roles previously associated with the
            email. Likewise, if a username is provided, it will replace the
            previous username associated with the email. Minimal requirements:
            <code>existing@email.com,,administrator:moderator</code>. Notice
            the <strong>double comma</strong> between the email and roles. This
            informs the importer that the username will not be changed. If the
            role is placed directly after the email, the importer will cease
            functioning, as the roles will be interpreted as usernames, and the
            roles column will be considered empty.
         </li>
      </ul>
   </div>

   <div class="Info">The second parameter, <code>Username</code>, must be at
      minimum <code><?php echo $username_limits['min']; ?></code> characters and at
      most <code><?php echo $username_limits['max']; ?></code> characters. If a
      duplicate username is found, the entire row of that
      duplicated username will be purged from the import, as usernames are the
      controlling value and must be unique. This means that only the first
      instance of a row with that given username will be imported. <strong>If
      invitation mode is selected, the constraints just described do
      not apply, as username is optional in this mode</strong>. If
      update mode is selected, the username is not required, and the email is
      the controlling value that will determine the row that gets updated.
   </div>

   <div class="Info">The third parameter, <code>Role</code>, can have
      multiple items. These items must be separated by a colon <code>:</code>.
      Roles with spaces in them can be wrapped with single or double
      quotation marks, but this is optional; spaces in roles work just fine
      without being wrapped in quotation marks. Roles are not
      case-sensitive, so "member" and "Member" are identical. Valid
      roles are: <em class="valid-statuses"><?php echo implode(', ', $this->Data('allowed_roles')); ?></em>.
   </div>

   <div class="Info">
      <strong>Note:</strong> If the CSV file will contain usernames with
      non-Latin characters, it is important that its default encoding
      be UTF-8. Not doing this can result in corrupted characters in usernames.
   </div>

   <h3>Example contents of CSV file:</h3>

   <div class="Info">
      This example contains nine lines. The first line has headers. Headers
      do not need to be present. If they are, check the option below so the
      importer can skip that line. The importer will fail if the syntax of the
      CSV file does not match the syntax in the example. The last line shows
      the optional syntax for a row if sending out invites,
      with the username excluded; in invite mode, the usernames in the
      previous rows will be used to pre-populate the registration form users
      will be linked to from the invitation email.

   <pre>
   Email,Username,Role
   john@example.com,johnny,Member
   paul@example2.com,paul,Member:Moderator
   kenny@example3.com,ken,Member:Banned
   tywin@example4.com,lanman,Member:Administrator:Moderator:"World's Best"
   rust@example5.com,carcosaguy,Member:World's Best:Moderator:Nice Ones
   foo+bar@example6.com,foo,World's Best:Member
   bar@example7.com,bar,"Nice Ones":Member
   johndoes@example.com,,member:administrator</pre>
   </div>


   <h3>Upload CSV file or link to CSV file:</h3>

   <?php
      echo $this->Form->Open(['enctype' => 'multipart/form-data', 'action' => Url('/settings/bulkusersimporter/upload'), 'id' => 'bulk-importer-form']);
      echo $this->Form->Errors();
   ?>

   <div class="Info">
      Upload a CSV file or provide the URL to a CSV file. If both inputs are filled, the
      uploaded file takes precedence. There is a max filesize of
      <?php echo C('Garden.Upload.MaxFileSize'); ?> for the upload component.
      The file size limit for a URL is higher.
   </div>

   <ul id="bulk-importer-list">
      <li id="bulk-importer-validation-feedback">
      </li>
      <li id="bulk-importer-file-download">
         <?php
            echo $this->Form->Label('Upload CSV:', 'import_files');
            echo $this->Form->Input('import_files[]', 'file', ['multiple' => 'multiple']);
         ?>
      </li>

      <li id="bulk-importer-file-url">
         <?php
            echo $this->Form->Label('Or download CSV from URL:', 'import_url');
            echo $this->Form->Input('import_url', 'text');
         ?>
      </li>

      <li>
         <?php
            echo $this->Form->CheckBox('has_headers', "First line has headers.", ['class' => 'bulk-note']);
         ?>
      </li>
   </ul>

   <?php echo $this->Form->Close('Start'); ?>
</div>
