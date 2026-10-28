<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $fillable = ['carne', 'nombre', 'apellidos', 'docente_id'];

    public function consejero()
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function matriculas()
    {
        return $this->hasMany(EstudianteMatriculado::class);
    }

    public function grupos()
    {
        return $this->belongsToMany(Grupo::class, 'estudiante_matriculados', 'estudiante_id', 'grupo_id')
            ->withPivot('nota')
            ->withTimestamps();
    }
}
