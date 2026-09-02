<?php

namespace App\Livewire\Forms;

use App\Models\Examen;
use Livewire\Form;

class ConfiguracionResultadosForm extends Form
{
    public float $puntajeAcierto = 1;

    public float $puntajeError = -0.01;

    public float $puntajeBlanco = 0.1;

    public float $puntajeMinimo = 50;

    public float $umbralFactor = 40;

    public bool $aplicarFactor = true;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'puntajeAcierto' => ['required', 'numeric', 'between:-100,100'],
            'puntajeError' => ['required', 'numeric', 'between:-100,100'],
            'puntajeBlanco' => ['required', 'numeric', 'between:-100,100'],
            'puntajeMinimo' => ['required', 'numeric', 'between:0,100'],
            'umbralFactor' => ['required', 'numeric', 'between:0,100'],
            'aplicarFactor' => ['boolean'],
        ];
    }

    public function cargar(Examen $examen): void
    {
        $this->puntajeAcierto = (float) $examen->puntaje_acierto_exa;
        $this->puntajeError = (float) $examen->puntaje_error_exa;
        $this->puntajeBlanco = (float) $examen->puntaje_blanco_exa;
        $this->puntajeMinimo = (float) $examen->puntaje_minimo_exa;
        $this->umbralFactor = (float) $examen->umbral_factor_dificultad_exa;
        $this->aplicarFactor = (bool) $examen->aplicar_factor_dificultad_exa;
    }

    /** @return array<string, float|bool> */
    public function datos(): array
    {
        return [
            'puntaje_acierto_exa' => $this->puntajeAcierto,
            'puntaje_error_exa' => $this->puntajeError,
            'puntaje_blanco_exa' => $this->puntajeBlanco,
            'puntaje_minimo_exa' => $this->puntajeMinimo,
            'umbral_factor_dificultad_exa' => $this->umbralFactor,
            'aplicar_factor_dificultad_exa' => $this->aplicarFactor,
        ];
    }
}
