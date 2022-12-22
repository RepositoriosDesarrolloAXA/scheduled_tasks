<?php

require_once 'conexion.php'; // base de datos
require_once 'Oauth256.php'; // autenticacion con netsuite
require_once 'funtions.php'; // funciones

error_reporting(E_ALL ^ E_WARNING);
header('Content-type: text/plain; charset=UTF-8');

    $control = 0;
    $inicio=0;
    $start=1;
    $end=1000;
    $valores_creados = 0;
    $valores_actualizados = 0;
    $cantidad_registros = 0;
    $data_array = array();
    //tiempo
    date_default_timezone_set('America/Bogota');
//  $tiempo_inicio = microtime_float();//
    $fecha_LOG = date('Y-m-d');
    $fecha_inicio_log = Date('Y-m-d\TH:i:s');
    $sql1 = "TRUNCATE public.dpps_netsuites RESTART IDENTITY";
    $result1 = pg_query( $dbconn, $sql1 );
    while($control == 0 ){ 
        if ($inicio== 0){
            $url =
            "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=2032&deploy=1&start=$start&end=$end";
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
        $data = json_decode($respuesta,true); // array de los registros del restlet
        //echo var_dump($data);
       $longitud = count($data);
        echo "Longitud:".$longitud."\n";
        //$cantidad_registros += $longitud;
 
      
        // Echo" total de datos ".$reg."\n";
    if ($control == 0){
            $j=1000;
            $v=1;
            $start=$end;
            $end=$end+$j;
            $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 
            for ($i=0; $i <$longitud ; $i++) {
                $data_formula= json_decode($data[$i]["values"]["custbody_axa_iv_info"],true);

                if($data_formula["descuentos"]!=NULL)
                { 
                   $id=$data[$i]["id"];
                   $data1_fecha= $data_formula["descuentos"][0]["date1"];
                   $data1_descuento= $data_formula["descuentos"][0]["discount1"];
                   $data1_total= $data_formula["descuentos"][0]["total1"];
                   $data1_porcentage= $data_formula["descuentos"][0]["porc1"];
                   $data2_fecha= $data_formula["descuentos"][0]["date2"];
                   $data2_descuento= $data_formula["descuentos"][0]["discount2"];
                   $data2_total= $data_formula["descuentos"][0]["total2"];
                   $data2_porcentage= $data_formula["descuentos"][0]["porc2"];
                   $data3_fecha= $data_formula["descuentos"][0]["date3"];
                   $data3_descuento= $data_formula["descuentos"][0]["discount3"];
                   $data3_total= $data_formula["descuentos"][0]["total3"];
                   $data3_porcentage= $data_formula["descuentos"][0]["porc3"];
                   $data4_fecha= $data_formula["descuentos"][0]["date4"];
                   $data4_descuento= $data_formula["descuentos"][0]["discount4"];
                   $data4_total= $data_formula["descuentos"][0]["total4"];
                   $data4_porcentage= $data_formula["descuentos"][0]["porc4"];
                   $data5_fecha= $data_formula["descuentos"][0]["date5"];
                   $data5_descuento= $data_formula["descuentos"][0]["discount5"];
                   $data5_total= $data_formula["descuentos"][0]["total5"];
                   $data5_porcentage= $data_formula["descuentos"][0]["porc5"];
                   $data_dg= json_decode($data[$i]["values"]["CUSTBODY_NSO_ZONA_GEOGRAFICA.custrecord_nso_dias_administrativos"],true);
                   //$fechacurrent = Date('Y-m-d\TH:i:s');
   
                   if($data_dg==''){
                      $data_dg=0;
                   }
                   if($data1_fecha=="''"){
                    $data1_fecha=date('Y-m-d');
                    $data1_total=0;
                    $data1_porcentage=0;
                    $data1_descuento=0;
                   }
//                 echo "fec:".$data2_fecha." ".$data3_fecha." ".$data4_fecha." ".$data5_fecha;
                   if($data2_fecha=="''"){
                    $data2_fecha=date('Y-m-d');
                    $data2_total=0;
                    $data2_porcentage=0;
                    $data2_descuento=0;
                   }
                   if($data3_fecha=="''"){
                    $data3_fecha=date('Y-m-d');
                    $data3_total=0;
                    $data3_porcentage=0;
                    $data3_descuento=0;
                   }
                   if($data4_fecha=="''"){
                    $data4_fecha=date('Y-m-d');
                    $data4_total=0;
                    $data4_porcentage=0;
                    $data4_descuento=0;
                   }
                   if($data5_fecha=="''"){
                    $data5_fecha=date('Y-m-d');
                    $data5_total=0;
                    $data5_porcentage=0;
                    $data5_descuento=0;
                   }
                $fecha_insertar = Date('Y-m-d\TH:i:s');
                   $sql1= "SELECT count(*) FROM public.dpps_netsuites WHERE factura_id=".$id."";
                    $count = pg_query($dbconn, $sql1);
                    if($count==0){
                        $sql_insert="INSERT INTO public.dpps_netsuites (
                        id_factura,
                        fecha_1, 
                        total_1, 
                        porcentage_1, 
                        descuento_1, 
                        fecha_2,
                        total_2, 
                        porcentage_2, 
                        descuento_2, 
                        fecha_3, 
                        total_3,
                        porcentage_3,
                        descuento_3, 
                        fecha_4, 
                        total_,
                        porcentage_4,
                        descuento_4, 
                        fecha_5, 
                        total_5, 
                        porcentage_5,
                        descuento_5, 
                        valorbasedpp,
                        dias_gracia,
                        created_at
                    )
                    VALUES 
                    (   
                        $id,

                        '$data1_fecha',
                        $data1_total,
                        $data1_porcentage,
                        $data1_descuento,

                        '$data2_fecha',
                        $data2_total,
                        $data2_porcentage,
                        $data2_descuento,

                        '$data3_fecha',
                        $data3_total,
                        $data3_porcentage,
                        $data3_descuento,

                        '$data4_fecha',
                        $data4_total,
                        $data4_porcentage,
                        $data4_descuento,

                        '$data5_fecha',
                        $data5_total,
                        $data5_porcentage,
                        $data5_descuento,
                        '0',
                        $data_dg,
                        '$fecha_insertar'
                    )";
                    //print(json_encode($sql_insert)."\n\n");
                     $result1 = pg_query($dbconn, $sql_insert);
                     $errorinsrt1 = pg_last_error($dbconn);
                   	 print($errorinsrt1."\n");
                }
               
            
            }
        }
        

        }   // fin del if inicio
        if ($longitud <= 1){
        $control = 1;
             break;
        } 
    } 
?> 
