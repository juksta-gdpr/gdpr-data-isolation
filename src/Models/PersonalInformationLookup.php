<?php

namespace Juksta\GdprDataIsolation\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalInformationLookup extends Model
{
    protected $connection = 'mysql_gdpr';

    protected $table = 'personal_information_lookup';

	protected $guarded = ['created_at', 'updated_at'];

	public $timestamps = false;

	public function personalInformation()
	{
	    return $this->belongsTo('Juksta\GdprDataIsolation\Models\PersonalInformation');
	}

    public function actualPersonalInformation()
    {
        return $this->personalInformation()
            ->where([['deleted', '!=', 1]])
        ;
    }
}
