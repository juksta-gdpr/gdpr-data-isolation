<?php

namespace Juksta\GdprDataIsolation\Eloquent;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

use Illuminate\Support\Facades\DB;

use Juksta\GdprDataIsolation\Models\PersonalInformationLookup,
    Juksta\GdprDataIsolation\Models\PersonalInformation,
    Juksta\GdprDataIsolation\Exceptions\PersonIdentificatorNotSetException,
    Juksta\GdprDataIsolation\Exceptions\CommonException as CommonGdprDataIsolationException
    ;

trait PrivateInformationOperational
{
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, array_keys($this->privateInformationAttributes))
            && !empty($value)
            && $personalInformationLookup = PersonalInformationLookup::where([['deleted', '!=', 1], ['token', $value]])->first()
        ) {
            $value = $personalInformationLookup
                ? $personalInformationLookup->actualPersonalInformation()->getResults()->value
                : null;
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, array_keys($this->privateInformationAttributes))) {
            $personalInformationLookup = !empty($this->$key) ? PersonalInformationLookup::where('token', $this->$key)->first() : null;

            if ($personalInformationLookup) {
                $personalInformationLookup->personalInformation->update(['value' => $value]);

                return $this;
            } else {
                if (!$this->getPersonIdentificatorGdrp()) {
                    throw new PersonIdentificatorNotSetException;
                }

                $token = (string) Uuid::uuid4();

                DB::connection('mysql_gdpr')->beginTransaction();

                DB::connection('mysql_gdpr')->unprepared("
                    UPDATE 
                        personal_information_lookup pil
                        JOIN personal_information pi ON (pi.id = pil.personal_information_id)
                    SET
                        pil.deleted = 1,
                        pi.deleted = 1
                    WHERE
                        pi.db_source = " . DB::connection()->getPdo()->quote($this->getTable() . '.' . $key) . "
                        AND pil.person_id = " . DB::connection()->getPdo()->quote($this->getPersonIdentificatorGdrp()) . "
                ");

                try {

                    $personalInformation = PersonalInformation::create([
                        'value' => $value,
                        'db_source' => $this->getTable() . '.' . $key,
                        'label' => $this->privateInformationAttributes[$key],
                    ]);

                    $personalInformationLookup = PersonalInformationLookup::create([
                        'token' => $token,
                        'person_id' => $this->getPersonIdentificatorGdrp(),
                        'personal_information_id' => $personalInformation->id,
                    ]);

                    DB::connection('mysql_gdpr')->commit();

                } catch (\Exception $e) {
                    DB::connection('mysql_gdpr')->rollback();

                    DB::connection('mysql_gdpr')->unprepared("
                        UPDATE 
                            personal_information_lookup pil 
                            JOIN personal_information pi ON (pi.id = pil.personal_information_id)
                        SET
                            pil.deleted = 0,
                            pi.deleted = 0
                        WHERE
                            pi.db_source = " . DB::connection()->getPdo()->quote($this->getTable() . '.' . $key) . "
                            AND pil.person_id = " . DB::connection()->getPdo()->quote($this->getPersonIdentificatorGdrp()) . "
                    ");

                    throw new CommonGdprDataIsolationException('Personal Information Records creation error', '100', $e);
                }

                DB::connection('mysql_gdpr')->unprepared("
                    DELETE pil, pi
                    FROM
                        personal_information_lookup pil
                        JOIN personal_information pi ON (pi.id = pil.personal_information_id)
                    WHERE
                        pil.deleted = 1
                ");

                $value = $token;
            }
        }

        return parent::setAttribute($key, $value);
    }

    public function getPersonIdentificatorGdrp()
    {
    }
}
