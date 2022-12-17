<?php

require_once 'conexion.php'; // base de datos
require_once 'Oauth256.php'; // autenticacion con netsuite
require_once 'funtions.php'; // funciones

error_reporting(E_ALL ^ E_WARNING);
header('Content-type: text/plain; charset=UTF-8');

//eliminar datos de la tabla
$sql_eliminar = "DELETE FROM inventario_netsuite";
$result1_eliminar = pg_query( $dbconn, $sql_eliminar );

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
    $tiempo_inicio = microtime_float();
    $fecha_LOG = date('Y-m-d');
    $fecha_inicio_log = Date('Y-m-d\TH:i:s');

    while($control == 0 ){ 

        if ($inicio== 0){
            $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=1309&deploy=1&start=$start&end=$end";
        }

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
            
            	print("Linea ".$i."\n");
                
                $item_id                 = $data[$i]["values"]["internalid"][$y]["text"];
                $nombre                  = empty($data[$i]["values"]["itemid"]) ? 'null' : str_replace("'", "", $data[$i]["values"]["itemid"]);
                $ubicacion_id            = $data[$i]["values"]["inventoryLocation.internalid"][$y]["text"];
                $ean                     = $data[$i]["values"]["upccode"];
                $disponible              = empty($data[$i]["values"]["locationquantityavailable"]) ? 0 : $data[$i]["values"]["locationquantityavailable"];

                $data_info[] = array(
                    "item_id" => $item_id,
                    "nombre" => $nombre,
                    'ubicacion_id' => $ubicacion_id,
                    'ean' => $ean,
                    'disponible' => $disponible
                );

                $sql1 = "INSERT INTO inventario_netsuite  
                        (item_id, nombre, ubicacion_id, ean, disponible, created_at) 
                        VALUES ($item_id, '$nombre', $ubicacion_id, '$ean', $disponible, CURRENT_TIMESTAMP)";

                $result1_creacion = pg_query( $dbconn, utf8_encode($sql1) );
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

    /********************** Luego copiar de la tabla a la principal *****************************/
	$tabla_copiada = pg_copy_to($dbconn, 'inventario_netsuite');
	//luego eliminamos la informacion de la tabla
	pg_query($dbconn, "DELETE FROM inventario");
	//luego pegamos
	pg_copy_from($dbconn, 'inventario', $tabla_copiada);

    //crear log
    $sql_log = "INSERT INTO log (proceso, tabla, cant_registro, cant_insertados, 
        cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo, created_at) VALUES 
        ('REGISTRO', 'inventario', $cantidad_registros, $valores_creados, $valores_actualizados, 
        '$fecha_LOG', '$fecha_inicio_log', '$fecha_fin_log', '$tiempo', CURRENT_TIMESTAMP)";
    $result1_creacion_log = pg_query( $dbconn, $sql_log );
    $errorinsrt1 =  pg_last_error($dbconn);
    if (!isset($errorinsrt1)){
        var_dump($errorinsrt1);
        exit;
    }

    //print($cantidad_registros);
    //print("\n\n".$tiempo);
    //print("\n\n".json_encode($data_info));

?> 