<?php

namespace App\Rules;

use App\Models\Facture;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class UniqueNumeroFacture implements ValidationRule
{
    protected $excludeId;

    public function __construct($excludeId = null)
    {
        $this->excludeId = $excludeId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Facture::whereHas('devis.projet.client', function ($q) {
            $q->where('id_utilisateur', Auth::id());
        })->where('numero_facture', $value);

        if ($this->excludeId) {
            $query->where('id_facture', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('Ce numéro de facture est déjà utilisé.');
        }
    }
}