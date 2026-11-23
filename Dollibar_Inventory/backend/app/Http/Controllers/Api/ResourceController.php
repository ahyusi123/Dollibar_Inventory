<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
class ResourceController extends Controller {
 private array $resources=['products'=>'products','categories'=>'categories','warehouses'=>'warehouses','suppliers'=>'suppliers','stock-movements'=>'stock_movements','purchase-orders'=>'purchase_orders'];
 private function table(string $resource): string {abort_unless(isset($this->resources[$resource]),404,'Inventory resource not found');return $this->resources[$resource];}
 public function index(Request $request,string $resource) {
  $table=$this->table($resource);$q=DB::table($table);
  if($request->filled('search')){$s=$request->string('search')->toString();$cols=Schema::getColumnListing($table);$q->where(function($w)use($cols,$s){foreach($cols as $c)if(in_array($c,['name','sku','code','type','reference','po_number','status','email','location','notes']))$w->orWhere($c,'like',"%$s%");});}
  if($resource==='products')$q->leftJoin('categories','products.category_id','=','categories.id')->select('products.*','categories.name as category_name');
  if($resource==='stock-movements')$q->leftJoin('products','stock_movements.product_id','=','products.id')->leftJoin('warehouses','stock_movements.warehouse_id','=','warehouses.id')->select('stock_movements.*','products.name as product_name','products.sku','warehouses.name as warehouse_name');
  return response()->json($q->orderByDesc($table.'.id')->paginate(min(max((int)$request->input('per_page',50),1),100)));
 }
 public function store(Request $r,string $resource) {
  $table=$this->table($resource);
  $rules=match($resource){
   'products'=>['sku'=>'required|string|max:100|unique:products,sku','name'=>'required|string|max:255','category_id'=>'nullable|exists:categories,id','unit'=>'nullable|string|max:30','unit_cost'=>'nullable|numeric|min:0','reorder_level'=>'nullable|numeric|min:0','is_active'=>'boolean'],
   'categories'=>['name'=>'required|string|max:255|unique:categories,name'],
   'warehouses'=>['code'=>'required|string|max:50|unique:warehouses,code','name'=>'required|string|max:255'],
   'suppliers'=>['name'=>'required|string|max:255','email'=>'nullable|email'],
   'purchase-orders'=>['po_number'=>'required|string|unique:purchase_orders,po_number','supplier_id'=>'nullable|exists:suppliers,id','status'=>'required|in:draft,ordered,partially_received,received,cancelled','currency'=>'nullable|string|size:3'],
   default=>[]
  };
  $data=$r->validate($rules);$allowed=Schema::getColumnListing($table);$data=array_intersect_key($data,array_flip($allowed));$data['created_at']=now();$data['updated_at']=now();
  $id=DB::table($table)->insertGetId($data);return response()->json(DB::table($table)->find($id),201);
 }
 public function show(string $resource,int $id){$row=DB::table($this->table($resource))->find($id);abort_if(!$row,404,'Record not found');return response()->json($row);}
 public function update(Request $r,string $resource,int $id){$table=$this->table($resource);abort_unless(DB::table($table)->where('id',$id)->exists(),404);$data=$r->except(['id','created_at','updated_at']);$data=array_intersect_key($data,array_flip(Schema::getColumnListing($table)));$data['updated_at']=now();DB::table($table)->where('id',$id)->update($data);return response()->json(DB::table($table)->find($id));}

 public function addPurchaseOrderItem(Request $r,int $id){
  abort_unless(DB::table('purchase_orders')->where('id',$id)->exists(),404,'Purchase order not found');
  $d=$r->validate(['product_id'=>'required|exists:products,id','quantity'=>'required|numeric|gt:0','unit_cost'=>'required|numeric|min:0']);
  return DB::transaction(function()use($d,$id){
   $line=round((float)$d['quantity']*(float)$d['unit_cost'],2);
   $itemId=DB::table('purchase_order_items')->insertGetId(['purchase_order_id'=>$id,'product_id'=>$d['product_id'],'quantity'=>$d['quantity'],'unit_cost'=>$d['unit_cost'],'line_total'=>$line,'created_at'=>now(),'updated_at'=>now()]);
   DB::table('purchase_orders')->where('id',$id)->increment('total',$line,['updated_at'=>now()]);
   return response()->json(DB::table('purchase_order_items')->find($itemId),201);
  });
 }
 public function destroy(string $resource,int $id){$n=DB::table($this->table($resource))->where('id',$id)->delete();abort_if(!$n,404);return response()->json(['message'=>'Deleted']);}
}
