<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    protected $guarded = [];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
