<?php

declare(strict_types=1);

namespace Apiato\Core\Traits\TestsTraits\PhpUnit;

use Apiato\Core\Abstracts\Models\UserModel as User;
use Faker\Generator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use UnitEnum;

trait TestsAuthHelperTrait
{
    public string $userName = 'username';

    /**
     * Logged in user object.
     */
    protected ?User $testingUser = null;

    /**
     * User class used by factory to create testing user.
     */
    protected ?string $userClass = null;

    /**
     * Logged in user object.
     */
    protected Generator $faker;

    /**
     * Roles and permissions, to be attached on the user.
     */
    protected array $access = [
        'permissions' => '',
        'roles'       => '',
    ];

    /**
     * State name on User factory.
     */
    private ?string $userAdminState = null;

    /**
     * Create testing user as Admin.
     */
    private ?bool $createUserAsAdmin = null;

    /**
     * Same as `getTestingUser()` but always overrides the User Access
     * (roles and permissions) with null. So the user can be used to test
     * if unauthorized user tried to access your protected endpoint.
     *
     * @param null $userDetails
     */
    public function getTestingUserWithoutAccess($userDetails = null, bool $createUserAsAdmin = false): User
    {
        return $this->getTestingUser($userDetails, $this->getNullAccess(), $createUserAsAdmin);
    }

    /**
     * Try to get the last logged-in User, if not found then create new one.
     * Note: if $userDetails are provided it will always create new user, even
     * if another one was previously created during the execution of your test.
     * By default, Users will be given the Roles and Permissions found in the class
     * `$access` property. But the $access parameter can be used to override the
     * defined roles and permissions in the `$access` property of your class.
     *
     * @param array|User|null $userDetails       what to be attached on the User object
     * @param array|null      $access            roles and permissions you'd like to provide this user with
     * @param bool            $createUserAsAdmin should create testing user as admin
     */
    public function getTestingUser(array|User|null $userDetails = null, ?array $access = null, bool $createUserAsAdmin = false): User
    {
        $this->createUserAsAdmin = $createUserAsAdmin;
        $this->userClass         = $this->userClass ?? Config::get('apiato.tests.user-class');
        $this->userAdminState    = Config::get('apiato.tests.user-admin-state');

        return \is_null($userDetails) ? $this->findOrCreateTestingUser($userDetails, $access)
            : $this->createTestingUser($userDetails, $access);
    }

    public function getEntityResourceKey(string $entityClassName): string
    {
        return Str::snake(Str::pluralStudly(class_basename($entityClassName)));
    }

    private function findOrCreateTestingUser(array|User|null $userDetails = null, ?array $access = null): User
    {
        return $this->testingUser ?: $this->createTestingUser($userDetails, $access);
    }

    private function createTestingUser(array|User|null $userDetails = null, ?array $access = null): User
    {
        // Create new user
        $user = $userDetails instanceof User ? $userDetails : $this->factoryCreateUser($userDetails);

        // Assign user roles and permissions based on the access property
        $user = $this->setupTestingUserAccess($user, $access);

        // Authentication the user
        $this->actingAs($user, 'api');

        // Set the created user
        return $this->testingUser = $user;
    }

    private function factoryCreateUser(?array $userDetails = null): User
    {
        /** @var User $user */
        $user = str_replace('::class', '', $this->userClass);

        if ($this->createUserAsAdmin) {
            $state = $this->userAdminState;

            return $user::factory()->{$state}()->create($this->prepareUserDetails($userDetails));
        }

        return $user::factory()->create($this->prepareUserDetails($userDetails));
    }

    private function prepareUserDetails(?array $userDetails = null): array
    {
        $defaultUserDetails = [
            $this->userName => $this->faker->userName,
            'email'         => $this->faker->email,
            'password'      => 'testing-password',
        ];

        // if no user detail provided, use the default details, to find the password or generate one before encoding it
        return $this->prepareUserPassword($userDetails ?: $defaultUserDetails);
    }

    private function prepareUserPassword(?array $userDetails): ?array
    {
        // Get password from the user details or generate one
        $password = $userDetails['password'] ?? $this->faker->password;

        // Hash the password and set it back at the user details
        $userDetails['password'] = Hash::make($password);

        return $userDetails;
    }

    /**
     * @param User $user
     *
     * @return User
     */
    private function setupTestingUserAccess($user, ?array $access = null)
    {
        $access = $access ?: $this->getAccess();

        $user = $this->setupTestingUserPermissions($user, $access);

        return $this->setupTestingUserRoles($user, $access);
    }

    private function getAccess(): ?array
    {
        return $this->access ?? null;
    }

    /**
     * @param User $user
     *
     * @return User
     */
    private function setupTestingUserPermissions($user, ?array $access)
    {
        $permissions = self::preparingAccessValues($access, 'permissions');
        if (!empty($permissions)) {
            $user->givePermissionTo($permissions);
            $user = $user->fresh();
        }

        return $user;
    }

    /**
     * @param User $user
     *
     * @return User
     */
    private function setupTestingUserRoles($user, ?array $access)
    {
        $roles = self::preparingAccessValues($access, 'roles');
        if (!empty($roles) && !$user->hasRole($roles)) {
            $user->assignRole($roles);
            $user = $user->fresh();
        }

        return $user;
    }

    private function getNullAccess(): array
    {
        return [
            'permissions' => null,
            'roles'       => null,
        ];
    }

    private static function preparingAccessValues(array $access, string $key): array
    {
        if (!\array_key_exists($key, $access) || !$access[$key]) {
            return [];
        }

        $accessValues = $access[$key];

        // If a string and this string contains a delimiter, then convert this to an array.
        if (is_string($accessValues)) {
            $accessValues = explode('|', $accessValues);
        }

        // If it is not already an array, wrap it with an array.
        $accessValues = \Arr::wrap($accessValues);

        // If an element of an array is an enumeration, then there is a need to cast it to a string.
        return array_map(static fn(string|int|UnitEnum $accessValue): string|int => $accessValue instanceof UnitEnum ? $accessValue->value : $accessValue, $accessValues);
    }
}
