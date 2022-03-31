<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\SurveyAnswerRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $user = $request->user();
        return SurveyResource::collection( Survey::where('user_id', $user->id)->paginate(8) );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreSurveyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSurveyRequest $request) {
        // get aull validated data
        $data = $request->validated();

        // image verification
        if( isset($data['image']) ) {
            $relativePath = $this->addImage( $data['image'] );
            $data['image'] = $relativePath;
        };
        
        // create new survey
        $survey = Survey::create( $data );

        // add questions & options to current survey
        foreach( $data['questions'] as $question ) {
            // affect the current survey id to question id
            $question['survey_id'] = $survey->id;
            // then add a question
            $this->addQuestion($question);
        }

        return new SurveyResource($survey);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function show(Survey $survey, Request $request) {
        // verifing if current user get autorization
        $user = $request->user();
        if($user->id !== $survey->user_id) {
            return abort(403, "Vous n'êtes pas autorisé à voir ce sondage.");
        };
        // render the survey
        return new SurveyResource($survey);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function publicSurveyView(Survey $survey) {
        // render the survey
        return new SurveyResource($survey);
    }

    /* */
    public function answerToSurveyQuestion (SurveyAnswerRequest $request, Survey $survey) {
        $validatedData = $request->validated();

        $surveyAnswer = SurveyAnswer::create([
            'survey_id' => $survey->id,
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s'),
        ]);

        foreach( $validatedData['surveyAnswers'] as $questionId => $answer) {
            $currentQuestion = SurveyQuestion::where(['id' => $questionId, 'survey_id' => $survey->id])->get();

            if( !$currentQuestion ) {
                return response("La question avec l'ID: \"$questionId\" est invalide.", 400);
            }

            $dataToSave = [
                'survey_question_id' => $questionId,
                'survey_answer_id' => $surveyAnswer->id,
                // if $answer is array, transforme it befor save
                'answer' => is_array($answer) ? json_encode($answer) : $answer,
            ];

            SurveyQuestionAnswer::create($dataToSave);
        }

        return response("", 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSurveyRequest  $request
     * @param  \App\Models\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSurveyRequest $request, Survey $survey) {
        // get request validated data
        $data = $request->validated();

        // image verification
        if( isset($data['image']) ) {
            $relativePath = $this->addImage( $data['image'] );
            $data['image'] = $relativePath;

            // if old image existe delete it and add new one
            if( $survey->image ) {
                $absolutePath = public_path($survey->image);
                File::delete($absolutePath);
            };
        };

        // update current survey
        $survey->update( $data );

        // get ids of existing question
        $existingIds = $survey->questions()->pluck('id')->toArray();

        // get ids of new question
        $newIds = Arr::pluck($data['questions'], 'id');

        // find out old question to delete
        $idsToDelete = array_diff($existingIds, $newIds);

        // find  new question to add
        $idsToAdd = array_diff($newIds, $existingIds);

        // delete old existing question
        SurveyQuestion::destroy($idsToDelete);

        // add  new question
        foreach( $data['questions'] as $question ) {
            if(in_array($question['id'], $idsToAdd)) {
                // affect the current survey id to question id
                $question['survey_id'] = $survey->id;
                // then add a question
                $this->addQuestion($question);
            }
        };

        // update the existing question
        $questionMap = collect($data['questions'])->keyBy('id');

        foreach( $survey->questions as $question ) {
            if(isset( $questionMap[ $question->id ] )) {
                // then update this question
                $this->updateQuestion($question, $questionMap[ $question->id ] );


            }
        };

        return new SurveyResource($survey);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function destroy(Survey $survey, Request $request) {
        // verifing if current user get autorization
        $user = $request->user();
        if($user->id !== $survey->user_id) {
            return abort(403, "Vous n'êtes pas autorisé à voir ce sondage.");
        };

        // delete survey
        $survey->delete();

        // delete image
        if( $survey->image ) {
            $absolutePath = public_path($survey->image);
            File::delete($absolutePath);
        };
        
        return response( '', 204);
    }

    // add image to data
    private function addImage( $image ) {
        // check if receve image url is valid base64 string
        if( preg_match( '/^data:image\/(\w+);base64,/', $image, $imageType )) {
            // take out the base64 encoded text without mine type ','
            $image = substr($image, strpos($image, ',') +1);

            // get the type of image (extenxion: jpg, png, etc.)
            $imageType =strtolower($imageType[1]); 

            // vérify the image type receve is a valide type accepted
            if( !in_array($imageType, [ 'jpg', 'png', 'jpeg', 'gif', 'webp', 'jfif' ] )) {
                throw new \Exception( "Invalide type d'image.");
            }

            // generate the name for image (url)
            $image = str_replace( ' ', '+', $image);
            $image = base64_decode($image);

            // vérify the image is false (with no error)
            if( $image == false ) {
                throw new \Exception( "base64_encod, erreur d'encodage, .");
            }

        } else {
            throw new \Exception( "L'URL ne correspont pas avec l'image.");
        }

        // path to save image (public/pictures)
        $dir = 'pictures/';
        $fileName = Str::random(). '.' .$imageType;

        // image path that we save in database
        $absolutePath = public_path($dir);
        $relativePath = $dir . $fileName;

        if( !File::exists($absolutePath)) {
            File::makeDirectory( $absolutePath, 0755, true );
        }

        file_put_contents($relativePath, $image);
        return $relativePath;
    }


    private function addQuestion($questionData) {
        // verify if data is set to current survey question data
        if( is_array($questionData['data'])) {
            // because in php we have data in array,
            //  we need to encode because the $data from front js is type Objet 
            $questionData['data'] = json_encode($questionData['data']);
        }

        // conditions
        $validatedData = Validator::make( $questionData, [
            'survey_id' => 'exists:App\Models\Survey,id',
            'type' => [
                'required', 
                Rule::in([
                    // comes from the survey model
                    Survey::QUESTION_INPUT_TEXT,
                    Survey::QUESTION_INPUT_SELECT,
                    Survey::QUESTION_INPUT_RADIO,
                    Survey::QUESTION_INPUT_CHECKBOX,
                    Survey::QUESTION_INPUT_TEXTAREA,
                ])
            ],
            'question' => 'required|string',
            'description' => 'nullable|string',
            'data' => 'present',
        ]);

        return SurveyQuestion::create($validatedData->validated());
    }


    private function updateQuestion(SurveyQuestion $questionToUpdate, $questionData) {
        if( is_array($questionData['data'])) {
            // because in php we have data in array,
            //  we need to encode because the $data from front js is type Objet 
            $questionData['data'] = json_encode($questionData['data']);
        }

        // conditions
        $validatedData = Validator::make( $questionData, [
            'id' => 'exists:App\Models\SurveyQuestion,id',
            'type' => [
                'required', 
                Rule::in([
                    // comes from the survey model
                    Survey::QUESTION_INPUT_TEXT,
                    Survey::QUESTION_INPUT_SELECT,
                    Survey::QUESTION_INPUT_RADIO,
                    Survey::QUESTION_INPUT_CHECKBOX,
                    Survey::QUESTION_INPUT_TEXTAREA,
                ])
            ],
            'question' => 'required|string',
            'description' => 'nullable|string',
            'data' => 'present',
        ]);

        return $questionToUpdate->update($validatedData->validated());
    }
}
