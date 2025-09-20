<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;

class UserLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 1. Extract domain from email
        $emailDomain = substr(strrchr($request->email, "@"), 1); // e.g. user@company.com => company.com

        // 2. Find company by domain
        $company = Company::where('domain', $emailDomain)->first();
        if (!$company) {
            return response()->json(['message' => 'Company not found for this email domain'], 404);
        }

        // 3. Point tenant connection to this company DB
        Config::set("database.connections.tenant", [
            "driver" => "mysql",
            "host" => env("DB_HOST", "127.0.0.1"),
            "port" => env("DB_PORT", "3306"),
            "database" => $company->database_name,
            "username" => env("DB_USERNAME", "root"),
            "password" => env("DB_PASSWORD", ""),
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
        ]);

        // 4. Authenticate user from tenant DB
        $tenantUser = (new User())->setConnection("tenant")
            ->where("email", $request->email)
            ->first();

        if (!$tenantUser || !Hash::check($request->password, $tenantUser->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        // 5. Load/create the user in the main DB (for tokens)
        $mainUser = User::where('email', $tenantUser->email)
            ->where('company_id', $company->id)
            ->first();

        if (!$mainUser) {
            $mainUser = User::create([
                'name'       => $tenantUser->name,
                'email'      => $tenantUser->email,
                'password'   => $tenantUser->password,
                'company_id' => $company->id,
                'role'       => $tenantUser->role,
                'max_limit'  => $tenantUser->max_limit,
                'created_by' => $tenantUser->created_by,
            ]);
        }

        // 6. Issue token from main DB
        $token = $mainUser->createToken('authToken')->plainTextToken;

        return response()->json([
            "message" => "Login successful",
            "token"   => $token,
            "user"    => $tenantUser,
            "company" => $company,
        ]);
    }
}
