<?php 
class Conexion {    

    static $mysqli;
    static $conexion;
    static $resultset;

    public static function conectar(){     

         $user = "felipepr_test";
         $pass = "Tato-1432";
         $server = "felipepriotti.com.ar";
         $db = "felipepr_test";
       
        self::$mysqli = new mysqli($server, $user, $pass, $db);

        if (self::$mysqli->connect_errno) {            
            echo "Erro de conexion a la base de datos";       
        }

        return self::$mysqli;
    } 

    public static function get($query){
        self::$conexion = self::conectar();
        self::$resultset = self::$conexion->query($query);         
        if( self::$resultset->num_rows < 1){                     
            return false;
        }             
        return self::$resultset;
    }

    public static function set($query){
        self::$conexion = self::conectar();
        self::$conexion->query($query);        
        self::$conexion->close();               
    }

    public static function cerrar(){
        self::$resultset->free(); 
        self::$conexion->close();
    }
    
} 

?> 
