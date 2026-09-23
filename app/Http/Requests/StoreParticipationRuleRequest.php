<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreParticipationRuleRequest extends FormRequest { public function authorize(): bool { return $this->user()?->role === 'admin' && $this->user()?->status === 'active'; } public function rules(): array { return ['edition_id'=>['required','exists:intramural_editions,id'],'name'=>['required','string','max:255'],'max_sports'=>['required','integer','min:1','max:50'],'max_events'=>['required','integer','min:1','max:100'],'description'=>['nullable','string','max:2000'],'is_active'=>['nullable','boolean']]; } }
