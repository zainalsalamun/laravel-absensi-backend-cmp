<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FaceEnrollment;
use App\Services\FaceRecognitionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FaceEnrollmentController extends Controller
{
    protected $faceRecognitionService;

    public function __construct(FaceRecognitionService $faceRecognitionService)
    {
        $this->faceRecognitionService = $faceRecognitionService;
    }

    /**
     * Enroll face for the authenticated user
     * 
     * POST /api/face-enrollment
     */
    public function enroll(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        try {
            // Handle photo upload
            $photoPath = $request->file('photo')->store('assets/faces', 'public');
            $fullPath = storage_path('app/public/' . $photoPath);

            // Extract face features and create embedding
            $result = $this->faceRecognitionService->enrollFace($user->id, $fullPath);

            if (!$result['success']) {
                // Delete uploaded photo if enrollment fails
                Storage::disk('public')->delete($photoPath);
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            // Delete old face enrollment if exists
            $oldEnrollment = FaceEnrollment::where('user_id', $user->id)->first();
            if ($oldEnrollment) {
                if ($oldEnrollment->photo_url) {
                    Storage::disk('public')->delete($oldEnrollment->photo_url);
                }
                $oldEnrollment->delete();
            }

            // Create new face enrollment
            $enrollment = FaceEnrollment::create([
                'user_id' => $user->id,
                'face_embedding' => $result['embedding'],
                'photo_url' => $photoPath,
                'face_features' => $result['features'],
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Face enrolled successfully. You can now use face recognition for attendance.',
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'photo_url' => Storage::url($enrollment->photo_url),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Face enrollment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Face enrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify face for attendance
     * 
     * POST /api/face-verify
     */
    public function verify(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        try {
            // Get user's face enrollment
            $enrollment = FaceEnrollment::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No face enrollment found. Please enroll your face first.',
                    'needs_enrollment' => true
                ], 404);
            }

            // Handle photo upload
            $photoPath = $request->file('photo')->store('assets/faces/temp', 'public');
            $fullPath = storage_path('app/public/' . $photoPath);

            // Verify face
            $result = $this->faceRecognitionService->verifyFace(
                $enrollment->face_embedding,
                $fullPath
            );

            // Delete temp photo
            Storage::disk('public')->delete($photoPath);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'is_match' => $result['is_match'],
                'similarity' => $result['similarity'] ?? 0,
                'threshold' => $result['threshold'] ?? 0,
                'needs_enrollment' => false
            ], $result['is_match'] ? 200 : 403);

        } catch (\Exception $e) {
            Log::error('Face verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Face verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check face enrollment status
     * 
     * GET /api/face-status
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $enrollment = FaceEnrollment::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'is_enrolled' => false,
                'message' => 'No face enrollment found. Please enroll your face.',
                'data' => null
            ], 200);
        }

        return response()->json([
            'is_enrolled' => true,
            'message' => 'Face is enrolled',
            'data' => [
                'enrollment_id' => $enrollment->id,
                'photo_url' => Storage::url($enrollment->photo_url),
                'created_at' => $enrollment->created_at,
                'updated_at' => $enrollment->updated_at,
            ]
        ], 200);
    }

    /**
     * Remove face enrollment
     * 
     * DELETE /api/face-enrollment
     */
    public function remove(Request $request)
    {
        $user = $request->user();

        $enrollment = FaceEnrollment::where('user_id', $user->id)->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'No face enrollment found'
            ], 404);
        }

        try {
            // Delete photo
            if ($enrollment->photo_url) {
                Storage::disk('public')->delete($enrollment->photo_url);
            }

            $enrollment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Face enrollment removed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Face enrollment removal error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove face enrollment'
            ], 500);
        }
    }

    /**
     * Update face enrollment (re-enroll)
     * 
     * PUT /api/face-enrollment
     */
    public function update(Request $request)
    {
        return $this->enroll($request);
    }
}
