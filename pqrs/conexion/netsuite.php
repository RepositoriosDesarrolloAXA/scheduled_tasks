<?php

require_once 'Oauth256.php'; // autenticacion con netsuite

function conexion_netsuite($script, $id, $start, $end) {

    if($id == null){
        $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=$script&deploy=1&start=$start&end=$end";
    } else {
        $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=$script&deploy=1&idbusqueda=$id";
    }
    
    $ejecutar = ejecutar($url);
    return $ejecutar;

}

function ejecutar($url) {

    $ckey = "ae0539ba95befa834d22e4671895f8989651526f07f211c453d90e277cf85a1e"; //Consumer Key
    $csecret = "8d3afbf8f655498c21510320e993fe9a999439306db8f7014387a15657116e1e"; //Consumer Secret
    $tkey = "8cefcbdb96c7c3da89fde32afdd5b402213f7d7c829d8fa87da1b1933f280e20"; //Token ID
    $tsecret = "8a84cc4b0bc414b6db31461f90100323d605c8401bb68c7516ca8ab542ceb8d3"; //Token Secret

    $consumer = new OAuthConsumer($ckey, $csecret);
    $token = new OAuthToken($tkey, $tsecret);
    $sig = new OAuthSignatureMethod_HMAC_SHA256(); //Signature

    $params = array(
        'oauth_nonce' => generateRandomString(),
        'oauth_timestamp' => idate('U'),
        'oauth_version' => '1.0',
        'oauth_token' => $tkey,
        'oauth_consumer_key' => $ckey,
        'oauth_signature_method' => $sig->get_name(),
    );

    $req = new OAuthRequest('GET', $url, $params);
    $req->set_parameter('oauth_signature', $req->build_signature($sig, $consumer, $token));
    $req->set_parameter('realm', "4572765");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        $req->to_header().',realm="4572765',
        'Content-Type: application/json',
        'Accept-Language: en',
        'Accept-Language: es'
    ]);

    $respuesta = curl_exec($ch);
    curl_close($ch);

    return $respuesta;

}