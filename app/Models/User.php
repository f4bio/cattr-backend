<?php

namespace App\Models;

use App\Mail\ResetPassword;
use App\Scopes\UserScope;
use App\Traits\HasRole;
use Carbon\Carbon;
use Eloquent as EloquentIdeHelper;
use Hash;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @apiDefine UserObject
 *
 * @apiSuccess {Integer}  user.id                       ID
 * @apiSuccess {String}   user.full_name                Name
 * @apiSuccess {String}   user.email                    Email
 * @apiSuccess {Integer}  user.company_id               Company ID
 * @apiSuccess {String}   user.avatar                   Avatar image url
 * @apiSuccess {Boolean}  user.screenshots_active       Should screenshots be captured
 * @apiSuccess {Boolean}  user.manual_time              Allow manual time edit
 * @apiSuccess {Integer}  user.screenshots_interval     Screenshots capture interval (seconds)
 * @apiSuccess {Boolean}  user.active                   Indicates active user when `TRUE`
 * @apiSuccess {String}   user.timezone                 User's timezone
 * @apiSuccess {ISO8601}  user.created_at               Creation DateTime
 * @apiSuccess {ISO8601}  user.updated_at               Update DateTime
 * @apiSuccess {ISO8601}  user.deleted_at               Delete DateTime or `NULL` if wasn't deleted
 * @apiSuccess {String}   user.url                     `Not used`
 * @apiSuccess {Boolean}  user.computer_time_popup     `Not used`
 * @apiSuccess {Boolean}  user.blur_screenshots        `Not used`
 * @apiSuccess {Boolean}  user.web_and_app_monitoring  `Not used`
 * @apiSuccess {String}   user.user_language            Language which is used for frontend translations and emails
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine UserParams
 *
 * @apiParam {Integer}  [id]                       ID
 * @apiParam {String}   [full_name]                Name
 * @apiParam {String}   [email]                    Email
 * @apiParam {Integer}  [company_id]               Company ID
 * @apiParam {String}   [avatar]                   Avatar image url
 * @apiParam {Boolean}  [screenshots_active]       Should screenshots be captured
 * @apiParam {Boolean}  [manual_time]              Allow manual time edit
 * @apiParam {Integer}  [screenshots_interval]     Screenshots capture interval (seconds)
 * @apiParam {Boolean}  [active]                   Indicates active user when `TRUE`
 * @apiParam {String}   [timezone]                 User's timezone
 * @apiParam {ISO8601}  [created_at]               Creation DateTime
 * @apiParam {ISO8601}  [updated_at]               Update DateTime
 * @apiParam {ISO8601}  [deleted_at]               Delete DateTime
 * @apiParam {String}   [url]                     `Not used`
 * @apiParam {Boolean}  [computer_time_popup]     `Not used`
 * @apiParam {Boolean}  [blur_screenshots]        `Not used`
 * @apiParam {Boolean}  [web_and_app_monitoring]  `Not used`
 * @apiParam {String}   [user_language]            Language which is used for frontend translations and emails
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine UserScopedParams
 *
 * @apiParam {Integer}  [users.id]                       ID
 * @apiParam {String}   [users.full_name]                Name
 * @apiParam {String}   [users.email]                    Email
 * @apiParam {Integer}  [users.company_id]               Company ID
 * @apiParam {String}   [users.avatar]                   Avatar image url
 * @apiParam {Boolean}  [users.screenshots_active]       Should screenshots be captured
 * @apiParam {Boolean}  [users.manual_time]              Allow manual time edit
 * @apiParam {Integer}  [users.screenshots_interval]     Screenshots capture interval (seconds)
 * @apiParam {Boolean}  [users.active]                   Indicates active user when `TRUE`
 * @apiParam {String}   [users.timezone]                 User's timezone
 * @apiParam {ISO8601}  [users.created_at]               Creation DateTime
 * @apiParam {ISO8601}  [users.updated_at]               Update DateTime
 * @apiParam {ISO8601}  [users.deleted_at]               Delete DateTime
 * @apiParam {String}   [users.url]                     `Not used`
 * @apiParam {Boolean}  [users.computer_time_popup]     `Not used`
 * @apiParam {Boolean}  [users.blur_screenshots]        `Not used`
 * @apiParam {Boolean}  [users.web_and_app_monitoring]  `Not used`
 * @apiParam {String}   [users.user_language]            Language which is used for frontend translations and emails
 *
 * @apiVersion 1.0.0
 */


/**
 * App\Models\User
 *
 * @property int $id
 * @property int $screenshots_active
 * @property int $manual_time
 * @property int $computer_time_popup
 * @property int $blur_screenshots
 * @property int $web_and_app_monitoring
 * @property int $screenshots_interval
 * @property int $active
 * @property int $nonce
 * @property int $client_installed
 * @property int $company_id
 * @property int $role_id
 * @property int $change_password
 * @property string $full_name
 * @property string $email
 * @property string $url
 * @property string $level
 * @property string $type
 * @property string $avatar
 * @property string $password
 * @property string $timezone
 * @property string $user_language
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $is_admin
 * @property bool $important
 * @property bool $invitation_sent
 * @property Role $role
 * @property string|null $remember_token
 * @property Project[]|Collection $projects
 * @property Task[]|Collection $tasks
 * @property TimeInterval[]|Collection $timeIntervals
 * @property-read int|null $notifications_count
 * @property-read int|null $projects_count
 * @property-read int|null $projects_relation_count
 * @property-read int|null $properties_count
 * @property-read int|null $tasks_count
 * @property-read int|null $time_intervals_count
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read Collection|ProjectsUsers[] $projectsRelation
 * @property-read Collection|Property[] $properties
 * @method static bool|null forceDelete()
 * @method static bool|null restore()
 * @method static EloquentBuilder|User whereActive($value)
 * @method static EloquentBuilder|User whereAvatar($value)
 * @method static EloquentBuilder|User whereBlurScreenshots($value)
 * @method static EloquentBuilder|User whereChangePassword($value)
 * @method static EloquentBuilder|User whereCompanyId($value)
 * @method static EloquentBuilder|User whereComputerTimePopup($value)
 * @method static EloquentBuilder|User whereCreatedAt($value)
 * @method static EloquentBuilder|User whereDeletedAt($value)
 * @method static EloquentBuilder|User whereEmail($value)
 * @method static EloquentBuilder|User whereFullName($value)
 * @method static EloquentBuilder|User whereId($value)
 * @method static EloquentBuilder|User whereImportant($value)
 * @method static EloquentBuilder|User whereManualTime($value)
 * @method static EloquentBuilder|User wherePassword($value)
 * @method static EloquentBuilder|User wherePoorTimePopup($value)
 * @method static EloquentBuilder|User whereRememberToken($value)
 * @method static EloquentBuilder|User whereScreenshotsActive($value)
 * @method static EloquentBuilder|User whereScreenshotsInterval($value)
 * @method static EloquentBuilder|User whereTimezone($value)
 * @method static EloquentBuilder|User whereUpdatedAt($value)
 * @method static EloquentBuilder|User whereUrl($value)
 * @method static EloquentBuilder|User whereWebAndAppMonitoring($value)
 * @method static EloquentBuilder|User newModelQuery()
 * @method static EloquentBuilder|User whereInvitationSent($value)
 * @method static EloquentBuilder|User whereIsAdmin($value)
 * @method static EloquentBuilder|User whereRoleId($value)
 * @method static EloquentBuilder|User whereType($value)
 * @method static EloquentBuilder|User whereUserLanguage($value)
 * @method static EloquentBuilder|User newQuery()
 * @method static EloquentBuilder|User query()
 * @method static QueryBuilder|User onlyTrashed()
 * @method static QueryBuilder|User withTrashed()
 * @method static QueryBuilder|User withoutTrashed()
 * @mixin EloquentIdeHelper
 * @property-read int|null $tokens_count
 * @property-read bool $delete_in_process
 */
class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use SoftDeletes;
    use HasRole;

    /**
     * table name from database
     * @var string
     */
    protected $table = 'users';

    /**
     * @var array
     */
    protected $with = [
        'role',
        'projectsRelation.role',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name',
        'email',
        'url',
        'company_id',
        'avatar',
        'screenshots_active',
        'manual_time',
        'computer_time_popup',
        'blur_screenshots',
        'web_and_app_monitoring',
        'screenshots_interval',
        'active',
        'password',
        'timezone',
        'important',
        'change_password',
        'role_id',
        'is_admin',
        'user_language',
        'type',
        'invitation_sent',
        'nonce',
        'client_installed',
        'last_activity',
        'delete_in_process',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'full_name' => 'string',
        'email' => 'string',
        'url' => 'string',
        'company_id' => 'integer',
        'avatar' => 'string',
        'screenshots_active' => 'integer',
        'manual_time' => 'integer',
        'computer_time_popup' => 'integer',
        'blur_screenshots' => 'boolean',
        'web_and_app_monitoring' => 'boolean',
        'screenshots_interval' => 'integer',
        'active' => 'integer',
        'password' => 'string',
        'timezone' => 'string',
        'important' => 'integer',
        'change_password' => 'int',
        'is_admin' => 'integer',
        'role_id' => 'integer',
        'user_language' => 'string',
        'type' => 'string',
        'invitation_sent' => 'boolean',
        'nonce' => 'integer',
        'client_installed' => 'integer',
        'delete_in_process' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'last_activity',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'nonce',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new UserScope);
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'online',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'nonce' => $this->nonce,
        ];
    }

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_users', 'user_id', 'project_id')
            ->withPivot('role_id');
    }

    /**
     * @return HasMany
     */
    public function projectsRelation(): HasMany
    {
        return $this->hasMany(ProjectsUsers::class, 'user_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'tasks_users', 'user_id', 'task_id');
    }

    /**
     * @return HasMany
     */
    public function timeIntervals(): HasMany
    {
        return $this->hasMany(TimeInterval::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')
            ->where('entity_type', Property::USER_CODE);
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($this->email, $token));
    }
    /**
     * Get the user's online status.
     *
     * @return bool
     */
    public function getOnlineAttribute(): bool
    {
        if (!isset($this->last_activity)) {
            return false;
        }

        return $this->last_activity->diffInSeconds(Carbon::now()) < config('app.user_activity.online_status_time');
    }

    /**
     * Set the user's password.
     *
     * @param string $password
     */
    public function setPasswordAttribute(string $password): void
    {
        if (Hash::needsRehash($password)) {
            $password = Hash::make($password);
        }

        $this->attributes['password'] = $password;
    }

    /**
     * @return HasMany|TaskComment
     */
    public function tasksComments()
    {
        return $this->hasMany(TaskComment::class, 'user_id');
    }
}
