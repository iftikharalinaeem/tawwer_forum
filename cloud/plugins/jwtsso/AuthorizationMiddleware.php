<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;

/**
 * A middleware that will look for a JWT authentication bearer token.
 */
class AuthorizationMiddleware {

    /** @var string  */
    public const AUTHORIZATION_REGEX = '`^Bearer\s+(?<jwt>[a-z0-9\-_]+?\.[a-z0-9\-_]+?\.[a-z0-9\-_]+)$`i';

    /** @var string  */
    public const PROVIDER_KEY = 'JWTSSODefault';

    /** @var Gdn_Session  */
    private $session;

    /** @var UserModel  */
    private $userModel;

    /** @var Gdn_AuthenticationProviderModel */
    private $authenticationProviderModel;

    /**
     * AuthorizationMiddleware constructor.
     *
     * @param Gdn_Session $session For starting user sessions.
     * @param UserModel $userModel For checking SSO.
     * @param Gdn_AuthenticationProviderModel $authenticationProviderModel
     */
    public function __construct(
        Gdn_Session $session,
        UserModel $userModel,
        Gdn_AuthenticationProviderModel $authenticationProviderModel
    ) {
        $this->session = $session;
        $this->userModel = $userModel;
        $this->authenticationProviderModel = $authenticationProviderModel;
    }

    /**
     * Check for a bearer token to authenticate the current user against the API.
     *
     * @param RequestInterface $request The request being processed.
     * @param callable $next The next middleware.
     * @return mixed Returns the response mostly unchanged accept for some added debug headers.
     * @throws ServerException When the JWT SSO provider has not been configured.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $authorization = $request->getHeader('Authorization');

        if (!empty($authorization) && preg_match(static::AUTHORIZATION_REGEX, $authorization, $authParts)) {
            $jwt = $authParts["jwt"];

            try {
                $userID = $this->authenticateJWT($jwt);

                if ($userID > 0) {
                    $this->session->start($userID, false, false);
                    $this->session->validateTransientKey(true);
                }

                $response = $next($request);
            } catch (SignatureInvalidException | ExpiredException | BeforeValidException $ex) {
                throw new ClientException($ex->getMessage(), 401);
            } catch (\UnexpectedValueException $ex) {
                throw new ClientException($ex->getMessage(), 400);
            }
        } else {
            // No bearer token so just process the request as normal.
            $response = $next($request);
        }

        return $response;
    }

    /**
     * Authenticate JWT
     *
     * @param string $jwt
     * @return int
     * @throws ServerException When the JWT SSO provider has not been configured.
     */
    private function authenticateJWT(string $jwt): int {
        $provider = $this->authenticationProviderModel->getProviderByKey(static::PROVIDER_KEY);

        if (empty($provider)) {
            throw new ServerException("The SSO provider has not been configured.", 500);
        } elseif (empty($provider['AssociationSecret'])) {
            throw new ServerException("The SSO provider does not have a JWT key configured.", 500);
        }

        $key = $provider['AssociationSecret'];
        $payload = JWT::decode($jwt, $key, array_keys(JWT::$supported_algs));

        $userID = $this->sso((array)$payload, $provider['KeyMap']);
        return $userID;
    }

    /**
     * Connect the user's JWT payload with a user.
     *
     * @param array $payload
     * @param array $fields
     * @return int
     * @throws ClientException Throws an exception if the user cannot be connected for some reason.
     * @throws Garden\Schema\ValidationException Throws an exception if the payload doesn't contain the required fields.
     */
    private function sso(array $payload, array $fields): int {

        if (empty($fields['UniqueID'])) {
            throw new ServerException("UniqueID field is required.", 500);
        }

        if (empty($fields['Email'])) {
            throw new ServerException("Email field is required.", 500);
        }

        if (empty($fields['Name'])) {
            throw new ServerException("Name field is required.", 500);
        }

        $schemaFields = [
            "{$fields['UniqueID']}:s",
            "{$fields['Email']}:s",
            "{$fields['Name']}:s",
        ];

        if ($fields['Photo']) {
            $schemaFields[] = "{$fields['Photo']}:s?";
        }

        if ($fields['FullName']) {
            $schemaFields[] = "{$fields['FullName']}:s?";
        }

        $sch = Garden\Schema\Schema::parse($schemaFields);

        $valid = $sch->validate($payload);

        // Check for an existing user first so that we don't sync bearer token data on existing users.
        $auth = $this->userModel->getAuthentication($valid['sub'], static::PROVIDER_KEY);
        if (empty($auth)) {
            $userData = [
                'Name' => $payload[$fields['Name']],
                'Email' => $payload[$fields['Email']]
            ];

            if ($fields['Photo']) {
                $userData['Photo'] = $payload[$fields['Photo']];
            }

            $userID = $this->userModel->connect($payload[$fields['UniqueID']], static::PROVIDER_KEY, $userData);
        } else {
            $userID = $auth['UserID'];
        }

        if (!$userID) {
            throw new ClientException($this->userModel->Validation->resultsText() ?: 'There was an error registering the user.');
        }

        return $userID;
    }
}
