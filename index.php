<?php

function addslashesRecursivo($var) {
    if (!is_array($var)) {
        return addslashes(htmlentities($var));
    }
        
    $new_var = array();
    foreach ($var as $k => $v)
        $new_var[addslashes(htmlentities($k))] = addslashesRecursivo($v);
        
    return $new_var;
}

$_GET = addslashesRecursivo($_GET);
$_SERVER = addslashesRecursivo($_SERVER);

final class index {
    private $Response = null;

    public function exception_handler($ex): void {
        if ($ex instanceof NotFoundException) {
            $this->Response->StatusCode = HTTPCodes::NOT_FOUND;
        } else {
            $this->Response->StatusCode = HTTPCodes::INTERNAL_SERVER_ERROR;
        }
        $this->Response->ContentType = "application/json; charset=UTF-8";
        $this->Response->Body = array("error" => $ex->getMessage());
        exit($this->Response->send());
    }
    
    
    public function autoload_function($name): void {
        $class = "./inc/".$name.".php";

        if(!file_exists($class))
        {
            throw new NotFoundException("Class '".$name."' not found.", 1);
        }
        
        include_once $class;
    }

    public function init(): void {
        error_reporting(E_ALL | E_STRICT);
        set_exception_handler(array($this, 'exception_handler'));
        spl_autoload_register(array($this, 'autoload_function'));
        
        $request = HTTPRequest::fromGlobals();
        $this->Response = new HTTPResponse($request->Protocol);
        $this->Response->ContentType = "application/json; charset=UTF-8";
                
        $code = $request->URL[0];
        $Bd = new Bd();
        $url = $Bd->seleccionar("codigos", "id = '$code'", "url_code")->fetch()['url_code'];

        if($url != null){
            $Bd->actualizar("codigos", "hits = hits+1", "id = '$code'");
            exit(header("Location: ". $url));
        }

        throw new NotFoundException("El código solicitado no está registrado en la base de datos. Inténtalo de nuevo más tarde.", 1);
    }
}

$index = new index();
$index->init();
