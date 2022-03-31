<?php

namespace App\Http\Resources;

use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class SurveyResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        // return parent::toArray($request);

        // clone of phe frontend survey model
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug, // if stats !== 'brouillon' => 'true'
            'status' => $this->status !== 'draft', // if image exist retur url, else null
            'image_url' => $this->image ? URL::to( $this->image) : null,
            'description' => $this->description,
            'questions' => SurveyQuestionResource::collection($this->questions),
            'expire_date' => (new DateTime($this->expire_date))->format('d/m/Y H:i:s'),
            'created_at' => (new DateTime($this->created_at))->format('d/m/Y H:i:s'),
            'updated_at' => (new DateTime($this->updated_at))->format('d/m/Y H:i:s'),
        ];
    }
}
