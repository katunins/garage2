<?php

namespace App\Models;

use App\Http\Controllers\DealsController;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

use function PHPUnit\Framework\isNull;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    static function masterEdit($data)
    {
        switch ($data['type']) {
            case 'new':
                $user = new User();
                $user->name = $data['name'];
                $user->type = 'master';
                $user->bitrixid = $data['bitrixid'] == "" ? null : $data['bitrixid'];
                // $user->password = Hash::make($data['password']);
                $user->password = $data['password'];
                $user->save();
                return;
            case 'newpass':
                // User::find($data['id'])->update(['password'=>Hash::make($data['password'])]);
                User::find($data['id'])->update(['password'=>$data['password']]);
                return;
            case 'delete':
                User::find($data['id'])->delete();
                return;
        }
    }

    static function updateAvatar(){
        foreach (User::all() as $userItem){
            if (isNull($userItem->avatar)) {
                $bitrixUser = DealsController::bitrixAPI(["ID" => $userItem->bitrixid], 'user.get');
                if (!$bitrixUser->error && $bitrixUser->result){
                   $userItem->avatar = '$bitrixUser->result[0]->PERSONAL_PHOTO';
                //    $userItem->save();
                };
                dump($userItem);
            };
        }
        dd ('ok');
        
        // $userId, $url
        // dd(DealsController::bitrixAPI(["ID" => User::find(1)->bitrixid], 'user.get')->result[0]->PERSONAL_PHOTO);
        // $User = User::find($userId);
        // if ($User && $url) {
        //     $User->avatar = $url;
        //     $User->save();
        //     return true;
        // }
        // return false;
    }
}
