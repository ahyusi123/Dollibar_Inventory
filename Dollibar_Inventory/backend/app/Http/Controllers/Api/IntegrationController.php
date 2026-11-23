<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
class IntegrationController extends Controller {
 public function index(){return response()->json(['system'=>'Dollibar Inventory','api_version'=>'v1','integrations'=>['hr'=>['purpose'=>'Employee reference lookup/sync','configured'=>true],'crater'=>['purpose'=>'Supplier and purchase-order invoice handoff','configured'=>(bool)config('services.crater.url')]]]);}
 public function createToken(Request $r){$d=$r->validate(['name'=>'required|string|max:100','abilities'=>'nullable|array']);$abilities=$d['abilities']??['inventory:read','inventory:write','integration:receive'];$token=$r->user()->createToken($d['name'],$abilities);return response()->json(['token'=>$token->plainTextToken,'abilities'=>$abilities],201);}
 public function hrEmployee(Request $r,string $externalId){
  $d=$r->validate(['employee_no'=>'nullable|string','name'=>'required|string','email'=>'nullable|email','department'=>'nullable|string','status'=>'nullable|string']);
  DB::table('external_references')->updateOrInsert(['system'=>'dolibarr_hr','external_id'=>$externalId],['entity_type'=>'employee','display_name'=>$d['name'],'payload'=>json_encode($d),'updated_at'=>now(),'created_at'=>now()]);
  return response()->json(['accepted'=>true,'external_id'=>$externalId]);
 }
 public function craterExport(Request $r,int $purchaseOrder){
  $po=DB::table('purchase_orders')->find($purchaseOrder);abort_if(!$po,404,'Purchase order not found');
  $items=DB::table('purchase_order_items')->join('products','products.id','=','purchase_order_items.product_id')->where('purchase_order_id',$purchaseOrder)->get(['products.sku','products.name','purchase_order_items.quantity','purchase_order_items.unit_cost','purchase_order_items.line_total']);
  $payload=['reference'=>$po->po_number,'currency'=>$po->currency,'total'=>$po->total,'supplier_id'=>$po->supplier_id,'items'=>$items];
  $url=config('services.crater.url');$key=config('services.crater.token');
  if(!$url)return response()->json(['message'=>'Crater URL is not configured','payload'=>$payload,'next_step'=>'Set CRATER_URL and CRATER_TOKEN, then retry.'],202);
  $response=Http::withToken($key)->acceptJson()->post(rtrim($url,'/').config('services.crater.invoice_endpoint'),$payload);
  if(!$response->successful())return response()->json(['message'=>'Crater request failed','status'=>$response->status(),'response'=>$response->json()],502);
  DB::table('purchase_orders')->where('id',$purchaseOrder)->update(['crater_invoice_id'=>(string)($response->json('data.id')??$response->json('id')),'updated_at'=>now()]);
  return response()->json(['sent'=>true,'crater_response'=>$response->json()]);
 }
 public function webhook(Request $r){
  $secret=config('services.integration.webhook_secret');
  abort_unless($secret && hash_equals($secret,(string)$r->header('X-Integration-Secret')),401,'Invalid integration secret');
  $d=$r->validate(['event'=>'required|string','source'=>'required|string','external_id'=>'nullable|string','data'=>'required|array']);
  DB::table('integration_events')->insert(['event'=>$d['event'],'source'=>$d['source'],'external_id'=>$d['external_id']??null,'payload'=>json_encode($d['data']),'created_at'=>now(),'updated_at'=>now()]);
  return response()->json(['received'=>true],202);
 }
}
