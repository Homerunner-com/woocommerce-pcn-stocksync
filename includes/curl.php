<?php

class PCNStockSync_Curl {

    /**
     * Function to handle all cURL request to PCN
     * @param $request
     * @param $postFields
     * @return bool|string
     */
    public function sendCurl($request, $postFields) {
        // Init CURL
        $curl = curl_init();

        // Generate Basic Auth
        $basicAuth = base64_encode(get_option('pcn_settings_baseauthusername').':'.get_option('pcn_settings_baseauthpassword'));

        // JSON encode postfields
        $jsonPostfields = json_encode($postFields);


        // Set cURL array
        curl_setopt_array($curl, array(
            CURLOPT_URL => get_option('pcn_settings_apiendpoint') . '?rquest=' . $request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonPostfields,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Basic {$basicAuth}"
            )
        ));

        // cURL Response
        $response = curl_exec($curl);

        // Close cURL
        curl_close($curl);

        return $response;

    }

    /**
     * Function to handle cURL for stocklist
     * @param int|string $maxResults
     * @param string|int $filter
     * @return mixed
     */
    public function getStockList($maxResults = "*", $filter = 'all') {
        // Store login credentials in an array for cURL
        $data = array(
            'cid' => get_option('pcn_settings_olsuserid'),
            'olsuser' => get_option('pcn_settings_olsusername'),
            'olspass' => get_option('pcn_settings_olspassword'),
            'filter' => $filter,
            'maxresults' => $maxResults
        );

        // Send cURL request through sendCurl function
        $jsonData = self::sendCurl('stocklist', $data);

        // Return array from json decoded data
        return json_decode($jsonData);
    }
}
