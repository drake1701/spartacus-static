<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Admin;

use Paperroll\Helper\Registry;
use Paperroll\Theme\Block;
use Paperroll\Theme\Layout;

require_once BASEDIR . '/access.php';

class Router
{
    /** @var  Layout */
    protected $page;

    /**
     * Router constructor.
     */
    public function __construct() {
        $this->_validateSession();
    }

    private function _validateSession() {

        if (!isset($_SESSION['loggedIn'])) {
            $_SESSION['loggedIn'] = false;
        }

        if(is_array($_POST) && !empty($_POST['password'])) {
            if(sha1($_POST['password']) == Registry::get('pass')) {
                $_SESSION['loggedIn'] = true;
                header('Location: /');
            }
        }

        if(!$_SESSION['loggedIn']) {
            $page = $this->getPage();
            $block = new Block('admin/login');
            $page->setData('content', $block);
            echo $page;
            exit;
        }

    }

    public function execute() {
        try {
            $request = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $request);
            $controller = array_shift($parts) ?: 'queue';
            $class = "\\Paperroll\\Admin\\Controller\\" . ucwords($controller);
            $action = array_shift($parts) ?: 'index';
            $route = new $class();
            $route->$action();
        } Catch (\Exception $e) {
            echo "<pre>";
            echo $e->getMessage()."\n".$e->getTraceAsString();
        }
    }

    /**
     * @return mixed
     */
    public function getPage() {
        if(!$this->page) {
            $this->page = new Layout('admin');
            $this->page->loadLayout();
        }
        return $this->page;
    }
}