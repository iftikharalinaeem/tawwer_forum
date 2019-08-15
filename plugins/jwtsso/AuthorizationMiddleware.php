<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\JWTSSO;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Gdn_Session;
use UserModel;

/**
 * A middleware that will look for an Oracle bearer token.
 */
class AuthorizationMiddleware {

    /** @var string  */
    public const AUTHORIZATION_REGEX = '`^Bearer\s+([a-z0-9\-_]+?\.[a-z0-9\-_]+?\.(?:[a-z0-9\-_]+))$`i';

    /** @var string  */
    public const PROVIDER_KEY = 'JWTSSODefault';

    /** @var Gdn_Session  */
    private $session;

    /** @var UserModel  */
    private $userModel;

    /** @var \Gdn_AuthenticationProviderModel */
    private $authenticationProviderModel;

    /**
     * AuthorizationMiddleware constructor.
     *
     * @param Gdn_Session $session For starting user sessions.
     * @param UserModel $userModel For checking SSO.
     * @param \Gdn_AuthenticationProviderModel $authenticationProviderModel
     */
    public function __construct(
        Gdn_Session $session,
        UserModel $userModel,
        \Gdn_AuthenticationProviderModel $authenticationProviderModel
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
     * @throws ServerException Throws an exception when Oracle isn't configured.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $authorization = $request->getHeader('Authorization');

        if (!empty($authorization) && preg_match(static::AUTHORIZATION_REGEX, $authorization, $m)) {
            $jwt = $m[1];

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
     * @throws ServerException The Oracle SSO provider has not been configured.
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

        $userID = $this->sso((array)$payload, $provider['KeyMap']); //TODO: spread keymap and send as string params
        return $userID;
    }

    /**
     * Connect the user's JWT payload with a user.
     *
     * @param array $payload The payload to connect.
     * @param array $keymap
     * @return int Returns the User ID of the new or existing user.
     * @throws ClientException Throws an exception if the user cannot be connected for some reason.
     * @throws \Garden\Schema\ValidationException Throws an exception if the payload doesn't contain the required fields.
     */
    private function sso(array $payload, string $uniqueID, string $email, string $name, ?string $photo = null, ?string $fullName = null): int {
        $schemaFields = [
            "{$uniqueID}:s",
            "{$email}:s",
            "{$name}:s",
        ];

        if ($photo) {
            $schemaFields[] = "{$photo}:s?";
        }

        if ($fullName) {
            $schemaFields[] = "{$fullName}:s?";
        }

        $sch = \Garden\Schema\Schema::parse($schemaFields);

        $valid = $sch->validate($payload);

        // Check for an existing user first so that we don't sync bearer token data on existing users.
        $auth = $this->userModel->getAuthentication($valid['sub'], static::PROVIDER_KEY);
        if (empty($auth)) {
            $userData = [
                'Name' => $valid[$keymap['Name']], //TODO: fix
                'Email' => $valid[$keymap['Email']] //TODO: fix
            ];

            if ($payload[$keymap['Photo']]) {
                $userData['Photo'] = $payload[$keymap['Photo']];
            }

            $userID = $this->userModel->connect($valid[$keymap['UniqueID']], static::PROVIDER_KEY, $userData);
        } else {
            $userID = $auth['UserID'];
        }

        if (!$userID) {
            throw new ClientException($this->userModel->Validation->resultsText() ?: 'There was an error registering the user.');
        }

        return $userID;
    }
}
