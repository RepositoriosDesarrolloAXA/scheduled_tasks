<?php
require_once 'conexion.php'; // base de datos
require_once 'Oauth256.php'; // autenticacion con netsuite
require_once 'funtions.php'; // funciones
error_reporting(E_ALL ^ E_WARNING);

header('Content-type: text/plain; charset=UTF-8');

        $control = 0;
        $inicio=0;
        $start=0;
        $end=1000;

        $sql1 = "TRUNCATE public.tbl_cartera_clientes RESTART IDENTITY";
        $result1 = pg_query( $dbconn, $sql1 );
    while($control == 0 ){ 

        if ($inicio== 0){
        $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=1878&deploy=1&start=$start&end=$end";
        }

        echo"se insertaron los datos desde el ".$start." al ".$end."\n";
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
        'header' => $req->to_header() . ',realm="4572765"'. " \r\n" . "Host: 4572765.suitetalk.api.netsuite.com \r\n" . "Content-Type: application/json"
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
     //  var_dump($data);

        // Echo" total de datos ".$reg."\n";
        if ($control == 0 && $longitud > 0){

            $j=1000;
            $v=1;
            $start=$end;
            $end=$end+$j;

            $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 
            echo "este es el valor de longitud:".$longitud."\n";

        for($i=0; $i<$longitud; $i++){ 
            $y=0;

            $tiporeg      = $data[$i]["values"]["recordType"];
            $id           = $data[$i]["id"];
            $idcli        = $data[$i]["values"]["formulanumeric"];
            $nit          = $data[$i]["values"]["formulatext"];
            $nombcli      = $data[$i]["values"]["formulatext_1"];
            $fecfac       = $data[$i]["values"]["trandate"];
            $tipodoc      = $data[$i]["values"]["type"][$y]["text"];
            $intid        = $data[$i]["values"]["type"][$y]["text"];
            $numfac       = $data[$i]["values"]["tranid"];
            $clase        = $data[$i]["values"]["formulatext_2"];
            $bodega       = $data[$i]["values"]["formulatext_3"];
            $zona         = $data[$i]["values"]["formulatext_4"];
            $taxes        = $data[$i]["values"]["formulanumeric_1"];
            $totalcart    = $data[$i]["values"]["amountremaining"];
            $valordoc     = $data[$i]["values"]["amount"];
            $rango        = $data[$i]["values"]["formulatext_5"];
            $diascar      = $data[$i]["values"]["formulanumeric_2"];
            if ($diascar == "")
               {$diascar=0;}
            $estado       = $data[$i]["values"]["statusref"][$y]["text"];
            $cuentacon    = $data[$i]["values"]["account.number"];
            $nombodega    = $data[$i]["values"]["location"][$y]["text"];
            $nomclase     = $data[$i]["values"]["class"][$y]["text"];
            $direnvio     = $data[$i]["values"]["shipaddress1"];
            $fechaven     = $data[$i]["values"]["formuladate"];
            $caeventa     = $data[$i]["values"]["CUSTBODY_AXA_CAMP_VENDE_WF.internalid"][$y]["value"];
            $zonaventa    = $data[$i]["values"]["shippingAddress.custrecord141"][$y]["text"];
     	    $hoy          = date('Y-m-d');
            $name= str_replace("'", '.', $nombcli);

            // INSERTA DATOS // 

            $sql1 = "INSERT INTO public.tbl_cartera_clientes(
                 tipodoc_carteracli, idcliente_carteracli, numdoccliente_carteracli, nomcliente_carteracli, fechadoc_carteracli, iddoc_carteracli, numdoc_carteracli, idclase_carteracli, idbodega_carteracli, idzona_carteracli, valimpuesto_carteracli, valsaldo_carteracli, valdoc_carteracli, rango_carteracli, diascar_carteracli, estadodoc_carteracli, fechaven_carteracli, fechacorte_carteracli,caeventa_carteracli,zonaventa_carteracli)
            VALUES ( '$tipodoc',$idcli,'$nit','$name','$fecfac',$id,'$numfac',$clase,$bodega,$zona,$taxes,$totalcart,$valordoc,'$rango',$diascar,'$estado','$fechaven','$hoy',$caeventa,'$zonaventa' )";

            $result1 = pg_query( $dbconn, $sql1 );
            $errorinsrt1 =  pg_last_error($dbconn);
            if (!isset($errorinsrt1)){

                echo "Error en insercion de datos";
                var_dump($errorinsrt1);
                exit;
            } else {
            	print($errorinsrt1."\n");
            }

        }; // for interno que recorre el arreglo del resultados 

    }   // fin del if inicio

    if ($longitud <= 1){
        $control = 1;
        echo "entro al if que detiene el proceso. \n";
        // break;
        }
} // fin del while control

 ?> 
