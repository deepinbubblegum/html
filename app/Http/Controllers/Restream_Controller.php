<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Cookie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class Restream_Controller extends BaseController
{
    private function autoID()
    {
        $id = DB::select(
            'SELECT CONCAT("SID-",LPAD(SUBSTRING(IFNULL(MAX(restream.id_stream), "0"), 5,6)+1, 6,"0")) as auto_id FROM restream'
        );
        return $id[0]->auto_id;
    }

    private function genKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(10));
    }

    private function genStreamKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(10));
    }

    public function getRestreaming(Request $request)
    {
        $recv = $request->all();
        $url_rtsp = $recv['url_rtsp'];
        $list = DB::table('restream')
        ->select('restream.id_stream', 'restream.rtsp_url', 'restream.live_url', 'restream.exp')
        ->where('restream.rtsp_url','=', $url_rtsp)
        ->get();
        // dd($url);
        if (count($list) > 0){
            $exp = $list[0]->exp;
            $now = Carbon::now();
            $now = $now->toDateTimeString();
            // if ($exp < $now){
            $exp = Carbon::now()->addMinutes(1);
            //$exp = Carbon::now()->addDays(7);
            $exp = $exp->toDateTimeString();
            DB::table('restream')
            ->where('restream.rtsp_url','=', $url_rtsp)
            ->update([
                'exp' => $exp
            ]);
            // }
        } else {
            // 'live_url' => $url .':7466/live/'. $this->genStreamKey() . '.m3u8',
            $id_stream = $this->autoID();
            $exp = Carbon::now()->addMinute(1);
            //$exp = Carbon::now()->addDays(7);
            $exp = $exp->toDateTimeString();
            DB::table('restream')
            ->insert([
                'id_stream' => $id_stream,
                'rtsp_url' => $url_rtsp,
                'live_url' => $this->genStreamKey(),
                'key' => $this->genKey(),
                'exp' => $exp
            ]);
        }

        $list = DB::table('restream')
        ->select('restream.id_stream', 'restream.rtsp_url', 'restream.live_url', 'restream.exp')
        ->where('restream.rtsp_url','=', $url_rtsp)
        ->get();

        $url = Request()->root();
        $data_return = [
            'rtsp_url' => $list[0]->rtsp_url,
            'live_url' => $url .':7466/live/'. $list[0]->live_url . '.m3u8',
            'exp' => $list[0]->exp
        ];
        return response()->json($data_return);
    }

    // example ffmpeg -an -rtsp_transport tcp -i "rtsp://1.20.196.245:5541/aaba7eb9-bdfe-4661-aefa-efb3c653d531/0" -tune zerolatency -vcodec libx264 -pix_fmt + -c:v copy -f flv "rtmp://43.228.84.23/live/oop?API_KEY=daeca5512da34b4ad306"
    public function authStream(Request $request)
    {
        $apikey = $request->input('API_KEY');
        $StreamKey = $request->input('name');

        $key_check = DB::table('restream')
        ->select('*')
        ->where('restream.live_url','=', $StreamKey)
        ->where('restream.key','=', $apikey)
        ->first();
        
        if ($key_check){
            http_response_code(200);
            die();
        }else{
            http_response_code(400);
            die();
        }
    }
}
