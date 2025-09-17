<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:doctor|admin']);
    }

    // GET /api/consultations/{id}
    public function show($id)
    {
        return Consultation::with('patient','doctor','prescriptions')->findOrFail($id);
    }

    // POST /api/consultations
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'reason'     => 'nullable|string',
            'diagnosis'  => 'nullable|string',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id'  => auth()->id(),
            'reason'     => $validated['reason'] ?? null,
            'diagnosis'  => $validated['diagnosis'] ?? null,
        ]);

        return response()->json($consultation, 201);
    }
}
