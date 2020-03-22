<?php
session_start();
include "./Modelo/Conexion.php";

$json = json_decode(file_get_contents('php://input'));
$updateList = $json->{'update'};
$insertList = $json->{'insert'};
$batchUpdateQuery = "";
$batchInsertQuery = "";

foreach ($updateList as $item) {
  $marca = $item->{'marca'};
  $descripcion = $item->{'descripcion'};
  $rubro = $item->{'rubro'};
  $precio = $item->{'precio'};
  $info = $item->{'info'};
  $codigo = $item->{'codigo'};
  $imagen = strtolower($codigo);
  $batchUpdateQuery = $batchUpdateQuery . "update productos set marca = '$marca', rubro = '$rubro'," .
    " aplicacion = '$descripcion', precio_lista = $precio, fecha_modif = now(), imagen = '$imagen', info = '$info' where codigo = '$codigo'; ";
}

foreach ($insertList as $item) {
  $batchInsertQuery = "insert into productos(codigo, marca, rubro, aplicacion, precio_lista, imagen, fecha_agregado, info) values ";
  $marca = $item->{'marca'};
  $descripcion = $item->{'descripcion'};
  $rubro = $item->{'rubro'};
  $precio = $item->{'precio'};
  $info = $item->{'info'};
  $codigo = $item->{'codigo'};
  $imagen = strtolower($codigo);
  $batchInsertQuery = $batchInsertQuery . "('$codigo','$marca','$rubro','$descripcion',$precio,'$imagen', now(), '$info'),";
}
$batchInsertQuery = substr($batchInsertQuery, 0, -1); //Quito la ultima coma

$conexion = Conexion::conectar();
$conexion->multi_query($batchUpdateQuery);
$conexion->close();
$conexion = Conexion::conectar();
$conexion->query($batchInsertQuery);
$conexion->close();

$jsonString = json_encode($json);
$json->{'insert_query'} = json_encode($batchInsertQuery);
$json->{'update_query'} = json_encode($batchUpdateQuery);
echo json_encode($json);
