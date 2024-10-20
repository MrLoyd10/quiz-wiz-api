<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\SharedQuiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharedQuizController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'shared_with_user_ids' => 'required|array',
            'shared_with_user_ids.*' => 'exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
        ]);

        foreach ($validated['shared_with_user_ids'] as $sharedWithUserId) {
            // Prevent sharing the quiz with themselves
            if ($sharedWithUserId == $user->id) {
                continue; // Skip this iteration if the user is trying to share with themselves
            }

            // Check if the quiz is already shared with the user
            $alreadyShared = SharedQuiz::where('quiz_id', $validated['quiz_id'])
                ->where('shared_with_user_id', $sharedWithUserId)
                ->exists();

            if (! $alreadyShared) {
                SharedQuiz::create([
                    'quiz_id' => $validated['quiz_id'],
                    'shared_with_user_id' => $sharedWithUserId,
                    'shared_by_user_id' => $user->id,
                ]);
            }
        }

        // Return response
        return response()->json([
            'success' => true,
            'message' => 'Quiz shared successfully',
        ], 201);
    }

    public function getSharedQuizzes()
    {
        $user = Auth::user();

        $sharedQuizzes = SharedQuiz::where('shared_with_user_id', $user->id)
            ->with('quiz', 'sharedByUser', 'sharedWithUser')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'shared_quizzes' => $sharedQuizzes,
        ]);
    }

    public function getAlreadyShared($quizId)
    {
        $user = Auth::user();
        $sharedTo = SharedQuiz::where('quiz_id', $quizId)
            ->where('shared_by_user_id', $user->id)
            ->with('sharedWithUser')
            ->get();

        return response()->json([
            'success' => true,
            'persons' => $sharedTo,
        ]);
    }

    public function unshare($sharedId)
    {
        $user = Auth::user();
        $sharedQuiz = SharedQuiz::find($sharedId);

        // Ensure the current user is authorized to delete the quiz sharing
        if ($sharedQuiz->shared_by_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this quiz sharing',
            ], 403);
        }

        $sharedQuiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shared quiz deleted successfully',
        ], 200);
    }
}
