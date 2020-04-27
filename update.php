<?php
session_start();
include "./Modelo/Conexion.php";

$data = json_decode(file_get_contents('php://input', true));
insert($data->{'insert'});
//update($data->{'update'});
//delete($data->{'delete'});
//setDate($data->{'novelties'});

function insert($list)
{
  $query = "INSERT INTO productos(codigo, marca, rubro, aplicacion, precio_lista, precio_oferta, imagen, fecha_agregado, info) values ";
  foreach ($list as $it) {
    $codigo = $it->{'codigo'};
    $descripcion = $it->{'descripcion'};
    $marca = $it->{'marca'};
    $rubro = $it->{'rubro'};
    $precio = $it->{'precio'};
    $precio_oferta = $it->{'precio_oferta'};
    $info = $it->{'info'};
    $imagen = $it->{'imagen'};
    $query = $query . "('$codigo','$marca','$rubro','$descripcion',$precio,$precio_oferta,'$imagen', now(), '$info'),";
    $query = substr($query, 0, -1);
  }
  $query = $query . ' ON DUPLICATE KEY UPDATE ' .
        'marca = VALUES(marca), '.
        'rubro = VALUES(rubro), '.
        'aplicacion = VALUES(aplicacion), '.
        'precio_lista = VALUES(precio_lista), '.
        'precio_oferta = VALUES(precio_oferta), '.
        'imagen = VALUES(imagen), '.
        'fecha_agregado = VALUES(fecha_agregado), '.
        'info = VALUES(info);';
  $conexion = Conexion::conectar();
  $conexion->query($query);
  $conexion->close();
}

function update($list)
{
  $query = "";
  foreach ($list as $it) {
    $codigo = $it->{'codigo'};
    $descripcion = $it->{'descripcion'};
    $marca = $it->{'marca'};
    $rubro = $it->{'rubro'};
    $precio = $it->{'precio'};
    $precio_oferta = $it->{'precio_oferta'};
    $info = $it->{'info'};
    $imagen = $it->{'imagen'};
    $query = $query . "UPDATE productos set marca = '$marca', rubro = '$rubro',
                    aplicacion = '$descripcion', precio_lista = $precio, precio_oferta = $precio_oferta,
                     fecha_modif = now(), imagen = '$imagen', info = '$info' WHERE codigo = '$codigo'; ";
  }
  $conexion = Conexion::conectar();
  $conexion->multi_query($query);
  $conexion->close();
}

function delete($list)
{
  $query = "";
  foreach ($list as $it) {
    $codigo = $it->{'codigo'};
    $query = $query . "DELETE FROM productos WHERE codigo = '$codigo';";
  }
  $conexion = Conexion::conectar();
  $conexion->multi_query($query);
  $conexion->close();
}

function setDate($list)
{
  $string = "";
  foreach ($list as $it){
    $string .= $it . ", ";
  }
  $conexion = Conexion::conectar();
  $conexion->query("INSERT into act_lista(fecha, cambios) values(now(),'$string')");
  $conexion->close();
}
