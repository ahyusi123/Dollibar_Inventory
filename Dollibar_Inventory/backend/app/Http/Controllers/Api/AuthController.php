<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;use App\Models\User;use Illuminate\Http\Request;use Illuminate\Support\Facades\Hash;use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
 public function login(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required|string']);$u=User::where('email',$d['email'])->first();if(!$u||!Hash::check($d['password'],$u->password))throw ValidationException::withMessages(['email'=>['Invalid credentials']]);if(!in_array($u->role,['inventory_admin','inventory_staff','integration']))throw ValidationException::withMessages(['email'=>['This account has no Inventory access']]);return response()->json(['token'=>$u->createToken('dollibar-inventory',['inventory:read','inventory:write','integration:receive'])->plainTextToken,'user'=>$u]);}
 public function logout(Request $r){$r->user()->currentAccessToken()?->delete();return response()->json(['message'=>'Logged out']);}
 public function me(Request $r){return response()->json($r->user());}
}
