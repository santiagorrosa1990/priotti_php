<?php
session_start();

$json = json_decode(file_get_contents('php://input'));

foreach ($json as $item){
    echo "Codigo " . $item->{'codigo'} . " y Marca: " . $item->{'marca'} . "\n";
}
//It works!