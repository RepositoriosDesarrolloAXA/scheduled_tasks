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

$resultado_cantidad = conexion_netsuite(2097, 'customsearch4973', 0, 1);
if($resultado_cantidad > 0){

    $start = 0;
    $end = 0;
    $cantidad_busqueda = 0;

    $cantidad_dividir = round($resultado_cantidad / 1000);
    for ($i=0; $i < $cantidad_dividir ; $i++) {

        $start = ($i == 0) ? 0 : $start + 1000;
        $end = $end + 1000;
        $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 

        $resultado = conexion_netsuite(2088, null, $start, $end);
        $data = json_decode($resultado, true);

        $cantidad_busqueda = $cantidad_busqueda + count($data);

        $informacion = array();

        if(!empty($data)){
            for($a=0; $a<count($data); $a++){

                $y=0;

                $internal_id                = $data[$a]["values"]["GROUP(CUSTITEM_AXA_VENDORMANUFACTURER_PJCL.internalid)"][$y]["text"];
                $nit                        = $data[$a]["values"]["GROUP(custitem_axa_vendormanufacturer_pjcl)"][$y]["text"];
                $laboratorio                = $data[$a]["values"]["GROUP(manufacturer)"];
                $linea                      = $data[$a]["values"]["GROUP(custitem_nso_axa_field_item_linea)"];

                $informacion[] = "($item_id, '$nombre', $ubicacion_id, '$ean', $disponible, CURRENT_TIMESTAMP)";

            }

            //guardando cabeceras
            if(!empty($internal_id)){

                $sql_validar_cabecera = "SELECT id FROM laboratorios WHERE internal_id = '".$internal_id."'";
                $result1 = pg_query( $dbconn, $sql_validar_cabecera );
                if (pg_num_rows($result1) > 0){
                    
                    //actualiza
                    $sql_actualizar_cabecera = "UPDATE laboratorios SET 
                        internal_id = '$internal_id', 
                        nit = '$nit', 
                        nombre = '$laboratorio',
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE internal_id = '".$internal_id."'";
                    pg_query( $dbconn, $sql_actualizar_cabecera );

                } else {

                    //crea
                    $sql_crear_cabecera = "INSERT INTO laboratorios (internal_id, nit, nombre, created_at) 
                    VALUES($internal_id, '$nit', '$laboratorio', CURRENT_TIMESTAMP)";
                    pg_query( $dbconn, $sql_crear_cabecera );

                }

                //guardando detalles
                $sql_validar_detalle = "SELECT id FROM lineas WHERE laboratorio_id = '".$internal_id."' 
                AND nombre = '".$linea."'";
                $result2 = pg_query( $dbconn, $sql_validar_detalle );
                if ($result2) {
                    if (pg_num_rows($result2) > 0){
                    
                        //actualiza
                        $sql_actualizar_detalle = "UPDATE lineas SET 
                            laboratorio_id = '$internal_id', 
                            nombre = '$linea', 
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE laboratorio_id = '".$internal_id."' AND nombre = '".$linea."'";
                        pg_query( $dbconn, $sql_actualizar_detalle );
    
                        $valores_actualizados++;
    
                    } else {
    
                        //crea
                        $sql_crear_detalle = "INSERT INTO lineas (laboratorio_id, nombre, created_at) 
                        VALUES('$internal_id', '$linea', CURRENT_TIMESTAMP)";
                        pg_query( $dbconn, $sql_crear_detalle );
    
                        $valores_creados++;
    
                    }    
                }

            }
        }

    }

    $fecha_fin_log = Date('Y-m-d\TH:i:s');
    $tiempo_fin = microtime_float();
    $tiempo_a = $tiempo_fin - $tiempo_inicio;
    $tiempo = $tiempo_a / 60;

    generarLog($dbconn, "laboratorios", $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_LOG, $fecha_inicio_log, 
    $fecha_fin_log, $tiempo, "CURRENT_TIMESTAMP");

    print("Actualización 'laboratorios' finalizada");

}