<?php
/*
	Metodo que cruza el monto sin aplicar de una NC -1000 al Diario creado
*/

function cruzarCreditMemoWithJournal($dbconn, $creditMemo){
	try{
    	// Armar body
		$body = new stdClass();
		$body->nc_id = $creditMemo->id_interno;
    	$body->diario_id = $creditMemo->id_journal;
    	$body->valor_pago_no_aplicado = $creditMemo->monto_aplicado;
    
    	print('Cruzando NC con Diario ' . json_encode($body, true)  . "\n");
    	$responseNetSuite = enviar_a_netsuiteNC($body);
    
    	// Guardar log de trazabilidad
    	$fecha_LOG = date('Y-m-d');
		$fecha_insertar = Date('Y-m-d\TH:i:s');
    	$sql_log = "
        	INSERT INTO logs (
            	proceso, tabla, mensaje, cant_registro, cant_insertados, cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo, created_at
            ) VALUES (
            	'Cruce de NC a favor con Diario: " . json_encode($body, true) . "', 'saldos_inf_a_favor', 'Cruce de NC con Diario: {$responseNetSuite}', 1, 1, 1, '$fecha_LOG', null, null, null, '$fecha_insertar'
            )
        ";
    	$result1_creacion_log = pg_query( $dbconn, $sql_log );
    
    	$decodificado = json_decode($responseNetSuite, true);
    	if($decodificado['message'] === 'success'){
        
        	// Actualizar el registro en saldos_inf_a_favor
        	$sqlUpdateRow = "
            	UPDATE saldos_inf_a_favor SET pago_id={$decodificado['response']['id']} 
            	WHERE id_interno=$creditMemo->id_interno AND id_journal=$creditMemo->id_journal AND monto_aplicado=$creditMemo->monto_aplicado
            ";
        	$resultUpdate = pg_query($dbconn, $sqlUpdateRow);
        	if(!$resultUpdate){
            	print("$creditMemo->id_interno Falló al actualizar \n");
            }else{
            	print("$creditMemo->id_interno Actualizado con exito \n");
            }
        }else{
            print('Error en NetSuite al intentar cruzar con el Diario: ' . $decodificado['response'] . "\n");
        }
    }catch(\Exception $err){
    	print("Ocurrió un error al intentar cruzar la nota crédito con el Diario a favor del cliente en NetSuite \n");
    }
}


function enviar_a_netsuiteNC($pago){

	// Credenciales LIVE
    $NETSUITE_URL= 'https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl';
    $NETSUITE_SCRIPT_ID='2095';
    $NETSUITE_DEPLOY_ID= '1';
    $NETSUITE_ACCOUNT= '4572765';
    $NETSUITE_CONSUMER_KEY= '2f9547576834cc96d21291c12471b25ce007af610831ff4eb95b6a60c305e523';
    $NETSUITE_CONSUMER_SECRET= '4c5dcfb635de5c59e14d4645d73b63e43709b0f4386b2d87b56bcd61acff4d32';
    $NETSUITE_TOKEN_ID= '1ac02d58a74e9845c0808bb66247f194e7c7f8fb2cf2c5692535915c35688c26';
    $NETSUITE_TOKEN_SECRET= 'd24d4672a59dff419b08bb5430c0dce58b50182f9f57cb0f8b497637e0e518a0';

	// Credenciales SANDBOX
	// $NETSUITE_URL= 'https://4572765-sb1.restlets.api.netsuite.com/app/site/hosting/restlet.nl';
	// $NETSUITE_SCRIPT_ID='2094';
	// $NETSUITE_DEPLOY_ID= '1';
	// $NETSUITE_ACCOUNT= '4572765_SB1';
	// $NETSUITE_CONSUMER_KEY= '4dba2d2b0840834096dc19fbcb650b4e10828ab06e7c03ece6b4d5f9cccc501e';
	// $NETSUITE_CONSUMER_SECRET= 'd5b415def84aa7f304f39823d3cc0edad42dc28df4ff9c438f9d036a410ccf82';
	// $NETSUITE_TOKEN_ID= 'e32b7c69c2fcdabec9176c7a894d46652a027ac65219542f88bee60b075e5e43';
	// $NETSUITE_TOKEN_SECRET= '4428be85cd23a208c50c5302211cd5f5e9e69adcd6189bd040c54cab294c8c95';

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
?>
