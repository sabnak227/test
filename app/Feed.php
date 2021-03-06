<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Feed extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'uuid', 'comment', 'created_at', 'updated_at'
    ];

    /**
     * Feed belongs to an user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function getUserFeedKey($userId) {
        return "user:$userId:feed";
    }

    /**
     * Prepend this feed into provided users' feed
     *
     * @param $userIds
     */
    public function attachToUser($userIds)
    {
        if(!is_array($userIds)) {
            $userIds = [$userIds];
        }
        Redis::pipeline(function ($pipe) use ($userIds) {
            foreach($userIds as $userId) {
                $pipe->zAdd($this->getUserFeedKey($userId), $this->created_at->getPreciseTimestamp(3), $this->uuid)
                    ->expire($this->getUserFeedKey($userId), env('CACHE_TTL', 60));
            }
        });
    }
    /**
     * Remove this feed from provided users' feed
     *
     * @param $userIds
     */
    public function detachFromUser($userIds)
    {
        if(!is_array($userIds)) {
            $userIds = [$userIds];
        }
        Redis::pipeline(function ($pipe) use ($userIds) {
            foreach($userIds as $userId) {
                $pipe->zRem($this->getUserFeedKey($userId), $this->uuid)
                    ->expire($this->getUserFeedKey($userId), env('CACHE_TTL', 60));
            }
        });
    }

    /**
     * Query cache first for feeds, if not found in cache, load from db
     *
     * @param $uuid
     * @return Feed
     */
    public static function findFeedByUuid($uuid) {

        $cache = Redis::hGetAll("feed:$uuid");
        if(!empty($cache)) {
            return (new Feed())->fill($cache);
        }

        return Feed::where('uuid', $uuid)->first();
    }
}
