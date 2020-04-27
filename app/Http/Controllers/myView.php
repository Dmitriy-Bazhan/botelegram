<?php

namespace App\Http\Controllers;

use App\MessengerUser;
use App\Product;
use App\Category;
use App\SmartFilter;
use App\User;
use Illuminate\Http\Request;

class myView extends Controller
{
    public function main()
    {
        $user = 5;
        $userData = new MessengerUser();
        $userData->first_name = $user;
        $userData->last_name = $user;
        $userData->user_name = $user;
        $userData->id_chat = $user;
        $userData->name = 'user' . $userData->id_chat;
        $userData->user_info = $user;
        $userData->response = 'null';
        $userData->save();

        return view('my-view');
    }
}

