<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
class Cleanspeak extends Gdn_Pluggable {

    /// Properties ///

    /**
     * Used when generating random UUID's for content and users.
     * Will be used when routing requests from HUB to proper site.
     *    First Item reserved for SITE ID
     * @var array
     */
    public $uuidSeed = array(0, 0, 0, 0);

    /**
     * @var Cleanspeak
     */
    public static $instance;

    function __construct() {
        parent::__construct();
        $this->FireEvent('Init');
    }

    /**
     * Get an instance of the model.
     *
     * @return Cleanspeak
     */
    public static function instance() {
        if (isset(self::$instance)) {
            return self::$instance;
        }
        self::$instance = new Cleanspeak();
        return self::$instance;
    }


    /**
     * Send post to cleanspeak to see if content requires moderation.
     *
     * @param $UUID
     * @param $content
     * @param bool $forceModeration
     * @return array|mixed
     */
    public function moderation($UUID, $content, $forceModeration = false) {

        if ($forceModeration) {
            $content['moderation'] = 'requiresApproval';
        }

        $response = $this->apiRequest('/content/item/moderate/' . urlencode($UUID), $content);

        return $response;

    }

    public function flag($UUID, $flag) {
        $response = $this->apiRequest('/content/item/flag/' . urlencode($UUID), $flag);
        return $response;
    }


    /**
     *
     * Generate UUIDs for Content.
     * see uuidSeed property for pattern to be used.
     *
     * @return string UUID
     */
    public function getRandomUUID() {
        $seed = $this->uuidSeed;
        foreach ($seed as &$int) {
            if (!$int) {
                $int = self::get32BitRand();
            }
        }

        return static::generateUUIDFromInts($seed);
    }

    public function getUserUUID($userID) {
        $userAuth = Gdn::SQL()->GetWhere('UserAuthentication', array('UserID' => $userID, 'ProviderKey' => 'cleanspeak'))
            ->FirstRow(DATASET_TYPE_ARRAY);
        if (GetValue('ForeignUserKey', $userAuth)) {
            return $userAuth['ForeignUserKey'];
        }
        return $this->generateUUIDFromInts(array($this->uuidSeed[0], 0, 0, $userID));
    }

    public static function getUserIDFromUUID($UUID) {
        $ints = self::getIntsFromUUID($UUID);
        if ($ints[3] == 0 || !is_numeric($ints[3])) {
            return false;
        }
        return $ints[3];
    }

    /**
     * Given an array of 4 numbers create a UUID
     *
     * @param arrat ints Ints to be converted to UUID.  4 numbers; last 3 default to 0
     * @return string UUID
     *
     * @throws Gdn_UserException
     */
    public static function generateUUIDFromInts($ints) {
        if (sizeof($ints) != 4 && !isset($ints[0])) {
            throw new Gdn_UserException('Invalid arguments passed to ' . __METHOD__);
        }
        if (!isset($ints[1])) {
            $ints[1] = 0;
        }
        if (!isset($ints[2])) {
            $ints[2] = 0;
        }
        if (!isset($ints[3])) {
            $ints[3] = 0;
        }
        $result = self::hexInt($ints[0]) . '-' . self::hexInt($ints[1], true) . '-'
            . self::hexInt($ints[2], true) . self::hexInt($ints[3]);
        return $result;
    }

    /**
     * Send API request to cleanspeak.
     *
     * @param string $url URL with Port number included
     * @param array $post Post data.
     * @return mixed Response from server. If json response will be decoded.
     *
     * @throws CleanspeakException
     */
    public function apiRequest($url, $post) {

        $proxyRequest = new ProxyRequest();
        $options = array(
            'Url' => rtrim(C('Plugins.Cleanspeak.ApiUrl'), '/').'/'.ltrim($url, '/')
        );
        $queryParams = array();
        if ($post != null) {
            $options['Method'] = 'POST';
            $options['PreEncodePost'] = false;
            $queryParams = json_encode($post);
        }
        $headers['Content-Type'] = 'application/json';

        $apiKey = C('Plugins.Cleanspeak.AccessToken', null);
        if (!empty($apiKey)) {
            $headers['Authentication'] = $apiKey;
        }
        Logger::log(Logger::DEBUG, 'Cleanspeak API Request.', array(
              'Options' => $options,
              'QueryParams' => $queryParams,
              'Header' => $headers
           ));

        $response = $proxyRequest->Request($options, $queryParams, null, $headers);

        if ($proxyRequest->ResponseStatus == 400) {
            Logger::log(Logger::ERROR, 'Cleanspeak Error in API request.', array('Response' => json_decode($response, true)));
            throw new CleanspeakException('Error in cleanspeak request.');
        } elseif ($proxyRequest->ResponseStatus == 0) {
            Logger::log(Logger::ERROR, 'Cleanspeak Error in API. No Response.');
            throw new CleanspeakException('Error communicating with the cleanspeak server.', 500);
        } elseif ($proxyRequest->ResponseStatus != 200) {
            Logger::log(Logger::ERROR, 'Cleanspeak Error in API request.', array('Response' => json_decode($response, true)));
            throw new CleanspeakException('Error communicating with the cleanspeak server.');
        } else {
            Logger::log(Logger::DEBUG, 'Cleanspeak API Response.', array('Response' => $response));
        }

        if (stristr($proxyRequest->ResponseHeaders['Content-Type'], 'application/json') != false) {
            $response = json_decode($response, true);
        }


        return $response;

    }

    /**
     * Split data into Parts as read by Cleanspeak.
     *
     * @param $data
     * @return array
     * @throws Gdn_UserException
     */
    public function getParts($data) {

        if (GetValue('Name', $data)) {
            $text = Gdn_Format::Text($data['Name'], false);
            if (!empty($text) && trim($text) != '') {
                $parts[] = array(
                    'content' => $text,
                    'name' => 'Name',
                    'type' => 'text'
                );
            }
        }
        if (GetValue('Body', $data)) {
            $text = Gdn_Format::Text($data['Body'], false);
            if (!empty($text) && trim($text) != '') {
                $parts[] = array(
                    'content' => $text,
                    'name' => 'Body',
                    'type' => 'text'
                );
            }
        }
        if (GetValue('Story', $data)) {
            $text = Gdn_Format::Text($data['Story'], false);
            if (!empty($text) && trim($text) != '') {
                $parts[] = array(
                    'content' => $text,
                    'name' => 'WallPost',
                    'type' => 'text'
                );
            }
        }


        // Attachments.
        if (val('MediaIDs', $data)) {
            $MediaModel = new Gdn_Model('Media');

            foreach ($data['MediaIDs'] as $MediaID) {
                $Media = $MediaModel->GetID($MediaID);
                if (!$Media) {
                    continue;
                }
                $Path = val('Path', $Media);
                $Name = 'Attachment';
                $Type = 'hyperlink';

                if (stristr(val('Type', $Media), 'image') !== false) {
                    $Name = 'Image';
                    $Type = 'image';
                }

                $parts[] = array(
                    'content' => Gdn_Upload::Url($Path),
                    'name' => $Name,
                    'type' => $Type
                );
            }

        }


        if (sizeof($parts) == 0) {
            throw new Gdn_UserException('Error getting parts from content');
        }
        return $parts;

    }

    /**
     * @param string $UUID Universal Unique Identifier.
     * @return array Containing the 4 numbers used to generate generateUUIDFromInts
     */
    public static function getIntsFromUUID($UUID) {
        $parts = str_split(str_replace('-', '', $UUID), 8);
        $parts = array_map('hexdec', $parts);
        return $parts;
    }


    /**
     * Get a random 32bit integer.  0x80000000 to 0xFFFFFFFF were not being tested with rand().
     *
     * @return int randon 32bi integer.
     */
    public static function get32BitRand() {
        return mt_rand(0, 0xFFFF) | (mt_rand(0, 0xFFFF) << 16);
    }

    /**
     * Used to help generate UUIDs; pad and convert from decimal to hexadecimal; and split if neeeded
     *
     * @param $int Integer to be converted
     * @param bool $split Split result into parts.
     * @return string
     */
    public static function hexInt($int, $split = false) {
        $result = substr(str_pad(dechex($int), 8, '0', STR_PAD_LEFT), 0, 8);
        if ($split) {
            $result = implode('-', str_split($result, 4));
        }
        return $result;
    }


}