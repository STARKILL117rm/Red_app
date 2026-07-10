<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('images', function (Blueprint $table) {
        $table->id();
        $table->string('url'); // Aquí se guarda el enlace o ruta de la foto
        
        // 🚨 ESTA LÍNEA ES LA MAGIA POLIMÓRFICA (Crea 'imageable_id' e 'imageable_type')
        $table->morphs('imageable'); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
