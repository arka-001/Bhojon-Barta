<?php
header('Content-Type: application/json');

$openrouter_api_key = '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';

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (isset($data['message'])) {
    $user_message = $data['message'];

    $api_data = array(
        'model' => 'deepseek/deepseek-r1-zero:free',
        'messages' => array(
            array(
                'role' => 'user',
                'content' => $user_message
            )
        )
    );

    $api_data_json = json_encode($api_data);

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $api_data_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openrouter_api_key
    ));

    $api_response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_message = curl_error($ch);
        curl_close($ch);
        error_log("cURL error: " . $error_message);
        echo json_encode(array('response' => 'Sorry, there was an error connecting to the chatbot service.'));
        exit;
    }

    curl_close($ch);

    $api_response_data = json_decode($api_response, true);

    if (isset($api_response_data['choices'][0]['message']['content'])) {
        $chatbot_response = $api_response_data['choices'][0]['message']['content'];

        // More robust replacement for \boxed{...}
        $chatbot_response = preg_replace('/\\\\boxed\{(.*?)\}/', '<span class="boxed">$1</span>', $chatbot_response); // Handles double backslash
        $chatbot_response = preg_replace('/\\boxed\{(.*?)\}/', '<span class="boxed">$1</span>', $chatbot_response);  // Handles single backslash. Run twice to catch multiple

        echo json_encode(array('response' => $chatbot_response));
    } else {
        error_log("Unexpected API response format: " . $api_response);
        echo json_encode(array('response' => 'Sorry, I could not understand the response from the chatbot service.'));
    }

} else {
    echo json_encode(array('response' => 'Error: No message received.'));
}
?>