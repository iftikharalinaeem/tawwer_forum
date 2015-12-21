<?php if (!defined('APPLICATION')) exit();

class EmailRouterController extends Gdn_Controller {
   /// Properties ///
   
   /**
    *
    * @var Gdn_Form 
    */
   public $Form;

   /**
    * Aliases for site hub/nodes.
    *
    * @var array
    */
   public $Aliases = [
      'adobeprerelease' => 'https://forums.adobeprerelease.com',
      'adobeprereleasestage' => 'https://forums.stage.adobeprerelease.com'
   ];

   /**
    * @var array A map of email domain names to forum domain names.
    */
   protected $emailDomains = [
      'email.vanillaforums.com' => 'vanillaforums.com',
      'vanillaforums.email' => 'vanillaforums.com',
      'vanillacommunity.email' => 'vanillacommunity.com',
      'vanillacommunities.email' => 'vanillacommunities.com'
   ];
   
   /// Methods ///
   
   /**
    * Include JS, CSS, and modules used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      
//      $this->MasterView = 'default';
      parent::Initialize();
   }

   /**
    * Parse a free-form email address into the name and email address.
    *
    * @param string $Email The email address to parse.
    * @return array Returns an array in the form `[$name, $email]`.
    */
   public static function ParseEmailAddress($Email) {
      $Name = '';
      if (preg_match('`([^<]*)<([^>]+)>`', $Email, $Matches)) {
         $Name = trim(trim($Matches[1]), '"');
         $Email = trim($Matches[2]);
      }
         
      if (!$Name) {
         $Name = trim(substr($Email, 0, strpos($Email, '@')), '@');
         
         $NameParts = explode('.', $Name);
         $NameParts = array_map('ucfirst', $NameParts);
         $Name = implode(' ', $NameParts);
      }
      
      $Result = array($Name, $Email);
      return $Result;
   }

   /**
    * Parse the email domain out of a raw email field.
    *
    * @param string $email The email to parse.
    * @param bool $onlyExisting Whether or not to check the domain against {@link $this->emailDomains}.
    * @return array Returns an array in the form `[$to, $domain]` or `['', '']` if the email domain isn't in our list.
    */
   public function parseEmailDomain($email, $onlyExisting) {
      list($_, $email) = static::ParseEmailAddress($email);
      list($to, $domain) = explode('@', $email, 2);

      if ($onlyExisting && !array_key_exists($domain, $this->emailDomains)) {
         return ['', ''];
      } else {
         return [$to, $domain];
      }
   }
   
   public static function ParseEmailHeader($Header) {
      $Result = array();
      $Parts = explode("\n", $Header);

      $i = NULL;
      foreach ($Parts as $Part) {
         $Part = trim($Part);
         if (!$Part)
            continue;
         if (preg_match('`^\s`', $Part)) {
            if (isset($Result[$i])) {
               $Result[$i] .= "\n".$Part;
            }
         } else {
//            self::Log("Headerline: $Part");
            list($Name, $Value) = explode(':', $Part, 2);
            $i = trim($Name);
            if (isset($Result[$i]))
               $Result[$i] .= "\n".ltrim($Value);
            else
               $Result[$i] = ltrim($Value);
         }
      }

      return $Result;
   }
   
   public function Sendgrid() {
      $this->SetData('Title', T('Sendgrid Proxy'));
      try {
         Gdn::Session()->Start(Gdn::UserModel()->GetSystemUserID(), FALSE);
         Gdn::Session()->User->Admin = FALSE;

         $this->Form = new Gdn_Form();

         if ($this->Form->IsPostBack()) {
//            self::Log("Postback");

//            self::Log("Getting post...");
            $Post = $this->Form->FormValues();
            
            // All of the post data can come in a variety of encodings.
            $Charsets = @json_decode($Post['charsets']);

            if (is_array($Charsets)) {
               // Convert all of the encodings to utf-8.
               $Encodings = array_map('strtolower', mb_list_encodings());

               foreach ($Charsets as $Key => $Charset) {
                  $Charset = strtolower($Charset);
                  if ($Charset == 'utf-8')
                     continue;
                  if (!in_array($Charset, $Encodings))
                     continue;
                  if (!isset($Post[$Key]))
                     continue;

                  Trace("Converting $Key from $Charset to utf-8.");
                  $Post[$Key] = mb_convert_encoding($Post[$Key], 'utf-8', $Charset);
               }
            }
            
//            self::Log("Post got...");
            $Data = ArrayTranslate($Post, array(
                'from' => 'From',
                'to' => 'To',
                'subject' => 'Subject',
                'headers' => 'Headers',
                'text' => 'Source'
            ));

   //         self::Log('Parsing headers.'.GetValue('headers', $Post, ''));
            $Headers = self::ParseEmailHeader(GetValue('headers', $Post, ''));
   //         self::Log('Headers: '.print_r($Headers, TRUE));
            $Headers = array_change_key_case($Headers);
            
            $HeaderData = ArrayTranslate($Headers, array('message-id' => 'MessageID', 'references' => 'References', 'in-reply-to' => 'ReplyTo'));
            $Data = array_merge($Data, $HeaderData);

            if (FALSE && GetValue('html', $Post)) {
               $Data['Body'] = $Post['html'];
               $Data['Format'] = 'Html';
            } else {
               $Data['Body'] = $Post['text'];
               $Data['Format'] = 'Html';
            }
            $Data['Subject'] = substr($Data['Subject'], 0, 100);
            
//            self::Log("Saving data...");
            $this->Data['_Status'][] = 'Saving data.';
            
            // Figure out the url from the email's address.
            $To = GetValue('x-forwarded-to', $Headers);
            $quotedDomains = array_map('preg_quote', array_keys($this->emailDomains));
            $pregDomains = implode('|', $quotedDomains);

            if (!$To) {
               // Check the received header.
               if (preg_match("`<([^<>@]+@(?:$pregDomains))>`", $Headers['received'], $Matches)) {
                  $To = $Matches[1];
               } else {
                  $To = $Data['To'];
               }
            }
            
            $LogModel = new Gdn_Model('EmailLog');
            $Data['Post'] = http_build_query($Data, '', '&');
            $Data['Charsets'] = GetValue('charsets', $Post, NULL);

            if ($this->isOutOfOffice($Headers, $Data)) {
               $Data['Response'] = 202;
               $Data['ResponseText'] = 'Out of office';
               $LogID = $LogModel->Insert($Data);
               return;
            } elseif ($this->isServerStatus($Headers, $Data)) {
               $Data['Response'] = 202;
               $Data['ResponseText'] = 'Server status';
               $LogID = $LogModel->Insert($Data);
               return;
            }

            $LogID = $LogModel->Insert($Data);
            
            list($Name, $Email) = self::ParseEmailAddress($To);
            list($slug, $emailDomain) = $this->parseEmailDomain($Email, true);
            if ($emailDomain && preg_match('`([^+@]+)([^@]*)$`', $slug, $Matches)) {
               $ToParts = explode('.', strtolower($Matches[1]));
//               $Args = $Matches[2];

               // Check for http or https.
               $Scheme = 'http';
               if (in_array(reset($ToParts), ['http', 'https'])) {
                  $Scheme = array_shift($ToParts);
               }

               $Folder = '';
               if (count($ToParts) > 1) {
                  // Check for a full domain. We are just going to support a few tlds because this is a legacy format.
                  $Part = array_pop($ToParts);
                  if (isset($this->Aliases[$Part])) {
                     // This is a site node alias in the form: folder.alias+args@email.vanillaforums.com.
                     $UrlParts = parse_url($this->Aliases[$Part]);
                     $Scheme = val('scheme', $UrlParts, 'http');
                     $Domain = val('host', $UrlParts, 'email.vanillaforums.com');
                     $Folder = '/'.array_pop($ToParts);
                  } elseif (count($ToParts) > 1 || in_array($Part, array('com', 'org', 'net'))) {
                     $Domain = implode('.', $ToParts).'.'.$Part;
                  } else {
                     // This is a to in the form of category.site.
                     $Domain = $Part.'.'.$this->emailDomains[$emailDomain];
                     $Args = array_shift($ToParts);
                     $To = "$Part+$Args@email.vanillaforums.com";
                     $Data[$To] = $To;
                  }
               } else {
                  $Domain = $ToParts[0].'.'.$this->emailDomains[$emailDomain];
               }

               $Url = "$Scheme://{$Domain}{$Folder}/utility/email.json";
               $LogModel->SetField($LogID, array('Url' => $Url));
            } else {
               $LogModel->SetField($LogID, array('Response' => 400, 'ResponseText' => "Invalid to: $To, $Email."));
               if (Debug())
                  throw new Exception("Invalid to: $To, $Email\n".var_dump($_POST), 400);
               $this->SetData('Error', "Invalid to: $To");
               $this->Render();
               return;
            }
            
            // Curl the data to the new forum.
            $C = curl_init();
            curl_setopt($C, CURLOPT_URL, $Url);
            curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
//            curl_setopt($C, CURLOPT_HEADER, FALSE);
//            curl_setopt($C, CURLINFO_HEADER_OUT, TRUE);
//            curl_setopt($C, CURLOPT_HEADERFUNCTION, array($this, 'CurlHeader'));
            curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($C, CURLOPT_POST, 1);
            curl_setopt($C, CURLOPT_POSTFIELDS, $Data['Post']);
            
            $Result = curl_exec($C);
            $Code = curl_getinfo($C, CURLINFO_HTTP_CODE);
            
            if ($Code == 200) {
               $ResultData = @json_decode($Result);
               if ($ResultData) {
                  $this->Data = $Data;
               }
               $LogModel->SetField($LogID, array('Response' => $Code, 'ResponseText' => $Result));
            } else {
               $Error = curl_error($C)."\n\n$Result";
               $LogModel->SetField($LogID, array('Response' => $Code, 'ResponseText' => $Error));

               if ($Code != 404) {
                  throw new Exception($Error, $Code);
               }
            }
         }

         
         $this->Render();
      } catch (Exception $Ex) {
         $Contents = $Ex->getMessage()."\n"
            .$Ex->getTraceAsString()."\n"
            .print_r($_POST, TRUE);
//         file_put_contents(PATH_UPLOADS.'/email/error_'.time().'.txt', $Contents);
         
         throw $Ex;
      }
   }

   /**
    * Determines whether or not an email is an out of office reply.
    *
    * @param array $headers The array of email headers.
    * @param array $data The email data.
    * @return bool Returns true if the email is an out of office email, or false otherwise.
    */
   public function isOutOfOffice($headers, $data) {
      if (
         val('x-autoreply', $headers) ||
         val('auto-submitted', $headers) == 'auto-replied' ||
         stripos($data['Subject'], 'Out of Office') !== false
      ) {
         return true;
      }
      return false;
   }

   /**
    * Determines whether or not an email is a system administrative status.
    *
    * @param array $headers The array of email headers.
    * @return bool Returns true if the headers indicate a system administrative status, or false otherwise.
    */
   public function isServerStatus($headers) {
      $contentType = val('content-type', $headers);

      if (stripos($contentType, 'multipart/report') !== false) {
         return true;
      }
      return false;
   }
   
   protected function CurlHeader($Handler, $HeaderString) {
      $this->LastHeaderString = $HeaderString;
   }
   
   public static function StripEmail($Body) {
      $SigFound = FALSE; 
      $InQuotes = 0;

      $Lines = explode("\n", trim($Body));
      $LastLine = count($Lines);

      for ($i = $LastLine - 1; $i >= 0; $i--) {
         $Line = $Lines[$i];

         if ($InQuotes === 0 && preg_match('`^\s*[>|]`', $Line)) {
            // This is a quote line.
            $LastLine = $i;
         } elseif (!$SigFound && preg_match('`^\s*--`', $Line)) {
            // -- Signature delimiter.
            $LastLine = $i;
            $SigFound = TRUE;
         } elseif (preg_match('`^\s*---.+---\s*$`', $Line)) {
            // This will catch an ------Original Message------ heade
            $LastLine = $i;
            $InQuotes = FALSE;
         } elseif ($InQuotes === 0) {
            if (preg_match('`wrote:\s*$`i', $Line)) {
               // This is the quote line...
               $LastLine = $i;
               $InQuotes = FALSE;
            } elseif (preg_match('`^\s*$`', $Line)) {
               $LastLine = $i;
            } else {
               $InQuotes = FALSE;
            }
         }
      }

      if ($LastLine >= 1) {
         $Lines = array_slice($Lines, 0, $LastLine);
      }
      $Result = trim(implode("\n", $Lines));
      return $Result;
   }
}
