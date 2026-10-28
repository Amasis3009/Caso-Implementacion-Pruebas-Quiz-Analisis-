<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $fillable = ['curso_id', 'numeroCiclo', 'ano', 'numeroGrupo', 'abierto'];

    protected $casts = ['abierto' => 'boolean'];

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    // 1..2 docentes
    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'docente_grupo');
    }

    public function matriculas()
    {
        return $this->hasMany(EstudianteMatriculado::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_matriculados', 'grupo_id', 'estudiante_id')
            ->withPivot('nota')
            ->withTimestamps();
    }
}
