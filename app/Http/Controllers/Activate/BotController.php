<?php

namespace App\Http\Controllers\Activate;

use App\Models\Bot\SmsBot;
use App\Services\Activate\UserService;

class BotController
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $bots = SmsBot::paginate(10)->sortBy('id','DESC');

        return view('activate.bot.index', compact(
            'bots',
        ));
    }
}
