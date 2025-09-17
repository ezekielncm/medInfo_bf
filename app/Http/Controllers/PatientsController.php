<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class PatientsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    // GET /api/patients
    public function index()
    {
        $this->authorize('viewAny', Patient::class);

        return Patient::with('user', 'doctors')->paginate(20);
    }

    // GET /api/patients/{id}
    public function show($id)
    {
        $patient = Patient::with('user','doctors')->findOrFail($id);
        $this->authorize('view', $patient);

        return $patient;
    }

    // POST /api/patients
    public function store(Request $request)
    {
        $this->authorize('create', Patient::class);

        $validated = $request->validate([
            'name'        => 'required',
            'email'       => 'required|email|unique:users',
            'password'    => 'required|min:8',
            'national_id' => 'required|unique:patients',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);
        $user->assignRole('patient');

        $patient = Patient::create([
            'user_id'     => $user->id,
            'national_id' => $validated['national_id'],
        ]);

        return response()->json($patient, 201);
    }
}
