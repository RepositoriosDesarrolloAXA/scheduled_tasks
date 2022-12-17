<?php

    $dbconn = pg_connect("host=172.16.1.6 dbname=toma_pedidos user=desarrolloaxa password=Ax4Desarr0ll0")
    or die('No se ha podido conectar: ' . pg_last_error());

	// $dbconn_pruebas = pg_connect("host=192.168.1.48 dbname=toma_pedidos_prueba user=desarrolloaxa password=Ax4Desarr0ll0")
    // or die('No se ha podido conectar: ' . pg_last_error());