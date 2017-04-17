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
        if(!isset($_SESSION['messages']))
            $_SESSION['messages'] = [];
    }

    private function _validateSession() {

        if (!isset($_SESSION['loggedIn'])) {
            $_SESSION['loggedIn'] = false;
        }

        if(is_array($_POST) && !empty($_POST['password'])) {
            if(sha1($_POST['password']) == Registry::get('pass')) {
                $_SESSION['loggedIn'] = true;
                $this->_goBack();
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
            $request = $_SERVER['REDIRECT_URL'];
            $parts = explode('/', trim($request, '/'));
            $controller = array_shift($parts);
            if($controller == 'back') $this->_goBack();
            if(strlen($controller) == 0) $controller = 'queue';
            $class = "\\Paperroll\\Admin\\Controller\\" . ucwords($controller);
            $action = array_shift($parts);
            if(strlen($action) == 0) $action = 'index';
            $action .= "Action";
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
            if(count($_SESSION['messages'])) {
                $this->page->setData('messages', '<div class="messages">'.implode('<br/>', $_SESSION['messages']).'</div>');
                $_SESSION['messages'] = [];
            }
        }
        return $this->page;
    }

    protected function _goBack($home = false) {
        if($home || !isset($_SERVER['HTTP_REFERER']))
            header('Location: /');
        else
            header('Location: '.$_SERVER['HTTP_REFERER']);
        exit;
    }
}