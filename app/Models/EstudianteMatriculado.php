<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EstudianteMatriculado extends Pivot
{
    protected $table = 'estudiantes_matriculados';

    // La tabla tiene columna id autoincremental
    public $incrementing = true;

    protected $casts = ['nota' => 'float'];

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
