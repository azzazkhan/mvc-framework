<?php

namespace App\Http\Controllers;

class HomeController
{
    /**
     * Returns view for homepage.
     * 
     * @return \Illuminate\View\View
     */
    public function __invoke()
    {
        return view('home', ['message' => 'Returned from controller']);
    }

    /**
     * Returns view for homepage.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('home', [
            'message' => 'Returned from controller index method'
        ])->layout('layouts.app');
    }
}
