<?php
/**
 * Plugin Name: Async API Operations with Polling and Logging
 * Description: Perform asynchronous API operations with polling for completion and log the response.
 * Version: 1.0
 */

// Include auth.php
require_once(plugin_dir_path(__FILE__) .'auth.php');
require_once(plugin_dir_path(__FILE__) .'encryption.php');


// Shortcode callback function

function get_token(){
      // Get the token
      $encrypeted_token =  get_cached_api_token();
      $token = decrypt_token($encrypeted_token);
      error_log('received token' . $token);
      return $token;
}
function get_text_edit($project_id ,  $date_filter) {
    error_log('1st function starts');
    error_log($date_filter['dateFrom']);
    error_log($date_filter['dateTo']);

    
    
    $token = get_token();
    $filetoken = make_api_call($token , $project_id , $date_filter);

    if ($filetoken) {
        // Log the API response
        error_log('Async API Operation Response with file token : ' . $filetoken);
        

        $second_response = call_second_api($filetoken , $token);

        if ($second_response) {
            // Log the second API response
            error_log('Second API Call Response: ' . json_encode($second_response));
            return $second_response;
        } else {
            error_log('Failed to perform second API call.');
            return 'No Data Available';
        }
    } else {
        error_log('Failed to perform async API operation.');
        return 'No Data Available';
    }
}

// Function to make the API call
function make_api_call($token , $project_id , $date_filter) {
    error_log('2 func starts');
    
    $date_from = $date_filter['dateFrom'];
    $date_to = $date_filter['dateTo'];

    if ($token) {
        // API endpoint URL and data
        $url = 'https://td.eu.wordbee-translator.com/api/resources/segments/textedits';
        $data = array(
            'scope' => array('type' => 'Project', 'projectid' => $project_id),
            'dateFrom' => $date_from,
            'dateTo' => $date_to
        );

        // Make the API call with token in header using wp_remote_post()
        $response = wp_remote_post($url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Auth-Token' =>  $token, // Add token to Authorization header
                'X-Auth-AccountId' => 'transladiem'
                )
        ));
        error_log('1 st api call made');

        error_log(var_export($response, true));
        // Check if the request was successful
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            // Decode the JSON response body
            $response_data = json_decode(wp_remote_retrieve_body($response), true);
            error_log('response suceess');
            // Check if operation started successfully
            if (isset($response_data['trm']['requestid'])) {
                // Poll for operation completion
                error_log('polling started');
                $result = poll_operation_completion($response_data['trm']['requestid'], $token);
                return $result;
            } else {
                error_log('Failed to start async API operation.');
                return false; // Operation failed to start
            }
        } else {
            error_log('Failed to perform async API operation. Error: ' . wp_remote_retrieve_response_message($response));
            return false; // API call failed
        }
    } else {
        error_log('Failed to obtain token for async API operation.');
        return false; // Failed to obtain token
    }
}

// Function to poll for operation completion
function poll_operation_completion($request_id, $token) {
    $url = 'https://td.eu.wordbee-translator.com/api/trm/status?requestid=' . $request_id;

    // Poll until the operation is finished
    do {
        // Wait for a brief moment before polling again
        sleep(2);

        // Make the API call to check operation status
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Auth-Token' =>  $token, 
                'X-Auth-AccountId' => 'transladiem'
            )
        ));

        // Check if the request was successful
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            // Decode the JSON response body
            $response_data = json_decode(wp_remote_retrieve_body($response), true);

            // Check if operation status is 'Finished'
            if (isset($response_data['trm']['status']) && $response_data['trm']['status'] === 'Finished') {
                // Return the file token if available
                if (isset($response_data['custom']['filetoken'])) {
                    return $response_data['custom']['filetoken'];
                } else {
                    return false; // File token not found
                }
            }
        } else {
            // Log error message if API call failed
            error_log('Failed to poll async API operation status. Error: ' . wp_remote_retrieve_response_message($response));
            return false; // API call failed
        }
    } while (true);
}

function call_second_api($filetoken, $token) {
    error_log('Calling second API');
    // API endpoint URL with filetoken parameter
    $url = 'https://td.eu.wordbee-translator.com/api/media/get/' . $filetoken;

    // Make the API call with token in header using wp_remote_get()
    $response = wp_remote_get($url, array(
        'headers' => array(
            'X-Auth-Token' =>  $token, 
            'X-Auth-AccountId' => 'transladiem'
        )
    ));

    // Check if the request was successful
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // Return the response body
        return wp_remote_retrieve_body($response);
    } else {
        error_log('Failed to perform second API call. Error: ' . wp_remote_retrieve_response_message($response));
        return false; // API call failed
    }
}
?>