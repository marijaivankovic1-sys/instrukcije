<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rezervacija extends Model
{
    protected $table = 'rezervacije';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'termin_id',
        'student_id',
        'napomena',
        'privitak_putanja',
        'status',
        'datum_rezervacije',
    ];

    public function termin()
    {
        return $this->belongsTo(Termin::class, 'termin_id');
    }

    public function student()
    {
        return $this->belongsTo(Korisnik::class, 'student_id');
    }
}