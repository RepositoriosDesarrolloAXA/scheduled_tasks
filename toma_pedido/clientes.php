<?php

/* ------------------------------------------------------- Insertar Clientes ------------------------------------------------------- */

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

    $data_info = array();

    //tiempo
    date_default_timezone_set('America/Bogota');
    set_time_limit(50000);
    $tiempo_inicio = microtime_float();
    $fecha_LOG = date('Y-m-d');
    $fecha_inicio_log = Date('Y-m-d\TH:i:s');

    while($control == 0 ){ 

        if ($inicio== 0){
            $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=1893&deploy=1&start=$start&end=$end";
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
                
                $id                         = $data[$i]["id"];
                $nombre                     = str_replace("'", " ", $data[$i]["values"]["formulatext"]);
                $nombre_compania            = str_replace("'", " ", $data[$i]["values"]["formulatext_1"]);
                $documento                  = $data[$i]["values"]["formulatext_3"];
                $correo_electronico         = $data[$i]["values"]["email"];
                $telefono                   = $data[$i]["values"]["phone"];
                $cupo                       = $data[$i]["values"]["formulatext_4"];
                $cupo_feria                 = $data[$i]["values"]["formulatext_5"];
                $categoria                  = $data[$i]["values"]["category"][$y]["text"];
                $mp_credito                 = $data[$i]["values"]["formulatext_7"];
                $mp_contado                 = $data[$i]["values"]["formulatext_6"];
            	$estado                 	= $data[$i]["values"]["isinactive"];
            
            	if($estado == true){
                    $estado = 'Si';
                } else {
                    $estado = 'No';
                }

                //alidar si existe cliente
                $sql1_validar = "SELECT id FROM cliente WHERE id = '".$id."'";
                $result1 = pg_query( $dbconn, $sql1_validar );
                if (pg_num_rows($result1) > 0){

                    //actualiza
                    $sql1 = "UPDATE cliente SET 
                        id = $id, 
                        nombre = '$nombre', 
                        nombre_compania = '$nombre_compania', 
                        documento = '$documento', 
                        correo_electronico = '$correo_electronico', 
                        telefono = '$telefono', 
                        cupo = $cupo, 
                        cupo_feria = $cupo_feria, 
                        categoria = '$categoria', 
                        mp_credito = $mp_credito, 
                        mp_contado = $mp_contado, 
                        isinactive = '$estado', 
                        updated_at = CURRENT_TIMESTAMP
                        WHERE id = '".$id."'";

                    $valores_actualizados++;

                } else {

                    //creación
                    $sql1 = "INSERT INTO cliente 
                    (id, nombre, nombre_compania, documento, correo_electronico, telefono, cupo, cupo_feria, categoria, mp_credito, 
                    mp_contado, isinactive, created_at) 
                    VALUES ($id, '$nombre', '$nombre_compania', '$documento', '$correo_electronico', '$telefono', 
                    $cupo, $cupo_feria, '$categoria', $mp_credito, $mp_contado, '$estado', CURRENT_TIMESTAMP)";

                    $valores_creados++;

                }

                $result1_creacion = pg_query( $dbconn, $sql1 );
                $errorinsrt1 =  pg_last_error($dbconn);
                if (!isset($errorinsrt1)){
                    var_dump($errorinsrt1);
                    exit;
                } else {
                	print($errorinsrt1."\n");
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

    //print($cantidad_registros);

    //print(json_encode($data_info));

    $sql_log = "INSERT INTO log (proceso, tabla, cant_registro, cant_insertados, 
        cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo, created_at) VALUES 
        ('REGISTRO', 'cliente', $cantidad_registros, $valores_creados, $valores_actualizados, 
        '$fecha_LOG', '$fecha_inicio_log', '$fecha_fin_log', '$tiempo', CURRENT_TIMESTAMP)";
    $result1_creacion_log = pg_query( $dbconn, $sql_log );
    $errorinsrt1 =  pg_last_error($dbconn);
    if (!isset($errorinsrt1)){
        var_dump($errorinsrt1);
        exit;
    }

?> 