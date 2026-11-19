<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE ordenes
                MODIFY estado ENUM(
                    'pendiente',
                    'preparando',
                    'listo',
                    'entregado',
                    'cancelada',
                    'cerrada'
                ) NOT NULL DEFAULT 'pendiente'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE ordenes
                MODIFY estado ENUM(
                    'pendiente',
                    'preparando',
                    'listo',
                    'entregado',
                    'cancelada'
                ) NOT NULL DEFAULT 'pendiente'
            ");
        }
    }
};