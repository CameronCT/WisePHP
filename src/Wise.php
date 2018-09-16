<?php

/*
 * WisePHP - Start templating Wisely
 * Author: Cameron Touchette
 */

class WisePHP {

    protected $path;
    protected $config;
    protected $values = array();

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

    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    private function getTemplateFile($file) {
        return $this->path['Templates'] . DIRECTORY_SEPARATOR . $file . '.html';
    }

    private function getCacheFile($file) {
        return $this->path['Cache'] . DIRECTORY_SEPARATOR . $file . '.wise';
    }

    private function isCached($file) {
        return (file_exists($this->getCacheFile($file)));
    }

    private function parse($file) {
        if (!file_exists($file) || !is_readable($file)) 
            throw new Exception("Unable to read file " . $file . "!");

        $output = file_get_contents($file);

        $output = preg_replace_callback('/\[\[ @append \"[A-Za-z0-9-_.\/\\\\ ]+" \]\]/', function($matches) {
            $name = explode('"', $matches[0])[1];
            return $this->parse($this->getTemplateFile($name));
        }, $output);

        foreach ($this->values AS $key => $value) {
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
            $output     =       str_replace($replace, number_format((int) $value), $output);

            // $ - Output Safe String
            $replace    =       "[\$$key]";
            $output     =       str_replace($replace, filter_var ($value, FILTER_SANITIZE_SPECIAL_CHARS), $output);
        }
        return $output;
    }

    private function cache($file) {

    }

    public function display($file) {

        $getFile = $this->getTemplateFile($file);

        if (!file_exists($getFile) || !is_readable($getFile)) 
            throw new Exception("Unable to read file " . $getFile . "!");

        if ($this->config['Cache']['Enabled']) {
            if ($this->isCached($getFile) && time() - $this->config['Cache']['Refresh'] <= filemtime($this->getCacheFile($file))) {
                clearstatcache();
                ob_start();
                readfile($this->getCacheFile($file));
                ob_end_flush();
                exit;
            } elseif (!$this->isCached($getFile) || time() - $this->config['Cache']['Refresh'] > filemtime($this->getCacheFile($file))) {
                $this->cache($this->parse($getFile));
                exit;
            }
        } elseif (!$this->config['Cache']['Enabled']) {
            $output = $this->parse($getFile);
            echo $output;
            exit;
        }
    }
    

}