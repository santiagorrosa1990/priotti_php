<?php
session_start();
include "./Modelo/Conexion.php";

$jsonList = json_decode(file_get_contents('php://input'));

$batchUpdateQuery = "";
$batchInsertQuery = "";

$bdList = listaBd();

foreach ($jsonList as $item) {
  $codigo = $item->{'codigo'};
  if ($bdList[$codigo] != null && $bdList['precio_lista'] != $item->{'precio'}) {
    $marca = $item->{'marca'};
    $descripcion = $item->{'descripcion'};
    $rubro = $item->{'rubro'};
    $precio = $item->{'precio'};
    $precio_oferta = $item->{'precio_oferta'};
    $info = $item->{'info'};
    $codigo = $item->{'codigo'};
    $imagen = strtolower($codigo);
    $batchUpdateQuery = $batchUpdateQuery . "update productos set marca = '$marca', rubro = '$rubro'," .
      " aplicacion = '$descripcion', precio_lista = $precio, precio_oferta = $precio_oferta, fecha_modif = now(), imagen = '$imagen', info = '$info' where codigo = '$codigo'; ";
  }
}

foreach ($jsonList as $item) {
  $codigo = $item->{'codigo'};
  if ($bdList[$codigo] == null) {
    $batchInsertQuery = "insert into productos(codigo, marca, rubro, aplicacion, precio_lista, precio_oferta, imagen, fecha_agregado, info) values ";
    $marca = $item->{'marca'};
    $descripcion = $item->{'descripcion'};
    $rubro = $item->{'rubro'};
    $precio = $item->{'precio'};
    $precio_oferta = $item->{'precio_oferta'};
    $info = $item->{'info'};
    $codigo = $item->{'codigo'};
    $imagen = strtolower($codigo);
    $batchInsertQuery = $batchInsertQuery . "('$codigo','$marca','$rubro','$descripcion',$precio,$precio_oferta,'$imagen', now(), '$info'),";
  }
}
$batchInsertQuery = substr($batchInsertQuery, 0, -1); //Quito la ultima coma

$conexion = Conexion::conectar();
$conexion->multi_query($batchUpdateQuery);
$conexion->close();
echo json_encode($batchUpdateQuery);
//$conexion = Conexion::conectar();
//$conexion->query($batchInsertQuery);
//$conexion->close();


function listaBd()
{
  $conexion = Conexion::conectar();
  $itemMap = [];
  $query = "select codigo, marca, rubro, aplicacion, precio_lista, imagen from productos";
  $resultset = $conexion->query($query);
  while ($row = $resultset->fetch_assoc()) {
    $row['vigente'] = 0;
    $itemMap[$row["codigo"]] = $row;
  }
  $resultset->free();
  $conexion->close();
  return $itemMap;
}
