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
            $q->where(function($query) use ($search) {
                $query->where('patient_name', 'like', "%{$search}%")
                      ->orWhere('doctor', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $q->orderBy('datetime', 'asc');

        // Return all appointments if 'all' parameter is present, otherwise paginate
        if ($request->query('all') === 'true') {
            return response()->json($q->get());
        }

        return response()->json($q->paginate($request->query('per_page', 10)));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['datetime'] = $this->normalizeDateTime($data['datetime']);
        $appointment = Appointment::create($data);
        return response()->json($appointment, 201);
    }

    public function show(Appointment $appointment)
    {
        return response()->json($appointment);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $this->validateData($request);
        $data['datetime'] = $this->normalizeDateTime($data['datetime']);
        $appointment->update($data);
        return response()->json($appointment);
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
    private function normalizeDateTime(string $value): string
    {
        // Convert "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD HH:MM:SS"
        if (str_contains($value, 'T')) {
            $value = str_replace('T', ' ', $value);
            if (strlen($value) === 16) {
                $value .= ':00';
            }
        }
        return $value;
    }
}
