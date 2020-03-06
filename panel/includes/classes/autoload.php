<?php
if (!isset($HOME)) {
    die();
}
/**
 * IntISP
 */
class IntISP 
{
    protected $config_path = null;
    protected $config = null;

    public function __construct()
    {
        $this->config_path = __DIR__.DIRECTORY_SEPARATOR;        
    }

    public function preInit() 
    {
        if (file_exists($this->config_path.'config.php')) 
        {
            require_once $this->config_path.'config.php';
            $this->config = [];
            //$this->config = require_once 'config.php'; //@todo make config an array

            require("includes/classes/session.db.php");	//Include MySQL database class
            require("includes/classes/mysql.db.php");	//Include PHP MySQL sessions
            $session = new Session();	//Start a new PHP MySQL session
        }     
        
        //if (!file_exists('config.php') || file_get_contents("config.php") == "") {
        if (!file_exists($this->config_path.'config.php') || empty($this->config)) {
            header('Location: install/');
            die();
        }

        if ($debug) {
            require 'includes/classes/php_error.class.php';
            $options = array(
                    'snippet_num_lines' => 3,
                    'background_text' => 'IntISP',
                    'error_reporting_off' => 0,
                    'enable_saving' => 0,
                    'display_line_numbers' => 0,
                    'server_name' => 'IntISP has stopped because an exception has occured.',
                    'error_reporting_on' => E_ALL,
            );
            php_error\reportErrors($options);
        } else {
            error_reporting(0);
        }
    }

    public function int_route($file, $CP = false)
    {
        require 'config.php';
        $mysqli = new mysqli();
        $con = mysqli_connect("$host", "$user", "$pass", "$data");
        // Check connection
        $sql = "SELECT value FROM settings WHERE code =  'theme' LIMIT 0 , 30";
        if ($result = mysqli_query($con, $sql)) {
            // Fetch one and one row
            while ($row = mysqli_fetch_row($result)) {
                $template_name = $row[0];
            }
            // Free result set
            mysqli_free_result($result);
        }
        mysqli_close($con);

        require_once 'vendor/autoload.php';

        require_once 'includes/classes/detect.class.php';
        $detect = new Mobile_Detect;
        if ($detect->isMobile()) {
            $template_name = "mobile";
        }


        $loader = new \Twig\Loader\FilesystemLoader('templates/'.$template_name);
        if (!$debug) {
            $twig = new \Twig\Environment($loader, [
                'cache' => 'cache',
            ]);
        } else {
            $twig = new \Twig\Environment($loader);
        }

        $HOME = true;
        include $file;
    }

    /** 
     * 
     */
    public function initPages() 
    {
        require 'config.php';
        require_once 'vendor/autoload.php';
        $router = new \Bramus\Router\Router();
        $router->get('/', function () {
            
            if (isset($_SESSION['user'])) {
                $this->int_route('includes/views/cp.tpl.php', true);
            } else {
                $this->int_route('includes/views/login.tpl.php');
                die();
            }
        });

        $router->get('/(\w+)', function($name) {
        if (file_exists("includes/views/" . $name . ".tpl.php")) {
            if ($name == "cp") {
            $this->int_route('includes/views/cp.tpl.php', true);
            } else {
                $this->int_route("includes/views/" . $name . ".tpl.php");
            }
        } else {
            header('HTTP/1.1 404 Not Found');
            echo file_get_contents("templates/404.html");
            die();
        }
        });
        $router->run();
    }
}
