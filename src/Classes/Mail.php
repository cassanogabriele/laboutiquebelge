<?php 

namespace App\Classe;
use Mailjet\Client;
use Mailjet\Resources;

class Mail{
    private $api_key = "228a86ea807eb35267bb0bd4dae9f011";
    private $api_key_secret = "683cdef9576759ab0669232d3a391888";
    

    // Instanciation de l'objet Mailjet
    public function send($to_email, $to_name, $subject, $content){
        $mj = new Client($this->api_key, $this->api_key_secret,true,['version' => 'v3.1']);
    
        $body = [
            'Messages' => [
            [
                'From' => [
                'Email' => "gabriel_cassano@hotmail.com",
                'Name' => "La Boutique Betge"
                ],
                'To' => [
                [
                    'Email' => $to_email,
                    'Name' => $to_name
                ]
                ],
                'TemplateID' => 4743414,
                'TemplateLanguage' => true,
                'Subject' => $subject,
                'Variables' => [
                    'content' => $content
                ]
              ]
            ]
        ];
    
        // Envoi 
        $ch = curl_init();
        \curl_setopt($ch, CURLOPT_CAINFO, "C:/wamp64/cacert.pem");
    
        $response = $mj->post(Resources::$Email, ['body' => $body]);   
        $response->success();
    }
}