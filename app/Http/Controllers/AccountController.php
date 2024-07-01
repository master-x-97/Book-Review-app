<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AccountController extends Controller
{
    //this method will show register page
    public function register(){
        return view('account.register');
    }
    // this method will register a user
    public function processRegister(Request $request){
        $validator = Validator::make($request->all(), [
        'name'=>'required|min:3',
        'email'=>'required|email|unique:users',
        'password'=>'required|confirmed|min:8',
        'password_confirmation'=>'required',
        ]);
        if($validator->fails()){
            return redirect()->route('account.register')->withErrors($validator)->withInput();
        }

        // now Register User

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        return redirect()->route('account.login')->with('success','You have registerd successfuly.');
    }

    // login
        public function login(){
            return view('account.login');
    }

    public function authenticate(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=> 'required|email',
            'password'=> 'required',
        ]);
        if($validator->fails()){ 
            return redirect()->route('account.login')->withInput()->withErrors($validator);
        }


        if (Auth::attempt(['email' => $request->email , 'password'=>$request->password])){
            return redirect()->route('account.profile');
        }else{
        return redirect()->route('account.login')->with('error','Either email/password is incorrect');

    }
}

// this method will show user profile
public function profile(){
    $user = User::find(Auth::user()->id);
    // dd($user);

    return view('account.profile',[
        'user' => $user
]);
}

//  this method will update user profile 
public function updateProfile(Request $request){
    $rules = [
        'name'=> 'required|min:3',
        'email'=> 'required|email|unique:users,email,'.Auth::user()->id.'.id',
    ] ;
    if(!empty($request->image)){
        $rules ['image'] = 'image';
    }
    
    $validator = Validator::make($request->all(), $rules);

    if($validator->fails()){
        return redirect()->route('account.profile')->withInput()->withErrors($validator);
    }
    $user = User::find(Auth::user()->id);
    $user->name = $request->name;
    $user->email = $request->email;
    $user->save();

    
    //here we will upload image
    if(!empty($request->image)){

        // Delete old image  here  
        File::delete(public_path('uploads/profile/'.$user->image));
        File::delete(public_path('uploads/profile/thumb/'.$user->image));


    $image = $request->image;
    $ext = $image->getClientOriginalExtension();
    $imageName = time().'.'.$ext; //121212.jpg
    $image->move(public_path('uploads/profile'),$imageName);

        $user->image = $imageName;
        $user->save();

        $manager = new ImageManager(Driver::class);
        $img = $manager->read(public_path('uploads/profile/'.$imageName)); 

        $img->cover(150, 150); 
        $img->save(public_path('uploads/profile/thumb/'.$imageName));
    }



    return redirect()->route('account.profile')->with('success','profile updated successfully');
}


public function logout(){
    Auth::logout();
    return redirect()->route('account.login');
}

public function myReview( Request $request){
    $reviews =Review::with('book')->where('user_id', Auth::user()->id);
    $reviews = $reviews->orderBy('created_at', 'DESC');

    if(!empty($request->keyword)){
        $reviews=$reviews->where('review','like','%'.$request->keyword.'%');
    }


    $reviews=$reviews->paginate(10);
    return view('account.my-reviews',[
        'reviews' =>$reviews
    ]);
}


}


