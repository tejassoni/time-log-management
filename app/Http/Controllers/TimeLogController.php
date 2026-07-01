<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    private $timeLogService;
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(Request $request)
    {
        // Handle the request to display time logs
        // You can add your logic here to fetch and return time logs
        return response()->json(['message' => 'Time logs index']);
    }

    public function store(Request $request)
    {
        // Handle the request to create a new time log
        // You can add your logic here to validate and save the time log
        return response()->json(['message' => 'Time log created']);
    }

}
