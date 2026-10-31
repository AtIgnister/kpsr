<?php
// TODO: Complete PSR-4 naming validation
// TODO: Multiple directories per prefix
$config = include("kpsr_config.php");
$err_enabled = $config["debug"]["throw_errors_on_fail"];

spl_autoload_register(function($path) use ($config) {
    $baseDir = __DIR__;
    $filepath = "";
    $filepath_prefix = "";
    $prefix = "";
    $split_path = parsePSR4Path($path);

    if(!$split_path) {
        return _throw_error("Your FQN $path could not be parsed as a PSR4 namespace. Please check if you have at least one toplevel namespace and no back-to-back backslashes.");
    }
    
    $psr_dirs = $config["psr4"];

    $maxLenMatched = -1;
    $currentMatchedPrefix = "";
    $currentMatchedPrefixDir = "";
    foreach ($psr_dirs as $classpath => $dir) {
        $split_prefix = parsePSR4Path(rtrim($classpath, "\\"));

        if (!$split_prefix) {
            continue;
        }

        // A prefix cannot be longer than the requested class path.
        if (count($split_prefix) > count($split_path)) {
            continue;
        }

        $matches = true;

        foreach ($split_prefix as $index => $name) {
            if (!isset($split_path[$index]) || $name !== $split_path[$index]) {
                $matches = false;
                break;
            }
        }

        if ($matches && count($split_prefix) > $maxLenMatched) {
            $maxLenMatched = count($split_prefix);
            $currentMatchedPrefix = $classpath;
            $currentMatchedPrefixDir = $dir;
        }
    }

    if($currentMatchedPrefix) {
        $filepath_prefix = $currentMatchedPrefixDir;
        $prefix = $currentMatchedPrefix;
    } else {
        return _throw_error("No known matched PSR prefix. The namespace of the class $path might be wrong?");
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
        $nextToken = $path[$i + 1] ?? null;

        if($curToken != $TOKEN_separator) {
            $curElem .= $curToken;
        }
        // SETUP END
        //

        // The fully qualified class name MUST have a top-level namespace name, also known as a "vendor namespace".
        if($pathLen <= 1) {
            return 0;
        }

        if($nextToken !== null && $curToken === $TOKEN_separator && $nextToken == $TOKEN_separator) {
            return 0;
        }

        if(($nextToken !== null && $nextToken === $TOKEN_separator || !isset($path[$i+1])) && !empty($curElem)) {
            $parsed[] = $curElem;
            $curElem = "";
        }

        // The fully qualified class name MUST have a terminating class name.
        if($nextToken !== null && $nextToken === $TOKEN_separator && !isset($path[$i+2])) {
            return 0;
        }
    }

    return $parsed;
}

function removePSR4Prefix(array $split_path, array $split_prefix): ?array {
    if (count($split_prefix) > count($split_path)) {
        return null;
    }

    foreach ($split_prefix as $index => $name) {
        if ($split_path[$index] !== $name) {
            return null;
        }
    }

    return array_slice($split_path, count($split_prefix));
}

function turnPrefixedPSR4IntoFilepath(string $prefix, string $prefixFilePath, string $path): string|int {
    $parsed_prefix = parsePSR4Path(rtrim($prefix, "\\"));
    $parsed_path = parsePSR4Path($path);
    $prefixFilePath = trim($prefixFilePath, "/");

    if(!$parsed_prefix || !$parsed_path) {
        return 0;
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
        _throw_error("The file \"$path\", which you are trying to include starting at directory \"$prefixFilePath\", does not exist. Try checking if you have a typo in your namespaces or PSR4 prefix config.");
        return 0;
    }
    
    return $path;
}

function _throw_error(string $msg): void {
    global $err_enabled;
    if($err_enabled) {
        throw new PSR4Exception($msg);
    }
}

class PSR4Exception extends Exception {};