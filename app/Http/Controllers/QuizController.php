<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Quiz;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $quizzes = $user->quizzes()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Quizzes retrieved successfully!',
            'quizzes' => $quizzes,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuizRequest $request)
    {
        $user = Auth::user();

        $validated = $request->validated();

        // Create the quiz
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'questions_count' => $validated['questions_count'],
        ]);

        // Loop through questions and options
        foreach ($validated['questions'] as $questionData) {
            // Create each question for the quiz
            $question = $quiz->questions()->create([
                'question' => $questionData['question'],
            ]);

            // Create options for each question
            foreach ($questionData['options'] as $optionData) {
                $question->options()->create([
                    'option' => $optionData['option'],
                    'is_correct' => $optionData['is_correct'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Quiz created successfully!',
            'quiz' => $quiz->load('questions.options'), // Load questions and their options
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Quiz $quiz)
    {
        $quiz->load('questions.options');

        return response()->json([
            'message' => 'Quiz details retrieved successfully!',
            'quiz' => $quiz,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuizRequest $request, Quiz $quiz)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz)
    {
        // Ensure the authenticated user owns the quiz
    $user = Auth::user();

    if ($quiz->user_id !== $user->id) {
        return response()->json([
            'message' => 'Unauthorized access to delete this quiz!',
        ], 403);
    }

    // Delete the quiz
    $quiz->delete();

    return response()->json([
        'message' => 'Quiz deleted successfully!',
    ], 200);
    }
}
