<?php

namespace Juksta\GdprDataIsolation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PersonalInformation extends Model
{
    protected $connection = 'mysql_gdpr';

    protected $table = 'personal_information';

	protected $guarded = ['created_at', 'updated_at'];

	public $timestamps = false;

    public static function getPersonalDataExport($userId)
    {
        $sql = '
            SELECT
                pi.label,
                pi.value
            FROM
                personal_information pi
                JOIN personal_information_lookup pil ON (pil.personal_information_id = pi.id)
            WHERE
                pi.deleted != 1
                AND pil.deleted != 1
                AND pil.person_id = :userId
        ';
        $result = DB::connection('mysql_gdpr')->select(DB::raw($sql), [
            'userId' => $userId
        ]);

        return $result;
    }
}
