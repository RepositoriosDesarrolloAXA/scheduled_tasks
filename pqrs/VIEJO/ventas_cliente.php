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
            $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=2090&deploy=1&start=$start&end=$end";
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
                
                $internal_id                                    = $data[$i]["id"];
                $nit_laboratorio                                = $data[$i]["values"]["item.custitem_axa_vendormanufacturer_pjcl"][$y]["text"];
                $laboratorio                                    = $data[$i]["values"]["item.manufacturer"];
                $tipo                                           = $data[$i]["values"]["type"][$y]["text"];
                $numero_documento                               = $data[$i]["values"]["tranid"];
                $nit_cliente                                    = $data[$i]["values"]["entity"][$y]["text"];
                $cliente                                        = str_replace("'", "", $data[$i]["values"]["custbody_axa_field_nomcliente_fjsr"]);
                $direccion                                      = str_replace("'", "", $data[$i]["values"]["shippingattention"]);
                $direccion_envio                                = $data[$i]["values"]["shipaddress1"];
                $barrio                                         = $data[$i]["values"]["shipaddress3"];
                $ciudad_envio                                   = $data[$i]["values"]["shipcity"];
                $departamento                                   = $data[$i]["values"]["shipstate"];
                $ubicacion                                      = $data[$i]["values"]["location"][$y]["text"];
                $clase                                          = $data[$i]["values"]["class"][$y]["text"];
                $precio_cliente                                 = $data[$i]["values"]["custbody_nso_precio_cliente"][$y]["text"];
                $metodo_pago                                    = $data[$i]["values"]["custbody_nso_metodo_pago"][$y]["text"];
                $categoria_cliente                              = $data[$i]["values"]["customerMain.category"][$y]["text"];
                $vendedor                                       = $data[$i]["values"]["custbody_nso_vendedor_ffvv"][$y]["text"];
                $nit_caeventa                                   = $data[$i]["values"]["CUSTBODY_AXA_CAMP_VENDE_WF.custentity_nslc_num_identificacion"];
                $caeventa                                       = str_replace("'", "", $data[$i]["values"]["custbody_axa_camp_vende_wf"][$y]["text"]);
                $origen                                         = $data[$i]["values"]["source"][$y]["text"];
                $tipo_origen                                    = $data[$i]["values"]["custbody_nso_ov_type"][$y]["text"];
                $periodo                                        = $data[$i]["values"]["postingperiod"][$y]["text"];
                $fecha                                          = $data[$i]["values"]["trandate"];
                $codigo_barras_item                             = $data[$i]["values"]["item.upccode"];
                $item                                           = str_replace("'", "", $data[$i]["values"]["item"][$y]["text"]);
                $linea_item                                     = empty($data[$i]["values"]["item.custitem_nso_axa_field_item_linea"]) ? 0 : $data[$i]["values"]["item.custitem_nso_axa_field_item_linea"];
                $embalaje_item                                  = empty($data[$i]["values"]["item.custitem_nso_axa_field_item_fromalm"]) ? 0 : $data[$i]["values"]["item.custitem_nso_axa_field_item_fromalm"];
                $cantidad_vendidad_item                         = empty($data[$i]["values"]["quantity"]) ? 0 : $data[$i]["values"]["quantity"];
                $valor_item                                     = empty($data[$i]["values"]["amount"]) ? 0 : $data[$i]["values"]["amount"];
                $valor_iva_item                                 = empty($data[$i]["values"]["taxamount"]) ? 0 : $data[$i]["values"]["taxamount"];
                $valor_descuento_item                           = empty($data[$i]["values"]["discountamount"]) ? 0 : $data[$i]["values"]["discountamount"];
                $valor_total_con_descuento_sin_iva_item         = empty($data[$i]["values"]["formulacurrency"]) ? 0 : $data[$i]["values"]["formulacurrency"];
                $subcategoria_l                                 = $data[$i]["values"]["item.custitem_nso_axa_field_item_categoria"][$y]["text"];
                $subcategoria_ll                                = $data[$i]["values"]["item.custitem_nso_axa_field_item_subcat"][$y]["text"];
                $subcategoria_lll                               = $data[$i]["values"]["item.custitem_nso_axa_field_item_grupo"][$y]["text"];

                //guardando cabeceras
                if(!empty($internal_id)){

                    $sql_validar_cabecera = "SELECT id FROM ventas_clientes WHERE internal_id = '".$internal_id."' AND numero_documento = '".$numero_documento."' AND codigo_barras_item = '".$codigo_barras_item."' AND item = '".$item."' AND cantidad_vendidad_item = '".$cantidad_vendidad_item."'";
                    $result1 = pg_query( $dbconn, $sql_validar_cabecera );
                    if (pg_num_rows($result1) > 0){
                        
                        //actualiza
                        $sql_query = "UPDATE ventas_clientes SET 
                            internal_id = '$internal_id', 
                            nit_laboratorio = '$nit_laboratorio', 
                            laboratorio = '$laboratorio',
                            tipo = '$tipo',
                            numero_documento = '$numero_documento',
                            nit_cliente = '$nit_cliente',
                            cliente = '$cliente',
                            direccion = '$direccion',
                            direccion_envio = '$direccion_envio',
                            barrio = '$barrio',
                            ciudad_envio = '$ciudad_envio',
                            departamento = '$departamento',
                            ubicacion = '$ubicacion',
                            clase = '$clase',
                            precio_cliente = '$precio_cliente',
                            metodo_pago = '$metodo_pago',
                            categoria_cliente = '$categoria_cliente',
                            vendedor = '$vendedor',
                            nit_caeventa = '$nit_caeventa',
                            caeventa = '$caeventa',
                            tipo_origen = '$tipo_origen',
                            periodo = '$periodo',
                            fecha = '$fecha',
                            codigo_barras_item = '$codigo_barras_item',
                            item = '$item',
                            linea_item = '$linea_item',
                            embalaje_item = $embalaje_item,
                            cantidad_vendidad_item = $cantidad_vendidad_item,
                            valor_item = $valor_item,
                            valor_iva_item = $valor_iva_item,
                            valor_descuento_item = $valor_descuento_item,
                            valor_total_con_descuento_sin_iva_item = $valor_total_con_descuento_sin_iva_item,
                            subcategoria_l = '$subcategoria_l',
                            subcategoria_ll = '$subcategoria_ll',
                            subcategoria_lll = '$subcategoria_lll',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE internal_id = '".$internal_id."' AND numero_documento = '".$numero_documento."' AND codigo_barras_item = '".$codigo_barras_item."' AND item = '".$item."' AND cantidad_vendidad_item = '".$cantidad_vendidad_item."'";
                        $valores_actualizados++;

                    } else {

                        //crea
                        $sql_query = "INSERT INTO ventas_clientes (internal_id, nit_laboratorio, laboratorio, tipo, 
                        numero_documento, nit_cliente, cliente, direccion, direccion_envio, barrio, ciudad_envio, 
                        departamento, ubicacion, clase, precio_cliente, metodo_pago, categoria_cliente, vendedor, nit_caeventa, 
                        caeventa, origen, tipo_origen, periodo, fecha, codigo_barras_item, item, linea_item, embalaje_item, 
                        cantidad_vendidad_item, valor_item, valor_iva_item, valor_descuento_item, valor_total_con_descuento_sin_iva_item, 
                        subcategoria_l, subcategoria_ll, subcategoria_lll, created_at) 
                        VALUES('$internal_id', '$nit_laboratorio', '$laboratorio', '$tipo', '$numero_documento', 
                        '$nit_cliente', '$cliente', '$direccion', '$direccion_envio', '$barrio', '$ciudad_envio', 
                        '$departamento', '$ubicacion', '$clase', '$precio_cliente', '$metodo_pago', '$categoria_cliente', 
                        '$vendedor', '$nit_caeventa', '$caeventa', '$origen', '$tipo_origen', '$periodo', '$fecha', 
                        '$codigo_barras_item', '$item', '$linea_item', $embalaje_item, $cantidad_vendidad_item, 
                        $valor_item, $valor_iva_item, $valor_descuento_item, $valor_total_con_descuento_sin_iva_item, 
                        '$subcategoria_l', '$subcategoria_ll', '$subcategoria_lll', CURRENT_TIMESTAMP)";
                        $valores_creados++;

                    }

                    $result1_creacion = pg_query( $dbconn, $sql_query );
                	$errorinsrt1 =  pg_last_error($dbconn);
                	if (!isset($errorinsrt1)){
                    	var_dump($errorinsrt1);
                    	exit;
                	} else {
                   	 	print($errorinsrt1."\n");
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
        ('REGISTRO', 'ventas_clientes', $cantidad_registros, $valores_creados, $valores_actualizados, 
        '$fecha_LOG', '$fecha_inicio_log', '$fecha_fin_log', '$tiempo', CURRENT_TIMESTAMP)";
    $result1_creacion_log = pg_query( $dbconn, $sql_log );
    $errorinsrt1 =  pg_last_error($dbconn);
    if (!isset($errorinsrt1)){
        var_dump($errorinsrt1);
        exit;
    }

?> 