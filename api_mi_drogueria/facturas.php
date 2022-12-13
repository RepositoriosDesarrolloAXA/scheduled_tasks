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

$resultado_cantidad = conexion_netsuite(2097, 'customsearch_nso_facturas_planillar__128', 0, 1);
if($resultado_cantidad > 0){

    $start = 0;
    $end = 0;
    $cantidad_busqueda = 0;

    $cantidad_for = ceil($resultado_cantidad/1000)*1000;
    $cantidad_dividir = $cantidad_for / 1000;
    for ($i=0; $i < $cantidad_dividir ; $i++) {

        $start = ($i == 0) ? 0 : $start + 1000;
        $end = $end + 1000;
        $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 

        $resultado = conexion_netsuite(2075, null, $start, $end);
        $data = json_decode($resultado, true);

        $cantidad_busqueda = $cantidad_busqueda + count($data);

        $informacion = array();

        if(!empty($data)){
            for($a=0; $a<count($data); $a++){

                $y=0;

                $id_interno                 = $data[$a]["id"];
                $documento                  = $data[$a]["values"]["tranid"];
                $cliente                    = $data[$a]["values"]["custbody_axa_field_nomcliente_fjsr"];
                $nit_cliente                = $data[$a]["values"]["custbody_nso_identi_ov"];
                $vendedor_id                = $data[$a]["values"]["custbody_nso_vendedor_ffvv"][$y]["value"];
                $vendedor                   = $data[$a]["values"]["custbody_nso_vendedor_ffvv"][$y]["text"];
                $clase_id                   = $data[$a]["values"]["class"][$y]["value"];
                $clase                      = $data[$a]["values"]["class"][$y]["text"];
                $ubicacion_id               = $data[$a]["values"]["location"][$y]["value"];
                $ubicacion                  = $data[$a]["values"]["location"][$y]["text"];

                $item_id                    = $data[$a]["values"]["item.internalid"][$y]["text"];;
                $nombre_producto            = $data[$a]["values"]["item.itemid"];
                $codigo_barras              = $data[$a]["values"]["item.upccode"];
                $cantidad                   = $data[$a]["values"]["quantity"];
                $laboratorio                = $data[$a]["values"]["item.manufacturer"];
                $categoria                  = $data[$a]["values"]["item.custitem_nso_axa_field_item_categoria"][$y]["text"];
                $sub_categoria              = $data[$a]["values"]["item.custitem_nso_axa_field_item_subcat"][$y]["text"];
                $precio                     = $data[$a]["values"]["fxrate"];
                $subtotal                   = $data[$a]["values"]["amount"];
                $descuento                  = $data[$a]["values"]["discountamount"];
                $porcentaje_iva             = $data[$a]["values"]["item.custitem_ks_tarifa_de_iva"][$y]["text"];
                $total_iva                  = $data[$a]["values"]["taxamount"];
                $type                       = $data[$a]["values"]["item.type"][$y]["text"];
                $lote                       = $data[$a]["values"]["serialnumber"];
                $fecha_vencimiento          = $data[$a]["values"]["inventoryDetail.expirationdate"];

                $cabeceras[] = $documento;

                //guardando cabeceras
                $sql_validar_cabecera = "SELECT id FROM facturas WHERE id_interno = '".$id_interno."'";
                $result1 = pg_query( $dbconn, $sql_validar_cabecera );
                if (pg_num_rows($result1) > 0){
                    
                    //actualiza
                    $sql_actualizar_cabecera = "UPDATE facturas SET 
                        documento = '$documento', 
                        cliente = '$cliente', 
                        nit_cliente = '$nit_cliente', 
                        vendedor_id = $vendedor_id, 
                        vendedor = '$vendedor', 
                        clase_id = $clase_id, 
                        clase = '$clase', 
                        ubicacion_id = $ubicacion_id, 
                        ubicacion = '$ubicacion', 
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE id_interno = '".$id_interno."'";
                    pg_query( $dbconn, $sql_actualizar_cabecera );

                } else {

                    //crea
                    $sql_crear_cabecera = "INSERT INTO facturas (id_interno, documento, cliente, nit_cliente, vendedor_id, 
                    vendedor, clase_id, clase, ubicacion_id, ubicacion, created_at) 
                    VALUES($id_interno, '$documento', '$cliente', '$nit_cliente', $vendedor_id, 
                    '$vendedor', $clase_id, '$clase', $ubicacion_id, '$ubicacion', CURRENT_TIMESTAMP)";
                    pg_query( $dbconn, $sql_crear_cabecera );

                }

                //guardando detalles
                $sql_validar_detalle = "SELECT id FROM detalles_facturas WHERE factura_id = '".$id_interno."' 
                AND codigo_barras = '".$codigo_barras."' AND nombre_producto = '".$nombre_producto."'";
                $result2 = pg_query( $dbconn, $sql_validar_detalle );
                if ($result2) {
                    if (pg_num_rows($result2) > 0){
                    
                        //actualiza
                        $sql_actualizar_detalle = "UPDATE detalles_facturas SET 
                            factura_id = $id_interno, 
                            item_id = $item_id, 
                            nombre_producto = '$nombre_producto', 
                            codigo_barras = '$codigo_barras', 
                            cantidad = $cantidad, 
                            laboratorio = '$laboratorio', 
                            categoria = '$categoria', 
                            sub_categoria = '$sub_categoria', 
                            precio = $precio, 
                            subtotal = $subtotal, 
                            porcentaje_iva = '$porcentaje_iva', 
                            total_iva = $total_iva, 
                            descuento = $descuento, 
                            type = '$type', 
                            lote = '$lote', 
                            fecha_vencimiento = '$fecha_vencimiento', 
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE factura_id = '".$id_interno."' AND codigo_barras = '".$codigo_barras."' AND nombre_producto = '".$nombre_producto."'";
                        pg_query( $dbconn, $sql_actualizar_detalle );
    
                        $valores_actualizados++;
    
                    } else {
    
                        //crea
                        $sql_crear_detalle = "INSERT INTO detalles_facturas (factura_id, item_id, nombre_producto, codigo_barras, 
                        cantidad, laboratorio, categoria, sub_categoria, precio, subtotal, porcentaje_iva, total_iva, descuento, 
                        type, lote, fecha_vencimiento, created_at) 
                        VALUES($id_interno, $item_id, '$nombre_producto', '$codigo_barras', $cantidad, '$laboratorio', 
                        '$categoria', '$sub_categoria', $precio, $subtotal, '$porcentaje_iva', $total_iva, $descuento, 
                        '$type', '$lote', '$fecha_vencimiento', CURRENT_TIMESTAMP)";
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

    generarLog($dbconn, "facturas", $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_LOG, $fecha_inicio_log, 
    $fecha_fin_log, $tiempo, "CURRENT_TIMESTAMP");

    print("Actualización 'facturas' finalizada");

}