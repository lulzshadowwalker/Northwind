<?php

namespace app\Contracts;

interface AuthProvider
{
    //  TODO: handle email conflict in-case of having multiple providers (if necessary)
    public function redirect();

    public function callback();
}
