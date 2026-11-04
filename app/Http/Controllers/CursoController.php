<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Curso::all());
    }

    // Crear curso: guarda en la base de datos y devuelve la URL del nuevo recurso
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'sigla' => ['required', 'string', 'max:255', 'unique:cursos,sigla'],
            'nombreCurso' => ['required', 'string', 'max:255'],
            'creditos' => ['required', 'integer', 'min:1'],
        ]);

        $curso = Curso::create($datos);

        return response()->json($curso, 201)
            ->header('Location', url("/cursos/{$curso->id}"));
    }

    public function show(Curso $curso): JsonResponse
    {
        return response()->json($curso->load('grupos'));
    }
}