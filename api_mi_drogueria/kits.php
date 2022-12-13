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

$resultado_cantidad = conexion_netsuite(2097, 'customsearch_axa_kits_ecom_3', 0, 1);
if($resultado_cantidad > 0){

    $start = 0;
    $end = 0;
    $cantidad_busqueda = 0;

    $cantidad_dividir = round($resultado_cantidad / 1000);
    for ($i=0; $i < $cantidad_dividir ; $i++) {

        $start = ($i == 0) ? 0 : $start + 1000;
        $end = $end + 1000;
        $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 

        $resultado = conexion_netsuite(2076, null, $start, $end);
        $data = json_decode($resultado, true);

        $cantidad_busqueda = $cantidad_busqueda + count($data);

        $informacion = array();

        if(!empty($data)){
            for($a=0; $a<count($data); $a++){

                $y=0;

                $internal_id                = $data[$a]["values"]["internalid"][$y]["text"];
                $nombre_kit                 = $data[$a]["values"]["itemid"];
                $linea                      = $data[$a]["values"]["custitem_nso_axa_field_item_linea"];
                $codigo_barras              = $data[$a]["values"]["memberItem.upccode"];
                $laboratorio                = $data[$a]["values"]["department"][$y]["text"];
                $descripcion                = $data[$a]["values"]["salesdescription"];
                $member_item                = $data[$a]["values"]["memberitem"][$y]["text"];

                //crea
                $sql_crear_detalle = "INSERT INTO kits (internal_id, nombre_kit, linea, 
                codigo_barras, laboratorio, descripcion, member_item, created_at) 
                VALUES('$internal_id', '$nombre_kit', '$linea', '$codigo_barras', '$laboratorio', 
                '$descripcion', '$member_item', CURRENT_TIMESTAMP)";
                pg_query( $dbconn, $sql_crear_detalle );

                $valores_creados++;

            }

        }

    }

    $fecha_fin_log = Date('Y-m-d\TH:i:s');
    $tiempo_fin = microtime_float();
    $tiempo_a = $tiempo_fin - $tiempo_inicio;
    $tiempo = $tiempo_a / 60;

    generarLog($dbconn, "kits", $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_LOG, $fecha_inicio_log, 
    $fecha_fin_log, $tiempo, "CURRENT_TIMESTAMP");

    print("Actualización 'kits' finalizada");

}