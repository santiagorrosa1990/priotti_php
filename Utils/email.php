<?php

class Email{

    public static function selector($opc){ 
        switch($opc){  
            case 1:
            return self::enviarPedido();      
            break;
            case 2:         
            return self::enviarMsj();
            break;                                         
            default: 
            return "Sin selección";                   
            break;
        }                                                     
    }

    private static function esUsuario(){
        if (isset($_SESSION["usuario"])) return true;
        return false;              
    }   

    public static function enviarMsj(){       
        if (isset($_REQUEST['email']))  {  
            $admin_email = "santiagorrosa@hotmail.com";       
            $admin_email = "fpriotti@felipepriotti.com.ar";
            //$email = $_REQUEST['email'];
            $email_exp = '/^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/'; 
            if(!preg_match($email_exp,$email)) {           
              return 'La dirección de correo proporcionada no es válida.';           
            }
            $subject = 'Consulta de '.$_REQUEST['nombre'];            
            $comment = $_REQUEST['mensaje']."\r\n".'Telefono: '.$_REQUEST['telefono']
            ."\r\n".'Email: '.$_REQUEST['email'];
            //send email
            mail($admin_email, $subject, $comment, "From:" . $email);
            //Email response
            return "Gracias por contactarnos! <br> En breve nos comunicaremos";
        }  
    }

    private static function enviarPedido(){
        include_once("../Modelo/DAOPedidos.php");            
        //$admin_email = "santiagorrosa@hotmail.com";
        $admin_email = "fpriotti@felipepriotti.com.ar";        
        $subject = 'Pedido de Cliente numero: '.$_SESSION['usuario'].' - '.$_SESSION['nombre'];          
        $message = DAOPedidos::selector(5); //Obtengo el pedido en formato html
        $message = '<strong>Comentarios:</strong> '.utf8_decode($_POST['comentario']).'<br><br>'.$message;
        if($message != false){
            $headers = "From: Carrito de Compras \r\n";
            //$headers .= "Reply-To: ". strip_tags($_POST['req-email']) . "\r\n";
            //$headers .= "CC: susan@example.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            //Send email
            mail($admin_email, $subject, $message, $headers);
            return "Su pedido ha sido enviado!";
        }
        return "El carrito está vacío!";
    }
}

if(isset($_POST["opcemail"])) echo Email::selector($_POST["opcemail"]);
