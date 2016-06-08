<?php

use Garden\Http;

/**
 * Class MailChimpWrapper
 *
 * Methods to facilitate commumicating with Mail Chimp.
 */

class MailChimpWrapper {
    var $version = '3.0';
    var $secure = false;
    var $timeout = 300;

    /**
     * Cache the user api_key so we only have to log in once per client instantiation
     */
    var $api_key;

    /**
     * Cache the information on the API location on the server
     */
    var $apiUrl;

    public function __construct($apikey, $secure=false) {
        $this->secure = $secure;
        $this->apiUrl = parse_url('https://api.mailchimp.com/' . $this->version . '/');
        $this->api_key = $apikey;
    }

    /**
     * Actually connect to the server and call the requested methods, parsing the result
     * You should never have to call this function manually
     *
     * @param null $endpoint
     * @param string $method
     * @param array $body
     * @return Http\HttpResponse
     * @throws Exception
     */
    public function callServer($endpoint = null, $method = 'GET', $body = array(), $returnBody = false) {
        // parse out which data center the request should be sent to from the API key
        $dataCenter = 'us1';
        if (strstr($this->api_key,'-')){
            list($key, $dataCenter) = explode('-',$this->api_key,2);
            if (!$dataCenter) $dataCenter = 'us1';
        }

        if (!$endpoint && $endpoint !== 'ping') {
            throw
                new Exception('Missing Endpoint');
        }

        if ($endpoint === 'ping') {
            $endpoint = '';
        }

        // create the endpoint based by appending the data center as a subdomain
        $destinationURI = $this->apiUrl['scheme'].'://'.$dataCenter.'.'.$this->apiUrl['host'].'/'.$this->version.'/'.$endpoint;

        $headers['Content-type'] = 'application/json';

        $options['auth'] = ['vf-user',  $this->api_key];

        $httpRequest =  new Http\HttpRequest($method, $destinationURI, $body, $headers, $options);
        $httpRequest->setTimeout($this->timeout);

        $response = $httpRequest->send();
        if ($returnBody) {
            $response->getBody();
        }
        return $response;
    }

    /**
     * Get a json encoded list of mailing lists at Mail Chimp.
     *
     * @return Http\HttpResponse
     * @throws Exception
     */
    function lists() {
        return $this->callServer('lists');
    }

    /**
     * Send an array of emails to be subscribed to a mailing list at Mail Chimp.
     *
     * @param $id
     * @param $batch
     * @param bool|true $double_optin
     * @param bool|false $update_existing
     * @param bool|true $replace_interests
     * @return Http\HttpResponse
     * @throws Exception
     */
    public function listBatchSubscribe($id, $batch, $double_optin=true, $update_existing=false, $replace_interests=true) {
        $body = [];
        foreach ($batch as $userInfo) {
            $emailType = val('EMAIL_TYPE', $userInfo, 'html');
            $email = val('EMAIL', $userInfo);
            $mergeFields = json_encode(['EMAIL_TYPE' => $emailType]);
            $body['operations'][] = [
                'method' => 'PUT',
                'path' => 'lists/'.$id.'/members/'.md5(strtolower($email)),
                'body' => json_encode(['email_address' => $email, 'status_if_new' => 'subscribed']),
                'merge_fields' => $mergeFields
            ];
        }
        $response = $this->callServer('batches', 'POST', $body, true);
        return $response;
    }


    /**
     * Send an array of emails to be subscribed to a mailing list at Mail Chimp.
     *
     * @param $id
     * @param $batch
     * @param bool|true $double_optin
     * @param bool|false $update_existing
     * @param bool|true $replace_interests
     * @return Http\HttpResponse
     * @throws Exception
     */
    public function listUpdateAddress($listID, $email = array(), $double_optin=true, $update_existing=false, $replace_interests=true) {
        $emailID = md5(strtolower(val('EMAIL', $email)));

        $removeBody = ["status" => "unsubscribed"];
        $removedResponse = $this->callServer('lists/'.$listID.'/members/'.$emailID, 'PATCH', $removeBody);
        $removed = $this->toArray($removedResponse);
        if (val('id', $removed) === $emailID && val('status', $removed) === 'unsubscribed') {
            $emailType = val('EMAIL_TYPE', $email, 'html');
            $mergeFields = json_encode(['EMAIL_TYPE' => $emailType]);
            $addBody = [
                'email_address' => val('NEW_EMAIL', $email),
                'status' => 'subscribed',
                'merge_fields' => $mergeFields
            ];
            $addResponse = $this->callServer('lists/'.$listID.'/members', 'POST', $addBody);
        }
    }

    /**
     * Get the information for one specific member of one specific mailing list at Mail Chimp.
     *
     * @param $listID
     * @param $email_address
     * @return Http\HttpResponse
     * @throws Exception
     */
    function listMemberInfo($listID, $email_address) {
        return $this->callServer('lists/'.$listID.'/members/'.md5(strtolower($email_address[0])));
    }

    /**
     * Check to see if the account is valid.
     *
     * @return bool
     * @throws Exception
     */
    function ping() {
        $response = $this->callServer('ping');
        return (val('account_id', json_decode($response))) ? true : false;
    }

    /**
     * JSON decode helper function
     *
     * @param null $json
     * @return mixed
     */
    function toArray($json = null) {
        if ($array = json_decode($json, true)) {
            return $array;
        }
    }
}