<?php

namespace App\Http\Controllers;

use App\Http\Resources\SurveyAnswerResource;
use App\Http\Resources\SurveyHomePageResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;

class HomeController extends Controller {
    //
    public function index(Request $request) {
        // get current user info
        $user = $request->user();

        // total number of survey for this user
        $totalSurveys = Survey::query()->where('user_id', $user->id)->count();

        // latest survey add
        $latestSurvey = Survey::query()->where('user_id', $user->id)->latest('created_at')->first();

        // total number of answer for this user
        $totalAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->count();

        // latest 5 answer
        $latestAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->orderBy('end_date', 'DESC') // ->latest('end_date')
            ->limit(5)
            ->getModels('survey_answers.*'); // selecte everything froms survey_answers table

        return [
            'totalSurveys' => $totalSurveys,
            'latestSurvey' => $latestSurvey ? new SurveyHomePageResource($latestSurvey) : null,
            'totalAnswers' => $totalAnswers,
            'latestAnswers' => SurveyAnswerResource::collection($latestAnswers),
        ];
    }
}
