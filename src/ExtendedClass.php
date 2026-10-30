<?php

namespace Kami\Kpsr;

class ExtendedClass extends TestClass {
    public function extended() {
        $this->example();
        echo "Hello from extended!";
    }
}