<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['preparando', 'listo', 'entregado'] as $estado) {
            DB::table('orden_detalles')
                ->where('estado', 'pendiente')
                ->whereIn('orden_id', function ($query) use ($estado) {
                    $query->select('id')
                        ->from('ordenes')
                        ->where('estado', $estado);
                })
                ->update(['estado' => $estado]);
        }

        DB::table('orden_detalles')
            ->where('estado', 'pendiente')
            ->whereIn('orden_id', function ($query) {
                $query->select('id')
                    ->from('ordenes')
                    ->where('estado', 'cerrada');
            })
            ->update(['estado' => 'entregado']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se revierte la sincronización de datos históricos.
    }
};
