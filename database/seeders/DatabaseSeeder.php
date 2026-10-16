<?php

namespace Database\Seeders;

use App\Models\{User, Product, Cart, Order, OrderItem, ShippingAddress, GlobalBanner};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Analyst
        User::create([
            'name' => 'Admin FusaShop', 'email' => 'admin@fusashop.com',
            'password' => Hash::make('password123'), 'role' => 'analyst',
        ]);

        // Merchants
        $m1 = User::create([
            'name' => 'Tienda Andina', 'email' => 'tienda@fusashop.com',
            'password' => Hash::make('password123'), 'role' => 'merchant',
        ]);
        $m2 = User::create([
            'name' => 'Tech Store', 'email' => 'tech@fusashop.com',
            'password' => Hash::make('password123'), 'role' => 'merchant',
        ]);

        // Consumers
        $c1 = User::create([
            'name' => 'Juan Pérez', 'email' => 'juan@fusashop.com',
            'password' => Hash::make('password123'), 'role' => 'consumer',
        ]);
        $c2 = User::create([
            'name' => 'María García', 'email' => 'maria@fusashop.com',
            'password' => Hash::make('password123'), 'role' => 'consumer',
        ]);
        Cart::create(['user_id' => $c1->id]);
        Cart::create(['user_id' => $c2->id]);

        foreach ([[$c1, 'Juan Pérez', 'Cra 7', '# 45-23', 'Chapinero', 'Bogotá', 'DC', '110231'],
                  [$c2, 'María García', 'Calle 6', '# 7-12', 'Centro', 'Fusagasugá', 'CU', '252211']] as [$u, $name, $street, $num, $district, $city, $state, $zip]) {
            ShippingAddress::create([
                'user_id' => $u->id, 'label' => 'Casa', 'recipient' => $name, 'phone' => '3001234567',
                'street' => $street, 'number' => $num, 'district' => $district, 'city' => $city,
                'state' => $state, 'postal_code' => $zip, 'is_default' => true,
            ]);
        }

        $categories = ['Ropa','Electrónica','Hogar','Deportes','Alimentos'];
        $products = [];
        foreach ([
            ['Camiseta Andina', 'Ropa', 45000, 50, $m1->id],
            ['Ruana de lana', 'Ropa', 120000, 30, $m1->id],
            ['Sombrero vueltiao', 'Ropa', 85000, 20, $m1->id],
            ['Mochila wayuu', 'Hogar', 180000, 15, $m1->id],
            ['Café especial 500g', 'Alimentos', 35000, 100, $m1->id],
            ['Audífonos BT', 'Electrónica', 95000, 40, $m2->id],
            ['Cargador USB-C', 'Electrónica', 28000, 80, $m2->id],
            ['Mouse inalámbrico', 'Electrónica', 55000, 35, $m2->id],
            ['Teclado mecánico', 'Electrónica', 145000, 20, $m2->id],
            ['Balón de fútbol', 'Deportes', 65000, 25, $m2->id],
        ] as [$name, $cat, $price, $stock, $mid]) {
            $products[] = Product::create([
                'merchant_id' => $mid,
                'name'        => $name,
                'slug'        => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
                'description' => "Producto de alta calidad: {$name}. Hecho con los mejores materiales.",
                'price'       => $price,
                'stock'       => $stock,
                'category'    => $cat,
                'is_active'   => true,
                'weight_grams' => in_array($cat, ['Ropa','Hogar']) ? 400 : 600,
                'length_cm'   => 30,
                'width_cm'    => 22,
                'height_cm'   => 10,
                'low_stock_threshold' => 5,
            ]);
        }

        // Sample order
        $order = Order::create([
            'user_id' => $c1->id, 'total' => 140000,
            'status' => 'delivered', 'payment_status' => 'paid',
            'shipping_address' => 'Cra 7 # 45-23, Bogotá',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $products[0]->id, 'quantity' => 2, 'price' => 45000]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $products[5]->id, 'quantity' => 1, 'price' => 95000]);

        // Global Commercial / Advertising Banners
        GlobalBanner::create([
            'title' => 'Café Especial de Origen Sumapaz — Finca & Tostión Tradicional',
            'image_path' => 'https://images.unsplash.com/photo-1509785307050-d4066910ec1e?q=80&w=1400&auto=format&fit=crop',
            'link_url' => '/catalogo?category=Alimentos',
            'is_active' => true,
            'sort_order' => 1
        ]);
        GlobalBanner::create([
            'title' => 'Ciudad Jardín: Viveros & Flores Exóticas de Fusagasugá',
            'image_path' => 'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?q=80&w=1400&auto=format&fit=crop',
            'link_url' => '/catalogo?category=Hogar',
            'is_active' => true,
            'sort_order' => 2
        ]);
        GlobalBanner::create([
            'title' => 'FusaTech Distribución Mayorista & Envíos Inmediatos',
            'image_path' => 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?q=80&w=1400&auto=format&fit=crop',
            'link_url' => '/catalogo?category=Electrónica',
            'is_active' => true,
            'sort_order' => 3
        ]);
    }
}
