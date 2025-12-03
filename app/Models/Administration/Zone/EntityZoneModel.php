<?php
 
namespace App\Models\Administration\Zone;
 
use Illuminate\Database\Eloquent\Model;
 
class EntityZoneModel extends Model
{
    protected $table = 'catalogo.cat_zona';
 
    // primary field
    protected $primaryKey = 'id_cat_zona';
 
    // incremental status
    public $incrementing = true;
 
    // primary field type
    protected $keyType = 'int';
 
    // create and update fields
    public $timestamps = false;
 
    // fields
    protected $fillable = [
        'descripcion',
        'estatus',
    ];
 
    // field casting
    protected $casts = [
        'estatus' => 'boolean',
    ];
}