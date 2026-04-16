<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\FaceEnrollment;
use App\Services\FaceRecognitionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $faceRecognitionService;

    public function __construct(FaceRecognitionService $faceRecognitionService)
    {
        $this->faceRecognitionService = $faceRecognitionService;
    }

    //checkin
    public function checkin(Request $request)
    {
        //validate lat and long
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = $request->user();

        // Check if company uses face recognition
        $company = Company::first();
        if ($company && $company->attendance_type === 'face') {
            $faceVerificationResult = $this->verifyFaceForAttendance($user->id, $request->file('photo'));

            if (!$faceVerificationResult['success']) {
                return response()->json([
                    'message' => $faceVerificationResult['message'],
                    'similarity' => $faceVerificationResult['similarity'] ?? 0,
                ], 403);
            }
        }

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('assets/attendances', 'public');
        }

        //save new attendance
        $attendance = new Attendance;
        $attendance->user_id = $user->id;
        $attendance->date = date('Y-m-d');
        $attendance->time_in = date('H:i:s');
        $attendance->latlon_in = $request->latitude . ',' . $request->longitude;
        $attendance->photo_in = $photoPath;
        $attendance->save();

        return response([
            'message' => 'Checkin success',
            'attendance' => $attendance
        ], 200);
    }

    //checkout
    public function checkout(Request $request)
    {
        //validate lat and long
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = $request->user();

        //get today attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', date('Y-m-d'))
            ->first();

        //check if attendance not found
        if (!$attendance) {
            return response(['message' => 'Checkin first'], 400);
        }

        // Check if company uses face recognition
        $company = Company::first();
        if ($company && $company->attendance_type === 'face') {
            $faceVerificationResult = $this->verifyFaceForAttendance($user->id, $request->file('photo'));

            if (!$faceVerificationResult['success']) {
                return response()->json([
                    'message' => $faceVerificationResult['message'],
                    'similarity' => $faceVerificationResult['similarity'] ?? 0,
                ], 403);
            }
        }

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('assets/attendances', 'public');
        }

        //save checkout
        $attendance->time_out = date('H:i:s');
        $attendance->latlon_out = $request->latitude . ',' . $request->longitude;
        $attendance->photo_out = $photoPath;
        $attendance->save();

        return response([
            'message' => 'Checkout success',
            'attendance' => $attendance
        ], 200);
    }

    /**
     * Verify face for attendance check-in/check-out
     *
     * @param int $userId User ID
     * @param \Illuminate\Http\UploadedFile $photo Uploaded photo
     * @return array Verification result
     */
    protected function verifyFaceForAttendance($userId, $photo)
    {
        try {
            // Get user's face enrollment
            $enrollment = FaceEnrollment::where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if (!$enrollment) {
                return [
                    'success' => false,
                    'message' => 'Face enrollment required. Please enroll your face first in profile settings.',
                    'similarity' => 0,
                    'needs_enrollment' => true
                ];
            }

            // Save photo temporarily
            $tempPath = $photo->store('assets/faces/temp', 'public');
            $fullPath = storage_path('app/public/' . $tempPath);

            // Verify face
            $result = $this->faceRecognitionService->verifyFace(
                $enrollment->face_embedding,
                $fullPath
            );

            // Delete temp photo
            Storage::disk('public')->delete($tempPath);

            if (!$result['success'] || !$result['is_match']) {
                return [
                    'success' => false,
                    'message' => 'Face verification failed. Please try again or contact admin.',
                    'similarity' => $result['similarity'] ?? 0,
                    'needs_enrollment' => false
                ];
            }

            return [
                'success' => true,
                'message' => 'Face verified',
                'similarity' => $result['similarity'] ?? 0
            ];

        } catch (\Exception $e) {
            Log::error('Face verification for attendance failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Face verification failed',
                'similarity' => 0
            ];
        }
    }

    //check is checkedin
    public function isCheckedin(Request $request)
    {
        //get today attendance
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('date', date('Y-m-d'))
            ->first();

        $isCheckout = $attendance ? $attendance->time_out : false;

        return response([
            'checkedin' => $attendance ? true : false,
            'checkedout' => $isCheckout ? true : false,
        ], 200);
    }

    //index
    public function index(Request $request)
    {
        $date = $request->input('date');

        $currentUser = $request->user();

        $query = Attendance::where('user_id', $currentUser->id);

        if ($date) {
            $query->where('date', $date);
        }

        $attendance = $query->get();

        return response([
            'message' => 'Success',
            'data' => $attendance
        ], 200);
    }
}
