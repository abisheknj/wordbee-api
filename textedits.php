<?php


// Include auth.php
require_once(plugin_dir_path(__FILE__) .'auth.php');
require_once(plugin_dir_path(__FILE__) .'encryption.php');

// Initialize the API call counter
function initialize_api_call_counter() {
    if (get_option('api_call_counter') === false) {
        update_option('api_call_counter', 0);
    }
}
add_action('init', 'initialize_api_call_counter');

// Increment the API call counter
function increment_api_call_counter() {
    $counter = get_option('api_call_counter');
    $counter++;
    update_option('api_call_counter', $counter);
}

// Function to get the current API call counter
function get_api_call_counter() {
    return get_option('api_call_counter');
}

// Shortcode callback function
function get_token() {
    // Get the token
    $encrypted_token = get_cached_api_token();
    $token = decrypt_token($encrypted_token);
    error_log('received token' . $token);
    return $token;
}

function get_text_edit($project_id, $source_language, $target_language, $date_filter) {
    error_log('1st function starts');
    error_log($source_language);
    error_log($target_language);
    error_log($date_filter['dateFrom']);
    error_log($date_filter['dateTo']);
    
    $token = get_token();
    $filetoken = make_api_call($token, $project_id, $date_filter);

    if ($filetoken) {
        // Log the API response
        error_log('Async API Operation Response with file token: ' . $filetoken);
        
        $second_response = call_second_api($filetoken, $token);

        if ($second_response) {
            // Log the second API response
            // error_log('Second API Call Response: ' . json_encode($second_response));
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
function make_api_call($token, $project_id, $date_filter) {
    error_log('2nd function starts');
    
    $date_from = $date_filter['dateFrom'];
    $date_to = $date_filter['dateTo'];

    if ($token) {
        // API endpoint URL and data
        $url = 'https://td.eu.wordbee-translator.com/api/resources/segments/textedits';
        $data = array(
            'scope' => array('type' => 'Project', 'projectid' => $project_id),
            'groupby' => 'ByUserAndLocale',
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
        error_log('1st API call made');

        error_log(var_export($response, true));
        // Check if the request was successful
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            // Increment the counter on successful API call
            increment_api_call_counter();

            // Decode the JSON response body
            $response_data = json_decode(wp_remote_retrieve_body($response), true);
            error_log('response success');
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
            error_log('Failed to perform async API operation');
            // error_log('Failed to perform async API operation. Error: ' . wp_remote_retrieve_response_message($response));
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
        sleep(1);

        // Make the API call to check operation status
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Auth-Token' =>  $token, 
                'X-Auth-AccountId' => 'transladiem'
            )
        ));

        // Check if the request was successful
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            // Increment the counter on successful API call
            increment_api_call_counter();

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
            // error_log('Failed to poll async API operation status. Error: ' . wp_remote_retrieve_response_message($response));
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
        // Increment the counter on successful API call
        increment_api_call_counter();

        // Return the response body
        return wp_remote_retrieve_body($response);
    } else {
        error_log('Failed to perform second API call.');
        // error_log('Failed to perform second API call. Error: ' . wp_remote_retrieve_response_message($response));
        return false; // API call failed
    }
}

// Function to get and display the API call counter
function get_counter() {
    $counter = get_api_call_counter();
   return $counter;
}

?>
