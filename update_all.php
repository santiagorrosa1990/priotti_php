<?php
session_start();
include "./Modelo/Conexion.php";

$jsonList = json_decode(file_get_contents('php://input'));

$batchUpdateQuery = "";
$batchInsertQuery = "";
$cont = 0;
foreach ($jsonList as $item) {
  if ($cont > 4500) {
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
  $cont = $cont + 1;
}
$cont = 0;
foreach ($jsonList as $item) {
  $batchInsertQuery = "insert into productos(codigo, marca, rubro, aplicacion, precio_lista, imagen, fecha_agregado, info) values ";
  if ($cont > 4500) {
    $marca = $item->{'marca'};
    $descripcion = $item->{'descripcion'};
    $rubro = $item->{'rubro'};
    $precio = $item->{'precio'};
    $info = $item->{'info'};
    $codigo = $item->{'codigo'};
    $imagen = strtolower($codigo);
    $batchInsertQuery = $batchInsertQuery . "('$codigo','$marca','$rubro','$descripcion',$precio,'$imagen', now(), '$info'),";
  }
  $cont = $cont + 1;
}
$batchInsertQuery = substr($batchInsertQuery, 0, -1); //Quito la ultima coma

$conexion = Conexion::conectar();
$conexion->multi_query($batchUpdateQuery);
$conexion->close();
echo json_encode($batchUpdateQuery);
//$conexion = Conexion::conectar();
//$conexion->query($batchInsertQuery);
//$conexion->close();
