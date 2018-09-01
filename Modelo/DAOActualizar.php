<?php 

session_start();

include "./Conexion.php";

class DAOActualizar {   

    ///ACTUALIZACION NO USA LAS TABLAS MARCA NI RUBROS

    public static function selector(){        
            if(isset($_POST["opc"])){                
                switch($_POST["opc"]){
                    case 1:
                    if(self::esAdmin()) return self::actualizar();            
                    return 'No autorizado'; 
                    break;  
                    case 2:
                    if(self::esAdmin()) return self::actualizarOfertas();                       
                    return 'No autorizado'; 
                    break;
                    case 3:
                    return self::getFechasAct();
                    break;                                         
                    default: 
                    return 'No se eligió opción';                   
                    break;
                }   
            }else{
               return 'No autorizado'; 
            }                           
    } 

    private static function actualizarOfertas(){
        $conexion = Conexion::conectar();
        $faltan = '';             
            if(file_exists("../Resources/uploads/ofertas.txt")){
                $txtofertas = fopen("../Resources/uploads/ofertas.txt", "r") or die("No se puede abrir archivo!");                        
                $query = "update productos set precio_oferta = 0"; 
                $conexion->query($query);  
                while(!feof($txtofertas)) {                             
                    $aux = trim(fgets($txtofertas));
                    if($aux != '' && strpos($aux, '$') == true){                                 
                        $arr = explode('$',$aux);               
                        $codigo = trim($arr[0]);
                        $oferta = str_replace(',', '.', str_replace('.', '', $arr[1]));
                        $query = "select codigo from productos where codigo = '$codigo'";
                        $result = $conexion->query($query);  
                        if($result->num_rows==0){
                            $faltan = $faltan.$codigo.'--$'.$oferta.'<br>';
                        }else{
                            $query = "update productos set precio_oferta = $oferta where codigo = '$codigo'";                 
                            $conexion->query($query);    
                        }                     
                    }              
                }                 
                fclose($txtofertas);
                $query = "insert into act_oferta(fecha) values(now())";
                $conexion->query($query);
                self::vaciaruploads();                                    
                return 'Faltan: <br>'.$faltan;            
            }else{   
                self::vaciaruploads();     
                return 'No se encuentra "ofertas.txt"';
            }            
        $conexion->free();   
        $conexion->close();  
    }

    private static function getFechasAct(){
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
        $fechas = array($foferta,$flista); //Se devuelven las fechas de las actualizaciones últimas
        return json_encode($fechas); 
        
    }

    private static function vaciaruploads(){
        $files = glob('../Resources/uploads/*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file)) unlink($file); // delete file
        }    
    }

    private static function esAdmin(){
        if (isset($_SESSION["usuario"]) && $_SESSION["es_admin"]==1) return true; 
        return false;        
    }

    private static function esUsuario(){
        if (isset($_SESSION["usuario"])) return true;
        return false;               
        
    }

    private static function listaRubros(){
        $txtrubros = fopen("../Resources/uploads/arubrosx.txt", "r") or die("No se puede abrir archivo!");  
        $listarubros = [];       
        $n = 0;
        while(!feof($txtrubros)) {                             
            $aux = fgets($txtrubros);
            if($aux != ""){           
                $id_rubro = substr($aux, 0, 7);
                $desc =  utf8_decode(str_replace("'", "`",substr($aux, 7))); 
                $listarubros[$id_rubro] = $desc;
            }                
        }
        fclose($txtrubros);
        return $listarubros;
    }    

    private static function listaMarcas(){
        $txtmarcas = fopen("../Resources/uploads/alineasx.txt", "r") or die("No se puede abrir archivo!");
        $listamarcas = [];
        $n = 0;
        while(!feof($txtmarcas)) {            
            $aux = fgets($txtmarcas); 
            if($aux != ""){     
                $id_marca = substr($aux, 0, 4);        
                $nombre = rtrim(str_replace('"', '', str_replace(' ', '',substr($aux, 4))));
                if($nombre == "ARTICULOSSINSTOCK"){
                    $nombre = "3M";
                }else if($nombre == "FUSIBLESFICHADESNUDOS"){
                    $nombre = "GEN-ROD";
                }                  
            $listamarcas[] =  ['id_marca'=>$id_marca, 'nombre'=>$nombre];  
            }                        
        } 
        fclose($txtmarcas);
        return $listamarcas;          
    }

    private static function listaPrecios(){
        $txtprecios = fopen("../Resources/uploads/aprecios.txt", "r") or die("No se puede abrir archivo!");
        $listaprecios = [];
        $n = 0;
        while(!feof($txtprecios)) {
            $aux = fgets($txtprecios);      
            if($aux != ""){
                $rubro = substr($aux, 0, 7); //Se borran los ceros a la izq
                $marca = substr($aux, 0, 4);
                $codigo = rtrim(substr($aux, 7, 20));
                $desc = utf8_decode(str_replace("'", "`",substr($aux, 27, 35)));            
                $precio = substr($aux, 62, 10);
                $precio = substr($precio, 0, -2).".".substr($precio, -2); //La coma 
                $precio = ltrim($precio, "0");            
                $imagen = str_replace(" ", "_", str_replace("/", "-", strtolower($codigo)));                  
                $key = array_search( $codigo, array_column($listaprecios, 'codigo'), true); //Modo estricto true               
                if($key === FALSE){ //Omito los duplicados que estan en el aprecios
                    $listaprecios[] = ["codigo"=>$codigo, "rubro"=>$rubro, "marca"=>$marca, "descripcion"=>$desc, "precio"=>$precio, "imagen"=>$imagen]; 
                }                                      
            }           
        }
        fclose($txtprecios);
        return $listaprecios;        
    }

    private static function armarLista(){
        $n = 0;
        $listarubros = self::listaRubros();
        $listamarcas = self::listaMarcas();
        $listaprecios = self::listaPrecios();       
        
        foreach($listaprecios as $pos => $item){
        $key = array_search($item["marca"], array_column($listamarcas, 'id_marca'));        
        $listaprecios[$pos]["marca"] = $listamarcas[$key]["nombre"];
        $key = array_search($item["rubro"], array_column($listarubros, 'id_rubro'));        
        $listaprecios[$pos]["rubro"] = $listarubros[$key]["desc"];                      
        }    
        return $listaprecios;
    }

    private static function listaBd(){
        $conexion = Conexion::conectar();
        $itemMap = [];
        $query = "select codigo, marca, rubro, aplicacion, precio_lista, imagen from productos";
        $resultset = $conexion->query($query);
        while($row = $resultset->fetch_assoc()) {            
            //$listabd[] = $row;
            $itemMap[$row["codigo"]] = $row;
        }
        //$query = "update productos set novedad = 0"; //Pongo las novedades a 0
        //$conexion->query($query);
        $resultset->free(); 
        $conexion->close();       
        return $itemMap;
    }

    private static function importar(){ 
        $act = 0;
        $nue = 0;       
        $conexion = Conexion::conectar();        
        $listaimp = self::armarLista();
        $listabd = self::listaBd();
        foreach($listaimp as $imp){
            $codigo = $imp['codigo'];
            $marca = $imp['marca'];
            $rubro = $imp['rubro'];
            $desc = $imp['descripcion'];
            $precio = $imp['precio'];  
            $imagen = $imp['imagen'];  
            $key = array_search( $codigo, array_column($listabd, 'codigo'), true); //Modo estricto true               
            if($key === FALSE){ //Si el producto no existe en la bd, se inserta
                $nue++;                                     
                $query = "insert into productos(codigo, marca, rubro, aplicacion, precio_lista, imagen, fecha_agregado)
                values ('$codigo','$marca','$rubro','$desc',$precio,'$imagen', now());";          
                $conexion->query($query);                
                continue;
            } //Si el producto ya existe, se chequea si los datos siguen siendo los mismos            
            if($marca != $listabd[$key]['marca'] || $desc != $listabd[$key]['aplicacion'] || $rubro != $listabd[$key]['rubro'] || $precio != $listabd[$key]['precio_lista']){                
                $act++;                
                $query = "update productos set marca = '$marca', rubro = '$rubro', 
                    aplicacion = '$desc', precio_lista = ".$precio.", fecha_modif = now(), imagen = '$imagen' where codigo = '$codigo';";
                $conexion->query($query);
            }                      
        } 
        $query = "insert into act_lista(fecha) values(now())";
        echo $nue.' nuevos.<br>'.$act.' actualizados.<br>';
        $conexion->query($query); 
        $conexion->close();
    }

    private static function guardarCopia(){
        $newfile = 'aprecios-'.date('Y-m-d').'.txt';
        copy('../Resources/uploads/aprecios.txt', '../Resources/listas/'.$newfile);
    }

    private static function actualizar(){
        if(file_exists("../Resources/uploads/aprecios.txt")&&file_exists("../Resources/uploads/alineasx.txt")&&file_exists("../Resources/uploads/arubrosx.txt")){         
            self::importar();
            self::guardarCopia();
            self::vaciaruploads();                       
            return "Lista actualizada!";            
        }else{   
            self::vaciaruploads();        
            return "Verifique que estén los 3 archivos necesarios!";
        }                                                  
    }
}  

echo DAOActualizar::selector();








 