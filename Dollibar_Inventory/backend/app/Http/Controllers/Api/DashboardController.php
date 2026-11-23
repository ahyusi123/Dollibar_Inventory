<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index() {
        $count=fn($table)=>DB::table($table)->count();
        $assetStatuses=DB::table('assets')->select('status',DB::raw('count(*) as total'))->groupBy('status')->get();
        $assetTypes=DB::table('assets')->select('category',DB::raw('count(*) as total'))->groupBy('category')->get();
        $recent=DB::table('assets')->orderByDesc('id')->limit(6)->get();
        return response()->json([
            'stats'=>[
                'total_assets'=>$count('assets'),'employees'=>$count('employees'),
                'assigned_assets'=>DB::table('assets')->where('status','In Use')->count(),
                'available_assets'=>DB::table('assets')->where('status','Available')->count(),
                'in_repair'=>DB::table('assets')->whereIn('status',['In Repair','In Service'])->count(),
                'active_licenses'=>DB::table('software_licenses')->where('status','Active')->count(),
                'open_repairs'=>DB::table('maintenance_records')->whereIn('status',['Open','In Progress'])->count(),
                'pending_transfers'=>DB::table('transfers')->where('status','Pending')->count()
            ],
            'status_breakdown'=>$assetStatuses,'category_breakdown'=>$assetTypes,'recent_assets'=>$recent
        ]);
    }
    public function assetReport() {
        return response()->json([
            'total'=>DB::table('assets')->count(),
            'by_status'=>DB::table('assets')->select('status',DB::raw('count(*) as total'))->groupBy('status')->get(),
            'by_site'=>DB::table('assets')->select('location',DB::raw('count(*) as total'))->groupBy('location')->get()
        ]);
    }
}
