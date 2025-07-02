<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresses', function (Blueprint $table) {
            $table->id('id_adresse');
            $table->string('ligne1');
            $table->string('ligne2')->nullable();
            $table->string('ligne3')->nullable();
            $table->string('ville');
            $table->string('code_postal');
            $table->string('pays');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresses');
    }
};