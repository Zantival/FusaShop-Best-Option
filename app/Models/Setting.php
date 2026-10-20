<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'description'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (!Schema::hasTable('settings')) {
            return $default;
        }

        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            $row = static::where('key', $key)->first();
            return $row ? $row->value : $default;
        });
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', ?string $description = null): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => (string) $value,
                'group' => $group,
                'description' => $description,
            ], fn ($v) => $v !== null)
        );

        Cache::forget("setting.{$key}");
        return $setting;
    }

    public static function commissionRate(): float
    {
        return (float) static::getValue('commission_rate', 8.0);
    }

    public static function bannerFee(): int
    {
        return (int) static::getValue('banner_fee', 150000);
    }

    public static function pqrsSlaBusinessDays(): int
    {
        return (int) static::getValue('pqrs_sla_business_days', 15);
    }

    public static function howToStartGuide(): array
    {
        $raw = static::getValue('how_to_start_guide');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'card_badge'        => '¿Nuevo en FusaShop?',
            'card_title'        => '¿No sabes cómo empezar?',
            'card_subtitle'     => 'Haz clic aquí y te guiamos en 1 minuto: elige si deseas comprar productos locales o formalizar, vender y certificar tu negocio en Fusagasugá.',
            'card_button_text'  => 'Comenzar Aquí',
            
            // Comprador
            'buyer_title'       => '¿Cómo Comprar en FusaShop?',
            'buyer_description' => 'Guía rápida para explorar comercios de Fusagasugá, pagar de forma 100% segura con pasarela Wompi, ganar FusaCoins y recibir tu pedido rastreado con Envia.com.',
            'buyer_video_url'   => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'buyer_steps'       => "1. Registro Rápido con Código: Crea tu cuenta y confírmala con el código de 6 dígitos que llega a tu correo.\n2. Explora Productos & Servicios: Encuentra productos agrícolas del Sumapaz y reserva en restaurantes y hoteles.\n3. Ahorra con FusaCoins: Gana 1 punto por cada $10.000 COP y aplícalo en el carrito para pagar menos.\n4. Pagos Wompi y Rastreo: Paga con Nequi, PSE o tarjetas y sigue tu pedido con guía Envia.com en tiempo real.",

            // Vendedor
            'seller_title'       => '¿Cómo Vender y Formalizar tu Negocio?',
            'seller_description' => 'Aprende a registrar tu tienda, formalizarte ante la DIAN y Cámara de Comercio y obtener tu Chulito de Comercio Verificado.',
            'seller_video_url'   => '',
            'seller_why_verify'  => '¿Por qué es necesario verificar tu negocio y en qué te ayuda? Obtener la certificación con RUT y Cámara de Comercio activa la insignia oficial de Comerciante Verificado ✅. Esto genera máxima confianza en los compradores, incrementa tus ventas hasta un 300%, te da acceso prioritario a pautar en los banners principales de FusaShop y agiliza las dispersiones bancarias de tus ingresos directamente a tu cuenta bancaria sin retenciones cautelares.',
            'seller_steps'       => "1. Registro Comercial: Crea tu cuenta con rol de Comerciante y valida tu correo con el código de 6 números.\n2. Tramita tu RUT en la DIAN: Es 100% gratuito en dian.gov.co. Genera el Formulario 001 con tu actividad económica comercial.\n3. Matrícula en Cámara de Comercio: Registra tu establecimiento ante la Cámara de Comercio de Bogotá/Fusagasugá y figura en el RUES nacional.\n4. Certificación y Chulito ✅: Sube tus documentos para que el Analista active tu insignia oficial de verificación tras cotejar tu NIT.",
            'dian_url'           => 'https://www.dian.gov.co',
            'rues_url'           => 'https://www.rues.org.co'
        ];
    }

    public static function formatVideoEmbedUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = trim($url);
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches)) {
            return 'https://www.youtube-nocookie.com/embed/' . $matches[1];
        }
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)/', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[3];
        }
        return $url;
    }
}
