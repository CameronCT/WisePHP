<?php

/*
 * WisePHP - Start templating Wisely
 * Author: Cameron Touchette
 */

class WisePHP {

    protected $path;
    protected $config;
    protected $values;

    public function __construct($templates_path, $cache_path, $cache = true, $cache_refresh = 60) {

        if (!$templates_path)
            throw new Exception("You must specify a templates path!");

        if (!$cache_path)
            throw new Exception("You must specify a cache path!");

        if (!is_readable($templates_path))
            throw new Exception("The templates path you have specified does not exist at $templates_path!");
            
        if (!is_readable($cache_path))
            throw new Exception("The cache path you have specified does not exist at $cache_path!");

        if (!is_writeable($templates_path))
            throw new Exception("Please change CHMOD to 777 for the templates path at $templates_path!");

        if (!is_writeable($cache_path))
            throw new Exception("Please change CHMOD to 777 for the cache path at $cache_path!");

        $this->path = [
            'Templates' =>  $templates_path,
            'Cache'     =>  $cache_path
        ];

        $this->config = [
            'Cache'     =>      [
                'Enabled'   =>  $cache,
                'Refresh'   =>  $cache_refresh
            ]
        ];
        return $this;
    }

    public function ping() {
        echo 'pong';
    }

    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    private function parseBlocks($file) {
        $output = file_get_contents($file);

        /*
        *      Templating Extends
        */

        // ![ - Extends
        $replace       =        '/[[ @extends\("(\w+)"\) ]]/';
        $output        =        preg_replace($replace, $this->parse("$1"));



        return $output;
    }

    private function parseVariables($file) {
        $output = file_get_contents($file);

        foreach ($this->values as $key => $value) {
            /*
            *      Templating Variables
            */
            // ! - Replace Strings
            $replace    =       "[!$key]";
            $output     =       str_replace($replace, $value, $output);

            // @ - Replace Functions
            $replace    =       "[@$key]";
            $output     =       str_replace($replace, $value, $output);

            // # - Number Format 
            $replace    =       "[#$key]";
            $output     =       str_replace($replace, (int) number_format($value), $output);

            // $ - Output Safe String
            $replace    =       "[\$$key]";
            $output     =       str_replace($replace, filter_var ($value, FILTER_SANITIZE_SPECIAL_CHARS), $output);
        }
        return $output;
    }

    private function parse($file) {
        $output = true;
        $output .= $this->parseBlocks($file);
        $output .= $this->parseVariables($file);
        return $output;
    }

    public function display($file) {
        $getPath = $this->path['Templates'] . DIRECTORY_SEPARATOR . $file . '.html';

        if (!file_exists($getPath) || !is_readable($getPath)) 
            throw new Exception("Unable to read file " . $getPath . "!");

        $output = "";
        if (!$this->config['Cache']['Enabled']) {
            ob_start();
            $output = $this->parse($getPath);
            ob_end_flush();
        }
        return $output;
    }

}