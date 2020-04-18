<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question as BotManQuestion;

use App\messengerUser as database;
use phpDocumentor\Reflection\DocBlock\Tags\Link;
use App\Product;
use App\Category;

class mainConversation extends Conversation
{
    public $products;
    public $link;

    public function run()
    {
        $this->firstQuestion();
    }

    public function firstQuestion()
    {

        $question = BotManQuestion::create('Задайте мне вопрос про смеситель.
                                            (Например: найти смеситель. Или: мне нужен смеситель.)');

        $this->ask($question, function (BotManAnswer $answer) {
            if (mb_stristr($answer->getText(), 'смесител')) {
                $this->showFoto();
            } elseif ('No') {
                $this->tryYet();
            }
        });
    }

    public function offerFiveProducts()
    {
        $question = BotManQuestion::create('Выберите смеситель');

        $data = \Opis\Closure\unserialize(file_get_contents('storage/array1.txt'));
        $count = count($data) - 1;
        for ($i = 0; $i <= 4; $i++) {
            $key = rand(0, $count);
            if (!empty($data[$key]['data']['name'])) {
                $this->products[$i] = $data[$key];
            } else {
                $i--;
            }
        }

        $question->addButtons([
            Button::create($this->products[0]['data']['name'])->value(0),
            Button::create($this->products[1]['data']['name'])->value(1),
            Button::create($this->products[2]['data']['name'])->value(2),
            Button::create($this->products[3]['data']['name'])->value(3),
            Button::create($this->products[4]['data']['name'])->value(4)
        ]);

        $this->ask($question, function (BotManAnswer $answer) {
            if ($answer->getText() != '') {
                $this->id = $answer->getText();


                $this->showLink();
            } else {
                $this->finish();
            }
        });

    }

//http://sandi-cms/storage/product/237-28451.webp

    public function showFoto()
    {
        //$data = \Opis\Closure\unserialize(file_get_contents('storage/array1.txt'));
        $data = Product::where('published', 1)->whereBetween('category_id',[1,30])->withData()->withAlias()
            ->withCategory()->get()->toArray();

        while (true) {
            $key = rand(0, count($data) - 1);
            if (!empty($data[$key]['data']['name']) && $data[$key]['published'] == 1) {
                $this->products[0] = $data[$key];
                break;
            }
        }
        $attachement = new Image('http://q-tap.com.ua/storage/product/' . $this->products[0]['sku'] . '.jpg');
        $message = OutgoingMessage::create('Вот классный смеситель')->withAttachment($attachement);
        $this->bot->reply($message);

        $message = OutgoingMessage::create('http://q-tap.com.ua/' . $this->products[0]['alias']['url']);
        $this->bot->reply($message);
        $this->finish();
    }

    private function tryYet()
    {
        $question = BotManQuestion::create('Где тут про смеситель? Попробуете снова? ');
        $question->addButtons([
            Button::create('Да')->value('Yes'),
            Button::create('Нет')->value('No'),
        ]);
        $this->ask($question, function (BotManAnswer $answer) {

            if ($answer->getText() == 'Yes') {
                $this->firstQuestion();
            } elseif ('No') {
                $this->exitWithoutSave();
            }
        });
    }

    private function finish()
    {
        $question = BotManQuestion::create('Показать еще смеситель? ');
        $question->addButtons([
            Button::create('Да')->value('Yes'),
            Button::create('Хватит')->value('No'),
        ]);
        $this->ask($question, function (BotManAnswer $answer) {

            if ($answer->getText() == 'Yes') {
                $this->showFoto();
            } elseif ('No') {
                $this->exitWithoutSave();
            }
        });
    }


    private function exitWithoutSave()
    {
        $message = OutgoingMessage::create('Всего хорошего');
        $this->bot->reply($message);
        return true;
    }

    private function exit()
    {
        $db = new database();
        $db->id_chat = $this->bot->getUser()->getId();
        $db->name = $this->response['name'];
        $db->response = $this->response['answer'];
        $db->save();

        $attachement = new Image('https://i.pinimg.com/236x/ed/67/55/ed67559d1c16c70ffb942d50f62cf583.jpg'); // Для картинки

        $message = OutgoingMessage::create('Ты умница,' . $this->response['name'])->withAttachment($attachement);
        $this->bot->reply($message);

//        $question = BotManQuestion::create('Как давно мы знакомы,' . $this->response['name'] .'?');
//        $question->addButtons([
//            Button::create('Да')->value(1),
//            Button::create('Нет')->value(2)
//        ]);

        return true;
    }

//    public function firstQuestion()
//    {
//        $categories = Category::withData()->withAlias()->withSmartFilters()->wherePublished(true)
//            ->whereIsMenuItem(true)->orderBy('sort')->get()->toTree();
//
//        $question = BotManQuestion::create('Выберите категорию:');
//
//        foreach ($categories as $category)
//        {
//            $question->addButtons([
//                Button::create($category->data->name)->value($category->alias->url)
//            ]);
//        }
//
//        $this->ask($question, function (BotManAnswer $answer) {
//            if ($answer->getText() != '') {
//                $this->url = $answer->getText();
//                $this->inToCategory();
//            } else {
//                $this->finish();
//            }
//        });
//
//    }

}
