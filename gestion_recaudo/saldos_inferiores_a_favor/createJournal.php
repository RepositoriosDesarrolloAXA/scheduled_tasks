<?php

/*
	Función que crea un unico diario para todo el total de registros (Pagos o Notas Créditos)
	que tienen saldo a favor del cliente -1000
*/

function createJournal($allData){
	try {
    	$amountTotal = 0;
    	$lineas = [];
    
        // Armar las líneas DEBITO 
    	foreach($allData as $row){
        	$amountTotal += $row->total_cartera;
        
        	$lineDebito = new stdClass();
        	$lineDebito->type = "DEBITO";
			$lineDebito->valor = $row->total_cartera;
			$lineDebito->ubicacion = $row->ubicacion_id;
			$lineDebito->clase = $row->clase_id;
			$lineDebito->cliente = $row->cliente_id;
        
        	array_push($lineas, $lineDebito);
        }
    
    	print("El monto total de crédito es $amountTotal \n");
    
    	// Armar la línea CREDITO
    	$lineCredito = new stdClass();
        $lineCredito->type = "CREDITO";
		$lineCredito->valor = $amountTotal;
		$lineCredito->ubicacion = 9;
		$lineCredito->clase = 1;
		$lineCredito->cliente = 281;

    	array_push($lineas, $lineCredito);
    
    	// Enviar a NetSuite
    	$body = new stdClass();
    	$body->lineas = $lineas;
    
		$respuesta = enviar_a_netsuite($body);
		$decodificado = json_decode($respuesta, true);
    	return $decodificado;
    } catch (\Exception $err) {
    	return false;
    }
}


function enviar_a_netsuite($pago){
	
	// Credenciales LIVE
    $NETSUITE_URL= 'https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl';
    $NETSUITE_SCRIPT_ID='2094';
    $NETSUITE_DEPLOY_ID= '1';
    $NETSUITE_ACCOUNT= '4572765';
    $NETSUITE_CONSUMER_KEY= '2f9547576834cc96d21291c12471b25ce007af610831ff4eb95b6a60c305e523';
    $NETSUITE_CONSUMER_SECRET= '4c5dcfb635de5c59e14d4645d73b63e43709b0f4386b2d87b56bcd61acff4d32';
    $NETSUITE_TOKEN_ID= '1ac02d58a74e9845c0808bb66247f194e7c7f8fb2cf2c5692535915c35688c26';
    $NETSUITE_TOKEN_SECRET= 'd24d4672a59dff419b08bb5430c0dce58b50182f9f57cb0f8b497637e0e518a0';

	// Credenciales SANDBOX
	// $NETSUITE_URL= 'https://4572765-sb1.restlets.api.netsuite.com/app/site/hosting/restlet.nl';
	// $NETSUITE_SCRIPT_ID='2092';
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