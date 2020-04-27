<?php

namespace App\Conversations;

use App\MessengerUser;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question as BotManQuestion;

use App\Product;
use App\Category;
use App\SmartFilter;

class mainConversation extends Conversation
{
    public $childs_id;
    public $child_id;
    public $products;
    public $category_id;
    public $category;
    public $link;
    public $childrens;
    public $child_alias;

    public function run()
    {
        $this->saveInfoUser();

    }

    public function firstQuestion()
    {
        $this->categories = Category::withData()->wherePublished(true)->whereIsMenuItem(true)->orderBy('sort')->get()->toTree();

        $question = BotManQuestion::create('Выберите категорию:');

        foreach ($this->categories as $category) {
            $question->addButtons([
                Button::create($category->data->name)->value($category->id)
            ]);
        }


        $question->addButtons([
            Button::create('Выйти из бота')->value('exit')
        ]);

        $this->ask($question, function (BotManAnswer $answer) {
            if ($answer->getText() != '') {
                if ($answer->getText() == 'exit') {
                    $this->exitWithoutSave();
                } else {
                    $this->category_id = $answer->getText();
                    $this->inToCategory();
                }
            }
        });
    }

    public function inToCategory()
    {
        $question = BotManQuestion::create('Выберите категорию:');

        foreach ($this->categories->where('id', $this->category_id)[$this->categories->where('id', $this->category_id)->keys()[0]]->children as $childs) {
            $question->addButtons([
                Button::create($childs->data->name)->value($childs->id)
            ]);
        }

        $question->addButtons([
            Button::create('Назад')->value('back'),
            Button::create('Выйти из бота')->value('exit')
        ]);


        $this->ask($question, function (BotManAnswer $answer) {
            if ($answer->getText() != '') {
                if ($answer->getText() == 'back') {
                    $this->firstQuestion();
                } elseif ($answer->getText() == 'exit') {
                    $this->exitWithoutSave();
                } else {
                    $this->childs_id = $answer->getText();
                    $this->deepInCategory();
                }
            }
        });
    }

    public function deepInCategory()
    {
        $question = BotManQuestion::create('Выберите категорию:');

        $keys = $this->categories->where('id', $this->category_id)[$this->categories->where('id', $this->category_id)->keys()[0]]
            ->children->where('id', $this->childs_id)->keys()[0];

        foreach ($this->categories->where('id', $this->category_id)[$this->categories->where('id', $this->category_id)->keys()[0]]
                     ->children->where('id', $this->childs_id)[$keys]->children as $child) {
            $question->addButtons([
                Button::create($child->data->name)->value($child->alias->url)
            ]);

        }

        $question->addButtons([
            Button::create('Начало')->value('begin'),
            Button::create('Назад')->value('back'),
            Button::create('Выйти из бота')->value('exit')
        ]);


        $this->ask($question, function (BotManAnswer $answer) {
            if ($answer->getText() != '') {
                if ($answer->getText() == 'begin') {
                    $this->firstQuestion();
                } elseif ($answer->getText() == 'back') {
                    $this->inToCategory();
                } elseif ($answer->getText() == 'exit') {
                    $this->exitWithoutSave();
                } else {
                    $this->child_alias = $answer->getText();
                    $this->showProductList();
                }
            }
        });
    }

    public function showProductList()
    {
        $message = OutgoingMessage::create('Посмотрите нашу продукцию:');
        $this->bot->reply($message);

        $category = Category::withDescendants()->withData()->withSmartFilterData()->withCharacteristicGroups()
            ->withAlias()->whereHasAlias($this->child_alias)->wherePublished(true)->firstOrFail();

        $descendants = $category->descendants;
        $categoryIds = isset($category->parent_id) ? $descendants->keyBy('id')->keys()->push($category->id) : null;
        $data['descendants'] = $descendants->toTree();

        $categoryId = $category->id;
        $smartFilterCategory = SmartFilter::withCategory()->whereFilterCategoryId($categoryId)->first();
        $data['smart_filter_category'] = $smartFilterCategory;

        $parameterData = null;

        if (isset($smartFilterCategory)) {
            $parameterData['filters'] = [$smartFilterCategory->characteristic_value_id];
            $categoryIds = collect()->push($smartFilterCategory->category_id);
        }


        $this->products = Product::withAlias()->joinData()->whereExistsCategoryId($categoryIds)
            ->whereParameters($parameterData)->wherePublished(true)->get();

        foreach ($this->products as $product) {
            $attachement = new Image('http://q-tap.com.ua/storage/product/' . $product['sku'] . '.jpg');
            $message = OutgoingMessage::create('http://q-tap.com.ua/' . $product['alias']['url'])->withAttachment($attachement);
            $this->bot->reply($message);
        }

        $question = BotManQuestion::create('Перейти в: ');

        $question->addButtons([
            Button::create('Начало')->value('begin'),
            Button::create('Назад')->value('back'),
            Button::create('Выйти из бота')->value('exit')
        ]);

        $this->ask($question, function (BotManAnswer $answer) {
            if ($answer->getText() != '') {
                if ($answer->getText() == 'begin') {
                    $this->firstQuestion();
                } elseif ($answer->getText() == 'back') {
                    $this->deepInCategory();
                } elseif ($answer->getText() == 'exit') {
                    $this->exitWithoutSave();
                }
            } else {
                $this->showProductList();
            }
        });
    }

    public function exitWithoutSave()
    {
        $message = OutgoingMessage::create('Всего хорошего');
        $this->bot->reply($message);
        return true;
    }

    protected function saveInfoUser()
    {
        $check = MessengerUser::where('id_chat', $this->bot->getUser()->getId())->first();

        if (!$check) {
            $userData = new MessengerUser();
            $userData->first_name = !empty($this->bot->getUser()->getFirstName()) ? $this->bot->getUser()->getFirstName() : 'is absent';
            $userData->last_name = !empty($this->bot->getUser()->getLastName()) ? $this->bot->getUser()->getLastName() : 'is absent';
            $userData->user_name = !empty($this->bot->getUser()->getUserName()) ? $this->bot->getUser()->getUserName() : 'is absent';
            $userData->id_chat = !empty($this->bot->getUser()->getId()) ? $this->bot->getUser()->getId() : 'is absent';
            $userData->name = 'user' . $userData->id_chat;
            $userData->user_info = !empty($this->bot->getUser()->getInfo()) ? $this->bot->getUser()->getInfo() : 'is absent';
            $userData->response = 'null';
            $userData->save();
        }
        $this->firstQuestion();
    }
}
