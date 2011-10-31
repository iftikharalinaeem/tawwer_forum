<?php if (!defined('APPLICATION')) exit();

class EmailRouterController extends Gdn_Controller {
   /// Properties ///
   
   /**
    *
    * @var Gdn_Form 
    */
   public $Form;
   
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
   
   public static function ParseEmailHeader($Header) {
      $Result = array();
      $Parts = explode("\n", $Header);

      $i = NULL;
      foreach ($Parts as $Part) {
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
         $this->Form->InputPrefix = '';

         if ($this->Form->IsPostBack()) {
//            self::Log("Postback");

//            self::Log("Getting post...");
            $Post = $this->Form->FormValues();
            
            $Path = PATH_UPLOADS .'/Email/'.date('Y-m-d_H-i-sP').'.txt';
            if (!file_exists(dirname($Path)))
               mkdir(dirname($Path), 0777, TRUE);
            file_put_contents($Path, print_r($Post, TRUE));
            
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
               $Data['Body'] = self::StripEmail($Post['text']);
               $Data['Format'] = 'Html';
            }

//            self::Log("Saving data...");
            $this->Data['_Status'][] = 'Saving data.';
            
            // Figure out the url from the email's address.
            $To = GetValue('x-forwarded-to', $Headers);
            if (!$To) {
               $To = $Data['To'];
            }
            
            if (preg_match('`([^+@]+)([^@]*)@(.+)`', $To, $Matches)) {
               $ClientName = $Matches[1];
               $Domain = $Matches[3];
               
               $Url = "http://$ClientName.vanillaforums.com/utility/email.json";
            } else {
               if ($this->DeliveryType() != DELIVERY_TYPE_ALL)
                  throw Exception("Invalid to: $To\n".var_dump($_POST));
               $this->SetData('Error', "Invalid to: $To");
               $this->Render();
               return;
            }
            
            // Curl the data to the new forum.
            $C = curl_init();
            curl_setopt($C, CURLOPT_URL, $Url);
            curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($C, CURLOPT_POST, 1);
            curl_setopt($C, CURLOPT_POSTFIELDS, $Data);
            
            $Result = curl_exec($C);
            $ResultInfo = curl_getinfo($C);
            $ResultData = @json_decode($Result);
            if ($ResultData) {
               $this->Data = $Data;
            }
         }

         
         $this->Render();
      } catch (Exception $Ex) {
         $Contents = $Ex->getMessage()."\n"
            .$Ex->getTraceAsString()."\n"
            .print_r($_POST, TRUE);
         file_put_contents(PATH_UPLOADS.'/email/error_'.time().'.txt', $Contents);
         
         throw $Ex;
      }
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