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

// Function to make API call to list endpoint
function make_list_api_call() {
    // Get the cached token
    $encrypted_token = get_cached_api_token();
    $token = decrypt_token($encrypted_token);
    error_log( 'encyp token is :' . $encrypted_token);
    error_log( 'token is :' . $token);

    if ( ! $token ) {
        // Failed to obtain or cache token
        error_log( 'Failed to obtain or cache API token.' );
        return;
    }

    $reqbody = json_encode( array(
        'query' => '{status} = 1 AND {reference}.StartsWith("b")'
    ) );

    // Headers for the API call
    $headers = array(
        'X-Auth-Token'    => $token,
        'X-Auth-AccountId' => 'transladiem', // Replace with your account id
        'Content-Type'    => 'application/json'
    );

    // Make a request to the list endpoint with the obtained token and accountid headers
    $response = wp_remote_post( LIST_ENDPOINT, array(
        'headers' => $headers,
        'body'    => $reqbody
    ) );

    if ( is_wp_error( $response ) ) {
        error_log( 'List endpoint call failed: ' . $response->get_error_message() );
        return;
    } else {
        $body = wp_remote_retrieve_body( $response );
        error_log( 'List endpoint response: ' . $body );
        return $body;
    }
}


function display_api_data_as_cards() {
    error_log( 'display function is working');
    // Include the file containing the make_list_api_call() function

    // Call the make_list_api_call() function to fetch the data
    $data = make_list_api_call();

    // Initialize output variable
    $output = '';

    error_log( 'Data came : ' . $data);
    $data_array = json_decode($data, true);

    
    if ($data) {
        // Start building the card layout
        $output .= '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';

        // Iterate over each row and add it as a card
        foreach ($data_array['rows'] as $row) {
            $output .= '<div style="background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width: calc(33.33% - 20px);">';
            $output .= '<div style="padding: 20px;">';
            $output .= '<h5 style="font-size: 18px; margin-bottom: 10px;">' . $row['reference'] . '</h5>';
            $output .= '<p style="font-size: 14px; color: #666; margin-bottom: 8px;">Status: ' . $row['statust'] . '</p>';
            $output .= '<p style="font-size: 14px; color: #666; margin-bottom: 8px;">Task: ' . $row['taskt'] . '</p>';
            $output .= '</div>'; // Close card-body
            $output .= '</div>'; // Close card
        }

        // Close the card container
        $output .= '</div>';
    } else {
        // No data available
        $output = 'No data available.';
    }

    
    return $output;
}







add_shortcode( 'wordbee_data', 'display_api_data_as_cards' );