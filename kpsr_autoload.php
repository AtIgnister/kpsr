<?php

spl_autoload_register(function($path) {
    $baseDir = __DIR__;
    $split_path = explode("\\", $path);
    $filepath = "";
    $filepath_prefix = "";
    $prefix = "";

    // SPEC: The fully qualified class name MUST have a top-level namespace name, also known as a "vendor namespace".
    //TODO: maybe improve parsing here
    if(!empty($split_path)) {
        return -1;
    }

    // SPEC: The fully qualified class name MUST have a terminating class name.
    if(str_ends_with($path, "\\")) {
        return -1;
    }

    $config = include("kpsr_config.php");
    
    $psr_dirs = $config["psr4"];

    foreach ($psr_dirs as $classpath => $dir) {
        //TODO: this does partial matches of class names (i.e. prefix App would incorrectly match Application\SomeClass - prevent this)
        if(str_starts_with($path, $classpath)) {
            $filepath_prefix = $dir;
            $prefix = $classpath;
            break;
        }
    }
    
    $filepath_unprefixed = str_replace($prefix, $filepath_prefix, $path);

    // TODO: str_replace replaces every occurence. maybe not the the best idea.
    $filepath_unprefixed = str_replace("\\", "/", $filepath_unprefixed);
    
    if(!str_ends_with($filepath_unprefixed, ".php")) {
        $filepath_unprefixed = $filepath_unprefixed . ".php";
    }

    if(!str_starts_with($filepath_unprefixed, "/")) {
        $filepath_unprefixed = "/" . $filepath_unprefixed;
    }

    $filepath_unprefixed = $baseDir . $filepath_unprefixed;

    require_once $filepath_unprefixed;
});