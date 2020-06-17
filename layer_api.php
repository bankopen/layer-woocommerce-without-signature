<?php
/*
 * Plugin Name: Layer Payment Gateway
 * Plugin URI: https://open.money/
 * Description: Open's Layer Payment Gateway integration for WooCommerce
 * Version: 1.0.1
 * Author: Openers
 * Author URI: https://open.money/
*/
Class LayerApi{
	const BASE_URL_SANDBOX = "https://sandbox-icp-api.bankopen.co/api";
    const BASE_URL_UAT = "https://icp-api.bankopen.co/api";

    public function __construct($env,$access_key,$secret_key){

        $this->env = $env;
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
    }

    public function create_payment_token($data){

        try {
            $pay_token_request_data = array(
                'amount'   			=> $data['amount'] ?? NULL,
                'currency' 			=> $data['currency'] ?? NULL,
                'name'     			=> $data['name'] ?? NULL,
                'email_id' 			=> $data['email_id'] ?? NULL,
                'contact_number' 	=> $data['contact_number'] ?? NULL,
                'mtx'    			=> $data['mtx'] ?? NULL,
                'udf'    			=> $data['udf'] ?? NULL,
            );

            $pay_token_data = $this->http_post($pay_token_request_data,"payment_token");

            return $pay_token_data;
        } catch (Exception $e){			
            return [
                'error' => $e->getMessage()
            ];

        } catch (Throwable $e){
			
			return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function get_payment_token($payment_token_id){

        if(empty($payment_token_id)){

            throw new Exception("payment_token_id cannot be empty");
        }

        try {

            return $this->http_get("payment_token/".$payment_token_id);

        } catch (Exception $e){

            return [
                'error' => $e->getMessage()
            ];

        } catch (Throwable $e){

            return [
                'error' => $e->getMessage()
            ];
        }

    }

    public function get_payment_details($payment_id){

        if(empty($payment_id)){

            throw new Exception("payment_id cannot be empty");
        }

        try {

            return $this->http_get("payment/".$payment_id);

        } catch (Exception $e){

            return [
                'error' => $e->getMessage()
            ];

        } catch (Throwable $e){

            return [
                'error' => $e->getMessage()
            ];
        }

    }


    function build_auth($body,$method){

        $time_stamp = time();
        unset($body['udf']);

        if(empty($body)){

            $token_string = $time_stamp.strtoupper($method);

        } else {

            $token_string = $time_stamp.strtoupper($method).json_encode($body);

        }

        $token = hash_hmac("sha256",$token_string,$this->secret_key);

        return [
            'Authorization'  =>  'Bearer '.$this->access_key.':'.$token,
            'X-O-Timestamp'  =>  $time_stamp,
            'Access-Key'    => $this->access_key
        ];

    }


    function http_post($data,$route){

        foreach (@$data as $key=>$value){

            if(empty($data[$key])){

                unset($data[$key]);
            }
        }

        if($this->env == 'live'){

            $url = self::BASE_URL_UAT."/".$route;

        } else {

            $url = self::BASE_URL_SANDBOX."/".$route;
        }


        $header = $this->build_auth($data,"post");


        $response = wp_remote_post($url,[
            'body'  => $data,
            'headers'  => $header,
        ]);

        return $this->handle_http_response($response);
    }

    function http_get($route){

        if($this->env == 'live'){

            $url = self::BASE_URL_UAT."/".$route;

        } else {

            $url = self::BASE_URL_SANDBOX."/".$route;
        }


        $header = $this->build_auth($data = [],"get");


        $response = wp_remote_get($url,[
            'headers'  => $header,
        ]);

        return $this->handle_http_response($response);

    }


    function handle_http_response($response){

        $response = (array)$response;


        try {

            $to_return = NULL;

            if(
                isset($response['response'])
                && !empty($response['response'])
                && isset($response['response']['code'])
                && ($response['response']['code'] < 200 || $response['response']['code'] > 210)
            ){

                if($response['response']['code'] == 422){

                    $error = "";

                    $error_body = json_decode($response['body'],true);


                    foreach ($error_body['error'] as $err){

                        if(isset($err[0])){

                            $error = $err[0];
                        }
                    }


                    return [
                        "error" => $error,
                        "error_data" => $response,
                    ];

                }

                return [
                    "error" => "api request failed",
                    "error_data" => $response,
                ];

            }
            if(isset($response['body']) && !empty($response['body'])){

                $to_return =  json_decode($response['body'],true);


                if(!empty($to_return)){

                    return $to_return;

                } else {

                    return [
                        "error" => "bad response received",
                        "error_data" => $response,
                    ];

                }

            }

        } catch (Throwable $exception){

            return [
                "error" => "and error occurred E68",
                "error_data" => $exception->getMessage(),
            ];

        } catch (Exception $exception){

            return [
                "error" => "unknown error occurred failed E32",
                "error_data" => $exception->getMessage(),
            ];
        }
    }


}
