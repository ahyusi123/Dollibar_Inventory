<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder {
 public function run(): void {
  User::firstOrCreate(['email'=>'admin@dollibar.test'],['name'=>'System Administrator','password'=>'password','role'=>'inventory_admin']);
  if (!DB::table('warehouses')->count()) DB::table('warehouses')->insert(['code'=>'MAIN','name'=>'Main Warehouse','location'=>'Lipa City','created_at'=>now(),'updated_at'=>now()]);
  if (!DB::table('categories')->count()) DB::table('categories')->insert([['name'=>'Office Supplies','created_at'=>now(),'updated_at'=>now()],['name'=>'IT Equipment','created_at'=>now(),'updated_at'=>now()]]);
  if (!DB::table('products')->count()) {
   DB::table('products')->insert([['sku'=>'PAPER-A4','name'=>'A4 Bond Paper','category_id'=>1,'unit'=>'ream','unit_cost'=>220,'reorder_level'=>20,'created_at'=>now(),'updated_at'=>now()],['sku'=>'MOUSE-USB','name'=>'USB Optical Mouse','category_id'=>2,'unit'=>'pcs','unit_cost'=>250,'reorder_level'=>10,'created_at'=>now(),'updated_at'=>now()]]);
   DB::table('stock_balances')->insert([['product_id'=>1,'warehouse_id'=>1,'quantity'=>55,'created_at'=>now(),'updated_at'=>now()],['product_id'=>2,'warehouse_id'=>1,'quantity'=>7,'created_at'=>now(),'updated_at'=>now()]]);
  }
  if (DB::table('employees')->count()) return;
  $people=[
   ['HIS-001','Maria Santos','maria@horizon.test','IT','IT Manager','Lipa City HQ'],
   ['HIS-002','Juan Dela Cruz','juan@horizon.test','Development','Software Developer','Makati City'],
   ['HIS-003','Ana Reyes','ana@horizon.test','Field Support','Technician','Clark, Pampanga'],
   ['HIS-004','Carlo Garcia','carlo@horizon.test','Finance','Finance Officer','Lipa City HQ'],
   ['HIS-005','Lea Mendoza','lea@horizon.test','Procurement','Procurement Staff','Lipa City HQ']
  ];
  foreach($people as $p) DB::table('employees')->insert(['employee_no'=>$p[0],'name'=>$p[1],'email'=>$p[2],'department'=>$p[3],'position'=>$p[4],'site'=>$p[5],'created_at'=>now(),'updated_at'=>now()]);
  $assets=[
   ['HIS-LAP-0001','Developer Laptop','Laptop','Dell','Latitude 5440','DL-5440-001','In Use','Good','Lipa City HQ',2,'2026-01-10','2028-01-10',65000],
   ['HIS-LAP-0002','Office Laptop','Laptop','HP','ProBook 440','HP-440-002','Available','Good','Makati City',null,'2026-02-01','2028-02-01',58000],
   ['HIS-DES-0001','Finance Desktop','Desktop','Lenovo','ThinkCentre','LN-TC-003','In Use','Good','Lipa City HQ',4,'2026-01-15','2028-01-15',42000],
   ['HIS-TAB-0001','Field Tablet','Tablet','Samsung','Tab Active','SA-TA-004','In Repair','Fair','Clark, Pampanga',3,'2025-08-20','2027-08-20',30000],
   ['HIS-MON-0001','Office Monitor','Monitor','LG','24MP','LG-24-005','Available','Good','Lipa City HQ',null,'2026-03-01','2027-03-01',8500],
   ['HIS-PHN-0001','Supervisor Phone','Mobile Phone','Samsung','A55','SM-A55-006','In Use','Good','Makati City',1,'2026-04-05','2027-04-05',22000]
  ];
  foreach($assets as $a) DB::table('assets')->insert(['asset_tag'=>$a[0],'name'=>$a[1],'category'=>$a[2],'brand'=>$a[3],'model'=>$a[4],'serial_number'=>$a[5],'status'=>$a[6],'condition'=>$a[7],'location'=>$a[8],'employee_id'=>$a[9],'purchase_date'=>$a[10],'warranty_end'=>$a[11],'purchase_cost'=>$a[12],'created_at'=>now(),'updated_at'=>now()]);
  DB::table('software_licenses')->insert([['software_name'=>'Microsoft 365 Business','vendor'=>'Microsoft','assigned_to'=>'IT Department','expiry_date'=>'2027-01-15','seats'=>100,'status'=>'Active','cost'=>120000,'created_at'=>now(),'updated_at'=>now()],['software_name'=>'Adobe Creative Cloud','vendor'=>'Adobe','assigned_to'=>'Design Team','expiry_date'=>'2026-12-10','seats'=>12,'status'=>'Active','cost'=>95000,'created_at'=>now(),'updated_at'=>now()]]);
  DB::table('maintenance_records')->insert(['asset_id'=>4,'issue'=>'Battery not charging','reported_by'=>'Ana Reyes','priority'=>'High','status'=>'In Progress','reported_date'=>now()->subDays(2),'created_at'=>now(),'updated_at'=>now()]);
  DB::table('notifications')->insert(['title'=>'Warranty reminder','message'=>'A registered asset warranty is approaching its expiry date.','type'=>'Warning','created_at'=>now(),'updated_at'=>now()]);
 }
}
