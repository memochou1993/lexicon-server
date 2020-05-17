<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @var User
     */
    private User $user;

    /**
     * Instantiate a new service instance.
     *
     * @param  User  $user
     */
    public function __construct(
        User $user
    ) {
        $this->user = $user;
    }

    /**
     * @param  string  $email
     * @param  string  $password
     * @param  string  $device
     * @return mixed
     */
    public function getToken(string $email, string $password, string $device): ?string
    {
        $user = $this->user->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user->createToken($device)->plainTextToken;
    }

    /**
     * @param  array  $relations
     * @return Model
     */
    public function getUser(array $relations): Model
    {
        return $this->user->with($relations)->find(Auth::id());
    }

    /**
     * @param  array  $data
     * @return Model
     */
    public function storeUser(array $data): Model
    {
        return $this->user->create($data);
    }

    /**
     * @param  array  $data
     * @return Model
     */
    public function updateUser(array $data): Model
    {
        $user = Auth::guard()->user();

        $user->update($data);

        return $user;
    }

    /**
     * @return int
     */
    public function destroyTokens(): int
    {
        return Auth::guard()->user()->tokens()->delete();
    }
}
