<?php

/* ---------------------------------------------------- Insertar AXLP Marginal ---------------------------------------------------- */

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
            $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=1904&deploy=1&start=$start&end=$end";
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
                
                $id                                 = $data[$i]["id"];
                $item_id                            = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.internalid"][$y]["text"];
                $lista_id                           = $data[$i]["values"]["CUSTRECORD_AXLP_NSO_PRECIOCLIENTEMARGI.internalid"][$y]["text"];
                $ubicacion_id                       = $data[$i]["values"]["formulatext"];
                $teleferia_id                       = empty($data[$i]["values"]["formulatext_1"]) ? 'null' : $data[$i]["values"]["formulatext_1"];
                $centro_costo_id                    = $data[$i]["values"]["formulatext_3"];

                $fecha_promocion_axa                = empty($data[$i]["values"]["custrecord_axlp_fecha_finalpromomargi"]) ? 'null' : "'" . $data[$i]["values"]["custrecord_axlp_fecha_finalpromomargi"] . "'";
                $porciento_promocion_axa            = empty($data[$i]["values"]["custrecord_axlp_dto_promomargi"]) ? 0 : $data[$i]["values"]["custrecord_axlp_dto_promomargi"];

                $fecha_incial_teleferia             = empty($data[$i]["values"]["custrecord_axlp_fechainicial_teleferiama"]) ? 'null' : "'" . $data[$i]["values"]["custrecord_axlp_fechainicial_teleferiama"] . "'";
                $fecha_final_teleferia              = empty($data[$i]["values"]["custrecord_axlp_fechafinal_teleferiamarg"]) ? 'null' : "'" . $data[$i]["values"]["custrecord_axlp_fechafinal_teleferiamarg"] . "'";
                $porciento_teleferia                = empty($data[$i]["values"]["formulanumeric"]) ? 0 : $data[$i]["values"]["formulanumeric"];
                $contado_sin_teleferia              = empty($data[$i]["values"]["custrecord_axlp_precio_calculcontamargi"]) ? 0 : $data[$i]["values"]["custrecord_axlp_precio_calculcontamargi"];
                $credito_sin_teleferia              = empty($data[$i]["values"]["custrecord_axlp_precio_calculadocreditma"]) ? 0 : $data[$i]["values"]["custrecord_axlp_precio_calculadocreditma"];
                $contado_teleferia                  = empty($data[$i]["values"]["custrecord_axlp_precio_calculadoteleconm"]) ? 0 : $data[$i]["values"]["custrecord_axlp_precio_calculadoteleconm"];
                $credito_teleferia                  = empty($data[$i]["values"]["custrecord_axlp_precio_calculadotelecrem"]) ? 0 : $data[$i]["values"]["custrecord_axlp_precio_calculadotelecrem"];
                $aplica_transferencia               = $data[$i]["values"]["custrecord_axlp_aplica_transfermargi"];
                $linea                              = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.custitem_nso_axa_field_item_linea"];
                $mp_credito                         = $data[$i]["values"]["formulatext_6"];
                $mp_contado                         = $data[$i]["values"]["formulatext_5"];
                $ean                                = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.upccode"];
                $generico                           = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.custitem_nso_axa_field_item_destec"];
                $nombre_item                        = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.displayname"];
                $precio_max_venta                   = empty($data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.custitem_nso_maximo_precio_venta"]) ? 0 : $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.custitem_nso_maximo_precio_venta"];
                $controlado                         = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.custitem_nso_axa_field_item_medcont"];
                $iva                                = empty($data[$i]["values"]["formulatext_2"]) ? 0 : $data[$i]["values"]["formulatext_2"];
                $embalaje                           = empty($data[$i]["values"]["formulanumeric_1"]) ? 0 : $data[$i]["values"]["formulanumeric_1"];
                $tipo_articulo                      = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.type"][$y]["text"];
                $tipo                               = $data[$i]["values"]["formulatext_4"];
            
            	$estado_articulo				    = $data[$i]["values"]["CUSTRECORD_AXLP_NSO_PRECIOITEMMARGINAL.isinactive"];
            	$unidad_venta_inst				    = $data[$i]["values"]["CUSTRECORD_AXLP_ITEMMARGI.custitem_nso_axa_field_item_unvtainst"];
                $precio_item_id				        = $data[$i]["values"]["formulatext_7"];

                if($aplica_transferencia == true){
                    $aplica_transferencia = 'Sí';
                } else {
                    $aplica_transferencia = 'No';
                }

                if($controlado == true){
                    $controlado = 'Sí';
                } else {
                    $controlado = 'No';
                }
            
            	if($estado_articulo == true){
                    $estado_articulo = 'Si';
                } else {
                    $estado_articulo = 'No';
                }

                //alidar si existe cliente
				$fecha_insertar = Date('Y-m-d\TH:i:s');
                $sql1_validar = "SELECT id FROM axlp_marginal WHERE id = '".$id."'";
                $result1 = pg_query( $dbconn, $sql1_validar );
                if (pg_num_rows($result1) > 0){

                    //actualiza
                    $sql1 = "UPDATE axlp_marginal SET 
                        id = $id, 
                        item_id = $item_id, 
                        lista_id = $lista_id, 
                        ubicacion_id = $ubicacion_id, 
                        teleferia_id = $teleferia_id, 
                        centro_costo_id = $centro_costo_id, 
                        fecha_promocion_axa = $fecha_promocion_axa, 
                        porciento_promocion_axa = $porciento_promocion_axa, 
                        fecha_incial_teleferia = $fecha_incial_teleferia, 
                        fecha_final_teleferia = $fecha_final_teleferia, 
                        porciento_teleferia = $porciento_teleferia, 
                        contado_sin_teleferia = $contado_sin_teleferia, 
                        credito_sin_teleferia = $credito_sin_teleferia, 
                        contado_teleferia = $contado_teleferia, 
                        credito_teleferia = $credito_teleferia, 
                        aplica_transferencia = '$aplica_transferencia', 
                        linea = '$linea', 
                        mp_credito = $mp_credito, 
                        mp_contado = $mp_contado, 
                        ean = '$ean', 
                        generico = '$generico', 
                        nombre_item = '$nombre_item', 
                        precio_max_venta = $precio_max_venta, 
                        controlado = '$controlado', 
                        iva = $iva, 
                        embalaje = '$embalaje', 
                        tipo_articulo = '$tipo_articulo', 
                        tipo = '$tipo', 
                        isinactive = '$estado_articulo', 
                        unidad_venta_inst = $unidad_venta_inst, 
                        precio_item_id = $precio_item_id, 
                        updated_at = '$fecha_insertar' 
                        WHERE id = '".$id."'";

                    $valores_actualizados++;

                } else {

                    //creación
                    $sql1 = "INSERT INTO axlp_marginal 
                    (id, item_id, lista_id, ubicacion_id, teleferia_id, centro_costo_id, fecha_promocion_axa, porciento_promocion_axa, 
                    fecha_incial_teleferia, fecha_final_teleferia, 
                    porciento_teleferia, contado_sin_teleferia, credito_sin_teleferia, contado_teleferia, credito_teleferia, 
                    aplica_transferencia, linea, mp_credito, mp_contado, ean, generico, nombre_item, precio_max_venta, 
                    controlado, iva, embalaje, tipo_articulo, tipo, isinactive, unidad_venta_inst, precio_item_id, created_at) 
                    VALUES ($id, $item_id, $lista_id, $ubicacion_id, $teleferia_id, $centro_costo_id, 
                    $fecha_promocion_axa, $porciento_promocion_axa, $fecha_incial_teleferia,
                    $fecha_final_teleferia, $porciento_teleferia, $contado_sin_teleferia, $credito_sin_teleferia, $contado_teleferia, 
                    $credito_teleferia, '$aplica_transferencia', '$linea', $mp_credito, $mp_contado, '$ean', '$generico', 
                    '$nombre_item', $precio_max_venta, '$controlado', $iva, '$embalaje', '$tipo_articulo', '$tipo', 
                    '$estado_articulo', $unidad_venta_inst, $precio_item_id, '$fecha_insertar')";

                    $valores_creados++;

                }

                //print($sql1 . "\n\n");

            	$result1_creacion = pg_query( $dbconn, $sql1);
                $errorinsrt1 =  pg_last_error($dbconn);
                if (!isset($errorinsrt1)){
                    var_dump($errorinsrt1);
                    exit;
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

    $fecha_insertar = Date('Y-m-d\TH:i:s');
    $sql_log = "INSERT INTO log (proceso, tabla, cant_registro, cant_insertados, 
        cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo, created_at) VALUES 
        ('REGISTRO', 'axlp_marginal', $cantidad_registros, $valores_creados, $valores_actualizados, 
        '$fecha_LOG', '$fecha_inicio_log', '$fecha_fin_log', '$tiempo', '$fecha_insertar')";
    $result1_creacion_log = pg_query( $dbconn, $sql_log );
    $errorinsrt1 =  pg_last_error($dbconn);
    if (!isset($errorinsrt1)){
        var_dump($errorinsrt1);
        exit;
    }

?> 