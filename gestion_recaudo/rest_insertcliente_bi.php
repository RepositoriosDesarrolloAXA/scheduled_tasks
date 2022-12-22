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
        $sql1 = "TRUNCATE cliente RESTART IDENTITY";
        $result1 = pg_query( $dbconn, $sql1 );

    while($control == 0 ){ 

        if ($inicio== 0){
        $url = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=1869&deploy=1&start=$start&end=$end";
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
     //  var_dump($data);

        // Echo" total de datos ".$reg."\n";
        if ($control == 0){

            $j=1000;
            $v=1;
            $start=$end;
            $end=$end+$j;

            $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 
            echo "Valores a Actualizar:".$longitud."\n";

        for($i=0; $i<$longitud; $i++){ 
            $y=0;

            $idcli        = $data[$i]["values"]["formulanumeric"];
            $nit          = $data[$i]["values"]["formulatext"];
            $nomcom       = $data[$i]["values"]["formulatext_1"];
            $nomcli       = $data[$i]["values"]["formulatext_2"];
            $fecrea       = $data[$i]["values"]["custentitycustentity_axa_pjcl_fcaxa"];
            $iddepto      = $data[$i]["values"]["formulanumeric_1"];
            $nodepto      = $data[$i]["values"]["formulatext_3"];
            $idciudad     = $data[$i]["values"]["CUSTENTITYKS_CIUDADOMUNICIPIOS.internalid"][$y]["text"];
            $nociudad     = $data[$i]["values"]["custentityks_ciudadomunicipios"][$y]["text"];
            $idtipodoc    = $data[$i]["values"]["custentity_ks_tipo_doc_identidad"][$y]["value"];
            $telefono     = $data[$i]["values"]["phone"];
            $direccion    = $data[$i]["values"]["shipaddress1"];
            $barrio       = $data[$i]["values"]["custentity_nso_clientes_barrio"];
            $estado       = $data[$i]["values"]["entitystatus"][$y]["text"];
            $correo       = $data[$i]["values"]["email"];
            $inactivo     = $data[$i]["values"]["isinactive"];
            $coperacion   = $data[$i]["values"]["custentitynso_clienntes_operaciones"][$y]["text"];
            $antcliente   = $data[$i]["values"]["custentitycustentity_nso_a_cliente"];
            $celular      = $data[$i]["values"]["mobilephone"];
            $hoy          = date('Y-m-d');
        	$fechacurrent = Date('Y-m-d\TH:i:s');

            if ( $fecrea == "" )
               { $fecrea = "2020-08-01"; }
            if ( $antcliente == "" )
               { $antcliente = "0"; }
            if ( $idciudad == "" )
               { $idciudad = "0"; }
            if ( $iddepto == "" )
               { $iddepto = "0"; }
                 $inactivo = "NO"; 
            if ( $inactivo == "1" )
               { $inactivo = "SI"; }
            if ( $idtipodoc == "" )
               { $idtipodoc = 0; }
            $nomcli1=str_replace('\'',' ',$nomcli);
            $nomcom1=str_replace('\'',' ',$nomcom);
            $estado1=str_replace('CLIENTE-','',$estado);

            // VALIDA EXISTE CLIENTE // 

            $sql1 = "SELECT id FROM cliente WHERE id = '".$idcli."'";
            $result1 = pg_query( $dbconn, $sql1 );
            if (pg_num_rows($result1) > 0)
               {
               // ACTUALIZA //
               $sql1 = "UPDATE cliente SET nombre='$nomcli1', documento='$nit', telefono='$telefono', correo_electronico='$correo', nombre_compania='$nomcom1', categoria='$coperacion', updated_at='$fechacurrent'
                        WHERE id = '$idcli'";
               }
               else
               {
               // INSERTA DATOS // 
               $sql1 = "INSERT INTO cliente (id, nombre, nombre_compania, documento, correo_electronico, telefono, cupo, cupo_feria, categoria, mp_credito, mp_contado, created_at, updated_at) VALUES ($idcli, '$nomcli1','$nomcom1','$nit','$correo','$telefono',0,0,'$coperacion',0,0,'$fechacurrent','$fechacurrent')";
               }

            $result1 = pg_query( $dbconn, $sql1 );
            $errorinsrt1 =  pg_last_error($dbconn);
            if (!isset($errorinsrt1)){

                echo "Error en insercion de datos";
                var_dump($errorinsrt1);
                exit;
            }

        }; // for interno que recorre el arreglo del resultados 

    }   // fin del if inicio

    if ($longitud <= 1){
        $control = 1;
        echo "entro al if que detiene el proceso. \n";
        break;
        }
} // fin del while control

 ?> 
