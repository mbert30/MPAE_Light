<?php

namespace App\Rules;

use App\Models\Devis;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class UniqueNumeroDevis implements ValidationRule
{
    protected $excludeId;

    public function __construct($excludeId = null)
    {
        $this->excludeId = $excludeId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Devis::whereHas('projet.client', function ($q) {
            $q->where('id_utilisateur', Auth::id());
        })->where('numero_devis', $value);

        if ($this->excludeId) {
            $query->where('id_devis', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('Ce numéro de devis est déjà utilisé.');
        }
    }
}