<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VietmapController extends Controller
{
    public function autocomplete(Request $request)
    {
        $data = $request->validate([
            'text' => ['required','string','min:2','max:200'],
        ]);

        $res = Http::get('https://maps.vietmap.vn/api/autocomplete/v4', [
            'apikey' => config('services.vietmap.key'),
            'text' => $data['text'],
            'display_type' => 1,
        ]);

        return response()->json($res->json(), $res->status());
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'refid' => ['required','string','max:500'],
        ]);

        $res = Http::get('https://maps.vietmap.vn/api/place/v4', [
            'apikey' => config('services.vietmap.key'),
            'refid' => $data['refid'],
        ]);

        return response()->json($res->json(), $res->status());
    }
}
