<?php

namespace App\Http\Controllers;

use App\Events\Test;
use App\Listeners\TestListener;
use Illuminate\Http\Request;

class TestController extends Controller
{
    //
    public function index(){

        event(new Test('nine'));
        echo 'helloworld';
    }

}
