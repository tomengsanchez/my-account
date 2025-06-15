<?php
namespace core; // Declare that this class belongs to the 'core' namespace

/*
 * App Core Class
 * Creates URL & loads core controller
 * URL FORMAT - /controller/method/params
 */
class Core {
    protected $currentController = 'Home'; // Default controller
    protected $currentMethod = 'index';    // Default method
    protected $params = [];

    public function __construct(){
        $url = $this->getUrl();

        // Look in controllers for the first part of the URL
        if(isset($url[0]) && file_exists('../app/controllers/' . ucwords($url[0]) . '.php')){
            // If the controller file exists, set it as the current controller
            $this->currentController = ucwords($url[0]);
            unset($url[0]);
        }

        // The autoloader will handle including the file, so we don't need `require_once` here.

        // Form the full, namespaced class name for the controller
        $controllerClassName = '\\controllers\\' . $this->currentController;
        // Instantiate the controller class
        $this->currentController = new $controllerClassName;

        // Check for the second part of the URL (the method)
        if(isset($url[1])){
            if(method_exists($this->currentController, $url[1])){
                $this->currentMethod = $url[1];
                unset($url[1]);
            }
        }

        // Get the remaining parts of the URL as parameters
        $this->params = $url ? array_values($url) : [];

        // Call the method on the controller with the given parameters
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    public function getUrl(){
        if(isset($_GET['url'])){
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}
