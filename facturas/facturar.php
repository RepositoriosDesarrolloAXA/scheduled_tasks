<?php

/* ------------------------------------------------------- Insertar Zonas ------------------------------------------------------- */

require_once 'Oauth256.php'; // autenticacion con netsuite
require_once 'funtions.php'; // funciones

error_reporting(E_ALL ^ E_WARNING);
header('Content-type: text/plain; charset=UTF-8');

//sigue
    $control = 0;
    $inicio=0;
    $start=0;
    $end=1000;
    $valores_creados = 0;
    $valores_actualizados = 0;
    $cantidad_registros = 0;

    $data_info = array();

    //tiempo
    date_default_timezone_set('America/Bogota');
    set_time_limit(50000);
    $tiempo_inicio = microtime_float();
    $fecha_LOG = date('Y-m-d');
    $fecha_inicio_log = Date('Y-m-d\TH:i:s');

    while($control == 0 ){ 

        if ($inicio== 0){
            $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=2049&deploy=1&start=$start&end=$end";
        }

        $ckey = "2f9547576834cc96d21291c12471b25ce007af610831ff4eb95b6a60c305e523"; //Consumer Key
        $csecret = "4c5dcfb635de5c59e14d4645d73b63e43709b0f4386b2d87b56bcd61acff4d32"; //Consumer Secret
        $tkey = "1ac02d58a74e9845c0808bb66247f194e7c7f8fb2cf2c5692535915c35688c26"; //Token ID
        $tsecret = "d24d4672a59dff419b08bb5430c0dce58b50182f9f57cb0f8b497637e0e518a0"; //Token Secret

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

        $header = array(
            'http'=>array(
            'method'=>"GET",
            'header' => $req->to_header() . ',realm="4572765"'. " \r\n" . "Host: 4572765-sb1.suitetalk.api.netsuite.com \r\n" . "Content-Type: application/json"
            )
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            $req->to_header().',realm="4572765',
            'Content-Type: application/json',
            'Accept-Language: en',
            'Accept-Language: es'
        ]);

        // sleep(1); echo "se detiene por dos segundos";
        $respuesta = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($respuesta, true); // array de los registros del restlet
        $longitud = count($data);

        $cantidad_registros += $longitud;
        
        //var_dump($longitud);
        
        // Echo" total de datos ".$reg."\n";
        if ($control == 0){

            $j=1000;
            $v=1;
            $start=$end;
            $end=$end+$j;
            
            $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 
            $array_ids = array();

            if($cantidad_registros >= 25){

                for($i=0; $i<25; $i++){ 
                    $y=0;
                    
                    $id = $data[$i]["id"];
    
                    if(!empty($id)){
                        array_push($array_ids, $id);
                    }
                    
                }; // for interno que recorre el arreglo del resultados 

                $separar = array_chunk($array_ids, 5);
                ejecutar($separar);

            }

        }   // fin del if inicio
        
        if ($longitud <= 1){
            $control = 1;
            // break;
        }
    } // fin del while control

    print("\n\n".$cantidad_registros);

    function ejecutar($data){

        foreach ($data as $key => $value) {

            // Crea dos recursos cURL
            $ch1 = curl_init();
            $ch2 = curl_init();
            $ch3 = curl_init();
            $ch4 = curl_init();
            $ch5 = curl_init();

            // Establece la URL y otras opciones apropiadas 
            curl_setopt($ch1, CURLOPT_URL, "https://4572765.extforms.netsuite.com/app/site/hosting/scriptlet.nl?script=1267&deploy=1&compid=4572765&h=cea4ee7d4ca5a2ccc80e&documento=$value[0]");
            curl_setopt($ch1, CURLOPT_HEADER, 0);

            curl_setopt($ch2, CURLOPT_URL, "https://4572765.extforms.netsuite.com/app/site/hosting/scriptlet.nl?script=1267&deploy=1&compid=4572765&h=cea4ee7d4ca5a2ccc80e&documento=$value[1]");
            curl_setopt($ch2, CURLOPT_HEADER, 0);

            curl_setopt($ch3, CURLOPT_URL, "https://4572765.extforms.netsuite.com/app/site/hosting/scriptlet.nl?script=1267&deploy=1&compid=4572765&h=cea4ee7d4ca5a2ccc80e&documento=$value[2]");
            curl_setopt($ch3, CURLOPT_HEADER, 0);

            curl_setopt($ch4, CURLOPT_URL, "https://4572765.extforms.netsuite.com/app/site/hosting/scriptlet.nl?script=1267&deploy=1&compid=4572765&h=cea4ee7d4ca5a2ccc80e&documento=$value[3]");
            curl_setopt($ch4, CURLOPT_HEADER, 0);

            curl_setopt($ch5, CURLOPT_URL, "https://4572765.extforms.netsuite.com/app/site/hosting/scriptlet.nl?script=1267&deploy=1&compid=4572765&h=cea4ee7d4ca5a2ccc80e&documento=$value[4]");
            curl_setopt($ch5, CURLOPT_HEADER, 0);

            // Crea el multi recurso cURL
            $mh = curl_multi_init();

            // A単ade los dos recursos
            curl_multi_add_handle($mh,$ch1);
            curl_multi_add_handle($mh,$ch2);
            curl_multi_add_handle($mh,$ch3);
            curl_multi_add_handle($mh,$ch4);
            curl_multi_add_handle($mh,$ch5);

            $active=null;
            // Ejecuta los recursos
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }

            // Cierra los recursos
            curl_multi_remove_handle($mh, $ch1);
            curl_multi_remove_handle($mh, $ch2);
            curl_multi_remove_handle($mh, $ch3);
            curl_multi_remove_handle($mh, $ch4);
            curl_multi_remove_handle($mh, $ch5);
            curl_multi_close($mh);

            print('Ejecuto: ' . $value[0] . "\n");
            print('Ejecuto: ' . $value[1] . "\n");
            print('Ejecuto: ' . $value[2] . "\n");
            print('Ejecuto: ' . $value[3] . "\n");
            print('Ejecuto: ' . $value[4] . "\n");

        }

    }

?> 