<?php

require_once 'conexion.php'; // base de datos
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

    $cabeceras = array();
    $detalles = array();

    //tiempo
    date_default_timezone_set('America/Bogota');
    $tiempo_inicio = microtime_float();
    $fecha_LOG = date('Y-m-d');
    $fecha_inicio_log = Date('Y-m-d\TH:i:s');

    while($control == 0 ){ 

        if ($inicio== 0){
            $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=2088&deploy=1&start=$start&end=$end";
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

            for($i=0; $i<$longitud; $i++){ 
                $y=0;
                
                $internal_id                = $data[$i]["values"]["GROUP(CUSTITEM_AXA_VENDORMANUFACTURER_PJCL.internalid)"][$y]["text"];
                $nit                        = $data[$i]["values"]["GROUP(custitem_axa_vendormanufacturer_pjcl)"][$y]["text"];
                $laboratorio                = $data[$i]["values"]["GROUP(manufacturer)"];
                $linea                      = $data[$i]["values"]["GROUP(custitem_nso_axa_field_item_linea)"];

                $cabeceras[] = $documento;

                //guardando cabeceras
                if(!empty($internal_id)){

                    $sql_validar_cabecera = "SELECT id FROM laboratorios WHERE internal_id = '".$internal_id."'";
                    $result1 = pg_query( $dbconn, $sql_validar_cabecera );
                    if (pg_num_rows($result1) > 0){
                        
                        //actualiza
                        $sql_actualizar_cabecera = "UPDATE laboratorios SET 
                            internal_id = '$internal_id', 
                            nit = '$nit', 
                            nombre = '$laboratorio',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE internal_id = '".$internal_id."'";
                        pg_query( $dbconn, $sql_actualizar_cabecera );

                    } else {

                        //crea
                        $sql_crear_cabecera = "INSERT INTO laboratorios (internal_id, nit, nombre, created_at) 
                        VALUES($internal_id, '$nit', '$laboratorio', CURRENT_TIMESTAMP)";
                        pg_query( $dbconn, $sql_crear_cabecera );

                    }

                    //guardando detalles
                    $sql_validar_detalle = "SELECT id FROM lineas WHERE laboratorio_id = '".$internal_id."' 
                    AND nombre = '".$linea."'";
                    $result2 = pg_query( $dbconn, $sql_validar_detalle );
                    if ($result2) {
                        if (pg_num_rows($result2) > 0){
                        
                            //actualiza
                            $sql_actualizar_detalle = "UPDATE lineas SET 
                                laboratorio_id = '$internal_id', 
                                nombre = '$linea', 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE laboratorio_id = '".$internal_id."' AND nombre = '".$linea."'";
                            pg_query( $dbconn, $sql_actualizar_detalle );
        
                            $valores_actualizados++;
        
                        } else {
        
                            //crea
                            $sql_crear_detalle = "INSERT INTO lineas (laboratorio_id, nombre, created_at) 
                            VALUES('$internal_id', '$linea', CURRENT_TIMESTAMP)";
                            pg_query( $dbconn, $sql_crear_detalle );
        
                            $valores_creados++;
        
                        }    
                    }

                }
                
            }; // for interno que recorre el arreglo del resultados 

        }   // fin del if inicio
        
        if ($longitud <= 1){
            $control = 1;
            // break;
        }
    } // fin del while control

    $fecha_fin_log = Date('Y-m-d\TH:i:s');
    
    $tiempo_fin = microtime_float();
    $tiempo_a = $tiempo_fin - $tiempo_inicio;
    $tiempo = $tiempo_a / 60;

    print("Finalizado\n");

    // print(count(array_unique($cabeceras)));
    // print(json_encode(array_unique($cabeceras)));

    /*crear log*/
    $sql_log = "INSERT INTO log (proceso, tabla, cant_registro, cant_insertados, 
        cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo, created_at) VALUES 
        ('REGISTRO', 'Laboratorios / Lineas', $cantidad_registros, $valores_creados, $valores_actualizados, 
        '$fecha_LOG', '$fecha_inicio_log', '$fecha_fin_log', '$tiempo', CURRENT_TIMESTAMP)";
    $result1_creacion_log = pg_query( $dbconn, $sql_log );
    $errorinsrt1 =  pg_last_error($dbconn);
    if (!isset($errorinsrt1)){
        var_dump($errorinsrt1);
        exit;
    }

?> 