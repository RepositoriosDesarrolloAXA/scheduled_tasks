<?php

    $dbconn = pg_connect("host=192.168.1.48 dbname=api_pqrs user=desarrolloaxa password=Ax4Desarr0ll0")
    or die('No se ha podido conectar: ' . pg_last_error());