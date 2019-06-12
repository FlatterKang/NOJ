<?php

namespace App\Http\Controllers\Oauth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccountModel;
use Laravel\Socialite\Facades\Socialite;
use Auth;

class GithubController extends Controller
{
    public function redirectTo()
    {
        $accountModel = new AccountModel();
        if(Auth::check() && $accountModel->getExtra(Auth::user()->id ,'github_username')){
            return view('oauth.index',[
                'page_title'=>"OAuth",
                'site_title'=>"NOJ",
                'navigation'=>"OAuth",
                'platform' => 'Github',
                'display_html' => 'You\'re already tied to the github account : <span class="text-info">'.$accountModel->getExtra(Auth::user()->id ,'github_username').'</span><br />
                You can choose to unbind or go back to the homepage',
                'buttons' => [
                    [
                        'text' => 'unbind',
                        'href' => route('oauth_github_unbind'),
                        'style' => 'btn-danger'
                    ],
                    [
                        'text' => 'home',
                        'href' => route('home'),
                    ],
                ]
            ]);
        }
        return Socialite::driver('github')->redirect();
    }

    public function handleCallback()
    {
        $github_user = Socialite::driver('github')->user();
        $accountModel = new AccountModel();

        if(Auth::check()){
            $user_id = Auth::user()->id;
            $ret = $accountModel->findExtra('github_username',$github_user->email);
            if(!empty($ret) && $ret['uid'] != $user_id){
                return view('oauth.index',[
                    'page_title'=>"OAuth",
                    'site_title'=>"NOJ",
                    'navigation'=>"OAuth",
                    'platform' => 'Github',
                    'display_html' => 'The github account is now tied to another NOJ account : <span class="text-danger">'.$accountModel->getExtra(Auth::user()->id ,'github_username').'</span><br />
                    You can try logging in using github',
                    'buttons' => [
                        [
                            'text' => 'home',
                            'href' => route('home'),
                        ],
                    ]
                ]);
            }
            $accountModel->setExtra($user_id,'github_username',$github_user->email);
            $accountModel->setExtra($user_id,'github_nickname',$github_user->nickname);
            $accountModel->setExtra($user_id,'github_token',$github_user->token,101);
            return view('oauth.index',[
                'page_title'=>"OAuth",
                'site_title'=>"NOJ",
                'navigation'=>"OAuth",
                'platform' => 'Github',
                'display_html' => 'You have successfully tied up the github account : <span class="text-info">'.$accountModel->getExtra(Auth::user()->id ,'github_username').'</span><br />
                You can log in to NOJ later using this account',
                'buttons' => [
                    [
                        'text' => 'home',
                        'href' => route('home'),
                    ],
                ]
            ]);
        }else{
            $ret = $accountModel->findExtra('github_username',$github_user->email);
            if(!empty($ret)){
                Auth::loginUsingId($ret['uid']);
                return redirect('/');
            }else{
                return view('oauth.index',[
                    'page_title'=>"OAuth",
                    'site_title'=>"NOJ",
                    'navigation'=>"OAuth",
                    'platform' => 'Github',
                    'display_text' => 'This github account doesn\'t seem to have a NOJ account, please register or log in first',
                    'buttons' => [
                        [
                            'text' => 'login',
                            'href' => route('login'),
                        ],
                        [
                            'text' => 'register',
                            'href' => route('register'),
                        ],
                    ]
                ]);
            }
        }
    }

    public function unbind()
    {
        $accountModel = new AccountModel();
        if($accountModel->getExtra(Auth::user()->id ,'github_username')){
            return view('oauth.index',[
                'page_title'=>"OAuth",
                'site_title'=>"NOJ",
                'navigation'=>"OAuth",
                'platform' => 'Github',
                'display_html' => 'You are trying to unbind the following two : <br />
                Your NOJ account : <span class="text-info">'.Auth::user()->email.'</span><br />
                This Github account : <span class="text-info">'.$accountModel->getExtra(Auth::user()->id ,'github_username').'</span><br />
                Make your decision carefully, although you can later establish the binding again',
                'buttons' => [
                    [
                        'text' => 'confirm',
                        'href' => route('oauth_github_unbind_confirm'),
                        'style' => 'btn-danger'
                    ],
                    [
                        'text' => 'home',
                        'href' => route('home'),
                    ],
                ]
            ]);
        }else{
            return view('oauth.index',[
                'page_title'=>"OAuth",
                'site_title'=>"NOJ",
                'navigation'=>"OAuth",
                'platform' => 'Github',
                'display_html' => 'You\'re not tied to github',
                'buttons' => [
                    [
                        'text' => 'home',
                        'href' => route('home'),
                    ],
                ]
            ]);
        }
    }

    public function confirmUnbind()
    {
        $accountModel = new AccountModel();
        $user_id = Auth::user()->id;
        if($accountModel->getExtra(Auth::user()->id ,'github_username')){
            $accountModel->setExtra($user_id,'github_username',null);
            $accountModel->setExtra($user_id,'github_nickname',null);
            $accountModel->setExtra($user_id,'github_token',null);
            return view('oauth.index',[
                'page_title'=>"OAuth",
                'site_title'=>"NOJ",
                'navigation'=>"OAuth",
                'platform' => 'Github',
                'display_html' => 'You have successfully unbound your Github account from your NOJ account',
                'buttons' => [
                    [
                        'text' => 'home',
                        'href' => route('home'),
                    ],
                ]
            ]);
        }else{
            return view('oauth.index',[
                'page_title'=>"OAuth",
                'site_title'=>"NOJ",
                'navigation'=>"OAuth",
                'platform' => 'Github',
                'display_html' => 'You\'re not tied to github',
                'buttons' => [
                    [
                        'text' => 'home',
                        'href' => route('home'),
                    ],
                ]
            ]);
        }

    }
}
