<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FallbackController extends Controller
{
    public function index($bot)
    {
        $bot->reply('Извините, я не знаю такую команду. Попробуйте: \'/start \'');
    }
}
