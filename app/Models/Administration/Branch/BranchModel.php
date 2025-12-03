<?php
namespace App\Models\Administration\Branch;
use Illuminate\Database\Eloquent\Model;
class BranchModel extends Model
{
    protected $table = 'catalogo.cat_rama';
    // primary field
    protected $primaryKey = 'id_cat_rama';
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