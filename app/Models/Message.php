<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'product_id', 'content', 'is_read'];

    public function sender()   { return $this->belongsTo(User::class, 'sender_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function product()  { return $this->belongsTo(Product::class); }

    public static function getBadWords()
    {
        return [
            'puta', 'mierda', 'marica', 'pendejo', 'pendeja', 'cabron', 'cabrón', 'idiota',
            'estupido', 'estúpido', 'imbecil', 'imbécil', 'malparido', 'malparida', 'gonorrea',
            'hijo de puta', 'hijueputa', 'jueputa', 'perra', 'pirobo', 'piroba', 'zorra', 'verga', 'culo',
            'carechimba', 'huevon', 'huevón', 'guevon', 'güevón', 'guevona',
            'maricon', 'maricón', 'cacorro', 'lambon', 'lambón', 'garbimba', 'gurrupleta',
            'catrehijueputa', 'triplehijueputa', 'careverga', 'caremonda', 'monda', 'mondá', 'chimbada',
            'hp', 'sapoperro', 'gonorreo', 'bobo', 'carechimba', 'gonorreita', 'malpari'
        ];
    }
}
