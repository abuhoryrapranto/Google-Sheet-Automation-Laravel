<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheet;
use App\Models\Info;
use DB;

class GoogleSheetController extends Controller
{
    public $googleSheetService;

    public function __construct(GoogleSheet $googleSheetService) 
    {
        $this->googleSheetService = $googleSheetService;
    }

    public function fetchDataFromGoogleSheet(Request $request) {

        $request->validate([
            'sheet_id' => 'required|string',
            'sheet_name' => 'string',
        ]);

        $data = $this->googleSheetService->readGoogleSheet($request->sheet_id, $request->sheet_name);
        if(!count($data) > 1) {
            return response()->json(["status_code" => 404,"message" => "Data Not Found", "data" => null], 404);
        }
        $keys = array_shift($data);
        $res = array();
        foreach($data as $row) {
            $newData = array_combine($keys, $row);
            array_push($res, $newData);
        }
        return response()->json(["status_code" => 200,"message" => "Data Found", "data" => $res], 200);
    }

    public function appendItemWiseSales(Request $request) {

        $request->validate([
            'sheet_id' => 'required|string',
            'sheet_name' => 'string',
        ]);

        $items = DB::table('order_details')
                    ->leftJoin('orders', 'order_details.order_id', '=', 'orders.id')
                    ->leftJoin('menus', 'order_details.menu_id', '=', 'menus.id')
                    ->leftJoin('brands', 'menus.brand_id', '=', 'brands.id')
                    ->where('order_details.status', 1)
                    ->selectRaw('current_date() as date')
                    ->selectRaw('brands.name as brand_name, menus.name as menu_name')
                    ->selectRaw("count(distinct order_details.order_id) as total_orders")
                    ->selectRaw("sum(order_details.quantity) as total_sold")
                    ->selectRaw("count(distinct orders.customer_id) as total_unique_users")
                    ->selectRaw('SUM(menus.unit_price * order_details.quantity) as total_price')
                    ->groupBy('order_details.menu_id')
                    ->orderBy('total_orders', 'desc')
                    ->get();
                 
        $data = [];
        foreach($items as $row) {
            array_push($data, array_values((array) $row));
        }
 
        $uploadData = $this->googleSheetService->saveToGoogleSheet($data, $request->sheet_id, $request->sheet_name);

        if(!$uploadData)
            return response()->json(['status_code' => 400, 'success' => false, 'message' => 'Something went wrong. Please check your sheet name & id'], 400);
        return response()->json(['status_code' => 200, 'success' => true, 'message' => 'Successfully append data in google sheet',], 200);
    }

    public function appendBrandWiseSales() {

        // $request->validate([
        //     'sheet_id' => 'required|string',
        //     'sheet_name' => 'string',
        // ]);

        $sheet_id = "1DrxotmOiQuHSbk6oTdG4rw7hJn-YHJdyznhF04xXHGo";
        $sheet_name = "Brandwise";

        $items = DB::table('order_details')
                    ->leftJoin('orders', 'order_details.order_id', '=', 'orders.id')
                    ->leftJoin('billing_details', 'orders.id', '=', 'billing_details.order_id')
                    ->leftJoin('menus', 'order_details.menu_id', '=', 'menus.id')
                    ->leftJoin('brands', 'menus.brand_id', '=', 'brands.id')
                    ->where('order_details.status', 1)
                    ->selectRaw('current_date() as date')
                    ->selectRaw('brands.name as brand_name')
                    ->selectRaw("count(distinct menus.id) as total_items")
                    ->selectRaw("count(distinct order_details.order_id) as total_orders")
                    ->selectRaw("count(distinct orders.customer_id) as total_unique_users")
                    ->selectRaw("sum(billing_details.gross_amount) as gross_sales")
                    ->selectRaw("sum(billing_details.discount) as discount")
                    ->selectRaw("sum(billing_details.net_amount) as net_amount")
                    ->selectRaw("sum(billing_details.delivery_charge) as delivery_charge")
                    ->selectRaw("(sum(billing_details.rider_commission) - sum(billing_details.delivery_charge)) as rider_subsidy")
                    ->groupBy('menus.brand_id')
                    ->orderBy('total_orders', 'desc')
                    ->get();
        
        $data = [];
        foreach($items as $row) {
            array_push($data, array_values((array) $row));
        }
 
        $uploadData = $this->googleSheetService->saveToGoogleSheet($data, $sheet_id, $sheet_name);

        if(!$uploadData)
            return response()->json(['status_code' => 400, 'success' => false, 'message' => 'Something went wrong. Please check your sheet name & id'], 400);
        return response()->json(['status_code' => 200, 'success' => true, 'message' => 'Successfully append data in google sheet',], 200);
    }

    public function appendOrderWiseSales(Request $request) {

        $request->validate([
            'sheet_id' => 'required|string',
            'sheet_name' => 'string',
        ]);

        $items = DB::table('orders')
                    ->leftJoin('order_details', 'order_details.order_id', '=', 'orders.id')
                    ->leftJoin('billing_details', 'orders.id', '=', 'billing_details.order_id')
                    ->leftJoin('menus', 'order_details.menu_id', '=', 'menus.id')
                    ->leftJoin('brands', 'menus.brand_id', '=', 'brands.id')
                    ->where('order_details.status', 1)
                    ->selectRaw('current_date() as date')
                    ->selectRaw('orders.id as order_id')
                    ->selectRaw("sum(order_details.quantity) as total_items")
                    ->selectRaw('orders.customer_name')
                    ->selectRaw('orders.customer_phone')
                    ->selectRaw("billing_details.gross_amount as gross_bills")
                    ->selectRaw("billing_details.delivery_charge as delivery_charge")
                    ->selectRaw("billing_details.discount as discount")
                    ->selectRaw("billing_details.net_amount as net_amount")
                    ->groupBy('orders.id')
                    ->orderBy('orders.id', 'asc')
                    ->get();

        foreach($items as $row) {
            $order = DB::table('order_details')
                        ->selectRaw('distinct brands.id as brand_id')
                        ->where('order_details.order_id', $row->order_id)
                        ->leftJoin('menus', 'order_details.menu_id', '=', 'menus.id')
                        ->leftJoin('brands', 'menus.brand_id', '=', 'brands.id')
                        ->get();
            $row->brands_id = str_replace(array("[", "]"), "", implode(", ", explode(",", $order->pluck('brand_id')))); 
        }
        
        $data = [];
        foreach($items as $row) {
            array_push($data, array_values((array) $row));
        }
 
        $uploadData = $this->googleSheetService->saveToGoogleSheet($data, $request->sheet_id, $request->sheet_name);

        if(!$uploadData)
            return response()->json(['status_code' => 400, 'success' => false, 'message' => 'Something went wrong. Please check your sheet name & id'], 400);
        return response()->json(['status_code' => 200, 'success' => true, 'message' => 'Successfully append data in google sheet',], 200);
    }
}
