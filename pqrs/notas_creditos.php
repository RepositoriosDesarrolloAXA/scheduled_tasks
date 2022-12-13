<?php 

require_once 'conexion/conexion.php'; // base de datos
require_once 'conexion/funtions.php'; // funciones
require_once 'conexion/netsuite.php'; // funciones

//tiempo
date_default_timezone_set('America/Bogota');
$tiempo_inicio = microtime_float();
$fecha_LOG = date('Y-m-d');
$fecha_inicio_log = Date('Y-m-d\TH:i:s');
$valores_actualizados = 0;
$valores_creados = 0;

$resultado_cantidad = conexion_netsuite(2097, 'customsearch_nso_credit_memos_axa', 0, 1);
if($resultado_cantidad > 0){

    $start = 0;
    $end = 0;
    $cantidad_busqueda = 0;

    $cantidad_dividir = round($resultado_cantidad / 1000);
    for ($i=0; $i < $cantidad_dividir ; $i++) {

        $start = ($i == 0) ? 0 : $start + 1000;
        $end = $end + 1000;
        $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 

        $resultado = conexion_netsuite(2089, null, $start, $end);
        $data = json_decode($resultado, true);

        $cantidad_busqueda = $cantidad_busqueda + count($data);

        $informacion = array();

        if(!empty($data)){
            for($a=0; $a<count($data); $a++){

                $y=0;

                $internal_id                    = $data[$a]["values"]["internalid"][$y]["text"];
                $numero                         = $data[$a]["values"]["tranid"];
                $fecha_creacion                 = $data[$a]["values"]["datecreated"];
                $nit_cliente                    = $data[$a]["values"]["entity"][$y]["text"];
                $nombre_cliente                 = str_replace("'", "", $data[$a]["values"]["custbody_axa_field_nomcliente_fjsr"]);
                $direccion_cliente              = str_replace("'", "", $data[$a]["values"]["shipaddress"]);
                $estado                         = $data[$a]["values"]["statusref"][$y]["text"];
                $valor                          = $data[$a]["values"]["amount"];
                $concepto_cliente               = empty($data[$a]["values"]["custbody_axa_camp_concepcliente"]) ? '' : $data[$a]["values"]["custbody_axa_camp_concepcliente"][$y]["text"];
                $factura_aplicada               = $data[$a]["values"]["appliedtotransaction"][$y]["text"];

                //guardando cabeceras
                if(!empty($internal_id)){

                    $sql_validar_cabecera = "SELECT id FROM notas_creditos WHERE internal_id = '".$internal_id."' AND numero = '".$numero."'";
                    $result1 = pg_query( $dbconn, $sql_validar_cabecera );
                    if (pg_num_rows($result1) > 0){
                        
                        //actualiza
                        $sql_query = "UPDATE notas_creditos SET 
                            internal_id = '$internal_id', 
                            numero = '$numero', 
                            fecha_creacion = '$fecha_creacion',
                            nit_cliente = '$nit_cliente',
                            nombre_cliente = '$nombre_cliente',
                            direccion_cliente = '$direccion_cliente',
                            estado = '$estado',
                            valor = $valor,
                            concepto_cliente = '$concepto_cliente',
                            factura_aplicada = '$factura_aplicada',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE internal_id = '".$internal_id."' AND numero = '".$numero."'";

                    } else {

                        //crea
                        $sql_query = "INSERT INTO notas_creditos (internal_id, numero, fecha_creacion, nit_cliente, nombre_cliente, 
                        direccion_cliente, estado, valor, concepto_cliente, factura_aplicada, created_at) 
                        VALUES('$internal_id', '$numero', '$fecha_creacion', '$nit_cliente', '$nombre_cliente', '$direccion_cliente', 
                        '$estado', $valor, '$concepto_cliente', '$factura_aplicada', CURRENT_TIMESTAMP)";

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

            }

        }

    }

    $fecha_fin_log = Date('Y-m-d\TH:i:s');
    $tiempo_fin = microtime_float();
    $tiempo_a = $tiempo_fin - $tiempo_inicio;
    $tiempo = $tiempo_a / 60;

    generarLog($dbconn, "notas_creditos", $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_LOG, $fecha_inicio_log, 
    $fecha_fin_log, $tiempo, "CURRENT_TIMESTAMP");

    print("Actualización 'notas_creditos' finalizada");

}