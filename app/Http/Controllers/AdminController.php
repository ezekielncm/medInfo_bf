<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    public function index() {
        $users = User::paginate(20);
        return $this->paginated($users, 'Liste des utilisateurs');
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role'     => 'required|in:admin,doctor,patient,laborantin'
        ]);

        try {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['role']);

            // Audit
            $this->audit('user_created', [
                'user_id' => $user->id,
                'role'    => $validated['role']
            ], 201);

            return $this->success($user, 'Utilisateur créé avec succès', 201);

        } catch (\Exception $e) {
            return $this->error('Erreur lors de la création', ['exception' => $e->getMessage()], 500);
        }
    }
}
