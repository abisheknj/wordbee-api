<?php

require_once(plugin_dir_path(__FILE__) .'auth.php');
require_once(plugin_dir_path(__FILE__) .'encryption.php');


// Shortcode callback function

function get_document_list($project_id) {

    $token = get_token();
    // Validate the project ID
    if (empty($project_id) || !is_numeric($project_id)) {
        error_log('project id sent for documents list :'  . $project_id);
        return 'Invalid project ID';
    }

    // API URL
    $api_url = 'https://td.eu.wordbee-translator.com/api/resources/documents/list';

    // Prepare the data to be sent in the body of the request
    $data = array(
        'scope' => array(
            'type' => 'Project',
            'projectid' => intval($project_id)
        )
    );

    // Make the API call with token in header using wp_remote_post()
    $response = wp_remote_post($api_url, array(
        'body' => json_encode($data),
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' => $token, // Add token to Authorization header
            'X-Auth-AccountId' => 'transladiem'
        )
    ));

    // Check if the response is an error
    if (is_wp_error($response)) {
        return 'Request Error: ' . $response->get_error_message();
    }

    // Decode the response
    $decoded_response = json_decode(wp_remote_retrieve_body($response), true);
    error_log("got data");

    // Check if the response is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'JSON Decode Error: ' . json_last_error_msg();
    }
    error_log('Decoded API Response: ' . print_r($decoded_response, true));
    return $decoded_response;



}
function get_total_word_count($project_id) {
    // Get the document list
    $document_list = get_document_list($project_id);

    // Validate the document list
    if (!is_array($document_list) || !isset($document_list['items'])) {
        error_log('Invalid document list');
        return 'Invalid document list';
    }

    // Initialize total word count
    $total_word_count = 0;

    // Iterate through each document and get the word counts
    foreach ($document_list['items'] as $document) {
        $document_id = $document['did'];
        error_log("Processing document ID: $document_id");
        
        $word_count = get_word_count_for_document($project_id, $document_id);

        // Validate the word counts response
        if (is_numeric($word_count)) {
            error_log("Document ID: $document_id, Words: $word_count");
            $total_word_count += $word_count;
        } else {
            error_log("Failed to retrieve word counts for document ID: $document_id");
        }
    }

    error_log("Total word count for project ID $project_id: $total_word_count");
    return $total_word_count;
}

function get_word_count_for_document($project_id, $document_id) {
    // Validate parameters
    if (empty($project_id) || !is_numeric($project_id) || empty($document_id) || !is_numeric($document_id)) {
        error_log('Invalid project ID or document ID');
        return 'Invalid project ID or document ID';
    }

    $token = get_token();
    
    $api_url = "https://td.eu.wordbee-translator.com/api/projects/{$project_id}/wordcounts/{$document_id}";

    // Make the API call with token in header using wp_remote_get()
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' => $token,
            'X-Auth-AccountId' => 'transladiem'
        )
    ));

    // Check if the response is an error
    if (is_wp_error($response)) {
        error_log('Request Error: ' . $response->get_error_message());
        return 'Request Error: ' . $response->get_error_message();
    }

    // Log the raw response body for debugging
    $response_body = wp_remote_retrieve_body($response);
    error_log('Raw API Response: ' . $response_body);

    // Decode the response
    $decoded_response = json_decode($response_body, true);

    // Check if the response is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON Decode Error: ' . json_last_error_msg());
        return 'JSON Decode Error: ' . json_last_error_msg();
    }

    // Log the decoded response for debugging
    error_log('Decoded API Response: ' . print_r($decoded_response, true));

    // Find the entry with the greatest wcid
    $max_wcid_entry = null;
    foreach ($decoded_response as $entry) {
        if ($max_wcid_entry === null || $entry['wcid'] > $max_wcid_entry['wcid']) {
            $max_wcid_entry = $entry;
        }
    }

    if ($max_wcid_entry && isset($max_wcid_entry['words'])) {
        error_log("Document ID $document_id, Max WCID: {$max_wcid_entry['wcid']}, Words: {$max_wcid_entry['words']}");
        return $max_wcid_entry['words'];
    }

    return 0;
}



?>
