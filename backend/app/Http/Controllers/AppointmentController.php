<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $q = Appointment::query();
                                    
        if ($search = $request->input('search')) {
            $q->where('patient_name', 'like', "%{$search}%")
              ->orWhere('doctor', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return $q->orderBy('datetime', 'asc')->paginate(10);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        return Appointment::create($data);
    }

    public function show(Appointment $appointment)
    {
        return $appointment;
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $this->validateData($request);
        $appointment->update($data);
        return $appointment;
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return response()->noContent();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'patient_name' => 'required|string|max:255',
            'doctor' => 'required|string|max:255',
            'datetime' => 'required|date',
            'status' => ['required', Rule::in(['Scheduled', 'Done', 'Cancelled'])],
        ]);
    }
}
