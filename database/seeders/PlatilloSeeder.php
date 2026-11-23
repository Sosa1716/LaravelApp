<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Platillo;
use Illuminate\Database\Seeder;

class PlatilloSeeder extends Seeder
{
    public function run(): void
    {
        $comidas = Categoria::where('nombre', 'Comidas')->first();
        $bebidas = Categoria::where('nombre', 'Bebidas')->first();
        $postres = Categoria::where('nombre', 'Postres')->first();

        $platillos = [
            [
                'categoria_id' => $comidas?->id ?? 1,
                'nombre' => 'Hamburguesa Clásica con Queso',
                'descripcion' => 'Carne 200g, queso cheddar, lechuga y tomate con papas fritas',
                'precio' => 145.00,
                'disponible' => true,
            ],
            [
                'categoria_id' => $comidas?->id ?? 1,
                'nombre' => 'Tacos de Arrachera (3 pzas)',
                'descripcion' => 'Tortilla de maíz, guacamole, cebollitas asadas',
                'precio' => 160.00,
                'disponible' => true,
            ],
            [
                'categoria_id' => $comidas?->id ?? 1,
                'nombre' => 'Pizza Artesanal Margarita',
                'descripcion' => 'Salsa de tomate casera, mozzarella fresca y albahaca',
                'precio' => 180.00,
                'disponible' => true,
            ],
            [
                'categoria_id' => $bebidas?->id ?? 2,
                'nombre' => 'Limonada Mineral con Menta',
                'descripcion' => 'Jugo natural de limón con hierbabuena fresca',
                'precio' => 45.00,
                'disponible' => true,
            ],
            [
                'categoria_id' => $postres?->id ?? 3,
                'nombre' => 'Pastel de Chocolate Fundido',
                'descripcion' => 'Volcán de chocolate caliente con bola de helado de vainilla',
                'precio' => 85.00,
                'disponible' => true,
            ],
        ];

        foreach ($platillos as $item) {
            Platillo::updateOrCreate(
                ['nombre' => $item['nombre']],
                $item
            );
        }
    }
}
