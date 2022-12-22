<?php

    function ejecutarPagos($dbconn)
    {

        $fecha_LOG_1 = date('Y-m-d');
        $fecha_inicio_log_1 = Date('Y-m-d\TH:i:s');
        $tiempo_inicio_1 = microtime_float();

        $sql1_validar = "SELECT * FROM pagos_saldos_inf WHERE pago_id_ is null";
        $result1 = pg_query( $dbconn, $sql1_validar );
        if (pg_num_rows($result1) > 0){

            while ($row = pg_fetch_array($result1)) {

            	// Validar que no se duplique el pago
            	$sql11_validar = "SELECT * FROM pagos_saldos_inf WHERE pago_id_ is not null and factura_id = " . $row["factura_id"] . " AND valor_saldo = " . $row["valor_saldo"];
        		$result11 = pg_query( $dbconn, $sql11_validar );
        		if (pg_num_rows($result11) == 0){
                	$factura_id = $row["factura_id"];
                	$valor = $row["valor_saldo"];

                	$sql_cartera = "SELECT * FROM cartera WHERE id_interno = '$factura_id'";
                	$result_cartera = pg_query( $dbconn, $sql_cartera );
                	if (pg_num_rows($result_cartera) > 0){

                    	while ($row_cartera = pg_fetch_array($result_cartera)) {

                        	$idd = $row_cartera["id_interno"];
                        	print("Efectuando el pago de la ID FACTURA #$idd \n\n");

                        	$pago = array(
                            	"invoices" => str_replace(' ', '', $row_cartera['id_interno']),
                            	"payment_amount" => str_replace(' ', '', $valor), //valor_pago
                            	"account_bank" => 1359, //cuenta
                            	"cuenta_cont" => 2262,
                            	"cliente" => str_replace(' ', '', $row_cartera['cliente_id']),
                            	"externalid" => random_int(1000, 7997114084), //externa_idse necesita una modificacion par la fechayhora
                            	"refnum" => str_replace(' ', '', $row_cartera['numero_documento']),
                            	"memo" => "FC-".str_replace(' ', '', $row_cartera['numero_documento'])."-AJUSTE SALDOS INFERIORES",
                            	"vendedor" => str_replace(' ', '', $row_cartera['cae_venta_id']),
                            	"fecha_r" => date('d/m/Y')
                        	);

                        	$respuesta = enviar_a_netsuite($pago);
                        	$decodificado = json_decode($respuesta, true);

                        	$fecha_fin_log_1 = Date('Y-m-d\TH:i:s');

                        	$tiempo_fin_1 = microtime_float();
                        	$tiempo_a_1 = $tiempo_fin_1 - $tiempo_inicio_1;
                        	$tiempo_1 = $tiempo_a_1 / 60;
                        	$mensaje = '';

                        	if($decodificado["message"] == 'success'){

                            	$id_pago = $decodificado["response"]["id"];

                            	//actualizar el pago
                            	$fecha = date('Y-m-d');
                            	$factura_id = $row_cartera['id_interno'];
                            	$sql_actualizar = "UPDATE pagos_saldos_inf SET 
                                	pago_id_ = $id_pago, 
                                	fecha_pago = '$fecha' 
                                	WHERE factura_id = $factura_id AND valor_saldo = $valor";
                            	$result_actualizacion = pg_query( $dbconn, $sql_actualizar);
                            	$errorinsrt1 =  pg_last_error($dbconn);
                            	if (!isset($errorinsrt1)){
                                	var_dump($errorinsrt1);
                                	exit;
                            	} else {
                                	print($errorinsrt1."\n\n");
                            	}

                            	$mensaje = 'Factura #'. $row_cartera['id_interno'] . ', Estado: SUCCESS,  Mensaje: ID del Pago #' .$id_pago;


                        	} else {

                            	$mensaje = 'Factura #'. $row_cartera['id_interno'] . ', Estado: ERROR,  Mensaje: ' .$decodificado["response"];

                            	$fecha_insertar_1 = Date('Y-m-d\TH:i:s');
                            	$sql_log_ejecutar = "INSERT INTO logs (proceso, tabla, mensaje, cant_registro, cant_insertados, 
                                	cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo) VALUES 
                                	('INSERCIÓN NETSUITE', 'pagos_saldos_inf', '$mensaje', 0, 0, 0, 
                                	'$fecha_LOG_1', '$fecha_inicio_log_1', '$fecha_fin_log_1', '$tiempo_1')";
                            	$result1_creacion_log = pg_query( $dbconn, $sql_log_ejecutar );
                            	$errorinsrt11 =  pg_last_error($dbconn);
                            	if (!isset($errorinsrt11)){
                                	var_dump($errorinsrt11);
                                	exit;
                            	}
                            	print("Ocurrio un error al ejecutar el pago\n\n]");

                        	}

                        	$sql_log_ejecutar = "INSERT INTO logs (proceso, tabla, mensaje, cant_registro, cant_insertados, 
                                cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo) VALUES 
                                ('INSERCIÓN NETSUITE', 'pagos_saldos_inf', '$mensaje', 0, 0, 0, 
                                '$fecha_LOG_1', '$fecha_inicio_log_1', '$fecha_fin_log_1', '$tiempo_1')";
                        	$result1_creacion_log = pg_query( $dbconn, $sql_log_ejecutar );
                        	$errorinsrt11 =  pg_last_error($dbconn);
                        	if (!isset($errorinsrt11)){
                            	var_dump($errorinsrt11);
                            	exit;
                        	}

                    	}
                	}
                }
            }

        }

    }

    function enviar_a_netsuite($pago)
    {

        $NETSUITE_URL= 'https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl';
        $NETSUITE_SCRIPT_ID='2053';
        $NETSUITE_DEPLOY_ID= '1';
        $NETSUITE_ACCOUNT= '4572765';
        $NETSUITE_CONSUMER_KEY= '2f9547576834cc96d21291c12471b25ce007af610831ff4eb95b6a60c305e523';
        $NETSUITE_CONSUMER_SECRET= '4c5dcfb635de5c59e14d4645d73b63e43709b0f4386b2d87b56bcd61acff4d32';
        $NETSUITE_TOKEN_ID= '1ac02d58a74e9845c0808bb66247f194e7c7f8fb2cf2c5692535915c35688c26';
        $NETSUITE_TOKEN_SECRET= 'd24d4672a59dff419b08bb5430c0dce58b50182f9f57cb0f8b497637e0e518a0';

        $data_string = json_encode($pago);
        
        $oauth_nonce = md5(mt_rand());
        $oauth_timestamp = time();
        $oauth_signature_method = 'HMAC-SHA256';
        $oauth_version = "1.0";

        $base_string =
            "POST&" . urlencode($NETSUITE_URL) . "&" .
            urlencode(
                "deploy=" . $NETSUITE_DEPLOY_ID
              . "&oauth_consumer_key=" . $NETSUITE_CONSUMER_KEY
              . "&oauth_nonce=" . $oauth_nonce
              . "&oauth_signature_method=" . $oauth_signature_method
              . "&oauth_timestamp=" . $oauth_timestamp
              . "&oauth_token=" . $NETSUITE_TOKEN_ID
              . "&oauth_version=" . $oauth_version
              . "&realm=" . $NETSUITE_ACCOUNT
              . "&script=" . $NETSUITE_SCRIPT_ID
            );
        $sig_string = urlencode($NETSUITE_CONSUMER_SECRET) . '&' . urlencode($NETSUITE_TOKEN_SECRET);
        $signature = base64_encode(hash_hmac("sha256", $base_string, $sig_string, true));

        $auth_header = "OAuth "
            . 'oauth_signature="' . rawurlencode($signature) . '", '
            . 'oauth_version="' . rawurlencode($oauth_version) . '", '
            . 'oauth_nonce="' . rawurlencode($oauth_nonce) . '", '
            . 'oauth_signature_method="' . rawurlencode($oauth_signature_method) . '", '
            . 'oauth_consumer_key="' . rawurlencode($NETSUITE_CONSUMER_KEY) . '", '
            . 'oauth_token="' . rawurlencode($NETSUITE_TOKEN_ID) . '", '
            . 'oauth_timestamp="' . rawurlencode($oauth_timestamp) . '", '
            . 'realm="' . rawurlencode($NETSUITE_ACCOUNT) .'"';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $NETSUITE_URL . '?&script=' . $NETSUITE_SCRIPT_ID . '&deploy=' . $NETSUITE_DEPLOY_ID . '&realm=' . $NETSUITE_ACCOUNT);
        curl_setopt($ch, CURLOPT_POST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $auth_header,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ]);

        $salida_ejecutaria = curl_exec($ch);
        //dd($salida_ejecutaria);
        curl_close($ch);
        //dd($salida_ejecutaria);
        return $salida_ejecutaria;

    }
