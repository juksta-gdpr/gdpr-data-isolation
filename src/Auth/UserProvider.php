<?php

namespace Juksta\GdprDataIsolation\Auth;

use Illuminate\Support\Str;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\UserProvider as UserProviderContract;
use Illuminate\Support\Facades\DB;


class UserProvider extends EloquentUserProvider implements UserProviderContract
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials)) {
            return;
        }

        $query = $this->createModel()->newQuery();

        $tokenData = DB::connection('mysql_gdpr')->select(DB::raw("
                SELECT pil.token
                FROM personal_information_lookup pil
                JOIN personal_information pi ON (pi.id = pil.personal_information_id)
                WHERE pi.db_source = :dbSource AND pi.value = :value
                LIMIT 1
            "), array(
            'dbSource' => 'users.email',
            'value' => $credentials['email']
        )); 

        if ($tokenData) {
            $credentials['email'] = $tokenData[0]->token;
        }
       
        foreach ($credentials as $key => $value) {
            if (!Str::contains($key, 'password')) {
                $query->where($key, $value);
            }
        }
        $user = $query->first();

		return $user;
    }
}
