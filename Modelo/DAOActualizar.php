<?php

session_start();

include "./Conexion.php";

class DAOActualizar
{

    ///ACTUALIZACION NO USA LAS TABLAS MARCA NI RUBROS

    public static function selector()
    {
        if (isset($_POST["opc"])) {
            switch ($_POST["opc"]) {
                case 1:
                    if (self::esAdmin()) {
                        return self::actualizar();
                    }

                    return 'No autorizado';
                    break;
                case 2:
                    if (self::esAdmin()) {
                        return self::actualizarOfertas();
                    }

                    return 'No autorizado';
                    break;
                case 3:
                    return self::getFechasAct();
                    break;
                default:
                    return 'No se eligió opción';
                    break;
            }
        } else {
            return 'No autorizado';
        }
    }

    private static function actualizarOfertas()
    {
        $conexion = Conexion::conectar();
        $faltan = '';
        if (file_exists("../Resources/uploads/ofertas.txt")) {
            $txtofertas = fopen("../Resources/uploads/ofertas.txt", "r") or die("No se puede abrir archivo!");
            $query = "update productos set precio_oferta = 0";
            $conexion->query($query);
            while (!feof($txtofertas)) {
                $aux = trim(fgets($txtofertas));
                if ($aux != '' && strpos($aux, '$') == true) {
                    $arr = explode('$', $aux);
                    $codigo = trim($arr[0]);
                    $oferta = str_replace(',', '.', str_replace('.', '', $arr[1]));
                    $query = "select codigo from productos where codigo = '$codigo'";
                    $result = $conexion->query($query);
                    if ($result->num_rows == 0) {
                        $faltan = $faltan . $codigo . '--$' . $oferta . '<br>';
                    } else {
                        $query = "update productos set precio_oferta = $oferta where codigo = '$codigo'";
                        $conexion->query($query);
                    }
                }
            }
            fclose($txtofertas);
            $query = "insert into act_oferta(fecha) values(now())";
            $conexion->query($query);
            self::vaciaruploads();
            return 'Faltan: <br>' . $faltan;
        } else {
            self::vaciaruploads();
            return 'No se encuentra "ofertas.txt"';
        }
        $conexion->free();
        $conexion->close();
    }

    private static function getFechasAct()
    {
        $conexion = Conexion::conectar();
        $query = "select max(fecha) as fecha from act_oferta";
        $result = $conexion->query($query);
        $row = $result->fetch_assoc();
        $foferta = $row["fecha"];
        $result->free();
        $query = "select max(fecha) as fecha from act_lista";
        $result = $conexion->query($query);
        $row = $result->fetch_assoc();
        $flista = $row["fecha"];
        $result->free();
        $conexion->close();
        $fechas = array($foferta, $flista); //Se devuelven las fechas de las actualizaciones últimas
        return json_encode($fechas);

    }

    private static function vaciaruploads()
    {
        $files = glob('../Resources/uploads/*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file);
            }
            // delete file
        }
    }

    private static function esAdmin()
    {
        if (isset($_SESSION["usuario"]) && $_SESSION["es_admin"] == 1) {
            return true;
        }

        return false;
    }

    private static function esUsuario()
    {
        if (isset($_SESSION["usuario"])) {
            return true;
        }

        return false;

    }

    private static function listaRubros()
    {
        $txtrubros = fopen("../Resources/uploads/arubrosx.txt", "r") or die("No se puede abrir archivo!");
        $listarubros = [];
        $n = 0;
        while (!feof($txtrubros)) {
            $aux = fgets($txtrubros);
            if ($aux != "") {
                $id_rubro = substr($aux, 0, 7);
                $desc = utf8_decode(str_replace("'", "`", substr($aux, 7)));
                // $listarubros[] = ['id_rubro'=>$id_rubro, 'desc'=>$desc];
                $listarubros[$id_rubro] = $desc;
            }
        }
        fclose($txtrubros);
        return $listarubros;
    }

    private static function listaMarcas()
    {
        $txtmarcas = fopen("../Resources/uploads/alineasx.txt", "r") or die("No se puede abrir archivo!");
        $listamarcas = [];
        $n = 0;
        while (!feof($txtmarcas)) {
            $aux = fgets($txtmarcas);
            if ($aux != "") {
                $id_marca = substr($aux, 0, 4);
                $nombre = rtrim(str_replace('"', '', str_replace(' ', '', substr($aux, 4))));
                if ($nombre == "ARTICULOSSINSTOCK") {
                    $nombre = "3M";
                } else if ($nombre == "FUSIBLESFICHADESNUDOS") {
                    $nombre = "GEN-ROD";
                }
                //$listamarcas[] =  ['id_marca'=>$id_marca, 'nombre'=>$nombre];
                $listamarcas[$id_marca] = $nombre;
            }
        }
        fclose($txtmarcas);
        return $listamarcas;
    }

    private static function listaPrecios()
    {
        $txtprecios = fopen("../Resources/uploads/aprecios.txt", "r") or die("No se puede abrir archivo!");
        $listaprecios = [];
        $n = 0;
        while (!feof($txtprecios)) {
            $aux = fgets($txtprecios);
            if ($aux != "") {
                $rubro = substr($aux, 0, 7); //Se borran los ceros a la izq
                $marca = substr($aux, 0, 4);
                $codigo = rtrim(substr($aux, 7, 20));
                $desc = utf8_decode(str_replace("'", "`", substr($aux, 27, 35)));
                $precio = substr($aux, 62, 10);
                $precio = substr($precio, 0, -2) . "." . substr($precio, -2); //La coma
                $precio = ltrim($precio, "0");
                $imagen = str_replace(" ", "_", str_replace("/", "-", strtolower($codigo)));
                if (!array_key_exists($codigo, $listaprecios)) { //Omito los duplicados que estan en el aprecios
                    $listaprecios[$codigo] = ["codigo" => $codigo, "rubro" => $rubro, "marca" => $marca, "descripcion" => $desc, "precio" => $precio, "imagen" => $imagen];
                }
            }
        }
        fclose($txtprecios);
        return $listaprecios;
    }

    private static function armarLista()
    {
        $n = 0;
        $listarubros = self::listaRubros();
        $listamarcas = self::listaMarcas();
        $listaprecios = self::listaPrecios();

        foreach ($listaprecios as $pos => $item) {
            $id_marca = $item["marca"];
            $listaprecios[$pos]["marca"] = $listamarcas[$id_marca];
            $id_rubro = $item["rubro"];
            $listaprecios[$pos]["rubro"] = $listarubros[$id_rubro];
        }
        return $listaprecios;
    }

    private static function listaBd()
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

    private static function importar()
    {
        $act = 0;
        $nue = 0;
        $batchInsertQuery = "insert into productos(codigo, marca, rubro, aplicacion, precio_lista, imagen, fecha_agregado) values ";
        $batchUpdateQuery = "";
        $conexion = Conexion::conectar();
        $start = microtime(true);
        $listaimp = self::armarLista();
        $impListSeconds = round((microtime(true) - $start), 2);
        $start = microtime(true);
        $listabd = self::listaBd();
        $bdListSeconds = round((microtime(true) - $start), 2);
        $start = microtime(true);
        foreach ($listaimp as $imp) {
            $codigo = $imp['codigo'];
            $marca = $imp['marca'];
            $rubro = $imp['rubro'];
            $desc = $imp['descripcion'];
            $precio = $imp['precio'];
            $imagen = $imp['imagen'];
            if (!array_key_exists($codigo, $listabd)) { //Si el producto no existe en la bd, se inserta
                $nue++;
                //$query = "insert into productos(codigo, marca, rubro, aplicacion, precio_lista, imagen, fecha_agregado)
                //values ('$codigo','$marca','$rubro','$desc',$precio,'$imagen', now());";
                $batchInsertQuery = $batchInsertQuery . "('$codigo','$marca','$rubro','$desc',$precio,'$imagen', now()),";
                //$conexion->query($query);
            } else {
                $dbItem = $listabd[$codigo];
                $listabd[$codigo]['vigente'] = 1;
                if (self::areNotEqual($dbItem, $imp)) {
                    $act++;
                    //$query = "update productos set marca = '$marca', rubro = '$rubro',
                     //       aplicacion = '$desc', precio_lista = " . $precio . ", fecha_modif = now(), imagen = '$imagen' where codigo = '$codigo';";
                    $batchUpdateQuery = $batchUpdateQuery . "update productos set marca = '$marca', rubro = '$rubro',
                    aplicacion = '$desc', precio_lista = $precio, fecha_modif = now(), imagen = '$imagen' where codigo = '$codigo'; ";
                    //$conexion->query($query);
                }
            }
        }
        $batchInsertQuery = substr($batchInsertQuery, 0, -1); //Quito la ultima coma
        $conexion->query($batchInsertQuery);
        $conexion->multi_query($batchUpdateQuery);
        $updateSeconds = round((microtime(true) - $start), 2);
        $start = microtime(true);
        self::disableInvalids($listabd, $conexion);
        $inactives = round((microtime(true) - $start), 2);
        $query = "insert into act_lista(fecha) values(now())";
        echo $nue . ' nuevos.<br>' . $act . ' actualizados.<br>';
        echo "Lista externa: $impListSeconds segundos.<br>";
        echo "Lista bd: $bdListSeconds segundos.<br>";
        echo "Actualizacion: $updateSeconds segundos.<br>";
        echo "No vigentes: $inactives segundos.<br>";
        $conexion->query($query);
        $conexion->close();
    }

    private static function disableInvalids($listabd, $conexion)
    {
        $query = "update productos set vigente = 1"; //Pongo todos vigentes y despues anulo uno por uno
        $conexion->query($query);
        foreach ($listabd as $item) {
            if ($item['vigente'] == 0) {
                $codigo = $item['codigo'];
                $batchQuery = $batchQuery . "update productos set vigente = 0 where codigo = '$codigo'; ";
                //$conexion->query($query);
            }
        }
        $conexion->multi_query($batchQuery);
    }

    private static function areNotEqual($db, $im)
    {
        return !($db['marca'] == $im['marca'] &&
            $db['aplicacion'] == $im['descripcion'] &&
            $db['rubro'] == $im['rubro'] &&
            $db['precio_lista'] == $im['precio']);
    }

    private static function guardarCopia()
    {
        $newfile = 'aprecios-' . date('Y-m-d') . '.txt';
        copy('../Resources/uploads/aprecios.txt', '../Resources/listas/' . $newfile);
    }

    private static function actualizar()
    {
        if (file_exists("../Resources/uploads/aprecios.txt") && file_exists("../Resources/uploads/alineasx.txt") && file_exists("../Resources/uploads/arubrosx.txt")) {
            $start = microtime(true);
            self::importar();
           // self::guardarCopia();
           // self::vaciaruploads();
            $time_elapsed_secs = round((microtime(true) - $start), 2);
            return "Lista actualizada en: $time_elapsed_secs segundos";
        } else {
            self::vaciaruploads();
            return "Verifique que estén los 3 archivos necesarios!";
        }
    }
}

echo DAOActualizar::selector();
