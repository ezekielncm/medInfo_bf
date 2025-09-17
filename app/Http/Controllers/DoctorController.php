<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin|doctor']);
    }

    // GET /api/doctors
    public function index()
    {
        return User::role('doctor')->with('patients')->paginate(20);
    }

    // POST /api/doctors/{doctor}/assign-patient/{patient}
    public function assignPatient($doctorId, $patientId)
    {
        $doctor = User::findOrFail($doctorId);
        $patient = \App\Models\Patient::findOrFail($patientId);

        $doctor->patients()->syncWithoutDetaching([$patient->id]);

        return response()->json(['message' => 'Patient assigné au médecin']);
    }
}
