<?php

class View_HTML {
    private $_vars = array();
    private $_header = 'Standard/Header.php';
    private $_footer = 'Standard/Footer.php';
    private $_javascript = array('http://code.jquery.com/jquery-1.9.1.min.js');
    private $_css = array();
    private $_page_title = APP_NAME;

    /**
     * Retrieve from local array if exists.
     * @param type $name
     * @return boolean
     */
    public function __get($name) {
        if(isset($this->_vars[$name])) {
            return $this->_vars[$name];
        }
        return false;
    }

    /**
     * Save to local array.
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->_vars[$name] = $value;
    }

    /**
     * Checks if variable is in local array.
     * @param string $name
     * @return boolean
     */
    public function __isset($name) {
        return isset($this->_vars[$name]);
    }

    /**
     * Includes $header file, or a default one if not specified.
     * @param type $header
     */
    public function getHeader($header = null) {
        $css_output = '';
        if(count($this->_css) > 0) {
            foreach($this->_css as $css) {
                $css_output .= "<link href='$css' rel='stylesheet' type='text/css'>\n";
            }
        }
        $this->css = $css_output;

        $javascript_output = '';
        if(count($this->_javascript) > 0) {
            foreach($this->_javascript as $javascript) {
                $javascript_output .= "<script src='$javascript'></script>\n";
            }
        }
        $this->javascript = $javascript_output;

        if(!is_null($header)) {
            include_once($header);
        } else if(!is_null($this->_header)) {
            include_once($this->_header);
        }
    }

    /**
     * Includes $footer file, or a default one if not specified.
     * @param string $footer
     */
    public function getFooter($footer = null) {
        if(!is_null($footer)) {
            include_once($footer);
        } else if(!is_null($this->_footer)) {
            include_once($this->_footer);
        }
    }

    /**
     * Get absolute path to the application view folder.
     * @return string
     */
    private static function getViewPath() {
        return PATH.'application/view';
    }

    /**
     * Get data redirected through this class' redirect() method.
     * Warning -- badly named redirect-data variables may not show up if that variable
     * already exists within the class (could have been set in the controller)
     *
     * FILTER_SANITIZE_STRING is run on all variables.
     *
     * Variables are accessed via magic __get method.
     */
    public function getRedirectedData() {
        if(isset($_SESSION['redirect-data']) && count($_SESSION['redirect-data']) > 0) {
            foreach($_SESSION['redirect-data'] as $var => $value) {
                if(isset($this->_vars[$var])) {
                    continue;
                }
                $this->$var = filter_var($value, FILTER_SANITIZE_STRING);
            }
        }
    }

    /**
     * Set the title of the page -- should be called before getHeader().
     * @param string $title
     */
    public function setPageTitle($title) {
        $this->_page_title = $title;
    }

    /**
     * Redirect to $url using a header() 302 request.
     * $data is saved in $_SESSION as "redirect-data"
     * @param string $url
     * @param array  $data
     */
    public function redirect($url, array $data = array()) {
        $url = filter_var($url, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $_SESSION['redirect-data'] = $data;
        header("Location: $url");
    }

    /**
     * Renders the template/view file you specify.  Path begins right after
     * the App/View directory.
     * @param string $view_template_file ie 'Character/List.php'
     * @return string rendered view
     */
    public function render($view_template_file) {
        $this->_vars['page_title'] = $this->_page_title;
        ob_start();

        include self::getViewPath().'/'.$view_template_file;

        // Now that page is drawn, remove any redirect-data
        if(isset($_SESSION['redirect-data'])) {
            unset($_SESSION['redirect-data']);
        }

        return ob_get_clean();
    }
}
