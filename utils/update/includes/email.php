<?php if (!defined('APPLICATION')) exit();

/**
 * This file is part of Runner.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

require_once(PATH_RUNNER.'/vendors/phpmailer/class.phpmailer.php');
class Email {

   /**
    * @var PHPMailer
    */
   public $PhpMailer;
   protected $Client;

   /**
    * @var boolean
    */
   private $_IsToSet;

   /**
    * Constructor
    */
   function __construct(&$Client) {
      $this->Client = $Client;
      
      $this->PhpMailer = new PHPMailer();
      $this->PhpMailer->CharSet = $this->Client->C('Garden.Charset', 'utf-8');
      $this->PhpMailer->SingleTo = $this->Client->C('Garden.Email.SingleTo', FALSE);
      $this->PhpMailer->PluginDir = PATH_RUNNER.'/vendors/phpmailer/';
      $this->Clear();
   }


   /**
    * Adds to the "Bcc" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "Bcc" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    * @return Email
    */
   public function Bcc($RecipientEmail, $RecipientName = '') {
      ob_start();
      $this->PhpMailer->AddBCC($RecipientEmail, $RecipientName);
      ob_end_clean();
      return $this;
   }
   
   /**
    * Adds to the "Cc" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "Cc" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    * @return Email
    */
   public function Cc($RecipientEmail, $RecipientName = '') {
      ob_start();
      $this->PhpMailer->AddCC($RecipientEmail, $RecipientName);
      ob_end_clean();
      return $this;
   }

   /**
    * Clears out all previously specified values for this object and restores
    * it to the state it was in when it was instantiated.
    *
    * @return Email
    */
   public function Clear() {
      $this->PhpMailer->ClearAllRecipients();
      $this->PhpMailer->Body = '';
      $this->PhpMailer->AltBody = '';
      $this->From();
      $this->_IsToSet = FALSE;
      $this->MimeType($this->Client->C('Garden.Email.MimeType', 'text/plain'));
      $this->_MasterView = 'email.master';
      return $this;
   }

   /**
    * Allows the explicit definition of the email's sender address & name.
    * Defaults to the applications Configuration 'SupportEmail' & 'SupportName'
    * settings respectively.
    *
    * @param string $SenderEmail
    * @param string $SenderName
    * @return Email
    */
   public function From($SenderEmail = '', $SenderName = '', $bOverrideSender = FALSE) {
      if ($SenderEmail == '') {
         $SenderEmail = $this->Client->C('Garden.Email.SupportAddress', '');
         if (!$SenderEmail) {
            $SenderEmail = 'noreply@vanillaforums.com';
         }
      }

      if ($SenderName == '')
         $SenderName = $this->Client->C('Garden.Email.SupportName', $this->Client->C('Garden.Title', ''));
      
      if($this->PhpMailer->Sender == '' || $bOverrideSender) $this->PhpMailer->Sender = $SenderEmail;
      
      ob_start();
      $this->PhpMailer->SetFrom($SenderEmail, $SenderName, FALSE);
      ob_end_clean();
      return $this;
   }

   /**
    * Allows the definition of a masterview other than the default:
    * "email.master".
    *
    * @param string $MasterView
    * @todo To implement
    * @return Email
    */
   public function MasterView($MasterView) {
      return $this;
   }

   /**
    * The message to be sent.
    *
    * @param string $Message The message to be sent.
    * @tod: implement
    * @return Email
    */
   public function Message($Message) {
   
      // htmlspecialchars_decode is being used here to revert any specialchar escaping done by Gdn_Format::Text()
      // which, untreated, would result in &#039; in the message in place of single quotes.
   
      if ($this->PhpMailer->ContentType == 'text/html') {
         $this->PhpMailer->MsgHTML(htmlspecialchars_decode($Message,ENT_QUOTES));
      } else {
         $this->PhpMailer->Body = htmlspecialchars_decode($Message,ENT_QUOTES);
      }
      return $this;
   }

   /**
    * Sets the mime-type of the email.
    *
    * Only accept text/plain or text/html.
    *
    * @param string $MimeType The mime-type of the email.
    * @return Email
    */
   public function MimeType($MimeType) {
      $this->PhpMailer->IsHTML($MimeType === 'text/html');
      return $this;
   }

   /**
    * @todo add port settings
    */
   public function Send($EventName = '') {
      
      $this->PhpMailer->IsSMTP();
      $SmtpHost = $this->Client->C('Garden.Email.SmtpHost', '');
      $SmtpPort = $this->Client->C('Garden.Email.SmtpPort', 25);
      if (strpos($SmtpHost, ':') !== FALSE) {
         list($SmtpHost, $SmtpPort) = explode(':', $SmtpHost);
      }

      $this->PhpMailer->Host = $SmtpHost;
      $this->PhpMailer->Port = $SmtpPort;
      $this->PhpMailer->SMTPSecure = $this->Client->C('Garden.Email.SmtpSecurity', '');
      $this->PhpMailer->Username = $Username = $this->Client->C('Garden.Email.SmtpUser', '');
      $this->PhpMailer->Password = $Password = $this->Client->C('Garden.Email.SmtpPassword', '');
      if(!empty($Username))
         $this->PhpMailer->SMTPAuth = TRUE;

      
      if (!$this->PhpMailer->Send()) {
         throw new Exception($this->PhpMailer->ErrorInfo);
      }
      
      return true;
   }
   
   /**
    * Adds subject of the message to the email.
    * 
    * @param string $Subject The subject of the message.
    */
   public function Subject($Subject) {
      $this->PhpMailer->Subject = $Subject;
      return $this;  
   }

   
   public function AddTo($RecipientEmail, $RecipientName = ''){
      ob_start();
      $this->PhpMailer->AddAddress($RecipientEmail, $RecipientName);
      ob_end_clean();
      return $this;
   }
   
   /**
    * Adds to the "To" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "To" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    */
   public function To($RecipientEmail, $RecipientName = '') {

      if (is_string($RecipientEmail)) {
         if (strpos($RecipientEmail, ',') > 0) {
            $RecipientEmail = explode(',', $RecipientEmail);
            // trim no need, PhpMailer::AddAnAddress() will do it
            return $this->To($RecipientEmail, $RecipientName);
         }
         if ($this->PhpMailer->SingleTo) return $this->AddTo($RecipientEmail, $RecipientName);
         if (!$this->_IsToSet){
            $this->_IsToSet = TRUE;
            $this->AddTo($RecipientEmail, $RecipientName);
         } else
            $this->Cc($RecipientEmail, $RecipientName);
         return $this;
         
      } elseif ($RecipientEmail instanceof stdClass) {
         $RecipientName = GetValue('Name', $RecipientEmail);
         $RecipientEmail = GetValue('Email', $RecipientEmail);
         return $this->To($RecipientEmail, $RecipientName);
      
      } elseif (is_array($RecipientEmail)) {
         $Count = count($RecipientEmail);
         if (!is_array($RecipientName)) $RecipientName = array_fill(0, $Count, '');
         if ($Count == count($RecipientName)) {
            $RecipientEmail = array_combine($RecipientEmail, $RecipientName);
            foreach($RecipientEmail as $Email => $Name) $this->To($Email, $Name);
         } else
            trigger_error(ErrorMessage('Size of arrays do not match', 'Email', 'To'), E_USER_ERROR);
         
         return $this;
      }
      
      trigger_error(ErrorMessage('Incorrect first parameter ('.GetType($RecipientEmail).') passed to function.', 'Email', 'To'), E_USER_ERROR);
   }
   
   public function Charset($Use = ''){
      if ($Use != '') {
         $this->PhpMailer->CharSet = $Use;
         return $this;
      }
      return $this->PhpMailer->CharSet;
   }
   
}