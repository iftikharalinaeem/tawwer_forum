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
      'adobeprereleasestage' => 'https://forums.stage.adobeprerelease.com',
      'adobeprereleasestaging' => 'https://forums.stage.adobeprerelease.com',
      'vanilladev' => 'http://vanilla.dev'
   ];

   /**
    * @var array A map of email domain names to forum domain names.
    */
   protected $emailDomains = [
      'email.vanillaforums.com' => 'vanillaforums.com',
      'vanillaforums.email' => 'vanillaforums.com',
      'vanillacommunity.email' => 'vanillacommunity.com',
      'vanillacommunities.email' => 'vanillacommunities.com',
      'dev.vanillaforums.email' => 'vanillawip.com',
      'vanillastaging.email' => 'vanillastaging.com'
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
            Logger::event('email_received', Logger::DEBUG, "Email received.", ['data' => $Post]);

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
                'text' => 'Source',
                'spam_score' => 'SpamScore'
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

            $LogModel = new EmailLogModel();
            $Data['Post'] = $Data;
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

            // Check for a site.
            $sources = array_merge(
               ['replyTo' => val('ReplyTo', $HeaderData, '')],
               $this->explodeReferences(val('References', $HeaderData, '')),
               ['to' => $Email]
            );

            $guessed = true;
            $site = $this->getSiteFromEmail($sources);

            if (!empty($site)) {
               $quessed = false;
               // Check for a multisite.
               if (!empty($site['multisite'])) {
                  $Url = "https://{$site['multisite']['real']}/utility/email.json";
               } else {
                  $Url = "https://{$site['name']}/utility/email.json";
               }
            } elseif ($emailDomain && preg_match('`([^+@]+)([^@]*)$`', $slug, $Matches)) {
               $ToParts = explode('.', strtolower($Matches[1]));
//               $Args = $Matches[2];

               // Check for http or https.
               $Scheme = 'https';
               if (in_array(reset($ToParts), ['http', 'https'])) {
                  $Scheme = array_shift($ToParts);
               }

               $Folder = '';
               $Part = end($ToParts);
               if (isset($this->Aliases[$Part])) {
                  $guessed = false;
                  $aliasUrl = $this->Aliases[$Part];

                  // Check this email against the hub to see if it should be forwarded somewhere.
                  if (strpos($Email, '+') === false) {
                     $forwardInfo = $this->checkHub($aliasUrl, $Email, $Data['Subject']);
                  }

                  if (!empty($forwardInfo)) {
                     $Url = val('Url', $forwardInfo).'/utility/email.json';
                     $Data['Post'] = array_replace($Data['Post'], val('Data', $forwardInfo, []));
                  } else {
                     array_pop($ToParts); // pop node name

                     // This is a site node alias in the form: folder.alias+args@email.vanillaforums.com.
                     $UrlParts = parse_url($this->Aliases[$Part]);
                     $Scheme = val('scheme', $UrlParts, 'https');
                     $Domain = val('host', $UrlParts, 'email.vanillaforums.com');
                     $Folder = '/'.array_pop($ToParts);
                  }
               } elseif (count($ToParts) > 1) {
                  // Check for a full domain. We are just going to support a few TLDs because this is a legacy format.
                  $Part = array_pop($ToParts);
                   if (count($ToParts) > 1 || in_array($Part, array('com', 'org', 'net'))) {
                     $Domain = implode('.', $ToParts).'.'.$Part;
                  } else {
                     // This is a to in the form of category.site.
                     $Domain = $Part.'.'.$this->emailDomains[$emailDomain];
                     $Args = array_shift($ToParts);
                     $To = "$Part+$Args@email.vanillaforums.com";
                     $Data[$To] = $To;
                  }
               } else {
                  $subdomain = $ToParts[0];
                  $Domain = "$subdomain.{$this->emailDomains[$emailDomain]}";

                  if (strpos($subdomain, '-') !== false) {
                     $Url = $this->getSiteUrlFromHost($Domain, $found)."/utility/email.json";
                     $guessed = !$found;
                  }
               }

               if (empty($Url)) {
                  $Url = "$Scheme://{$Domain}{$Folder}/utility/email.json";
               }
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
            curl_setopt($C, CURLOPT_POSTFIELDS, http_build_query($Data['Post'], '', '&'));

            $Result = curl_exec($C);
            $Code = curl_getinfo($C, CURLINFO_HTTP_CODE);

            if ($Code == 200) {
               $ResultData = @json_decode($Result);
               if ($ResultData) {
                  $this->Data = $Data;
               }
               $LogModel->SetField($LogID, array('Post' => $Data['Post'], 'Response' => $Code, 'ResponseText' => $Result));
            } else {
               // Check to see if the site exists at all.
               if ($guessed) {
                  $r = $this->getSiteUrlFromHost(parse_url($Url, PHP_URL_HOST));
               }
               if ($guessed && empty($r)) {
                  $LogModel->setFeild(
                      $LogID,
                      ['Post' => $Data['Post'], 'Response' => 410, 'ResponseText' => 'Site does not exist.']
                  );
               } else {
                  $Error = curl_error($C)."\n\n$Result";
                  $LogModel->SetField($LogID, array('Post' => $Data['Post'], 'Response' => $Code, 'ResponseText' => $Error));

                  if ($Code != 404) {
                     throw new Exception($Error, $Code);
                  }
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
    * Make an API call to the hub to see where the email should be forwarded to.
    *
    * @param string $baseUrl The base URL of the hub.
    * @param string $email The email address that the email has been sent to.
    * @param string $subject The subject of the email.
    */
   private function checkHub($baseUrl, $email, $subject) {
      $url = "$baseUrl/hub/api/v1/utility/emailroute.json";
      $params = ['Email' => $email, 'Subject' => $subject];
      $headers = [];

      $urlParts = parse_url($url);

      // Fix the URL for localhost calls.
      if ($urlParts['host'] === 'localhost' || stringEndsWith($urlParts['host'], '.dev')) {
         $headers['Host'] = $urlParts['host'];
         $urlParts['host'] = '127.0.0.1';
         $url = http_build_url($baseUrl, $urlParts);
      }

      $request = new ProxyRequest();
      $response = $request->Request([
          'URL' => $url,
          'Cookies' => false,
          'Method' => 'POST',
          'Timeout' => 100,
      ], $params, null, $headers);

      if (strpos($request->ContentType, 'application/json') !== false) {
         $response = json_decode($response, true);
      }

      if ($request->responseClass('2xx') && is_array($response)) {
         return $response;
      }

      return null;
   }

   /**
    * Get the site row from a list of email address sources.
    *
    * @param string[] $sources The possible reply-to sources of the email.
    * @return array|null Returns the site row or null if a site could not be determined.
    */
   private function getSiteFromEmail($sources) {
      if (!class_exists('Communication')) {
         return null;
      }

      foreach ($sources as $key => $source) {
         if (preg_match('`-s(\d+)@`', $source, $matches)) {
            $siteID = $matches[1];

            $parameters = [
                '__path' => '/site/full',
                '__method' => 'GET',
                'siteid' => $siteID,
            ];
            // Look up the site.
            $response = Communication::data('/orcProxy?'.http_build_query($parameters))
                ->method('POST')
                ->cache(60)
                ->send();
            $response = valr('proxyResponse.data', $response);

            if (is_array(val('site', $response))) {
               $site = $response['site'];

               if (!empty($response['multisite'])) {
                  $site['multisite'] = $response['multisite'];
               }

               Logger::event(
                  'emailer_site',
                  Logger::INFO,
                  'Site {site.name} found from source: {source}.',
                  ['source' => $source, 'site' => $this->trimSiteForLog($site), 'sourceKey' => $key]
               );

               return $site;
            }
         }
      }

      return null;
   }

   /**
    * Take a string in the form of an email references header and explode it into individual email IDs.
    *
    * @param $referencesString
    */
   private function explodeReferences($referencesString) {
      $references = preg_split('`>\s*<`', $referencesString);
      $result = [];

      $i = 0;
      foreach ($references as $reference) {
         $reference = trim($reference);
         if (!$reference) {
            continue;
         }
         $reference = '<'.trim($reference, '<>').'>';
         $result[sprintf('reference%02d', $i)] = $reference;

         $i++;
      }

      return $result;
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

   /**
    * Look up a site and see what it should be called as.
    *
    * Nodes will use their Vanilla name convention by default (i.e. slug-node.vanillaforums.com). A node with this URL
    * cannot be called directly and must fall back to its multisite format.
    *
    * @param string $host The hostname to look at or an empty string if there is no site.
    * @param bool &$found Whether or not the site was found.
    * @return string Returns the site URL.
    */
   private function getSiteUrlFromHost($host, &$found = false) {
      $default = "https://$host";

      if (!class_exists('Communication')) {
         return $default;
      }

      $parameters = [
        '__path' => '/site/query',
        '__method' => 'GET',
        'query' => $host,
        'users' => 0,
        'verbose' => 0,
      ];
      // Look up the site ID.
      $queryResponse = Communication::data('/site/query')
          ->method('POST')
          ->cache(60)
          ->send();
      $queryResponse = valr('proxyResponse.data', $queryResponse);

//      Logger::event('orchestration_site_query', Logger::DEBUG, "Sites", ['response' => $queryResponse]);
      if (empty(val('sites', $queryResponse))) {
         return '';
      }

      $parameters = [
        '__path' => '/site/full',
        '__method' => 'GET',
        'siteid' => valr('sites.0.SiteID', $queryResponse),
      ];
      // Look up the site.
      $siteResponse = Communication::data('/orcProxy?'.http_build_query($parameters))
          ->method('POST')
          ->cache(60)
          ->send();
      $siteResponse = valr('proxyResponse.data', $siteResponse);

      if (!empty($siteResponse['site'])) {
         $found = true;

         Logger::event(
             'emailer_site',
             Logger::INFO,
             'Site {site.name} found from source: {source}.',
             [
                 'source' => $host,
                 'site' => $this->trimSiteForLog($siteResponse['site'], val('multisite', $siteResponse, null)),
                 'sourceKey' => 'host'
             ]
         );
      }


//      Logger::event('orchestration_site_full', Logger::DEBUG, "Site", ['response' => $siteResponse]);

      if (is_array(val('multisite', $siteResponse))) {
         // This is a multisite and must use a different URL format.
         $multisite = $siteResponse['multisite'];
         $result = "https://{$multisite['real']}";
         return $result;
      }

      return $default;
   }


   /**
    * Trim a site row so it doesn't take up too much room in the log.
    *
    * @param array $site The site to trim.
    * @param array|null $multisite The multisite info, if any.
    * @return array Returns a subset of the site as an array.
    */
   private function trimSiteForLog($site, $multisite = null) {
      $r = arrayTranslate($site, ['siteid', 'accountid', 'name', 'state', 'cluster', 'domain', 'multisite']);

      if (is_array($multisite) && !empty($multisite)) {
         $r['multisite'] = $multisite;
      }
   }

}