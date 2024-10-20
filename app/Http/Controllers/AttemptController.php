<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttemptRequest;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Option;
use App\Models\Quiz;
use Auth;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function store(StoreAttemptRequest $request)
    {
        $user = Auth::user();
        // Validate incoming data
        $validated = $request->validated();

        //Create the attempt
        $attempt = Attempt::create([
            'quiz_id' => $validated['quiz_id'],
            'user_id' => $user->id,
        ]);

        $totalCorrectAnswers = 0;

        // Loop through the answers and store them
        foreach ($validated['answers'] as $answerData) {
            // Find the option to check if it's correct
            $option = Option::find($answerData['option_id']);
            $isCorrect = $option->is_correct; // Whether the selected option is correct

            if ($isCorrect) {
                $totalCorrectAnswers++;
            }

            // Save the attempt answer
            AttemptAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $answerData['question_id'],
                'option_id' => $answerData['option_id'],
                'is_correct' => $isCorrect,
            ]);
        }

        $attempt->update([
            'score' => $totalCorrectAnswers,
        ]);

        return response()->json([
            'message' => 'Attempt created successfully!',
            'attempt_id' => $attempt->id,
            'score' => $totalCorrectAnswers,
        ], 201);
    }

    public function show($attemptId)
    {
        // Fetch the attempt along with questions, options, and user's answers
        $attempt = Attempt::with([
            'quiz',                             // Quiz details
            'attemptAnswers.option',            // User answer
            'attemptAnswers.question.options',  // Options
        ])->findOrFail($attemptId);

        // Prepare the data to send to the frontend
        $data = [
            'quiz_title' => $attempt->quiz->title,
            'quiz_description' => $attempt->quiz->description,
            'questions_count' => $attempt->quiz->questions_count,
            'score' => $attempt->score,
            'created_at' => $attempt->quiz->created_at,
            'questions' => $attempt->attemptAnswers->map(function ($attemptanswer) {
                return [
                    'id' => $attemptanswer->question->id,
                    'question' => $attemptanswer->question->question,
                    'options' => $attemptanswer->question->options->map(function ($option) use ($attemptanswer) {
                        return [
                            'id' => $option->id,
                            'option' => $option->option,
                            'is_correct' => $option->is_correct,
                            'is_selected' => $option->id == $attemptanswer->option->id,
                            'user_is_correct' => $option->id == $attemptanswer->option_id && $option->is_correct,
                        ];
                    }),
                ];
            }),
        ];

        // We use 'use ($answer)' in the line 63 so we can use the $answer variable in the closure
        // Without that we cant compare the '$option->id == $answer->option_id' because the $answer is outside the closure

        return response()->json([
            'message' => 'Attempt details retrieved successfully!',
            'attempt' => $data,
        ]);
    }

    public function getAllAttempts(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
        ]);

        $quiz = Quiz::find($validated['quiz_id']);

        $attempts = Attempt::where('quiz_id', $validated['quiz_id'])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Attempt details retrieved successfully!',
            'quiz' => $quiz,
            'attempts' => $attempts,
        ]);
    }

    public function getMyAttempts()
    {
        $user = Auth::user();
        $attempts = Attempt::where('user_id', $user->id)
            ->with('quiz')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'User Attempts retrieved successfully!',
            'my_attempts' => $attempts,
        ]);
    }

    // The purpose of this is to know if a user already attempted the quiz
    // To avoid taking the quiz multiple times.
    public function checkAttempt($quizId)
    {
        $user = Auth::user();

        $attempt = Attempt::where('user_id', $user->id)->where('quiz_id', $quizId)->first();

        if ($attempt) {
            return response()->json([
                'canAttempt' => false,
                'message' => 'User already attempted the quiz',
                'attempt_id' => $attempt->id,
            ]);
        } else {
            return response()->json([
                'canAttempt' => true,
                'message' => 'User can attempt the quiz',
            ]);
        }
    }

    public function destroy($attemptId)
    {
        // Find the attempt by its ID
        $attempt = Attempt::find($attemptId);

        // Check if the attempt exists and if the authenticated user is the owner
        if (!$attempt) {
            return response()->json([
                'message' => 'Unauthorized or attempt not found',
            ], 403);  // Forbidden if not found or user is unauthorized
        }

        // Delete the attempt and its related answers
        $attempt->attemptAnswers()->delete();  // Deletes related AttemptAnswer records if they exist
        $attempt->delete();  // Deletes the attempt

        return response()->json([
            'message' => 'Attempt deleted successfully!',
        ], 200);  // 200 OK on successful deletion
    }
}
