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

$resultado_cantidad = conexion_netsuite(2097, 'customsearch_nso_facturas_planillar___13', 0, 1);
if($resultado_cantidad > 0){

    $start = 0;
    $end = 0;
    $cantidad_busqueda = 0;

    $cantidad_dividir = round($resultado_cantidad / 1000);
    for ($i=0; $i < $cantidad_dividir ; $i++) {

        $start = ($i == 0) ? 0 : $start + 1000;
        $end = $end + 1000;
        $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 

        $resultado = conexion_netsuite(2090, null, $start, $end);
        $data = json_decode($resultado, true);

        $cantidad_busqueda = $cantidad_busqueda + count($data);

        $informacion = array();

        if(!empty($data)){
            for($a=0; $a<count($data); $a++){

                $y=0;

                $internal_id                                    = $data[$a]["id"];
                $nit_laboratorio                                = $data[$a]["values"]["item.custitem_axa_vendormanufacturer_pjcl"][$y]["text"];
                $laboratorio                                    = $data[$a]["values"]["item.manufacturer"];
                $tipo                                           = $data[$a]["values"]["type"][$y]["text"];
                $numero_documento                               = $data[$a]["values"]["tranid"];
                $nit_cliente                                    = $data[$a]["values"]["entity"][$y]["text"];
                $cliente                                        = str_replace("'", "", $data[$a]["values"]["custbody_axa_field_nomcliente_fjsr"]);
                $direccion                                      = str_replace("'", "", $data[$a]["values"]["shippingattention"]);
                $direccion_envio                                = $data[$a]["values"]["shipaddress1"];
                $barrio                                         = $data[$a]["values"]["shipaddress3"];
                $ciudad_envio                                   = $data[$a]["values"]["shipcity"];
                $departamento                                   = $data[$a]["values"]["shipstate"];
                $ubicacion                                      = $data[$a]["values"]["location"][$y]["text"];
                $clase                                          = $data[$a]["values"]["class"][$y]["text"];
                $precio_cliente                                 = $data[$a]["values"]["custbody_nso_precio_cliente"][$y]["text"];
                $metodo_pago                                    = $data[$a]["values"]["custbody_nso_metodo_pago"][$y]["text"];
                $categoria_cliente                              = $data[$a]["values"]["customerMain.category"][$y]["text"];
                $vendedor                                       = $data[$a]["values"]["custbody_nso_vendedor_ffvv"][$y]["text"];
                $nit_caeventa                                   = $data[$a]["values"]["CUSTBODY_AXA_CAMP_VENDE_WF.custentity_nslc_num_identificacion"];
                $caeventa                                       = str_replace("'", "", $data[$a]["values"]["custbody_axa_camp_vende_wf"][$y]["text"]);
                $origen                                         = $data[$a]["values"]["source"][$y]["text"];
                $tipo_origen                                    = $data[$a]["values"]["custbody_nso_ov_type"][$y]["text"];
                $periodo                                        = $data[$a]["values"]["postingperiod"][$y]["text"];
                $fecha                                          = $data[$a]["values"]["trandate"];
                $codigo_barras_item                             = $data[$a]["values"]["item.upccode"];
                $atem                                           = str_replace("'", "", $data[$a]["values"]["item"][$y]["text"]);
                $linea_item                                     = empty($data[$a]["values"]["item.custitem_nso_axa_field_item_linea"]) ? 0 : $data[$a]["values"]["item.custitem_nso_axa_field_item_linea"];
                $embalaje_item                                  = empty($data[$a]["values"]["item.custitem_nso_axa_field_item_fromalm"]) ? 0 : $data[$a]["values"]["item.custitem_nso_axa_field_item_fromalm"];
                $cantidad_vendidad_item                         = empty($data[$a]["values"]["quantity"]) ? 0 : $data[$a]["values"]["quantity"];
                $valor_item                                     = empty($data[$a]["values"]["amount"]) ? 0 : $data[$a]["values"]["amount"];
                $valor_iva_item                                 = empty($data[$a]["values"]["taxamount"]) ? 0 : $data[$a]["values"]["taxamount"];
                $valor_descuento_item                           = empty($data[$a]["values"]["discountamount"]) ? 0 : $data[$a]["values"]["discountamount"];
                $valor_total_con_descuento_sin_iva_item         = empty($data[$a]["values"]["formulacurrency"]) ? 0 : $data[$a]["values"]["formulacurrency"];
                $subcategoria_l                                 = $data[$a]["values"]["item.custitem_nso_axa_field_item_categoria"][$y]["text"];
                $subcategoria_ll                                = $data[$a]["values"]["item.custitem_nso_axa_field_item_subcat"][$y]["text"];
                $subcategoria_lll                               = $data[$a]["values"]["item.custitem_nso_axa_field_item_grupo"][$y]["text"];

                //guardando cabeceras
                if(!empty($internal_id)){

                    $sql_validar_cabecera = "SELECT id FROM ventas_clientes WHERE internal_id = '".$internal_id."' AND numero_documento = '".$numero_documento."' AND codigo_barras_item = '".$codigo_barras_item."' AND item = '".$item."' AND cantidad_vendidad_item = '".$cantidad_vendidad_item."'";
                    $result1 = pg_query( $dbconn, $sql_validar_cabecera );
                    if (pg_num_rows($result1) > 0){
                        
                        //actualiza
                        $sql_query = "UPDATE ventas_clientes SET 
                            internal_id = '$internal_id', 
                            nit_laboratorio = '$nit_laboratorio', 
                            laboratorio = '$laboratorio',
                            tipo = '$tipo',
                            numero_documento = '$numero_documento',
                            nit_cliente = '$nit_cliente',
                            cliente = '$cliente',
                            direccion = '$direccion',
                            direccion_envio = '$direccion_envio',
                            barrio = '$barrio',
                            ciudad_envio = '$ciudad_envio',
                            departamento = '$departamento',
                            ubicacion = '$ubicacion',
                            clase = '$clase',
                            precio_cliente = '$precio_cliente',
                            metodo_pago = '$metodo_pago',
                            categoria_cliente = '$categoria_cliente',
                            vendedor = '$vendedor',
                            nit_caeventa = '$nit_caeventa',
                            caeventa = '$caeventa',
                            tipo_origen = '$tipo_origen',
                            periodo = '$periodo',
                            fecha = '$fecha',
                            codigo_barras_item = '$codigo_barras_item',
                            item = '$item',
                            linea_item = '$linea_item',
                            embalaje_item = $embalaje_item,
                            cantidad_vendidad_item = $cantidad_vendidad_item,
                            valor_item = $valor_item,
                            valor_iva_item = $valor_iva_item,
                            valor_descuento_item = $valor_descuento_item,
                            valor_total_con_descuento_sin_iva_item = $valor_total_con_descuento_sin_iva_item,
                            subcategoria_l = '$subcategoria_l',
                            subcategoria_ll = '$subcategoria_ll',
                            subcategoria_lll = '$subcategoria_lll',
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE internal_id = '".$internal_id."' AND numero_documento = '".$numero_documento."' AND codigo_barras_item = '".$codigo_barras_item."' AND item = '".$item."' AND cantidad_vendidad_item = '".$cantidad_vendidad_item."'";
                        $valores_actualizados++;

                    } else {

                        //crea
                        $sql_query = "INSERT INTO ventas_clientes (internal_id, nit_laboratorio, laboratorio, tipo, 
                        numero_documento, nit_cliente, cliente, direccion, direccion_envio, barrio, ciudad_envio, 
                        departamento, ubicacion, clase, precio_cliente, metodo_pago, categoria_cliente, vendedor, nit_caeventa, 
                        caeventa, origen, tipo_origen, periodo, fecha, codigo_barras_item, item, linea_item, embalaje_item, 
                        cantidad_vendidad_item, valor_item, valor_iva_item, valor_descuento_item, valor_total_con_descuento_sin_iva_item, 
                        subcategoria_l, subcategoria_ll, subcategoria_lll, created_at) 
                        VALUES('$internal_id', '$nit_laboratorio', '$laboratorio', '$tipo', '$numero_documento', 
                        '$nit_cliente', '$cliente', '$direccion', '$direccion_envio', '$barrio', '$ciudad_envio', 
                        '$departamento', '$ubicacion', '$clase', '$precio_cliente', '$metodo_pago', '$categoria_cliente', 
                        '$vendedor', '$nit_caeventa', '$caeventa', '$origen', '$tipo_origen', '$periodo', '$fecha', 
                        '$codigo_barras_item', '$item', '$linea_item', $embalaje_item, $cantidad_vendidad_item, 
                        $valor_item, $valor_iva_item, $valor_descuento_item, $valor_total_con_descuento_sin_iva_item, 
                        '$subcategoria_l', '$subcategoria_ll', '$subcategoria_lll', CURRENT_TIMESTAMP)";
                        $valores_creados++;

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

    generarLog($dbconn, "ventas_clientes", $cantidad_busqueda, $valores_creados, $valores_actualizados, $fecha_LOG, $fecha_inicio_log, 
    $fecha_fin_log, $tiempo, "CURRENT_TIMESTAMP");

    print("Actualización 'ventas_clientes' finalizada");

}