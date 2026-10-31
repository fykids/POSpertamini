<?php
namespace App\Http\Controllers;
use App\Contracts\D1ServiceInterface; use Illuminate\Contracts\View\View; use Illuminate\Http\Request;
class DashboardController extends Controller { public function __construct(private D1ServiceInterface $d1) {} public function index(Request $request): View { try { $stats=$this->d1->dashboardStats(); $transactions=$this->d1->transactions($request->only('page','search','date')); } catch (\Throwable $e) { $stats=[]; $transactions=['data'=>[],'meta'=>[]]; } return view('dashboard.index',compact('stats','transactions')); } }
