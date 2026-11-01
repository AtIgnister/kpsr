<?php

return [
    "psr4" => [
        "" => "fallback/",
        "Kami\\" => "src/",
        "Kami\\Kpsr\\" => ["src/", "ext/"]
    ],
    "debug" => [
        "throw_errors_on_fail" => true
    ]
];