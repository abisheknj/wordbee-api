<?php

require_once 'encryption.php';
/**
 * Plugin Name: Wordbee API Token Display
 * Description: Retrieves and displays Wordbee API token.
 * Version: 1.0
 */

// API Key - Replace 'YOUR_API_KEY' with your actual API key
define( 'API_KEY', 'YmlOcUkyb3lxZ2V0ejFob01wdmJ4SVVHZURWRjdYVlpFVHJCdTFWSFVzNTZvdlhaY2MzZVVkZkpPbXUyZkNiWDo6EUN3M7Sq0SshTUFuyc7v1g==' );


define('ACCOUNT_ID' , 'transladiem');

// Authentication endpoint URL
define( 'AUTH_ENDPOINT', 'https://td.eu.wordbee-translator.com/api/auth/token' );
// List endpoint URL
define( 'LIST_ENDPOINT', 'https://td.eu.wordbee-translator.com/api/jobs/list' );



// Function to obtain API token
function get_api_token() {
    $key = decrypt_token(API_KEY);
    $body = json_encode( array(
        'accountid' => ACCOUNT_ID,
        'key'       => $key
    ) );

    // Make a request to the authentication endpoint with API key
    $response = wp_remote_post( AUTH_ENDPOINT, array(
        'body'    => $body,
        'headers' => array( 'Content-Type' => 'application/json' ),
    ) );

    if ( is_wp_error( $response ) ) {
        error_log( 'Authentication call failed: ' . $response->get_error_message() );
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    // if ( ! is_array( $data ) || ! isset( $data['token'] ) ) {
    //     error_log( 'Failed to decode API token from response: ' . $body );
    //     return false;
    // }
    
    error_log( 'sending token' . $data);
    return $data;
}

function get_cached_api_token() {
    $token = get_transient('api_token'); // Check if token is cached
    error_log( 'method starting' . $token );
    $decryptedToken = decrypt_token(API_KEY);
    error_log( 'decry token is :' . $decryptedToken);


    if ( ! $token ) {
        error_log( 'token not there , so generatin' );
        // Token not cached or expired, fetch new token
        $token_response = get_api_token();

        if ( $token_response ) {
           
            $encrypted_token = encrypt_token($token_response);
            // Cache the token for 30 minutes (token expiration time)
            set_transient( 'api_token', $encrypted_token, 29* MINUTE_IN_SECONDS );
            error_log( 'cache done' . $token_response);
            return $encrypted_token;
        }
        
    }
    error_log( 'token is present' . $token );

    return $token;
}