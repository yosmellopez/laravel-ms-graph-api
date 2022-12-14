<?php

namespace Ylplabs\LaravelMsGraphApi;

/**
 * msgraphapi api documentation can be found at https://developer.msgraphapi.com/reference
 **/

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Ylplabs\LaravelMsGraphApi\Events\Microsoft365APISignInEvent;
use Ylplabs\LaravelMsGraphApi\Facades\MsGraphAPI as Api;
use Ylplabs\LaravelMsGraphApi\Models\MsGraphTokenAPI;
use Ylplabs\LaravelMsGraphApi\Resources\Emails;

class MsGraphAPI
{

    public function emails()
    {
        return new Emails();
    }

    /**
     * Set the base url that all API requests use
     * @var string
     */
    protected static $baseUrl = 'https://graph.microsoft.com/v1.0/';

    /**
     * Make a connection or return a token where it's valid
     * @return mixed
     */
    public function connect($id = null)
    {
        //if no id passed get logged in user
        if ($id == null) {
            $id = auth()->id();
        }

        //set up the provides loaded values from the config
        $provider = new GenericProvider([
            'clientId' => config('msgraphapi.clientId'),
            'clientSecret' => config('msgraphapi.clientSecret'),
            'redirectUri' => config('msgraphapi.redirectUri'),
            'urlAuthorize' => config('msgraphapi.urlAuthorize'),
            'urlAccessToken' => config('msgraphapi.urlAccessToken'),
            'urlResourceOwnerDetails' => config('msgraphapi.urlResourceOwnerDetails'),
            'scopes' => config('msgraphapi.scopes')
        ]);

        //when no code param redirect to Microsoft
        if (!request()->has('code')) {

            return redirect($provider->getAuthorizationUrl());

        } elseif (request()->has('code')) {


            // With the authorization code, we can retrieve access tokens and other data.
            try {
                // Get an access token using the authorization code grant
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => request('code')
                ]);
                $result = $this->storeToken($accessToken->getToken(), $accessToken->getRefreshToken(), $accessToken->getExpires(), $id);

                //get user details
                $me = Api::get('me', null, [], $id);

                $event = [
                    'token_id' => $result->id,
                    'info' => $me
                ];

                //fire event
                event(new Microsoft365APISignInEvent($event));

                if ($id == null) {
                    $id = config('msgraphapi.defaultUserId');
                }

                //find record and add email - not required but useful none the less
                $t = MsGraphTokenAPI::findOrFail($result->id);
                $t->email = isset($me['mail']) ? $me['mail'] : $me['userPrincipalName'];
                $t->user_id = $id;
                $t->save();

                return redirect(config('msgraphapi.msgraphapiLandingUri'));

            } catch (IdentityProviderException $e) {
                die('error:' . $e->getMessage());
            }

        }
    }

    /**
     * @return object
     */
    public function isConnected()
    {
        return $this->getTokenData() == null ? false : true;
    }

    /**
     * logout of application and Microsoft 365, redirects back to the provided path
     * @param string $redirectPath
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect($redirectPath = '/', $logout = true, $id = null)
    {
        $id = ($id) ? $id : auth()->id();
        $token = MsGraphTokenAPI::where('user_id', $id)->first();
        if ($token != null) {
            $token->delete();
        }

        //if logged in and $logout is set to true then logout
        if ($logout == true && auth()->check()) {
            auth()->logout();
        }

        return redirect()->away('https://login.microsoftonline.com/common/oauth2/v2.0/logout?post_logout_redirect_uri=' . url($redirectPath));
    }

    /**
     * Return authenticated access token or request new token when expired
     * @param  $id integer - id of the user
     * @param  $returnNullNoAccessToken null when set to true return null
     * @return return string access token
     */
    public function getAccessToken($id = null, $returnNullNoAccessToken = null)
    {
        //use id if passed otherwise use logged in user
        $id = ($id) ? $id : auth()->id();
        $token = MsGraphTokenAPI::where('user_id', $id)->first();

        // Check if tokens exist otherwise run the oauth request
        if (!isset($token->access_token)) {

            //don't redirect simply return null when no token found with this option
            if ($returnNullNoAccessToken == true) {
                return null;
            }

            return redirect(config('msgraphapi.redirectUri'));
        }

        // Check if token is expired
        // Get current time + 5 minutes (to allow for time differences)
        $now = time() + 300;
        if ($token->expires <= $now) {
            // Token is expired (or very close to it) so let's refresh

            // Initialize the OAuth client
            $oauthClient = new GenericProvider([
                'clientId' => config('msgraphapi.clientId'),
                'clientSecret' => config('msgraphapi.clientSecret'),
                'redirectUri' => config('msgraphapi.redirectUri'),
                'urlAuthorize' => config('msgraphapi.urlAuthorize'),
                'urlAccessToken' => config('msgraphapi.urlAccessToken'),
                'urlResourceOwnerDetails' => config('msgraphapi.urlResourceOwnerDetails'),
                'scopes' => config('msgraphapi.scopes')
            ]);

            $newToken = $oauthClient->getAccessToken('refresh_token', ['refresh_token' => $token->refresh_token]);

            // Store the new values
            $this->storeToken($newToken->getToken(), $newToken->getRefreshToken(), $newToken->getExpires(), $id);

            return $newToken->getToken();

        } else {
            // Token is still valid, just return it
            return $token->access_token;
        }
    }

    /**
     * @param  $id - integar id of user
     * @return object
     */
    public function getTokenData($id = null)
    {
        $id = ($id) ? $id : auth()->id();
        return MsGraphTokenAPI::where('user_id', $id)->first();
    }

    /**
     * Store token
     * @param  $access_token string
     * @param  $refresh_token string
     * @param  $expires string
     * @param  $id integer
     * @return object
     */
    protected function storeToken($access_token, $refresh_token, $expires, $id)
    {
        //cretate a new record or if the user id exists update record
        return MsGraphTokenAPI::updateOrCreate(['user_id' => $id], [
            'user_id' => $id,
            'access_token' => $access_token,
            'expires' => $expires,
            'refresh_token' => $refresh_token
        ]);
    }

    /**
     * __call catches all requests when no found method is requested
     * @param  $function - the verb to execute
     * @param  $args - array of arguments
     * @return json request
     * @throws Exception
     */
    public function __call($function, $args)
    {
        $options = ['get', 'post', 'patch', 'put', 'delete'];
        $path = (isset($args[0])) ? $args[0] : null;
        $data = (isset($args[1])) ? $args[1] : null;
        $headers = (isset($args[2])) ? $args[2] : null;
        $id = (isset($args[3])) ? $args[3] : auth()->id();

        if (in_array($function, $options)) {
            return self::guzzle($function, $path, $data, $headers, $id);
        } else {
            //request verb is not in the $options array
            throw new Exception($function . ' is not a valid HTTP Verb');
        }
    }

    /**
     * run guzzle to process requested url
     * @param  $type string
     * @param  $request string
     * @param  $data array
     * @param array $headers
     * @param  $id integer
     * @return json object
     */
    protected function guzzle($type, $request, $data = [], $headers = [], $id = null)
    {
        try {
            $client = new Client;

            if ($id == null) {
                $id = config('msgraphapi.defaultUserId');
            }

            $mainHeaders = [
                'Authorization' => 'Bearer ' . $this->getAccessToken($id),
                'content-type' => 'application/json',
                'Prefer' => config('msgraphapi.preferTimezone')
            ];

            if (is_array($headers)) {
                $headers = array_merge($mainHeaders, $headers);
            } else {
                $headers = $mainHeaders;
            }

            $response = $client->$type(self::$baseUrl . $request, [
                'headers' => $headers,
                'body' => json_encode($data),
            ]);

            if ($response == null) {
                return null;
            }

            $statusCode = $response->getStatusCode();
            if ($statusCode > 200 && $statusCode < 205) {
                return json_decode("{}");
            }
            if ($response->getBody() == null) {
                return json_decode("{}");
            }
            return json_decode($response->getBody()->getContents(), true);

        } catch (ClientException $e) {
            throw new Exception($e->getResponse()->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
