<?php
use App\Http\Controllers\BotManController;
use App\Conversations\mainConversation;

//echo '<h1>Hello</h1>';

$botman = resolve('botman');

$botman->hears('Hi', function ($bot) {
    $bot->reply('Hello!');
});

$botman->hears('Start conversation', BotManController::class.'@startConversation');

$botman->hears('/start', function($bot){
    $bot->startConversation( new mainConversation);
});


$botman->fallback('App\Http\Controllers\FallbackController@index');