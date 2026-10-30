<?php

spl_autoload_register(function($path) {
    $baseDir = __DIR__;
    $filepath = "";
    $filepath_prefix = "";
    $prefix = "";
    $split_path = parsePSR4Path($path);

    if(!$split_path) {
        return;
    }

    $config = include("kpsr_config.php");
    
    $psr_dirs = $config["psr4"];

    foreach ($psr_dirs as $classpath => $dir) {
        //TODO: this does partial matches of class names (i.e. prefix App would incorrectly match Application\SomeClass - prevent this)
        // also: prefixes must be matched from longest to shortest.
        if(str_starts_with($path, $classpath)) {
            $filepath_prefix = $dir;
            $prefix = $classpath;
            break;
        }
    }

    // we dont have a prefix, burn everything down; all hope is lost.
    if(!$prefix) {
        return;
    }

    $filepath = turnPrefixedPSR4IntoFilepath($prefix, $filepath_prefix, $path);
    if ($filepath) {
        require_once $filepath;
    }

    return;
});

function parsePSR4Path(string $path): array|int {
    $pathLen = strlen($path);
    $curElem = "";
    $parsed = [];
    
    $TOKEN_separator = "\\";

    for ($i=0; $i < $pathLen; $i++) {

        //
        // SETUP START
        $curToken = $path[$i];
        if($i+1 < $pathLen) {
            $nextToken = $path[$i + 1];
        }
        if($curToken != $TOKEN_separator) {
            $curElem .= $curToken;
        }
        // SETUP END
        //

        // The fully qualified class name MUST have a top-level namespace name, also known as a "vendor namespace".
        if($pathLen <= 1) {
            return -1;
        }

        if(isset($nextToken) && $curToken === $TOKEN_separator && $nextToken == $TOKEN_separator) {
            return -1;
        }

        if((isset($nextToken) && $nextToken === $TOKEN_separator || !isset($path[$i+1])) && !empty($curElem)) {
            $parsed[] = $curElem;
            $curElem = "";
        }

        // The fully qualified class name MUST have a terminating class name.
        if(isset($nextToken) && $nextToken === $TOKEN_separator && !isset($path[$i+2])) {
            return -1;
        }
    }

    return $parsed;
}

function removePSR4Prefix(array $split_path, array $split_prefix): array|int {
    if(count($split_prefix) > count($split_path)) {
        return -1;
    }

    for ($i=0; $i < count($split_prefix) ; $i++) {
        if($split_path[$i] != $split_prefix[$i]) {
            return -1;
        } else {
            unset($split_path[$i]);
        }
    }

    //TODO: this is stupid. make it less stupid.
    $split_path = array_values($split_path);

    return $split_path;
}

function turnPrefixedPSR4IntoFilepath(string $prefix, string $prefixFilePath, string $path): string|int {
    $parsed_prefix = parsePSR4Path(rtrim($prefix, "\\"));
    $parsed_path = parsePSR4Path($path);
    $prefixFilePath = trim($prefixFilePath, "/");

    if(!$parsed_prefix || !$parsed_path) {
        return -1;
    }

    $unprefixed_path = removePSR4Prefix($parsed_path, $parsed_prefix);

    $path = __DIR__ . "/$prefixFilePath". "/";
    $unprefixed_path_len = count($unprefixed_path);
    foreach ($unprefixed_path as $index => $name) {
        $path .= $name;
        if($index < $unprefixed_path_len - 1) {
            $path .= "/";
        }
    }

    $path .= ".php";

    if(!is_file($path)) {
        return -1;
    }
    
    return $path;
}