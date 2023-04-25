<?php

namespace App\Http\Controllers\Activate;

use App\Models\Order\SmsOrder;

class OrderController
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $orders = SmsOrder::paginate(15)->sortBy('id','DESC');

        return view('activate.order.index', compact(
            'orders',
        ));
    }
}
