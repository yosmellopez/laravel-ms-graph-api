<?php

return [

    /*
    * the clientId is set from the Microsoft portal to identify the application
    * https://apps.dev.microsoft.com
    */
    'clientId' => env('MSGRAPHAPI_CLIENT_ID'),

    /*
    * set the application secret
    */

    'clientSecret' => env('MSGRAPHAPI_SECRET_ID'),

    /*
    * Set the url to trigger the oauth process this url should call return MsGraphAPI::connect();
    */
    'redirectUri' => env('MSGRAPHAPI_OAUTH_URL'),

    /*
    * set the url to be redirected to once the token has been saved
    */

    'msgraphapiLandingUri' => env('MSGRAPHAPI_LANDING_URL'),

    /*
    set the tenant authorize url
    */

    'tenantUrlAuthorize' => env('MSGRAPHAPI_TENANT_AUTHORIZE'),

    /*
    set the tenant token url
    */
    'tenantUrlAccessToken' => env('MSGRAPHAPI_TENANT_TOKEN'),

    /*
    set the authorize url
    */
    'urlAuthorize' => 'https://login.microsoftonline.com/' . env('MSGRAPHAPI_TENANT_ID', 'common') . '/oauth2/v2.0/authorize',

    /*
    set the token url
    */
    'urlAccessToken' => 'https://login.microsoftonline.com/' . env('MSGRAPHAPI_TENANT_ID', 'common') . '/oauth2/v2.0/token',

    /*
    set the scopes to be used, Microsoft Graph API will accept up to 20 scopes
    */

    'scopes' => 'offline_access openid calendars.readwrite contacts.readwrite files.readwrite mail.readwrite mail.send tasks.readwrite mailboxsettings.readwrite user.readwrite',

    /*
    The default timezone is set to Europe/London this option allows you to set your prefered timetime
    */
    'preferTimezone' => env('MSGRAPHAPI_PREFER_TIMEZONE', 'outlook.timezone="Europe/London"'),
];
