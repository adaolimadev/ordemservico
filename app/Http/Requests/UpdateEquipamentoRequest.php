<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Valida se o cliente existe e se está ativo (situacao = true)
            'cliente_id' => [
                'required', 
                'integer', 
                Rule::exists('clientes', 'id')->where('situacao', true)
            ],
            
            'tipo_equipamento_id' => ['required', 'integer', 'exists:tipos_equipamentos,id'],
            
            // Ignorar o ID do equipamento atual da validação unique
            'numero_serie' => [
                'required', 
                'string', 
                Rule::unique('equipamentos', 'numero_serie')->ignore($this->route('equipamento'))
            ],
            
            'marca' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'situacao' => ['boolean'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'cliente_id.exists' => 'O cliente selecionado não existe ou está inativo.',
            'numero_serie.unique' => 'Este número de série já está cadastrado em outro equipamento no sistema.',
        ];
    }
}