<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\Platillo;
use App\Models\Mesa;
use App\Models\User;

class OrdenSeeder extends Seeder
{
    public function run(): void
    {
        $mesero = User::whereHas('role', fn($q) => $q->where('nombre', 'Mesero'))->first() 
            ?? User::first();

        $mesas = Mesa::take(5)->get();
        $platillos = Platillo::all();

        if ($platillos->isEmpty() || $mesas->isEmpty() || !$mesero) {
            return;
        }

        // Orden 1: Pendiente
        $orden1 = Orden::updateOrCreate(
            ['id' => 1],
            [
                'mesa_id' => $mesas[0]->id,
                'user_id' => $mesero->id,
                'estado' => 'pendiente',
                'total' => 350.00
            ]
        );
        $orden1->detalles()->delete();
        OrdenDetalle::create([
            'orden_id' => $orden1->id,
            'platillo_id' => $platillos[0]->id,
            'cantidad' => 2,
            'precio' => $platillos[0]->precio,
            'notas' => 'Una sin cebolla, una término medio'
        ]);
        if (isset($platillos[3])) {
            OrdenDetalle::create([
                'orden_id' => $orden1->id,
                'platillo_id' => $platillos[3]->id,
                'cantidad' => 2,
                'precio' => $platillos[3]->precio,
                'notas' => 'Con poco hielo'
            ]);
        }

        // Orden 2: Preparando
        $orden2 = Orden::updateOrCreate(
            ['id' => 2],
            [
                'mesa_id' => $mesas[1]->id,
                'user_id' => $mesero->id,
                'estado' => 'preparando',
                'total' => 340.00
            ]
        );
        $orden2->detalles()->delete();
        if (isset($platillos[1])) {
            OrdenDetalle::create([
                'orden_id' => $orden2->id,
                'platillo_id' => $platillos[1]->id,
                'cantidad' => 1,
                'precio' => $platillos[1]->precio,
                'notas' => 'Tortillas bien doradas'
            ]);
        }
        if (isset($platillos[2])) {
            OrdenDetalle::create([
                'orden_id' => $orden2->id,
                'platillo_id' => $platillos[2]->id,
                'cantidad' => 1,
                'precio' => $platillos[2]->precio,
                'notas' => 'Masa delgada y bien cocida'
            ]);
        }

        // Orden 3: Listo
        $orden3 = Orden::updateOrCreate(
            ['id' => 3],
            [
                'mesa_id' => $mesas[2]->id,
                'user_id' => $mesero->id,
                'estado' => 'listo',
                'total' => 85.00
            ]
        );
        $orden3->detalles()->delete();
        if (isset($platillos[4])) {
            OrdenDetalle::create([
                'orden_id' => $orden3->id,
                'platillo_id' => $platillos[4]->id,
                'cantidad' => 1,
                'precio' => $platillos[4]->precio,
                'notas' => 'Extra salsa de chocolate'
            ]);
        }

        // Orden 4: Entregada / Pagada
        $orden4 = Orden::updateOrCreate(
            ['id' => 4],
            [
                'mesa_id' => $mesas[3]->id,
                'user_id' => $mesero->id,
                'estado' => 'entregado',
                'total' => 485.00
            ]
        );
        $orden4->detalles()->delete();
        OrdenDetalle::create([
            'orden_id' => $orden4->id,
            'platillo_id' => $platillos[0]->id,
            'cantidad' => 2,
            'precio' => $platillos[0]->precio,
            'notas' => null
        ]);
        if (isset($platillos[2])) {
            OrdenDetalle::create([
                'orden_id' => $orden4->id,
                'platillo_id' => $platillos[2]->id,
                'cantidad' => 1,
                'precio' => $platillos[2]->precio,
                'notas' => null
            ]);
        }
        \App\Models\Pago::updateOrCreate(
            ['orden_id' => $orden4->id],
            [
                'metodo' => 'tarjeta',
                'monto' => 485.00,
                'estado' => 'pagado'
            ]
        );
    }
}
