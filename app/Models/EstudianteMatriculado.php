<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteMatriculado extends Model
{
    protected $table = 'estudiantes_matriculados';

    protected $fillable = ['grupo_id', 'estudiante_id', 'nota'];

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
