<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class InventoryController extends Controller {
 public function dashboard(){
  return response()->json([
   'products'=>DB::table('products')->count(),
   'low_stock'=>DB::table('products')->leftJoin('stock_balances','products.id','=','stock_balances.product_id')->select('products.id','products.sku','products.name','products.reorder_level',DB::raw('COALESCE(SUM(stock_balances.quantity),0) as on_hand'))->groupBy('products.id','products.sku','products.name','products.reorder_level')->havingRaw('on_hand <= products.reorder_level')->get(),
   'stock_value'=>DB::table('stock_balances')->join('products','products.id','=','stock_balances.product_id')->selectRaw('COALESCE(SUM(stock_balances.quantity * products.unit_cost),0) as value')->value('value'),
   'recent_movements'=>DB::table('stock_movements')->join('products','products.id','=','stock_movements.product_id')->join('warehouses','warehouses.id','=','stock_movements.warehouse_id')->select('stock_movements.*','products.name as product_name','products.sku','warehouses.name as warehouse_name')->latest('stock_movements.id')->limit(8)->get()
  ]);
 }
 public function adjust(Request $r){
  $d=$r->validate(['product_id'=>'required|exists:products,id','warehouse_id'=>'required|exists:warehouses,id','type'=>'required|in:receipt,issue,adjustment','quantity'=>'required|numeric|gt:0','reference'=>'nullable|string|max:100','notes'=>'nullable|string']);
  return DB::transaction(function()use($d,$r){
   $balance=DB::table('stock_balances')->where(['product_id'=>$d['product_id'],'warehouse_id'=>$d['warehouse_id']])->lockForUpdate()->first();
   $current=(float)($balance->quantity??0);$delta=$d['type']==='issue'?-(float)$d['quantity']:(float)$d['quantity'];
   if($d['type']==='issue' && $current < $d['quantity'])return response()->json(['message'=>'Insufficient stock'],422);
   if($balance)DB::table('stock_balances')->where('id',$balance->id)->update(['quantity'=>$current+$delta,'updated_at'=>now()]);
   else DB::table('stock_balances')->insert(['product_id'=>$d['product_id'],'warehouse_id'=>$d['warehouse_id'],'quantity'=>$delta,'created_at'=>now(),'updated_at'=>now()]);
   $id=DB::table('stock_movements')->insertGetId(['product_id'=>$d['product_id'],'warehouse_id'=>$d['warehouse_id'],'type'=>$d['type'],'quantity'=>$d['quantity'],'reference'=>$d['reference']??null,'notes'=>$d['notes']??null,'created_by'=>$r->user()->id,'source_system'=>'inventory','created_at'=>now(),'updated_at'=>now()]);
   return response()->json(['movement'=>DB::table('stock_movements')->find($id),'on_hand'=>$current+$delta],201);
  });
 }
}
